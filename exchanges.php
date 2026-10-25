<?php
// ==============================================================
// exchanges.php
// pusat manajemen pertukaran skill: Permintaan Masuk, Terkirim, Aktif, dan Riwayat
// ==============================================================

$page_title = "Daftar Pertukaran Skill";
require_once __DIR__ . '/includes/header.php';

require_login(); // satpam halaman: wajib login

$user = current_user();
$user_id = (int)$user['id'];

$tab = $_GET['tab'] ?? 'active'; // 'incoming', 'sent', 'active', 'history'

// --------------------------------------------------------------
// AKSI FORM CEPAT: TERIMA, TOLAK, BATALKAN
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';
    $exchange_id = (int)($_POST['exchange_id'] ?? 0);

    if ($exchange_id > 0 && is_db_connected()) {
        
        // 1. TERIMA REQUEST (Hanya receiver yang bisa)
        if ($action === 'accept') {
            $stmt = $conn->prepare("UPDATE exchanges SET status = 'accepted' WHERE id = ? AND receiver_id = ? AND status = 'pending'");
            if ($stmt) {
                $stmt->bind_param("ii", $exchange_id, $user_id);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    set_flash('success', 'Permintaan pertukaran skill berhasil diterima! Silakan hubungi partner untuk koordinasi sesi.');
                    
                    // Ambil requester_id buat kirim notifikasi
                    $q_req = $conn->query("SELECT requester_id FROM exchanges WHERE id = $exchange_id");
                    if ($q_req && $r_row = $q_req->fetch_assoc()) {
                        add_notification((int)$r_row['requester_id'], $user['name'] . " telah MENERIMA request pertukaran skill-mu! Cek jadwalnya.", "exchange-detail.php?id=" . $exchange_id);
                    }
                }
                $stmt->close();
            }
            header('Location: exchanges.php?tab=active');
            exit;
        }

        // 2. TOLAK REQUEST (Hanya receiver yang bisa)
        if ($action === 'reject') {
            $reason = trim($_POST['reject_reason'] ?? 'Jadwal belum cocok');
            
            // Cek apakah request tipe poin buat refund 15 poin
            $q_ex = $conn->query("SELECT requester_id, type, points_cost FROM exchanges WHERE id = $exchange_id AND receiver_id = $user_id AND status = 'pending'");
            if ($q_ex && $ex_row = $q_ex->fetch_assoc()) {
                $req_id = (int)$ex_row['requester_id'];

                $stmt = $conn->prepare("UPDATE exchanges SET status = 'rejected', reject_reason = ? WHERE id = ? AND receiver_id = ?");
                if ($stmt) {
                    $stmt->bind_param("sii", $reason, $exchange_id, $user_id);
                    $stmt->execute();
                    $stmt->close();

                    // kembalikan poin jika ditolak
                    if ($ex_row['type'] === 'points' && (int)$ex_row['points_cost'] > 0) {
                        add_points($req_id, 15, 'points_refund', 'Pengembalian 15 poin karena request ditolak partner', $exchange_id);
                    }

                    add_notification($req_id, "Maaf, request pertukaran skill kamu ditolak oleh " . $user['name'] . ".", "exchanges.php?tab=history");
                    set_flash('info', 'Permintaan barter telah ditolak.');
                }
            }
            header('Location: exchanges.php?tab=incoming');
            exit;
        }

        // 3. BATALKAN REQUEST (Requester membatalkan yang pending)
        if ($action === 'cancel') {
            $q_ex = $conn->query("SELECT receiver_id, type, points_cost FROM exchanges WHERE id = $exchange_id AND requester_id = $user_id AND status = 'pending'");
            if ($q_ex && $ex_row = $q_ex->fetch_assoc()) {
                $stmt = $conn->prepare("UPDATE exchanges SET status = 'cancelled', reject_reason = 'Dibatalkan pengirim' WHERE id = ? AND requester_id = ?");
                if ($stmt) {
                    $stmt->bind_param("ii", $exchange_id, $user_id);
                    $stmt->execute();
                    $stmt->close();

                    // kembalikan poin jika dibatalkan
                    if ($ex_row['type'] === 'points') {
                        add_points($user_id, 15, 'points_refund', 'Pengembalian 15 poin pembatalan request', $exchange_id);
                    }

                    set_flash('info', 'Request berhasil dibatalkan.');
                }
            }
            header('Location: exchanges.php?tab=sent');
            exit;
        }
    }
}

