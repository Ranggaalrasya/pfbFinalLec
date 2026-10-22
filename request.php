<?php
// ==============================================================
// request.php
// formulir pengajuan request barter skill (Swap Skill atau Bayar 15 Poin)
// ==============================================================

$page_title = "Kirim Request Barter";
require_once __DIR__ . '/includes/header.php';

require_login(); // satpam halaman: wajib login

$user = current_user();
$user_id = (int)$user['id'];

$receiver_id = (int)($_GET['receiver_id'] ?? 0);
if ($receiver_id <= 0 || $receiver_id === $user_id) {
    set_flash('danger', 'Kamu tidak bisa mengirimkan request ke dirimu sendiri.');
    header('Location: discover.php');
    exit;
}

// Ambil info calon partner (receiver)
$receiver = null;
if (is_db_connected()) {
    $stmt = $conn->prepare("SELECT id, name, major, photo FROM users WHERE id = ? AND is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $receiver_id);
        $stmt->execute();
        $receiver = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$receiver) {
    set_flash('danger', 'Mahasiswa tujuan tidak ditemukan.');
    header('Location: discover.php');
    exit;
}

// Ambil skill yang BISA diajarkan oleh receiver (yang bisa kita minta)
$receiver_offers = [];
if (is_db_connected()) {
    $q_ro = $conn->query("
        SELECT us.skill_id, s.name AS skill_name, us.level 
        FROM user_skills us 
        JOIN skills s ON us.skill_id = s.id 
        WHERE us.user_id = $receiver_id AND us.type = 'offer'
    ");
    if ($q_ro) {
        while ($row = $q_ro->fetch_assoc()) {
            $receiver_offers[] = $row;
        }
    }
}

// Ambil skill yang BISA diajarkan oleh saya (untuk ditawarkan balik)
$my_offers = [];
if (is_db_connected()) {
    $q_mo = $conn->query("
        SELECT us.skill_id, s.name AS skill_name, us.level 
        FROM user_skills us 
        JOIN skills s ON us.skill_id = s.id 
        WHERE us.user_id = $user_id AND us.type = 'offer'
    ");
    if ($q_mo) {
        while ($row = $q_mo->fetch_assoc()) {
            $my_offers[] = $row;
        }
    }
}

$error_msg = '';

// PROSES SUBMIT REQUEST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $type = $_POST['type'] ?? 'swap'; // 'swap' atau 'points'
    $requested_skill_id = (int)($_POST['requested_skill_id'] ?? 0);
    $offered_skill_id = ($type === 'swap') ? (int)($_POST['offered_skill_id'] ?? 0) : null;
    $scheduled_at = trim($_POST['scheduled_at'] ?? '');
    $mode = $_POST['mode'] ?? 'online';
    $location = trim($_POST['location'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validasi form
    if ($requested_skill_id <= 0) {
        $error_msg = 'Pilih skill dari partner yang ingin kamu pelajari.';
    } elseif ($type === 'swap' && (empty($offered_skill_id) || $offered_skill_id <= 0)) {
        $error_msg = 'Pilih skill milikmu yang ingin kamu ajarkan sebagai barter gantinya.';
    } elseif ($type === 'points' && (int)$user['points'] < 15) {
        $error_msg = 'Saldo poinmu tidak mencukupi untuk request satu arah. Saldo kamu saat ini: ' . (int)$user['points'] . ' Poin (Minimal butuh 15 Poin).';
    } elseif (empty($scheduled_at)) {
        $error_msg = 'Tentukan usulan tanggal & jam sesi pertukaran.';
    } elseif (strtotime($scheduled_at) < time()) {
        $error_msg = 'Jadwal yang kamu pilih sudah lewat. Pilih waktu di masa mendatang ya.';
    } elseif (!in_array($mode, ['online', 'offline'])) {
        $error_msg = 'Pilih mode online atau offline.';
    } elseif (is_db_connected()) {
        // Cek apakah udah ada request aktif antara kedua user ini untuk skill yang sama
        $stmt_check = $conn->prepare("
            SELECT id FROM exchanges 
            WHERE requester_id = ? AND receiver_id = ? AND requested_skill_id = ? AND status IN ('pending', 'accepted')
        ");
        $stmt_check->bind_param("iii", $user_id, $receiver_id, $requested_skill_id);
        $stmt_check->execute();
        $active_res = $stmt_check->get_result();

        if ($active_res && $active_res->num_rows > 0) {
            $error_msg = 'Kamu masih memiliki request aktif yang sedang menunggu atau berjalan dengan partner ini untuk materi tersebut.';
        } else {
            $points_cost = ($type === 'points') ? 15 : 0;

            // Simpan request ke tabel exchanges
            $stmt_ins = $conn->prepare("
                INSERT INTO exchanges (
                    requester_id, receiver_id, type, requested_skill_id, offered_skill_id, 
                    points_cost, message, scheduled_at, mode, location, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            if ($stmt_ins) {
                $stmt_ins->bind_param(
                    "iisiisssss",
                    $user_id, $receiver_id, $type, $requested_skill_id, $offered_skill_id,
                    $points_cost, $message, $scheduled_at, $mode, $location
                );

                if ($stmt_ins->execute()) {
                    $exchange_id = $stmt_ins->insert_id;
                    $stmt_ins->close();

                    // Kalo pake poin, tahan 15 poin requester sekarang
                    if ($type === 'points') {
                        add_points($user_id, -15, 'points_spent', 'Poin ditahan untuk request sesi ke ' . $receiver['name'], $exchange_id);
                    }

                    // Kirim notifikasi in-app ke receiver
                    $notif_text = $user['name'] . " mengirimkan request pertukaran skill (" . strtoupper($type) . ")!";
                    add_notification($receiver_id, $notif_text, "exchange-detail.php?id=" . $exchange_id);

                    set_flash('success', 'Request barter skill berhasil dikirim ke ' . $receiver['name'] . '! Tunggu konfirmasi darinya ya.');
                    header('Location: exchanges.php?tab=sent');
                    exit;
                } else {
                    $error_msg = 'Gagal menyimpan request: ' . $conn->error;
                }
            }
        }
        $stmt_check->close();
    }
}
?>

<div class="container" style="padding: 40px 16px 80px;">
    <div style="max-width: 680px; margin: 0 auto;">
        
        <!-- Header Halaman -->
        <div style="text-align: center; margin-bottom: 30px;">
            <a href="user.php?id=<?= $receiver_id ?>" style="font-size: 13px; font-weight: 600; color: var(--blue-600);">← Kembali ke Profil <?= e($receiver['name']) ?></a>
            <h1 style="font-size: 26px; font-weight: 800; color: var(--ink); margin-top: 8px;">
                Ajukan Pertukaran Skill 🤝
            </h1>
            <p style="font-size: 14px; color: var(--gray-600); margin-top: 4px;">
                Kirim proposal belajar bersama teman kampus.
            </p>
        </div>

        <!-- Kartu Target Partner -->
        <div style="background: var(--blue-50); border: 1px solid var(--blue-100); border-radius: var(--radius-lg); padding: 16px 20px; display: flex; align-items: center; gap: 14px; margin-bottom: 24px;">
            <div class="mini-avatar" style="background: var(--blue-500); width: 44px; height: 44px; font-size: 16px;">
                <?= strtoupper(substr($receiver['name'], 0, 1)) ?>
            </div>
            <div>
                <strong style="font-size: 15px; color: var(--ink);"><?= e($receiver['name']) ?></strong>
                <span style="display: block; font-size: 12px; color: var(--blue-600); font-weight: 600;"><?= e($receiver['major']) ?></span>
            </div>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div style="background: #fee2e2; border-left: 4px solid var(--danger); color: #991b1b; padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 24px; font-size: 13px; font-weight: 600;">
                <?= e($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- Kartu Formulir Pengajuan -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 36px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            
            <form action="request.php?receiver_id=<?= $receiver_id ?>" method="POST">
                <?= csrf_field() ?>

                <!-- 1. Pilihan Metode Barter (Swap vs Poin) -->
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px;">Metode Pertukaran</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        
                        <label style="border: 2px solid var(--blue-500); background: var(--blue-50); padding: 14px; border-radius: var(--radius-md); cursor: pointer; display: flex; align-items: flex-start; gap: 10px;">
                            <input type="radio" name="type" value="swap" id="type_swap" checked onchange="toggleTypeFields()" style="margin-top: 3px;">
                            <div>
                                <strong style="font-size: 13px; color: var(--blue-600); display: block;">Barter Skill (Swap) ⚡</strong>
                                <span style="font-size: 12px; color: var(--gray-600);">Kamu ajarin 1 skill milikmu sebagai gantinya. 100% gratis!</span>
                            </div>
                        </label>

                        <label style="border: 2px solid var(--gray-200); background: var(--white); padding: 14px; border-radius: var(--radius-md); cursor: pointer; display: flex; align-items: flex-start; gap: 10px;" id="type_points_label">
                            <input type="radio" name="type" value="points" id="type_points" onchange="toggleTypeFields()" style="margin-top: 3px;">
                            <div>
                                <strong style="font-size: 13px; color: var(--ink); display: block;">Bayar Poin (15 Poin) 🪙</strong>
                                <span style="font-size: 12px; color: var(--gray-600);">Belajar satu arah tanpa mengajar balik. Saldo: <strong><?= (int)$user['points'] ?> Poin</strong></span>
                            </div>
                        </label>

                    </div>
                </div>

                <!-- 2. Skill yang Mau Dipelajari dari Partner -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">
                        Skill yang Ingin Kamu Pelajari dari <?= e(explode(' ', $receiver['name'])[0]) ?> <span style="color: var(--danger);">*</span>
                    </label>
                    <select name="requested_skill_id" required style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none; background: var(--white);">
                        <option value="">-- Pilih Skill Partner --</option>
                        <?php foreach ($receiver_offers as $ro): ?>
                            <option value="<?= (int)$ro['skill_id'] ?>">
                                <?= e($ro['skill_name']) ?> (Tingkat: <?= ucfirst(e($ro['level'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 3. Skill yang Ditawarkan Balik (Hanya untuk Tipe Swap) -->
                <div id="offered_skill_group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">
                        Skill Milikmu yang Ingin Kamu Ajarkan Balik <span style="color: var(--danger);">*</span>
                    </label>
                    <?php if (empty($my_offers)): ?>
                        <div style="background: #fef3c7; border: 1px solid #fde68a; padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; color: #92400e;">
                            Kamu belum mendaftarkan skill yang bisa diajarkan di profilmu. 
                            <a href="my-skills.php" target="_blank" style="font-weight: 700; text-decoration: underline;">Tambah skill dulu</a> atau gunakan opsi <strong>Bayar Poin</strong>.
                        </div>
                    <?php else: ?>
                        <select name="offered_skill_id" style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none; background: var(--white);">
                            <option value="">-- Pilih Skill Pengganti Milikmu --</option>
                            <?php foreach ($my_offers as $mo): ?>
                                <option value="<?= (int)$mo['skill_id'] ?>">
                                    <?= e($mo['skill_name']) ?> (Tingkat: <?= ucfirst(e($mo['level'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <!-- 4. Usulan Jadwal Sesi & Mode Pertemuan -->
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 14px; margin-bottom: 18px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">
                            Usulan Waktu Sesi <span style="color: var(--danger);">*</span>
                        </label>
                        <input type="datetime-local" name="scheduled_at" required min="<?= date('Y-m-d\TH:i') ?>"
                               style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Mode Sesi</label>
                        <select name="mode" style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none; background: var(--white);">
                            <option value="online">Online (GMeet / Zoom)</option>
                            <option value="offline">Offline (Kampus / Kafe)</option>
                        </select>
                    </div>
                </div>

                <!-- 5. Lokasi / Link Meeting -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">
                        Tautan Meeting / Tempat Pertemuan <small style="font-weight: 400; color: var(--gray-400);">(opsional, bisa didiskusikan nanti)</small>
                    </label>
                    <input type="text" name="location" placeholder="Contoh: Google Meet (akan dibuatkan) atau Perpustakaan Lt 3"
                           style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none;">
                </div>

                <!-- 6. Pesan Pengantar / Target Belajar -->
                <div style="margin-bottom: 28px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">
                        Pesan Pengantar
                    </label>
                    <textarea name="message" rows="3" placeholder="Sampaikan salam perkenalan, materi spesifik apa yang mau dipelajari, dan kenapa kamu tertarik belajar bareng dia..."
                              style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none; resize: vertical;"></textarea>
                </div>

                <!-- Tombol Submit -->
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px;">
                    Kirim Permintaan Barter Skill <span class="btn-arrow">↗</span>
                </button>
            </form>

        </div>
    </div>
</div>

<script>
// Fungsi buat sembunyikan/tampilkan pilihan skill pengganti sesuai opsi Swap vs Poin
function toggleTypeFields() {
    const isSwap = document.getElementById('type_swap').checked;
    const group = document.getElementById('offered_skill_group');
    if (group) {
        group.style.display = isSwap ? 'block' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
