<?php
// ==============================================================
// exchange-detail.php
// detail lengkap pertukaran skill, stepper status, konfirmasi selesai dua arah & form review
// ==============================================================

$page_title = "Detail Pertukaran";
require_once __DIR__ . '/includes/header.php';

require_login(); // satpam halaman: wajib login

$user = current_user();
$user_id = (int)$user['id'];

$exchange_id = (int)($_GET['id'] ?? 0);
if ($exchange_id <= 0) {
    set_flash('danger', 'ID pertukaran tidak valid.');
    header('Location: exchanges.php');
    exit;
}

// Ambil detail pertukaran
$exchange = null;
if (is_db_connected()) {
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            u_req.name AS req_name, u_req.major AS req_major, u_req.whatsapp AS req_wa, u_req.photo AS req_photo,
            u_rec.name AS rec_name, u_rec.major AS rec_major, u_rec.whatsapp AS rec_wa, u_rec.photo AS rec_photo,
            s_req.name AS requested_skill_name,
            s_off.name AS offered_skill_name
        FROM exchanges e
        JOIN users u_req ON e.requester_id = u_req.id
        JOIN users u_rec ON e.receiver_id = u_rec.id
        JOIN skills s_req ON e.requested_skill_id = s_req.id
        LEFT JOIN skills s_off ON e.offered_skill_id = s_off.id
        WHERE e.id = ? AND (e.requester_id = ? OR e.receiver_id = ?)
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("iii", $exchange_id, $user_id, $user_id);
        $stmt->execute();
        $exchange = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$exchange) {
    set_flash('danger', 'Data pertukaran tidak ditemukan atau kamu tidak memiliki akses.');
    header('Location: exchanges.php');
    exit;
}

$is_requester = ($exchange['requester_id'] == $user_id);
$partner_name = $is_requester ? $exchange['rec_name'] : $exchange['req_name'];
$partner_wa = $is_requester ? $exchange['rec_wa'] : $exchange['req_wa'];
$partner_id = $is_requester ? (int)$exchange['receiver_id'] : (int)$exchange['requester_id'];

// --------------------------------------------------------------
// KONFIRMASI SESI SELESAI (Dua Arah / Mutual Confirmation)
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_done') {
    verify_csrf();

    if ($exchange['status'] === 'accepted') {
        if ($is_requester) {
            $conn->query("UPDATE exchanges SET requester_confirmed = 1 WHERE id = $exchange_id");
            $exchange['requester_confirmed'] = 1;
        } else {
            $conn->query("UPDATE exchanges SET receiver_confirmed = 1 WHERE id = $exchange_id");
            $exchange['receiver_confirmed'] = 1;
        }

        // Cek apakah KEDUA BELAH PIHAK sudah konfirmasi selesai
        $check_q = $conn->query("SELECT requester_confirmed, receiver_confirmed, type, points_cost FROM exchanges WHERE id = $exchange_id");
        if ($check_q && $c_row = $check_q->fetch_assoc()) {
            if ($c_row['requester_confirmed'] == 1 && $c_row['receiver_confirmed'] == 1) {
                // Resmi COMPLETED!
                $conn->query("UPDATE exchanges SET status = 'completed', completed_at = NOW() WHERE id = $exchange_id");
                $exchange['status'] = 'completed';

                $req_id = (int)$exchange['requester_id'];
                $rec_id = (int)$exchange['receiver_id'];

                if ($c_row['type'] === 'swap') {
                    // Karena barter skill, kedua mahasiswa saling mengajar (+20 poin untuk masing-masing!)
                    add_points($req_id, 20, 'teach_complete', 'Mengajarkan ' . $exchange['offered_skill_name'] . ' ke ' . $exchange['rec_name'], $exchange_id);
                    add_points($rec_id, 20, 'teach_complete', 'Mengajarkan ' . $exchange['requested_skill_name'] . ' ke ' . $exchange['req_name'], $exchange_id);
                } else {
                    // Tipe poin: receiver mengajar (+20 poin mengajar + 15 poin bayaran = +35 poin!)
                    add_points($rec_id, 20, 'teach_complete', 'Mengajarkan ' . $exchange['requested_skill_name'] . ' ke ' . $exchange['req_name'], $exchange_id);
                    add_points($rec_id, 15, 'points_received', 'Menerima 15 poin sesi belajar dari ' . $exchange['req_name'], $exchange_id);
                }

                // Kirim notifikasi selamat
                add_notification($req_id, "Sesi barter dengan " . $exchange['rec_name'] . " telah resmi SELESAI! Tulis review yuk.", "exchange-detail.php?id=" . $exchange_id);
                add_notification($rec_id, "Sesi barter dengan " . $exchange['req_name'] . " telah resmi SELESAI! Poin reward sudah masuk.", "exchange-detail.php?id=" . $exchange_id);

                set_flash('success', 'Selamat! Sesi pertukaran telah diverifikasi selesai oleh kedua pihak. Poin reward sudah ditambahkan ke akunmu!');
                header("Location: exchange-detail.php?id=$exchange_id#review");
                exit;
            } else {
                set_flash('info', 'Konfirmasi kamu telah dicatat! Menunggu partner juga mengonfirmasi sesi selesai.');
                header("Location: exchange-detail.php?id=$exchange_id");
                exit;
            }
        }
    }
}