// --------------------------------------------------------------
// QUERY AMBIL DATA SESUAI TAB YANG DIBUKA
// --------------------------------------------------------------
$list = [];
if (is_db_connected()) {
    $base_sql = "
        SELECT 
            e.*,
            u_req.name AS requester_name, u_req.photo AS requester_photo, u_req.major AS requester_major, u_req.whatsapp AS requester_wa,
            u_rec.name AS receiver_name, u_rec.photo AS receiver_photo, u_rec.major AS receiver_major, u_rec.whatsapp AS receiver_wa,
            s_req.name AS req_skill_name,
            s_off.name AS off_skill_name,
            (SELECT COUNT(*) FROM reviews rev WHERE rev.exchange_id = e.id AND rev.reviewer_id = $user_id) AS user_reviewed
        FROM exchanges e
        JOIN users u_req ON e.requester_id = u_req.id
        JOIN users u_rec ON e.receiver_id = u_rec.id
        JOIN skills s_req ON e.requested_skill_id = s_req.id
        LEFT JOIN skills s_off ON e.offered_skill_id = s_off.id
    ";

    if ($tab === 'incoming') {
        $base_sql .= " WHERE e.receiver_id = $user_id AND e.status = 'pending' ORDER BY e.created_at DESC";
    } elseif ($tab === 'sent') {
        $base_sql .= " WHERE e.requester_id = $user_id AND e.status = 'pending' ORDER BY e.created_at DESC";
    } elseif ($tab === 'active') {
        $base_sql .= " WHERE (e.requester_id = $user_id OR e.receiver_id = $user_id) AND e.status = 'accepted' ORDER BY e.scheduled_at ASC";
    } else { // 'history'
        $base_sql .= " WHERE (e.requester_id = $user_id OR e.receiver_id = $user_id) AND e.status IN ('completed', 'rejected', 'cancelled') ORDER BY e.updated_at DESC";
    }

    $res = $conn->query($base_sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $list[] = $row;
        }
    }
}

// Hitung badge counter untuk tab incoming
$count_incoming = 0;
if (is_db_connected()) {
    $q_ci = $conn->query("SELECT COUNT(*) AS total FROM exchanges WHERE receiver_id = $user_id AND status = 'pending'");
    if ($q_ci && $r = $q_ci->fetch_assoc()) $count_incoming = (int)$r['total'];
}
?>

