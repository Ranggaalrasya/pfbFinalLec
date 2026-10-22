<?php
// ==============================================================
// dashboard.php
// dashboard utama mahasiswa untuk memantau poin, jadwal, dan request masuk
// ==============================================================

$page_title = "Dashboard Mahasiswa";
require_once __DIR__ . '/includes/header.php';

require_login(); // hanya mahasiswa terdaftar yang bisa masuk

$user = current_user();
$user_id = (int)$user['id'];

// Ambil statistik ringkas mahasiswa
$total_completed = 0;
$avg_rating = 5.0;
$total_reviews = 0;
$pending_incoming_count = 0;

if (is_db_connected()) {
    // hitung total pertukaran yang sudah sukses (completed)
    $q_comp = $conn->query("SELECT COUNT(*) AS total FROM exchanges WHERE (requester_id = $user_id OR receiver_id = $user_id) AND status = 'completed'");
    if ($q_comp && $r = $q_comp->fetch_assoc()) {
        $total_completed = (int)$r['total'];
    }

    // hitung rata-rata rating review
    $q_rev = $conn->query("SELECT AVG(rating) AS avg_r, COUNT(*) AS total_r FROM reviews WHERE reviewee_id = $user_id AND is_hidden = 0");
    if ($q_rev && $r = $q_rev->fetch_assoc()) {
        if ($r['total_r'] > 0) {
            $avg_rating = round((float)$r['avg_r'], 1);
            $total_reviews = (int)$r['total_r'];
        }
    }

    // hitung request masuk yang butuh persetujuan user
    $q_pend = $conn->query("SELECT COUNT(*) AS total FROM exchanges WHERE receiver_id = $user_id AND status = 'pending'");
    if ($q_pend && $r = $q_pend->fetch_assoc()) {
        $pending_incoming_count = (int)$r['total'];
    }
}

// Cari info badge saat ini & badge berikutnya
$current_badge = ['name' => 'Bibit', 'icon' => '🌱', 'min_points' => 0];
$next_badge = ['name' => 'Tunas', 'icon' => '🌿', 'min_points' => 50];

