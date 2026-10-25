<?php
// ==============================================================
// admin/index.php
// dashboard utama administrator TukarSkill
// ringkasan metrik pengguna, transaksi barter, dampak SDG 4 & katalog
// ==============================================================

$page_title = "Admin Dashboard";
require_once __DIR__ . '/header.php';

// Inisialisasi variabel statistik
$stats = [
    'total_users' => 0,
    'total_students' => 0,
    'total_admins' => 0,
    'total_skills' => 0,
    'total_categories' => 0,
    'total_exchanges' => 0,
    'completed_exchanges' => 0,
    'pending_exchanges' => 0,
    'accepted_exchanges' => 0,
    'total_reviews' => 0,
    'avg_rating' => 0,
    'total_points_circulating' => 0
];

$recent_users = [];
$recent_exchanges = [];

if (is_db_connected()) {
    // 1. Hitung total user & role
    $u_res = $conn->query("
        SELECT 
            COUNT(*) AS total_users,
            SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) AS total_students,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) AS total_admins,
            SUM(points) AS total_points
        FROM users
    ");
    if ($u_res && $u_row = $u_res->fetch_assoc()) {
        $stats['total_users'] = (int)$u_row['total_users'];
        $stats['total_students'] = (int)$u_row['total_students'];
        $stats['total_admins'] = (int)$u_row['total_admins'];
        $stats['total_points_circulating'] = (int)$u_row['total_points'];
    }

    // 2. Hitung katalog skill & kategori
    $s_res = $conn->query("SELECT COUNT(*) AS total FROM skills");
    if ($s_res && $s_row = $s_res->fetch_assoc()) $stats['total_skills'] = (int)$s_row['total'];

    $c_res = $conn->query("SELECT COUNT(*) AS total FROM categories");
    if ($c_res && $c_row = $c_res->fetch_assoc()) $stats['total_categories'] = (int)$c_row['total'];

    // 3. Hitung status exchanges
    $e_res = $conn->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted
        FROM exchanges
    ");
    if ($e_res && $e_row = $e_res->fetch_assoc()) {
        $stats['total_exchanges'] = (int)$e_row['total'];
        $stats['completed_exchanges'] = (int)$e_row['completed'];
        $stats['pending_exchanges'] = (int)$e_row['pending'];
        $stats['accepted_exchanges'] = (int)$e_row['accepted'];
    }

    // 4. Hitung ulasan & rata-rata rating
    $r_res = $conn->query("SELECT COUNT(*) AS total, AVG(rating) AS avg_rate FROM reviews");
    if ($r_res && $r_row = $r_res->fetch_assoc()) {
        $stats['total_reviews'] = (int)$r_row['total'];
        $stats['avg_rating'] = round((float)($r_row['avg_rate'] ?? 0), 1);
    }

    // 5. Ambil 5 user pendaftar terbaru
    $u_latest = $conn->query("SELECT id, name, nim, email, major, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    if ($u_latest) {
        while ($row = $u_latest->fetch_assoc()) $recent_users[] = $row;
    }

    // 6. Ambil 5 pertukaran terbaru
    $e_latest = $conn->query("
        SELECT 
            e.id, e.status, e.type, e.created_at,
            u_req.name AS req_name, u_rec.name AS rec_name,
            s_req.name AS skill_name
        FROM exchanges e
        JOIN users u_req ON e.requester_id = u_req.id
        JOIN users u_rec ON e.receiver_id = u_rec.id
        JOIN skills s_req ON e.requested_skill_id = s_req.id
        ORDER BY e.created_at DESC 
        LIMIT 5
    ");
    if ($e_latest) {
        while ($row = $e_latest->fetch_assoc()) $recent_exchanges[] = $row;
    }
}

// Estimasi dampak SDG 4 (1 sesi barter ~ 2 jam belajar peer-to-peer berkualitas)
$impact_hours = $stats['completed_exchanges'] * 2;
?>

