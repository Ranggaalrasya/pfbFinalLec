<?php
// ==============================================================
// points.php
// dompet & riwayat transaksi poin mahasiswa, koleksi badge & progres tier
// ==============================================================

$page_title = "Dompet Poin & Badge";
require_once __DIR__ . '/includes/header.php';

require_login(); // satpam halaman: wajib login bro

$user = current_user();
$user_id = (int)$user['id'];

// Ambil info poin paling fresh dari database
$current_points = (int)$user['points'];
$total_earned = 0;
$transactions = [];
$badges = [];

if (is_db_connected()) {
    // 1. Refresh poin & info user
    $u_res = $conn->query("SELECT points FROM users WHERE id = $user_id LIMIT 1");
    if ($u_res && $u_row = $u_res->fetch_assoc()) {
        $current_points = (int)$u_row['points'];
    }

    // 2. Hitung total poin akumulasi yang pernah didapatkan
    $sum_res = $conn->query("SELECT SUM(amount) AS total_in FROM point_transactions WHERE user_id = $user_id AND amount > 0");
    if ($sum_res && $s_row = $sum_res->fetch_assoc()) {
        $total_earned = (int)($s_row['total_in'] ?? $current_points);
    }

    // 3. Ambil daftar badge dan status kepemilikannya
    $b_res = $conn->query("
        SELECT b.*, ub.earned_at 
        FROM badges b 
        LEFT JOIN user_badges ub ON b.id = ub.badge_id AND ub.user_id = $user_id 
        ORDER BY b.min_points ASC
    ");
    if ($b_res) {
        while ($b_row = $b_res->fetch_assoc()) {
            $badges[] = $b_row;
        }
    }

    // 4. Ambil riwayat transaksi poin
    $stmt_tx = $conn->prepare("
        SELECT * FROM point_transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    if ($stmt_tx) {
        $stmt_tx->bind_param("i", $user_id);
        $stmt_tx->execute();
        $res_tx = $stmt_tx->get_result();
        while ($t_row = $res_tx->fetch_assoc()) {
            $transactions[] = $t_row;
        }
        $stmt_tx->close();
    }
}

// Cari badge aktif saat ini dan target badge berikutnya
$active_badge = null;
$next_badge = null;
foreach ($badges as $b) {
    if ($b['earned_at'] !== null) {
        $active_badge = $b;
    } elseif ($next_badge === null && $current_points < (int)$b['min_points']) {
        $next_badge = $b;
    }
}

// Hitung persentase progres ke badge berikutnya
$progress_pct = 100;
if ($next_badge) {
    $min_curr = $active_badge ? (int)$active_badge['min_points'] : 0;
    $target = (int)$next_badge['min_points'];
    $diff = $target - $min_curr;
    $curr_in_step = max(0, $current_points - $min_curr);
    $progress_pct = $diff > 0 ? min(100, round(($curr_in_step / $diff) * 100)) : 100;
}
?>

<div class="container" style="padding: 40px 16px 80px;">

    <!-- HEADER DOMPET -->
    <div style="margin-bottom: 32px;">
        <span class="badge-tag" style="background: var(--blue-100); color: var(--blue-600); margin-bottom: 8px; display: inline-flex; align-items: center; gap: 6px;">
            <?= icon('coins', '', 14) ?> <span>TukarSkill Gamification & Reward</span>
        </span>
        <h1 style="font-size: 28px; font-weight: 800; color: var(--ink);">
            Dompet Poin & Koleksi Badge
        </h1>
        <p style="font-size: 14px; color: var(--gray-600); margin-top: 4px;">
            Setiap kali kamu berbagi ilmu, mengajar teman, atau menulis review jujur, kamu mengumpulkan poin dan apresiasi komunitas.
        </p>
    </div>

    <!-- 3 BENTO CARDS RINGKASAN POIN -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 36px;">
        
        <!-- KARTU 1: Saldo Poin Sekarang -->
        <div style="background: linear-gradient(135deg, var(--blue-600) 0%, #1e40af 100%); border-radius: var(--radius-xl); padding: 28px; color: var(--white); box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4); position: relative; overflow: hidden;">
            <div style="position: absolute; right: -15px; bottom: -20px; opacity: 0.12; color: #ffffff; user-select: none;">
                <?= icon('coins', '', 140) ?>
            </div>
            <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9;">Saldo Poin Aktif</span>
            <div style="font-size: 42px; font-weight: 900; margin: 10px 0 6px;">
                <?= number_format($current_points, 0, ',', '.') ?> <span style="font-size: 20px; font-weight: 600;">Poin</span>
            </div>
            <p style="font-size: 12px; opacity: 0.85; margin: 0;">
                Bisa dipakai belajar skill baru tanpa barter (15 poin/sesi)
            </p>
        </div>

        <!-- KARTU 2: Badge Mahasiswa Saat Ini -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 28px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <span style="font-size: 12px; font-weight: 700; color: var(--gray-400); text-transform: uppercase; letter-spacing: 0.5px;">Gelar & Badge Kamu</span>
                <div style="display: flex; align-items: center; gap: 14px; margin-top: 12px;">
                    <div style="color: var(--blue-600); width: 48px; height: 48px; border-radius: var(--radius-md); background: var(--blue-50); display: flex; align-items: center; justify-content: center;">
                        <?= badge_icon_svg($active_badge['name'] ?? 'Bibit', 28) ?>
                    </div>
                    <div>
                        <div style="font-size: 20px; font-weight: 800; color: var(--ink);">
                            <?= e($active_badge['name'] ?? 'Bibit Mahasiswa') ?>
                        </div>
                        <span style="font-size: 12px; color: var(--gray-600);">
                            <?= e($active_badge['description'] ?? 'Baru bergabung dalam gerakan SDG 4') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Progres ke badge selanjutnya -->
            <div style="margin-top: 18px;">
                <?php if ($next_badge): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 6px;">
                        <span style="color: var(--gray-600);">Target: <?= e($next_badge['name']) ?></span>
                        <span style="color: var(--blue-600);"><?= $current_points ?> / <?= $next_badge['min_points'] ?> Poin</span>
                    </div>
                    <div style="width: 100%; height: 8px; background: var(--gray-200); border-radius: 999px; overflow: hidden;">
                        <div style="width: <?= $progress_pct ?>%; height: 100%; background: var(--blue-500); border-radius: 999px;"></div>
                    </div>
                <?php else: ?>
                    <div style="font-size: 12px; font-weight: 700; color: #16a34a; background: #f0fdf4; padding: 8px 12px; border-radius: var(--radius-md); text-align: center; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <?= icon('trophy', '', 16) ?> <span>Kamu telah mencapai badge kasta tertinggi!</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- KARTU 3: Akumulasi Poin Masuk -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 28px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <span style="font-size: 12px; font-weight: 700; color: var(--gray-400); text-transform: uppercase; letter-spacing: 0.5px;">Total Poin Dihasilkan</span>
                <div style="font-size: 34px; font-weight: 800; color: var(--ink); margin-top: 10px;">
                    +<?= number_format($total_earned, 0, ',', '.') ?>
                </div>
                <p style="font-size: 12px; color: var(--gray-600); margin-top: 4px;">
                    Total reputasi kontribusi kamu di platform TukarSkill sejak awal.
                </p>
            </div>
            <a href="leaderboard.php" class="btn btn-outline btn-sm" style="align-self: flex-start; margin-top: 14px; display: inline-flex; align-items: center; gap: 6px;">
                <span>Lihat Ranking Leaderboard</span> <?= icon('arrow-up-right', '', 12) ?>
            </a>
        </div>

    </div>

    <!-- SECTION KOLEKSI SEMUA BADGE -->
    <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm); margin-bottom: 36px;">
        <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
            <?= icon('medal', '', 20) ?> <span>Pohon Gelar & Reputasi Badge</span>
        </h3>
        <p style="font-size: 13px; color: var(--gray-600); margin-bottom: 24px;">
            Badge akan terbuka otomatis begitu saldo poinmu mencapai syarat minimal. Tunjukkan reputasimu ke calon partner!
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <?php foreach ($badges as $bg): 
                $unlocked = ($bg['earned_at'] !== null) || ($current_points >= (int)$bg['min_points']);
            ?>
                <div style="border: 2px solid <?= $unlocked ? 'var(--blue-400)' : 'var(--gray-200)' ?>; background: <?= $unlocked ? '#f8fafc' : '#f9fafb' ?>; border-radius: var(--radius-lg); padding: 22px; text-align: center; position: relative; opacity: <?= $unlocked ? '1' : '0.65' ?>;">
                    <?php if ($unlocked): ?>
                        <span style="position: absolute; top: 12px; right: 12px; font-size: 10px; font-weight: 800; background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 999px; display: inline-flex; align-items: center; gap: 3px;">
                            <?= icon('check', '', 10) ?> TERBUKA
                        </span>
                    <?php else: ?>
                        <span style="position: absolute; top: 12px; right: 12px; font-size: 10px; font-weight: 800; background: var(--gray-200); color: var(--gray-600); padding: 2px 8px; border-radius: 999px; display: inline-flex; align-items: center; gap: 3px;">
                            <?= icon('lock', '', 10) ?> TERKUNCI
                        </span>
                    <?php endif; ?>

                    <div style="width: 56px; height: 56px; margin: 0 auto 12px; border-radius: var(--radius-md); background: <?= $unlocked ? 'var(--blue-50)' : 'var(--gray-100)' ?>; color: <?= $unlocked ? 'var(--blue-600)' : 'var(--gray-400)' ?>; display: flex; align-items: center; justify-content: center;">
                        <?= badge_icon_svg($bg['name'], 28) ?>
                    </div>
                    <strong style="font-size: 15px; color: var(--ink); display: block; margin-bottom: 4px;">
                        <?= e($bg['name']) ?>
                    </strong>
                    <span style="font-size: 11px; font-weight: 700; color: var(--blue-600); display: block; margin-bottom: 8px;">
                        Syarat: <?= (int)$bg['min_points'] ?> Poin
                    </span>
                    <p style="font-size: 12px; color: var(--gray-600); margin: 0; line-height: 1.4;">
                        <?= e($bg['description']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- DUA KOLOM: RIWAYAT TRANSAKSI & CARA MENDAPATKAN POIN -->
    <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 28px; align-items: start;">
        
        <!-- TABEL RIWAYAT TRANSAKSI POIN -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); display: flex; align-items: center; gap: 8px;">
                    <?= icon('file-text', '', 18) ?> <span>Aktivitas & Riwayat Transaksi</span>
                </h3>
                <span style="font-size: 12px; color: var(--gray-400);">50 aktivitas terakhir</span>
            </div>

            <?php if (empty($transactions)): ?>
                <div style="text-align: center; padding: 40px 20px; color: var(--gray-400);">
                    <div style="margin-bottom: 8px;"><?= icon('file-text', '', 36) ?></div>
                    <p style="font-size: 14px;">Belum ada riwayat transaksi poin tercatat.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto; border-radius: var(--radius-md);">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--gray-200); color: var(--gray-600);">
                                <th style="padding: 12px 8px;">Waktu</th>
                                <th style="padding: 12px 8px;">Aktivitas</th>
                                <th style="padding: 12px 8px; text-align: right;">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): 
                                $is_pos = (int)$t['amount'] >= 0;
                            ?>
                                <tr style="border-bottom: 1px solid var(--gray-100);">
                                    <td style="padding: 14px 8px; color: var(--gray-400); white-space: nowrap; font-size: 12px;">
                                        <?= date('d M Y, H:i', strtotime($t['created_at'])) ?>
                                    </td>
                                    <td style="padding: 14px 8px;">
                                        <div style="font-weight: 700; color: var(--ink);">
                                            <?= e($t['description']) ?>
                                        </div>
                                        <span style="font-size: 11px; color: var(--gray-400); text-transform: uppercase;">
                                            Tipe: <?= e($t['type']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 8px; text-align: right; font-weight: 800; white-space: nowrap;">
                                        <span style="color: <?= $is_pos ? '#16a34a' : '#dc2626' ?>; background: <?= $is_pos ? '#dcfce7' : '#fee2e2' ?>; padding: 4px 10px; border-radius: 999px; font-size: 12px;">
                                            <?= $is_pos ? '+' : '' ?><?= (int)$t['amount'] ?> Poin
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- PANDUAN CARA DAPAT & PENGGUNAAN POIN -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: 28px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm);">
                <h4 style="font-size: 16px; font-weight: 800; color: var(--ink); margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    <?= icon('coins', '', 18) ?> <span>Cara Mengumpulkan Poin</span>
                </h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
                    <li style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--gray-600); display: inline-flex; align-items: center; gap: 6px;">
                            <?= icon('plus', '', 14) ?> Daftar Akun Baru
                        </span>
                        <strong style="color: #16a34a;">+20 Poin</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--gray-600); display: inline-flex; align-items: center; gap: 6px;">
                            <?= icon('user', '', 14) ?> Lengkapi Profil & Kontak
                        </span>
                        <strong style="color: #16a34a;">+10 Poin</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--gray-600); display: inline-flex; align-items: center; gap: 6px;">
                            <?= icon('graduation-cap', '', 14) ?> Tuntas Mengajar Teman
                        </span>
                        <strong style="color: #16a34a;">+20 Poin</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--gray-600); display: inline-flex; align-items: center; gap: 6px;">
                            <?= icon('coins', '', 14) ?> Sesi via Pembayaran Poin
                        </span>
                        <strong style="color: #16a34a;">+35 Poin</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--gray-600); display: inline-flex; align-items: center; gap: 6px;">
                            <?= icon('edit', '', 14) ?> Memberikan Review Partner
                        </span>
                        <strong style="color: #16a34a;">+5 Poin</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: var(--gray-600); display: inline-flex; align-items: center; gap: 6px;">
                            <?= icon('star', '', 14) ?> Mendapat Rating Bintang 5
                        </span>
                        <strong style="color: #16a34a;">+10 Poin</strong>
                    </li>
                </ul>
            </div>

            <div style="background: var(--mint); border-radius: var(--radius-xl); padding: 28px; border: 1px solid #bbf7d0;">
                <h4 style="font-size: 16px; font-weight: 800; color: var(--mint-dark); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                    <?= icon('shield-check', '', 18) ?> <span>Dukungan SDG 4 Quality Education</span>
                </h4>
                <p style="font-size: 12px; color: var(--ink); line-height: 1.5; margin-bottom: 14px;">
                    Sistem poin di TukarSkill bukan mata uang komersial, melainkan metrik penghargaan atas kesediaan mahasiswa saling mendidik sesama tanpa batasan finansial.
                </p>
                <a href="discover.php" class="btn btn-primary btn-sm" style="width: 100%; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                    <span>Cari Teman Belajar Sekarang</span> <?= icon('arrow-up-right', '', 12) ?>
                </a>
            </div>

        </div>

    </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