<div class="container" style="padding: 40px 16px 80px;">

    <!-- Header Halaman -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 32px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: var(--ink);">Aktivitas Pertukaran Skill</h1>
            <p style="font-size: 14px; color: var(--gray-600); margin-top: 4px;">
                Pantau status proposal belajar, jadwal pertemuan aktif, dan konfirmasi sesi selesai.
            </p>
        </div>
        <a href="discover.php" class="btn btn-primary btn-sm">+ Buat Request Baru <span class="btn-arrow">↗</span></a>
    </div>

    <!-- TAB NAVIGASI (4 TAB) -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 28px; border-bottom: 2px solid var(--gray-200); padding-bottom: 12px;">
        <a href="exchanges.php?tab=active" class="btn <?= ($tab === 'active') ? 'btn-dark' : 'btn-outline' ?> btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= icon('calendar', '', '14px') ?> Sesi Aktif
        </a>
        <a href="exchanges.php?tab=incoming" class="btn <?= ($tab === 'incoming') ? 'btn-dark' : 'btn-outline' ?> btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= icon('inbox', '', '14px') ?> Permintaan Masuk <?= ($count_incoming > 0) ? "<span style='background:var(--danger); color:#fff; font-size:11px; padding:2px 6px; border-radius:10px;'>$count_incoming</span>" : '' ?>
        </a>
        <a href="exchanges.php?tab=sent" class="btn <?= ($tab === 'sent') ? 'btn-dark' : 'btn-outline' ?> btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= icon('send', '', '14px') ?> Terkirim
        </a>
        <a href="exchanges.php?tab=history" class="btn <?= ($tab === 'history') ? 'btn-dark' : 'btn-outline' ?> btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= icon('clock', '', '14px') ?> Riwayat Selesai & Batal
        </a>
    </div>

    <!-- DAFTAR ITEM PERTUKARAN -->
    <?php if (empty($list)): ?>
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 50px 20px; text-align: center; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <div style="color: var(--gray-400); margin-bottom: 12px; display: flex; justify-content: center;">
                <?= icon('coffee', '', '44px') ?>
            </div>
            <h3 style="font-size: 18px; font-weight: 800; color: var(--ink);">Tidak ada data pertukaran di tab ini</h3>
            <p style="font-size: 13px; color: var(--gray-600); margin-top: 6px;">
                Saat ada request masuk atau jadwal sesi baru, daftarnya akan muncul di sini.
            </p>
            <a href="discover.php" class="btn btn-outline btn-sm" style="margin-top: 16px;">Jelajahi Partner Skill Sekarang</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($list as $item): 
                $is_requester = ($item['requester_id'] == $user_id);
                $partner_name = $is_requester ? $item['receiver_name'] : $item['requester_name'];
                $partner_photo = $is_requester ? $item['receiver_photo'] : $item['requester_photo'];
                $partner_major = $is_requester ? $item['receiver_major'] : $item['requester_major'];
                $partner_wa = $is_requester ? $item['receiver_wa'] : $item['requester_wa'];
            ?>
                <div style="background: var(--white); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                    
                    <!-- Kiri: Info Partner & Materi -->
                    <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 280px;">
                        <?= render_avatar($partner_photo, $partner_name, 48) ?>
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <strong style="font-size: 16px; color: var(--ink);"><?= e($partner_name) ?></strong>
                                <span class="badge-tag" style="background: var(--blue-50); color: var(--blue-600); font-size: 11px;">
                                    <?= strtoupper(e($item['type'])) ?>
                                </span>
                                <span class="badge-tag" style="background: var(--gray-100); color: var(--gray-600); font-size: 11px;">
                                    <?= strtoupper(e($item['mode'])) ?>
                                </span>
                            </div>
                            
                            <p style="font-size: 13px; color: var(--gray-600); margin-top: 4px;">
                                Materi Dipelajari: <strong style="color: var(--blue-600);"><?= e($item['req_skill_name']) ?></strong>
                                <?php if ($item['type'] === 'swap'): ?>
                                    ⇄ Barter: <strong style="color: #65a30d;"><?= e($item['off_skill_name'] ?? '-') ?></strong>
                                <?php else: ?>
                                    <span style="color: var(--lime-dark); font-weight: 700;">(Bayar 15 Poin)</span>
                                <?php endif; ?>
                            </p>

                            <p style="font-size: 12px; color: var(--gray-400); margin-top: 2px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span style="display: inline-flex; align-items: center; gap: 4px;"><?= icon('calendar', '', '13px') ?> <?= format_tanggal_id($item['scheduled_at']) ?></span>
                                <?php if (!empty($item['location'])): ?>
                                    <span>•</span>
                                    <span style="display: inline-flex; align-items: center; gap: 4px;"><?= icon('map-pin', '', '13px') ?> <?= e($item['location']) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Kanan: Tombol Aksi Sesuai Status & Tab -->
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        
                        <!-- Aksi Tab Permintaan Masuk: TERIMA / TOLAK -->
                        <?php if ($tab === 'incoming'): ?>
                            <form action="exchanges.php" method="POST" style="display: inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="accept">
                                <input type="hidden" name="exchange_id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm">Terima Barter</button>
                            </form>

                            <form action="exchanges.php" method="POST" onsubmit="return confirm('Tolak permintaan pertukaran ini?');" style="display: inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="exchange_id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);">Tolak</button>
                            </form>
                        <?php endif; ?>

                        <!-- Aksi Tab Terkirim: BATALKAN -->
                        <?php if ($tab === 'sent' && $item['status'] === 'pending'): ?>
                            <form action="exchanges.php" method="POST" onsubmit="return confirm('Batalkan request ini?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="exchange_id" value="<?= (int)$item['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);">Batalkan Request</button>
                            </form>
                        <?php endif; ?>

                        <!-- Aksi Tab Aktif: Chat WhatsApp & Detail Sesi -->
                        <?php if ($item['status'] === 'accepted'): ?>
                            <?php if (!empty($partner_wa)): ?>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $partner_wa) ?>" target="_blank" class="btn btn-outline btn-sm" style="color: #16a34a; border-color: #86efac; display: inline-flex; align-items: center; gap: 6px;">
                                    <?= icon('phone', '', '14px') ?> WhatsApp
                                </a>
                            <?php endif; ?>
                            <a href="exchange-detail.php?id=<?= (int)$item['id'] ?>" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                                <?= icon('handshake', '', '14px') ?> Konfirmasi Selesai
                            </a>
                        <?php endif; ?>

                        <!-- Aksi Tab Riwayat: Tulis Review -->
                        <?php if ($item['status'] === 'completed' && (int)$item['user_reviewed'] === 0): ?>
                            <a href="exchange-detail.php?id=<?= (int)$item['id'] ?>#review" class="btn btn-dark btn-sm" style="background: var(--star); color: var(--ink); display: inline-flex; align-items: center; gap: 6px;">
                                <?= icon('star', '', '14px') ?> Beri Review
                            </a>
                        <?php endif; ?>

                        <!-- Tombol Detail Umum -->
                        <a href="exchange-detail.php?id=<?= (int)$item['id'] ?>" class="btn btn-outline btn-sm">Detail →</a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
