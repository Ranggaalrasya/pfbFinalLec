<?php
// ==============================================================
// leaderboard.php
// papan peringkat kontributor mahasiswa terbaik di tukarskill
// podium 3 besar (emas, perak, perunggu) & tabel ranking lengkap
// ==============================================================

$page_title = "Leaderboard Mahasiswa";
require_once __DIR__ . '/includes/header.php';

$current_user_id = is_logged_in() ? (int)current_user()['id'] : 0;
$tab = $_GET['tab'] ?? 'points'; // tab filter: points | swaps | rating

// Tentukan query order sesuai filter tab yang dipilih
$order_by = "u.points DESC, u.rating DESC";
if ($tab === 'swaps') {
    $order_by = "completed_count DESC, u.points DESC";
} elseif ($tab === 'rating') {
    $order_by = "u.rating DESC, u.points DESC";
}

$leaderboard = [];
$my_rank = null;

if (is_db_connected()) {
    // Ambil data user mahasiswa dengan penghitungan sesi selesai
    $sql = "
        SELECT 
            u.id, u.name, u.major, u.points, u.rating, u.photo,
            (SELECT COUNT(*) FROM exchanges e WHERE (e.requester_id = u.id OR e.receiver_id = u.id) AND e.status = 'completed') AS completed_count,
            (SELECT b.name FROM user_badges ub JOIN badges b ON ub.badge_id = b.id WHERE ub.user_id = u.id ORDER BY b.min_points DESC LIMIT 1) AS badge_name,
            (SELECT b.icon FROM user_badges ub JOIN badges b ON ub.badge_id = b.id WHERE ub.user_id = u.id ORDER BY b.min_points DESC LIMIT 1) AS badge_icon
        FROM users u
        WHERE u.role = 'student' AND u.is_active = 1
        ORDER BY $order_by
        LIMIT 50
    ";

    $res = $conn->query($sql);
    if ($res) {
        $rank = 1;
        while ($row = $res->fetch_assoc()) {
            $row['rank'] = $rank;
            if ($row['id'] == $current_user_id) {
                $my_rank = $row;
            }
            $leaderboard[] = $row;
            $rank++;
        }
    }
}

// Pisahkan Top 3 buat podium dan sisanya buat tabel list
$top3 = array_slice($leaderboard, 0, 3);
$rest = array_slice($leaderboard, 3);
?>