if (is_db_connected()) {
    $q_b = $conn->query("
        SELECT b.* 
        FROM user_badges ub 
        JOIN badges b ON ub.badge_id = b.id 
        WHERE ub.user_id = $user_id 
        ORDER BY b.min_points DESC 
        LIMIT 1
    ");
    if ($q_b && $r = $q_b->fetch_assoc()) {
        $current_badge = $r;
    }

    // cari target badge berikutnya
    $q_next = $conn->query("SELECT * FROM badges WHERE min_points > " . (int)$user['total_points'] . " ORDER BY min_points ASC LIMIT 1");
    if ($q_next && $r = $q_next->fetch_assoc()) {
        $next_badge = $r;
    } else {
        $next_badge = null; // sudah mencapai tier tertinggi (Mentor)
    }
}

// Ambil jadwal pertukaran yang aktif / diterima (accepted)
$upcoming_sessions = [];
if (is_db_connected()) {
    $q_ses = $conn->query("
        SELECT e.*, 
               u_req.name AS requester_name, u_req.whatsapp AS requester_wa,
               u_rec.name AS receiver_name, u_rec.whatsapp AS receiver_wa,
               s_req.name AS req_skill_name,
               s_off.name AS off_skill_name
        FROM exchanges e
        JOIN users u_req ON e.requester_id = u_req.id
        JOIN users u_rec ON e.receiver_id = u_rec.id
        JOIN skills s_req ON e.requested_skill_id = s_req.id
        LEFT JOIN skills s_off ON e.offered_skill_id = s_off.id
        WHERE (e.requester_id = $user_id OR e.receiver_id = $user_id) 
          AND e.status = 'accepted'
        ORDER BY e.scheduled_at ASC
        LIMIT 3
    ");
    if ($q_ses) {
        while ($row = $q_ses->fetch_assoc()) {
            $upcoming_sessions[] = $row;
        }
    }
}

// Ambil 3 rekomendasi partner SkillMatch tercepat
$match_recommendations = [];
if (is_db_connected()) {
    // cari user yang menawarkan skill yang kita inginkan
    $q_rec = $conn->query("
        SELECT DISTINCT u.id, u.name, u.major, s.name AS offer_skill_name, COALESCE(AVG(r.rating), 5.0) AS rating
        FROM user_skills us_offer
        JOIN users u ON us_offer.user_id = u.id
        JOIN skills s ON us_offer.skill_id = s.id
        LEFT JOIN reviews r ON r.reviewee_id = u.id AND r.is_hidden = 0
        WHERE us_offer.type = 'offer' 
          AND u.id != $user_id 
          AND u.is_active = 1
          AND us_offer.skill_id IN (
              SELECT skill_id FROM user_skills WHERE user_id = $user_id AND type = 'want'
          )
        GROUP BY u.id
        LIMIT 3
    ");
    if ($q_rec) {
        while ($row = $q_rec->fetch_assoc()) {
            $match_recommendations[] = $row;
        }
    }
}
?>

<div class="container" style="padding: 40px 16px 80px;">

    <!-- Sapaan Atas & Aksi Cepat -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 32px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: var(--ink);">
                Hai, <?= e(explode(' ', $user['name'])[0]) ?> 👋
            </h1>
            <p style="font-size: 14px; color: var(--gray-600); margin-top: 4px;">
                Selamat datang kembali di pusat aktivitas barter skill-mu.
            </p>
        </div>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="my-skills.php" class="btn btn-outline btn-sm">+ Tambah Skill Saya</a>
            <a href="discover.php" class="btn btn-primary btn-sm">Cari Partner Baru <span class="btn-arrow">↗</span></a>
        </div>
    </div>

    <!-- Alert Permintaan Masuk Kalo Ada Request Pending -->
    <?php if ($pending_incoming_count > 0): ?>
        <div style="background: var(--lime); border-radius: var(--radius-lg); padding: 18px 24px; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 24px;">📬</span>
                <div>
                    <strong style="color: var(--lime-dark); font-size: 15px;">Kamu punya <?= $pending_incoming_count ?> permintaan tukar skill baru!</strong>
                    <p style="font-size: 13px; color: var(--lime-dark); opacity: 0.9; margin-top: 2px;">Segera cek dan respon calon partner belajarmu.</p>
                </div>
            </div>
            <a href="exchanges.php?tab=incoming" class="btn btn-dark btn-sm">Lihat Permintaan →</a>
        </div>
    <?php endif; ?>

    <!-- 4 KARTU STATISTIK BENTO ALA LEARNNIX -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 36px;">
        
        <!-- Kartu 1: Saldo Poin -->
        <div style="background: var(--white); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-size: 12px; font-weight: 700; color: var(--gray-600); text-transform: uppercase;">Saldo Poin Aktif</span>
                <span style="font-size: 20px;">🌱</span>
            </div>
            <div style="font-size: 32px; font-weight: 800; color: var(--blue-600); line-height: 1;">
                <?= (int)$user['points'] ?> <span style="font-size: 14px; color: var(--gray-400); font-weight: 600;">PTS</span>
            </div>
            <p style="font-size: 12px; color: var(--gray-600); margin-top: 8px;">
                Bisa dipakai request sesi tanpa barter. <a href="points.php" style="color: var(--blue-600); font-weight: 600;">Riwayat</a>
            </p>
        </div>

        <!-- Kartu 2: Badge Mahasiswa -->
        <div style="background: var(--white); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-size: 12px; font-weight: 700; color: var(--gray-600); text-transform: uppercase;">Tingkat Badge</span>
                <span style="font-size: 20px;"><?= e($current_badge['icon'] ?? '🌱') ?></span>
            </div>
            <div style="font-size: 28px; font-weight: 800; color: var(--ink); line-height: 1;">
                <?= e($current_badge['name'] ?? 'Bibit') ?>
            </div>
            <p style="font-size: 12px; color: var(--gray-600); margin-top: 8px;">
                <?php if ($next_badge): ?>
                    Perlu <?= ((int)$next_badge['min_points'] - (int)$user['total_points']) ?> poin lagi ke <?= e($next_badge['icon'] . ' ' . $next_badge['name']) ?>
                <?php else: ?>
                    🏆 Kamu sudah di level tertinggi Mentor!
                <?php endif; ?>
            </p>
        </div>

        <!-- Kartu 3: Pertukaran Sukses -->
        <div style="background: var(--white); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-size: 12px; font-weight: 700; color: var(--gray-600); text-transform: uppercase;">Sesi Selesai</span>
                <span style="font-size: 20px;">🤝</span>
            </div>
            <div style="font-size: 32px; font-weight: 800; color: var(--ink); line-height: 1;">
                <?= $total_completed ?> <span style="font-size: 14px; color: var(--gray-400); font-weight: 600;">Sesi</span>
            </div>
            <p style="font-size: 12px; color: var(--gray-600); margin-top: 8px;">
                Total sesi belajar & mengajar yang sukses.
            </p>
        </div>

        <!-- Kartu 4: Reputasi & Review -->
        <div style="background: var(--white); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-size: 12px; font-weight: 700; color: var(--gray-600); text-transform: uppercase;">Reputasi Kamu</span>
                <span style="font-size: 20px; color: var(--star);">★</span>
            </div>
            <div style="font-size: 32px; font-weight: 800; color: var(--ink); line-height: 1;">
                <?= number_format($avg_rating, 1) ?> <span style="font-size: 14px; color: var(--gray-400); font-weight: 600;">/ 5.0</span>
            </div>
            <p style="font-size: 12px; color: var(--gray-600); margin-top: 8px;">
                Dari <?= $total_reviews ?> review ulasan teman kampus.
            </p>
        </div>

    </div>

    <!-- GRID DUA KOLOM: Sesi Terdekat & Rekomendasi Partner -->
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 28px; align-items: start;">
        
        <!-- KOLOM KIRI: Sesi Pertukaran Terdekat -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 18px; font-weight: 800; color: var(--ink);">Jadwal Sesi Terdekat 📅</h3>
                <a href="exchanges.php" style="font-size: 13px; font-weight: 600; color: var(--blue-600);">Semua Sesi →</a>
            </div>

            <?php if (empty($upcoming_sessions)): ?>
                <div style="text-align: center; padding: 40px 20px; color: var(--gray-400);">
                    <div style="font-size: 36px; margin-bottom: 10px;">☕</div>
                    <p style="font-size: 14px; font-weight: 600; color: var(--gray-600);">Belum ada jadwal sesi pertukaran aktif.</p>
                    <p style="font-size: 13px; margin-top: 4px;">Cari partner yang cocok dan ajukan permintaan tukar skill pertamamu!</p>
                    <a href="discover.php" class="btn btn-outline btn-sm" style="margin-top: 16px;">Jelajahi Skill Sekarang</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($upcoming_sessions as $ses): 
                        $is_requester = ($ses['requester_id'] == $user_id);
                        $partner_name = $is_requester ? $ses['receiver_name'] : $ses['requester_name'];
                        $partner_wa = $is_requester ? $ses['receiver_wa'] : $ses['requester_wa'];
                    ?>
                        <div style="border: 1px solid var(--gray-200); border-radius: var(--radius-md); padding: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                            <div>
                                <span class="badge-tag" style="background: var(--blue-100); color: var(--blue-600); margin-bottom: 6px;">
                                    <?= strtoupper(e($ses['mode'])) ?> • <?= format_tanggal_id($ses['scheduled_at']) ?>
                                </span>
                                <h4 style="font-size: 15px; font-weight: 700; color: var(--ink); margin-top: 4px;">
                                    Partner: <?= e($partner_name) ?>
                                </h4>
                                <p style="font-size: 13px; color: var(--gray-600); margin-top: 2px;">
                                    Materi: <strong><?= e($ses['req_skill_name']) ?></strong>
                                    <?= !empty($ses['off_skill_name']) ? ' ⇄ <strong>' . e($ses['off_skill_name']) . '</strong>' : ' (Via Poin)' ?>
                                </p>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <?php if (!empty($partner_wa)): ?>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $partner_wa) ?>" target="_blank" class="btn btn-outline btn-sm" style="color: #15803d; border-color: #86efac;">
                                        WhatsApp 💬
                                    </a>
                                <?php endif; ?>
                                <a href="exchange-detail.php?id=<?= (int)$ses['id'] ?>" class="btn btn-dark btn-sm">Detail</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- KOLOM KANAN: Rekomendasi Partner SkillMatch -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 18px; font-weight: 800; color: var(--ink);">Rekomendasi Match ✨</h3>
                <a href="skillmatch.php" style="font-size: 13px; font-weight: 600; color: var(--blue-600);">Semua →</a>
            </div>

            <?php if (empty($match_recommendations)): ?>
                <div style="text-align: center; padding: 30px 10px; color: var(--gray-400);">
                    <div style="font-size: 32px; margin-bottom: 8px;">🎯</div>
                    <p style="font-size: 13px; color: var(--gray-600);">
                        Tambahkan skill yang ingin kamu pelajari di menu <strong>Skill Saya</strong> agar SkillMatch bisa merekomendasikan partner yang cocok!
                    </p>
                    <a href="my-skills.php" class="btn btn-outline btn-sm" style="margin-top: 14px;">Atur Skill Incaran</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($match_recommendations as $match): ?>
                        <div style="padding: 14px; background: var(--blue-50); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="mini-avatar" style="background: var(--blue-500); width: 36px; height: 36px; font-size: 13px;">
                                    <?= strtoupper(substr($match['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h4 style="font-size: 13px; font-weight: 700; color: var(--ink);"><?= e($match['name']) ?></h4>
                                    <span style="font-size: 11px; color: var(--blue-600); font-weight: 600;">Bisa: <?= e($match['offer_skill_name']) ?></span>
                                </div>
                            </div>
                            <a href="user.php?id=<?= (int)$match['id'] ?>" class="btn btn-dark btn-sm" style="font-size: 11px; padding: 5px 10px;">
                                Profil →
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Banner SDG Komitmen -->
            <div style="background: var(--mint); border-radius: var(--radius-md); padding: 14px; margin-top: 24px; font-size: 12px; color: var(--mint-dark); line-height: 1.5;">
                <strong>🌱 SDG 4 Quality Education</strong><br>
                Setiap kali kamu mengajarkan skill ke mahasiswa lain, kamu membantu membuka akses ilmu tanpa batasan finansial.
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