// Ambil review jika sesi sudah selesai
$my_review = null;
$partner_review = null;
if ($exchange['status'] === 'completed') {
    $q_r1 = $conn->query("SELECT * FROM reviews WHERE exchange_id = $exchange_id AND reviewer_id = $user_id LIMIT 1");
    if ($q_r1) $my_review = $q_r1->fetch_assoc();

    $q_r2 = $conn->query("SELECT * FROM reviews WHERE exchange_id = $exchange_id AND reviewer_id = $partner_id LIMIT 1");
    if ($q_r2) $partner_review = $q_r2->fetch_assoc();
}
?>

<div class="container" style="padding: 40px 16px 80px;">
    
    <!-- Tombol Kembali -->
    <div style="margin-bottom: 24px;">
        <a href="exchanges.php" style="font-size: 13px; font-weight: 600; color: var(--blue-600); display: inline-flex; align-items: center; gap: 6px;">
            <?= icon('arrow-left', '', '14px') ?> Kembali ke Daftar Pertukaran
        </a>
    </div>

    <!-- STEPPER TIMELINE STATUS -->
    <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); margin-bottom: 32px;">
        <h3 style="font-size: 15px; font-weight: 700; color: var(--gray-600); text-transform: uppercase; margin-bottom: 20px;">
            Status Progres Sesi:
        </h3>

        <?php
        $st = $exchange['status'];
        $steps = [
            ['key' => 'pending', 'label' => 'Proposal Terkirim'],
            ['key' => 'accepted', 'label' => 'Diterima & Koordinasi'],
            ['key' => 'ongoing', 'label' => 'Sesi Berlangsung'],
            ['key' => 'completed', 'label' => 'Selesai & Review']
        ];
        
        $active_step = 0;
        if ($st === 'pending') $active_step = 1;
        elseif ($st === 'accepted') $active_step = (strtotime($exchange['scheduled_at']) <= time()) ? 3 : 2;
        elseif ($st === 'completed') $active_step = 4;
        ?>

        <div style="display: flex; justify-content: space-between; position: relative; gap: 12px; flex-wrap: wrap;">
            <?php foreach ($steps as $idx => $sp): 
                $num = $idx + 1;
                $is_done = ($num <= $active_step && $st !== 'rejected' && $st !== 'cancelled');
            ?>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $is_done ? 'var(--blue-500)' : 'var(--gray-200)' ?>; color: <?= $is_done ? 'var(--white)' : 'var(--gray-600)' ?>; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center;">
                        <?= $is_done ? '✓' : $num ?>
                    </div>
                    <span style="font-size: 13px; font-weight: <?= $is_done ? '700' : '500' ?>; color: <?= $is_done ? 'var(--ink)' : 'var(--gray-400)' ?>;">
                        <?= e($sp['label']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($st === 'rejected'): ?>
            <div style="margin-top: 18px; padding: 12px 16px; background: #fee2e2; border-radius: var(--radius-md); font-size: 13px; color: #991b1b; font-weight: 600;">
                ❌ Proposal ini ditolak oleh partner. Alasan: "<?= e($exchange['reject_reason'] ?? 'Tidak disebutkan') ?>".
            </div>
        <?php elseif ($st === 'cancelled'): ?>
            <div style="margin-top: 18px; padding: 12px 16px; background: var(--gray-100); border-radius: var(--radius-md); font-size: 13px; color: var(--gray-600); font-weight: 600;">
                ⚪ Pertukaran ini telah dibatalkan.
            </div>
        <?php endif; ?>
    </div>

    <!-- DETAIL KONTEN DUA KOLOM -->
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 28px; align-items: start;">
        
        <!-- KOLOM KIRI: Informasi Materi & Pesan -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="font-size: 20px; font-weight: 800; color: var(--ink);">Rincian Sesi Barter</h2>
                    <span class="badge-tag" style="background: var(--blue-100); color: var(--blue-600);">
                        <?= strtoupper(e($exchange['type'])) ?> METHOD
                    </span>
                </div>

                <div style="background: var(--blue-50); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--blue-600); text-transform: uppercase;">Materi Dipelajari:</span>
                        <div style="font-size: 16px; font-weight: 800; color: var(--ink); margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            <span style="color: var(--blue-600); display: inline-flex;"><?= icon('graduation-cap', '', '18px') ?></span>
                            <?= e($exchange['requested_skill_name']) ?>
                        </div>
                        <span style="font-size: 12px; color: var(--gray-600);">Diajarkan oleh: <?= e($exchange['rec_name']) ?></span>
                    </div>

                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #65a30d; text-transform: uppercase;">Skill Pengganti (Barter):</span>
                        <div style="font-size: 16px; font-weight: 800; color: var(--ink); margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            <?php if ($exchange['type'] === 'swap'): ?>
                                <span style="color: #65a30d; display: inline-flex;"><?= icon('repeat', '', '16px') ?></span>
                                <?= e($exchange['offered_skill_name']) ?>
                            <?php else: ?>
                                <span style="color: var(--lime-dark); display: inline-flex;"><?= icon('coins', '', '16px') ?></span>
                                Bayar 15 Poin
                            <?php endif; ?>
                        </div>
                        <span style="font-size: 12px; color: var(--gray-600);">Diajarkan oleh: <?= e($exchange['req_name']) ?></span>
                    </div>
                </div>

                <!-- Info Waktu, Mode & Tempat -->
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px; color: var(--ink);">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: var(--gray-600); display: inline-flex;"><?= icon('calendar', '', '16px') ?></span>
                        <strong style="color: var(--gray-600);">Jadwal Sesi:</strong>
                        <span><?= format_tanggal_id($exchange['scheduled_at']) ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: var(--gray-600); display: inline-flex;"><?= icon('globe', '', '16px') ?></span>
                        <strong style="color: var(--gray-600);">Format Pertemuan:</strong>
                        <span><?= ucfirst(e($exchange['mode'])) ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: var(--gray-600); display: inline-flex;"><?= icon('map-pin', '', '16px') ?></span>
                        <strong style="color: var(--gray-600);">Lokasi / Link:</strong>
                        <span><?= !empty($exchange['location']) ? e($exchange['location']) : '<span style="color:var(--gray-400); font-style:italic;">Belum ditentukan</span>' ?></span>
                    </div>
                    <?php if (!empty($exchange['message'])): ?>
                        <div style="margin-top: 10px; padding: 14px; background: var(--gray-100); border-radius: var(--radius-md); font-size: 13px; color: var(--gray-600);">
                            <strong>Pesan Pengantar:</strong><br>
                            "<?= nl2br(e($exchange['message'])) ?>"
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- KONFIRMASI SELESAI KEDUA PIHAK -->
            <?php if ($exchange['status'] === 'accepted'): ?>
                <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 2px solid var(--blue-400);">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                        <?= icon('shield-check', '', '20px') ?> Verifikasi Sesi Selesai
                    </h3>
                    <p style="font-size: 13px; color: var(--gray-600); margin-bottom: 20px;">
                        Untuk mencegah kecurangan, status pertukaran hanya akan tuntas setelah <strong>kedua mahasiswa</strong> menekan tombol konfirmasi di bawah.
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                        <div style="padding: 14px; border-radius: var(--radius-md); background: <?= ($exchange['requester_confirmed'] == 1) ? 'var(--mint)' : 'var(--gray-100)' ?>; text-align: center;">
                            <strong style="font-size: 13px; display: block;"><?= e($exchange['req_name']) ?></strong>
                            <span style="font-size: 12px; font-weight: 700; color: <?= ($exchange['requester_confirmed'] == 1) ? 'var(--mint-dark)' : 'var(--gray-400)' ?>;">
                                <?= ($exchange['requester_confirmed'] == 1) ? '✓ Sudah Konfirmasi' : '⏳ Belum Konfirmasi' ?>
                            </span>
                        </div>

                        <div style="padding: 14px; border-radius: var(--radius-md); background: <?= ($exchange['receiver_confirmed'] == 1) ? 'var(--mint)' : 'var(--gray-100)' ?>; text-align: center;">
                            <strong style="font-size: 13px; display: block;"><?= e($exchange['rec_name']) ?></strong>
                            <span style="font-size: 12px; font-weight: 700; color: <?= ($exchange['receiver_confirmed'] == 1) ? 'var(--mint-dark)' : 'var(--gray-400)' ?>;">
                                <?= ($exchange['receiver_confirmed'] == 1) ? '✓ Sudah Konfirmasi' : '⏳ Belum Konfirmasi' ?>
                            </span>
                        </div>
                    </div>

                    <?php 
                    $my_confirmed = $is_requester ? ($exchange['requester_confirmed'] == 1) : ($exchange['receiver_confirmed'] == 1);
                    ?>

                    <?php if (!$my_confirmed): ?>
                        <form action="exchange-detail.php?id=<?= $exchange_id ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="confirm_done">
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">
                                Saya Sudah Selesai Menjalani Sesi Ini ✓
                            </button>
                        </form>
                    <?php else: ?>
                        <div style="text-align: center; font-size: 13px; color: #16a34a; font-weight: 700;">
                            ✓ Kamu sudah konfirmasi selesai. Menunggu partner belajarmu konfirmasi juga ya!
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- SECTION FORM REVIEW (Muncul Pas Status Completed) -->
            <?php if ($exchange['status'] === 'completed'): ?>
                <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);" id="review">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                        <?= icon('star', '', '20px') ?> Ulasan Sesi Barter
                    </h3>
                    <p style="font-size: 13px; color: var(--gray-600); margin-bottom: 20px;">
                        Beri rating & apresiasi ke <strong><?= e($partner_name) ?></strong>. Kamu akan mendapatkan <strong>+5 Poin</strong> karena menulis review!
                    </p>

                    <?php if ($my_review): ?>
                        <div style="background: var(--blue-50); border-radius: var(--radius-md); padding: 18px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <strong style="font-size: 14px;">Ulasan yang Kamu Tulis:</strong>
                                <span style="color: var(--star); font-size: 16px; display: inline-flex; align-items: center; gap: 4px;">
                                    <?= icon('star', '', '16px') ?> <?= (int)$my_review['rating'] ?>/5
                                </span>
                            </div>
                            <p style="font-size: 13px; color: var(--ink); line-height: 1.5;">
                                "<?= e($my_review['comment']) ?>"
                            </p>
                        </div>
                    <?php else: ?>
                        <form action="review.php" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="exchange_id" value="<?= $exchange_id ?>">
                            <input type="hidden" name="reviewee_id" value="<?= $partner_id ?>">

                            <div style="margin-bottom: 16px;">
                                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Rating Bintang</label>
                                <select name="rating" required style="width: 100%; padding: 10px 14px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none; background: var(--white);">
                                    <option value="5">★★★★★ — 5 Bintang (Sangat Memuaskan!)</option>
                                    <option value="4">★★★★☆ — 4 Bintang (Bagus & Bermanfaat)</option>
                                    <option value="3">★★★☆☆ — 3 Bintang (Cukup Baik)</option>
                                    <option value="2">★★☆☆☆ — 2 Bintang (Kurang Maksimal)</option>
                                    <option value="1">★☆☆☆☆ — 1 Bintang (Tidak Sesuai Harapan)</option>
                                </select>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Ulasan / Feedback</label>
                                <textarea name="comment" rows="3" required placeholder="Ceritakan bagaimana cara mengajar partner, kejelasan penjelasan, dan keramahannya..."
                                          style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 13px; outline: none; resize: vertical;"></textarea>
                            </div>

                            <button type="submit" class="btn btn-dark" style="background: var(--star); color: var(--ink); font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                                <?= icon('star', '', '16px') ?> Kirim Review & Klaim +5 Poin
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($partner_review): ?>
                        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                            <strong style="font-size: 13px; color: var(--gray-600); display: block; margin-bottom: 6px;">Ulasan dari <?= e($partner_name) ?> untukmu:</strong>
                            <div style="color: var(--star); font-size: 14px; margin-bottom: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                <?= icon('star', '', '14px') ?> <?= (int)$partner_review['rating'] ?>/5
                            </div>
                            <p style="font-size: 13px; color: var(--ink); font-style: italic;">
                                "<?= e($partner_review['comment']) ?>"
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- KOLOM KANAN: Kontak Partner WhatsApp & Sertifikat Barter SDG 4 -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <?= icon('users', '', '20px') ?> Partner Belajar
            </h3>

            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 20px;">
                <?= render_avatar($is_requester ? $exchange['rec_photo'] : $exchange['req_photo'], $partner_name, 50) ?>
                <div>
                    <strong style="font-size: 16px; color: var(--ink); display: block;"><?= e($partner_name) ?></strong>
                    <span style="font-size: 12px; color: var(--gray-400);"><?= e($is_requester ? $exchange['rec_major'] : $exchange['req_major']) ?></span>
                </div>
            </div>

            <!-- TAMPILKAN KONTAK HANYA JIKA STATUS ACCEPTED ATAU COMPLETED -->
            <?php if (in_array($exchange['status'], ['accepted', 'completed'])): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
                    <span style="font-size: 11px; font-weight: 700; color: #16a34a; text-transform: uppercase; display: block; margin-bottom: 4px;">
                        WhatsApp Terbuka:
                    </span>
                    <strong style="font-size: 15px; color: var(--ink); display: block; margin-bottom: 10px;">
                        <?= !empty($partner_wa) ? e($partner_wa) : 'Nomor belum diisi di profil' ?>
                    </strong>
                    <?php if (!empty($partner_wa)): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $partner_wa) ?>" target="_blank" class="btn btn-primary btn-sm" style="background: #16a34a; border-color: #16a34a; width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                            <?= icon('phone', '', '14px') ?> Chat WhatsApp Sekarang
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="background: var(--gray-100); border-radius: var(--radius-md); padding: 14px; font-size: 12px; color: var(--gray-600); line-height: 1.5; margin-bottom: 20px;">
                    🔒 Kontak WhatsApp partner hanya akan dibuka setelah proposal disetujui untuk menjaga privasi.
                </div>
            <?php endif; ?>

            <!-- Sertifikat Barter SDG 4 (Hanya jika Status Selesai) -->
            <?php if ($exchange['status'] === 'completed'): ?>
                <div style="background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); border: 1px solid var(--blue-200); border-radius: var(--radius-lg); padding: 18px; margin-bottom: 20px; text-align: center;">
                    <div style="color: var(--blue-600); margin-bottom: 6px; display: flex; justify-content: center;">
                        <?= icon('award', '', '32px') ?>
                    </div>
                    <strong style="font-size: 14px; color: var(--ink); display: block;">Bukti Barter & SDG 4</strong>
                    <p style="font-size: 12px; color: var(--gray-600); margin: 4px 0 14px;">
                        Cetak sertifikat bukti kontribusi pembelajaran berkualitas untuk portofolio.
                    </p>
                    <a href="certificate.php?id=<?= $exchange_id ?>" target="_blank" class="btn btn-primary btn-sm" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                        <?= icon('award', '', '14px') ?> Unduh Bukti Barter SDG 4
                    </a>
                </div>
            <?php endif; ?>

            <a href="user.php?id=<?= $partner_id ?>" class="btn btn-outline btn-sm" style="width: 100%; text-align: center;">
                Buka Profil Lengkap Partner
            </a>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