<div class="container" style="padding: 40px 16px 80px;">

    <!-- HEADER TITLE & FILTER TABS -->
    <div style="text-align: center; max-width: 640px; margin: 0 auto 40px;">
        <span class="badge-tag" style="background: var(--blue-100); color: var(--blue-600); margin-bottom: 12px; display: inline-block;">
            🏆 Papan Peringkat Komunitas
        </span>
        <h1 style="font-size: 32px; font-weight: 800; color: var(--ink); margin-bottom: 8px;">
            Leaderboard TukarSkill
        </h1>
        <p style="font-size: 14px; color: var(--gray-600); line-height: 1.5;">
            Apresiasi tertinggi untuk mahasiswa yang paling aktif berbagi pengetahuan, menginspirasi sesama, dan memajukan pendidikan berkualitas di kampus.
        </p>

        <!-- TABS FILTER -->
        <div style="display: inline-flex; background: var(--gray-100); padding: 4px; border-radius: 999px; margin-top: 20px; gap: 4px;">
            <a href="leaderboard.php?tab=points" style="padding: 8px 18px; border-radius: 999px; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s; <?= ($tab === 'points') ? 'background: var(--white); color: var(--ink); box-shadow: var(--shadow-sm);' : 'color: var(--gray-600);' ?>">
                🪙 Poin Terbanyak
            </a>
            <a href="leaderboard.php?tab=swaps" style="padding: 8px 18px; border-radius: 999px; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s; <?= ($tab === 'swaps') ? 'background: var(--white); color: var(--ink); box-shadow: var(--shadow-sm);' : 'color: var(--gray-600);' ?>">
                🎓 Sesi Terbanyak
            </a>
            <a href="leaderboard.php?tab=rating" style="padding: 8px 18px; border-radius: 999px; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s; <?= ($tab === 'rating') ? 'background: var(--white); color: var(--ink); box-shadow: var(--shadow-sm);' : 'color: var(--gray-600);' ?>">
                ⭐ Rating Tertinggi
            </a>
        </div>
    </div>

    <!-- BANNER POSISI SAYA (KALO LOGIN & ADA DI RANKING) -->
    <?php if ($my_rank): ?>
        <div style="background: linear-gradient(135deg, var(--blue-50) 0%, #e0f2fe 100%); border: 1px solid var(--blue-200); border-radius: var(--radius-lg); padding: 16px 24px; margin-bottom: 36px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--blue-600); color: var(--white); font-weight: 900; font-size: 18px; display: flex; align-items: center; justify-content: center;">
                    #<?= $my_rank['rank'] ?>
                </div>
                <div>
                    <strong style="font-size: 15px; color: var(--ink); display: block;">Posisi Kamu Saat Ini: Ranking #<?= $my_rank['rank'] ?></strong>
                    <span style="font-size: 12px; color: var(--gray-600);">Terus bagikan skillmu untuk naik ke podium 3 besar!</span>
                </div>
            </div>
            <div style="font-size: 16px; font-weight: 800; color: var(--blue-600);">
                🪙 <?= number_format($my_rank['points']) ?> Poin
            </div>
        </div>
    <?php endif; ?>

    <!-- PODIUM 3 BESAR (EMAS, PERAK, PERUNGGU) -->
    <?php if (count($top3) >= 3): ?>
        <div style="display: flex; justify-content: center; align-items: flex-end; gap: 16px; margin-bottom: 48px; flex-wrap: wrap;">
            
            <!-- JUARA 2: PERAK -->
            <div style="flex: 1; min-width: 200px; max-width: 240px; background: var(--white); border-radius: var(--radius-xl); padding: 24px 20px 28px; text-align: center; border: 2px solid #cbd5e1; box-shadow: var(--shadow-sm); order: 1;">
                <div style="font-size: 28px; margin-bottom: 4px;">🥈</div>
                <div class="mini-avatar" style="width: 64px; height: 64px; margin: 0 auto 12px; background: #94a3b8; font-size: 24px; color: var(--white); box-shadow: 0 4px 12px rgba(148, 163, 184, 0.4);">
                    <?= strtoupper(substr($top3[1]['name'], 0, 1)) ?>
                </div>
                <strong style="font-size: 15px; color: var(--ink); display: block; margin-bottom: 2px;">
                    <a href="user.php?id=<?= $top3[1]['id'] ?>" style="color: inherit; text-decoration: none;">
                        <?= e($top3[1]['name']) ?>
                    </a>
                </strong>
                <span style="font-size: 11px; color: var(--gray-400); display: block; margin-bottom: 10px;">
                    <?= e($top3[1]['major']) ?>
                </span>
                <span class="badge-tag" style="background: #f1f5f9; color: #475569; font-size: 11px; margin-bottom: 12px;">
                    <?= $top3[1]['badge_icon'] ?? '🌱' ?> <?= e($top3[1]['badge_name'] ?? 'Bibit') ?>
                </span>
                <div style="font-size: 18px; font-weight: 800; color: var(--ink);">
                    <?= number_format($top3[1]['points']) ?> <span style="font-size: 12px; font-weight: 600; color: var(--gray-600);">Poin</span>
                </div>
            </div>

            <!-- JUARA 1: EMAS (PODIUM TENGAH TINGGI) -->
            <div style="flex: 1.1; min-width: 220px; max-width: 270px; background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%); border-radius: var(--radius-xl); padding: 32px 24px 36px; text-align: center; border: 2px solid #fde047; box-shadow: 0 12px 30px -10px rgba(234, 179, 8, 0.35); order: 2; transform: translateY(-12px);">
                <div style="font-size: 36px; margin-bottom: 4px;">👑</div>
                <div class="mini-avatar" style="width: 78px; height: 78px; margin: 0 auto 12px; background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%); font-size: 30px; color: var(--white); box-shadow: 0 6px 18px rgba(234, 179, 8, 0.45); border: 3px solid #fef08a;">
                    <?= strtoupper(substr($top3[0]['name'], 0, 1)) ?>
                </div>
                <strong style="font-size: 17px; color: var(--ink); display: block; margin-bottom: 2px;">
                    <a href="user.php?id=<?= $top3[0]['id'] ?>" style="color: inherit; text-decoration: none;">
                        <?= e($top3[0]['name']) ?>
                    </a>
                </strong>
                <span style="font-size: 12px; color: var(--gray-400); display: block; margin-bottom: 10px;">
                    <?= e($top3[0]['major']) ?>
                </span>
                <span class="badge-tag" style="background: #fef9c3; color: #854d0e; font-size: 11px; margin-bottom: 12px;">
                    <?= $top3[0]['badge_icon'] ?? '🏆' ?> <?= e($top3[0]['badge_name'] ?? 'Mentor SDG') ?>
                </span>
                <div style="font-size: 22px; font-weight: 900; color: #854d0e;">
                    <?= number_format($top3[0]['points']) ?> <span style="font-size: 13px; font-weight: 600; color: #a16207;">Poin</span>
                </div>
            </div>

            <!-- JUARA 3: PERUNGGU -->
            <div style="flex: 1; min-width: 200px; max-width: 240px; background: var(--white); border-radius: var(--radius-xl); padding: 24px 20px 28px; text-align: center; border: 2px solid #fed7aa; box-shadow: var(--shadow-sm); order: 3;">
                <div style="font-size: 28px; margin-bottom: 4px;">🥉</div>
                <div class="mini-avatar" style="width: 64px; height: 64px; margin: 0 auto 12px; background: #d97706; font-size: 24px; color: var(--white); box-shadow: 0 4px 12px rgba(217, 119, 6, 0.35);">
                    <?= strtoupper(substr($top3[2]['name'], 0, 1)) ?>
                </div>
                <strong style="font-size: 15px; color: var(--ink); display: block; margin-bottom: 2px;">
                    <a href="user.php?id=<?= $top3[2]['id'] ?>" style="color: inherit; text-decoration: none;">
                        <?= e($top3[2]['name']) ?>
                    </a>
                </strong>
                <span style="font-size: 11px; color: var(--gray-400); display: block; margin-bottom: 10px;">
                    <?= e($top3[2]['major']) ?>
                </span>
                <span class="badge-tag" style="background: #ffedd5; color: #9a3412; font-size: 11px; margin-bottom: 12px;">
                    <?= $top3[2]['badge_icon'] ?? '🌱' ?> <?= e($top3[2]['badge_name'] ?? 'Mahir') ?>
                </span>
                <div style="font-size: 18px; font-weight: 800; color: var(--ink);">
                    <?= number_format($top3[2]['points']) ?> <span style="font-size: 12px; font-weight: 600; color: var(--gray-600);">Poin</span>
                </div>
            </div>

        </div>
    <?php endif; ?>

    <!-- TABEL LENGKAP RANKING 4 DAN SETERUSNYA -->
    <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 20px;">
            Daftar Peringkat Mahasiswa
        </h3>

        <?php if (empty($leaderboard)): ?>
            <div style="text-align: center; padding: 40px; color: var(--gray-400);">
                Belum ada data mahasiswa yang dapat ditampilkan.
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--gray-200); color: var(--gray-600);">
                            <th style="padding: 12px 10px; width: 60px;">Rank</th>
                            <th style="padding: 12px 10px;">Mahasiswa</th>
                            <th style="padding: 12px 10px;">Badge Gelar</th>
                            <th style="padding: 12px 10px; text-align: center;">Rating</th>
                            <th style="padding: 12px 10px; text-align: center;">Sesi Selesai</th>
                            <th style="padding: 12px 10px; text-align: right;">Total Poin</th>
                            <th style="padding: 12px 10px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $row): 
                            $is_me = ($row['id'] == $current_user_id);
                        ?>
                            <tr style="border-bottom: 1px solid var(--gray-100); background: <?= $is_me ? '#eff6ff' : 'transparent' ?>;">
                                <td style="padding: 14px 10px; font-weight: 800; color: var(--ink);">
                                    <?php if ($row['rank'] == 1): ?>🥇
                                    <?php elseif ($row['rank'] == 2): ?>🥈
                                    <?php elseif ($row['rank'] == 3): ?>🥉
                                    <?php else: ?>#<?= $row['rank'] ?><?php endif; ?>
                                </td>
                                <td style="padding: 14px 10px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="mini-avatar" style="width: 36px; height: 36px; font-size: 14px; background: var(--blue-500);">
                                            <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong style="color: var(--ink); display: block;">
                                                <?= e($row['name']) ?> <?= $is_me ? '<span style="font-size:10px; background:var(--blue-500); color:white; padding:1px 6px; border-radius:999px;">Kamu</span>' : '' ?>
                                            </strong>
                                            <span style="font-size: 11px; color: var(--gray-400);"><?= e($row['major']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 14px 10px;">
                                    <span style="font-size: 12px; font-weight: 600; color: var(--gray-600);">
                                        <?= $row['badge_icon'] ?? '🌱' ?> <?= e($row['badge_name'] ?? 'Bibit') ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 10px; text-align: center; color: var(--star); font-weight: 700;">
                                    ★ <?= number_format($row['rating'], 1) ?>
                                </td>
                                <td style="padding: 14px 10px; text-align: center; font-weight: 700; color: var(--ink);">
                                    <?= (int)$row['completed_count'] ?> Sesi
                                </td>
                                <td style="padding: 14px 10px; text-align: right; font-weight: 800; color: var(--blue-600); font-size: 14px;">
                                    🪙 <?= number_format($row['points']) ?>
                                </td>
                                <td style="padding: 14px 10px; text-align: center;">
                                    <a href="user.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 11px;">
                                        Profil
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