<div style="max-width: 1280px; margin: 0 auto;">

    <!-- HEADER TITLE -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 800; color: #0f172a;">
                Dashboard Administrator
            </h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 4px;">
                Pantau performa platform, aktivitas pertukaran skill antar mahasiswa, dan kontribusi SDG 4.
            </p>
        </div>
        <div style="font-size: 12px; color: #64748b; background: white; padding: 8px 16px; border-radius: 8px; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 6px;">
            <?= icon('calendar', '', '13px') ?> Waktu Server: <strong><?= date('d M Y, H:i') ?> WIB</strong>
        </div>
    </div>

    <!-- 5 BENTO KPI METRICS CARDS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 32px;">
        
        <!-- KARTU 1: Total Pengguna -->
        <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Pengguna</span>
                <span style="color: #64748b;"><?= icon('users', '', '20px') ?></span>
            </div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a;">
                <?= number_format($stats['total_users']) ?>
            </div>
            <span style="font-size: 11px; color: #16a34a; font-weight: 600;">
                <?= $stats['total_students'] ?> Mahasiswa • <?= $stats['total_admins'] ?> Admin
            </span>
        </div>

        <!-- KARTU 2: Total Barter Selesai -->
        <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Barter Selesai</span>
                <span style="color: #2563eb;"><?= icon('repeat', '', '20px') ?></span>
            </div>
            <div style="font-size: 28px; font-weight: 800; color: #2563eb;">
                <?= number_format($stats['completed_exchanges']) ?>
            </div>
            <span style="font-size: 11px; color: #64748b;">
                dari <?= $stats['total_exchanges'] ?> total proposal
            </span>
        </div>

        <!-- KARTU 3: Jam Dampak SDG 4 -->
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 12px; font-weight: 700; opacity: 0.9; text-transform: uppercase;">Dampak SDG 4</span>
                <span style="color: white;"><?= icon('award', '', '20px') ?></span>
            </div>
            <div style="font-size: 28px; font-weight: 900;">
                ~<?= number_format($impact_hours) ?> <span style="font-size: 16px; font-weight: 600;">Jam</span>
            </div>
            <span style="font-size: 11px; opacity: 0.9;">
                Pembelajaran peer-to-peer gratis
            </span>
        </div>

        <!-- KARTU 4: Katalog Skill -->
        <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Katalog Skill</span>
                <span style="color: #0f172a;"><?= icon('lightbulb', '', '20px') ?></span>
            </div>
            <div style="font-size: 28px; font-weight: 800; color: #0f172a;">
                <?= number_format($stats['total_skills']) ?>
            </div>
            <span style="font-size: 11px; color: #64748b;">
                dalam <?= $stats['total_categories'] ?> kategori bidang ilmu
            </span>
        </div>

        <!-- KARTU 5: Total Poin Beredar -->
        <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Poin Beredar</span>
                <span style="color: #d97706;"><?= icon('coins', '', '20px') ?></span>
            </div>
            <div style="font-size: 28px; font-weight: 800; color: #d97706;">
                <?= number_format($stats['total_points_circulating']) ?>
            </div>
            <span style="font-size: 11px; color: #64748b; display: inline-flex; align-items: center; gap: 4px;">
                <span style="color: var(--star); display: inline-flex;"><?= icon('star', '', '12px') ?></span> <?= $stats['avg_rating'] ?>/5.0 (<?= $stats['total_reviews'] ?> ulasan)
            </span>
        </div>

    </div>

    <!-- DUA TABEL: PENDAFTAR TERBARU & TRANSAKSI BARTER TERBARU -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
        
        <!-- TABEL 1: PENGGUNA TERBARU -->
        <div style="background: white; border-radius: 12px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <?= icon('users', '', '16px') ?> Pengguna Terdaftar Baru
                </h3>
                <a href="users.php" style="font-size: 12px; font-weight: 700; color: #2563eb; text-decoration: none;">
                    Lihat Semua →
                </a>
            </div>

            <?php if (empty($recent_users)): ?>
                <p style="font-size: 13px; color: #94a3b8; text-align: center; padding: 20px 0;">Belum ada pengguna terdaftar.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 11px; text-transform: uppercase;">
                                <th style="padding: 8px 4px;">Nama / NIM</th>
                                <th style="padding: 8px 4px;">Jurusan</th>
                                <th style="padding: 8px 4px; text-align: center;">Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $u): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 10px 4px;">
                                        <strong style="color: #0f172a; display: block;"><?= e($u['name']) ?></strong>
                                        <span style="font-size: 11px; color: #64748b;"><?= e($u['nim']) ?></span>
                                    </td>
                                    <td style="padding: 10px 4px; font-size: 12px; color: #64748b;">
                                        <?= e($u['major'] ?? '-') ?>
                                    </td>
                                    <td style="padding: 10px 4px; text-align: center;">
                                        <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 999px; <?= ($u['role'] === 'admin') ? 'background: #fef08a; color: #854d0e;' : 'background: #e2e8f0; color: #475569;' ?>">
                                            <?= strtoupper(e($u['role'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- TABEL 2: PERTUKARAN TERBARU -->
        <div style="background: white; border-radius: 12px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <?= icon('repeat', '', '16px') ?> Aktivitas Pertukaran Terbaru
                </h3>
                <span style="font-size: 12px; color: #94a3b8;">5 transaksi terakhir</span>
            </div>

            <?php if (empty($recent_exchanges)): ?>
                <p style="font-size: 13px; color: #94a3b8; text-align: center; padding: 20px 0;">Belum ada proposal pertukaran.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 11px; text-transform: uppercase;">
                                <th style="padding: 8px 4px;">Partisipan</th>
                                <th style="padding: 8px 4px;">Skill</th>
                                <th style="padding: 8px 4px; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_exchanges as $ex): 
                                $status_badge = [
                                    'pending' => ['bg' => '#fef3c7', 'color' => '#b45309', 'label' => 'Pending'],
                                    'accepted' => ['bg' => '#dbeafe', 'color' => '#1d4ed8', 'label' => 'Accepted'],
                                    'completed' => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'Completed'],
                                    'rejected' => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'label' => 'Rejected'],
                                    'cancelled' => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => 'Cancelled']
                                ][$ex['status']] ?? ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => $ex['status']];
                            ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 10px 4px;">
                                        <strong style="color: #0f172a; display: block;"><?= e($ex['req_name']) ?></strong>
                                        <span style="font-size: 11px; color: #64748b;">→ <?= e($ex['rec_name']) ?></span>
                                    </td>
                                    <td style="padding: 10px 4px; font-size: 12px; color: #334155; font-weight: 600;">
                                        <?= e($ex['skill_name']) ?>
                                    </td>
                                    <td style="padding: 10px 4px; text-align: center;">
                                        <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: <?= $status_badge['bg'] ?>; color: <?= $status_badge['color'] ?>;">
                                            <?= $status_badge['label'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
