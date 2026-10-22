<?php
// ==============================================================
// skillmatch.php
// algoritma pencocokan cerdas (SkillMatch™) berdasarkan skill timbal balik, jadwal, dan reputasi
// ==============================================================

$page_title = "SkillMatch™ — Rekomendasi Partner";
require_once __DIR__ . '/includes/header.php';

require_login(); // satpam halaman: wajib login

$user = current_user();
$user_id = (int)$user['id'];

// Ambil skill yang saya miliki (offer) dan yang saya inginkan (want)
$my_offers = [];
$my_wants = [];
if (is_db_connected()) {
    $q_my_skills = $conn->query("SELECT skill_id, type FROM user_skills WHERE user_id = $user_id");
    if ($q_my_skills) {
        while ($r = $q_my_skills->fetch_assoc()) {
            if ($r['type'] === 'offer') {
                $my_offers[] = (int)$r['skill_id'];
            } else {
                $my_wants[] = (int)$r['skill_id'];
            }
        }
    }
}

// Ambil jadwal luang saya (availability)
$my_schedule = [];
if (is_db_connected()) {
    $q_my_av = $conn->query("SELECT day, start_time, end_time FROM availabilities WHERE user_id = $user_id");
    if ($q_my_av) {
        while ($r = $q_my_av->fetch_assoc()) {
            $my_schedule[] = $r;
        }
    }
}

// --------------------------------------------------------------
// ALGORITMA SKOR SKILLMATCH (0 - 100 Poin)
// --------------------------------------------------------------
$candidates = [];

if (is_db_connected() && (!empty($my_offers) || !empty($my_wants))) {
    // ambil semua mahasiswa aktif selain diri sendiri dan admin
    $q_users = $conn->query("
        SELECT u.id, u.name, u.major, u.batch_year, u.photo, u.bio,
               COALESCE(AVG(r.rating), 5.0) AS avg_rating,
               COUNT(DISTINCT r.id) AS review_count
        FROM users u
        LEFT JOIN reviews r ON r.reviewee_id = u.id AND r.is_hidden = 0
        WHERE u.id != $user_id AND u.role = 'user' AND u.is_active = 1
        GROUP BY u.id
    ");

    if ($q_users) {
        while ($candidate = $q_users->fetch_assoc()) {
            $cand_id = (int)$candidate['id'];

            // ambil skill offer & want kandidat
            $cand_offers = [];
            $cand_wants = [];
            $cand_offer_names = [];
            $cand_want_names = [];

            $q_cs = $conn->query("
                SELECT us.skill_id, us.type, s.name AS skill_name 
                FROM user_skills us 
                JOIN skills s ON us.skill_id = s.id 
                WHERE us.user_id = $cand_id
            ");
            if ($q_cs) {
                while ($cs = $q_cs->fetch_assoc()) {
                    if ($cs['type'] === 'offer') {
                        $cand_offers[] = (int)$cs['skill_id'];
                        $cand_offer_names[(int)$cs['skill_id']] = $cs['skill_name'];
                    } else {
                        $cand_wants[] = (int)$cs['skill_id'];
                        $cand_want_names[(int)$cs['skill_id']] = $cs['skill_name'];
                    }
                }
            }

            // hitung komponen kecocokan
            $score = 0;
            $matched_get = [];  // skill yang bisa kita pelajari dari dia
            $matched_give = []; // skill yang bisa kita ajarkan ke dia

            // Komponen A: Dia bisa mengajarkan apa yang saya inginkan (+40 poin)
            $intersection_get = array_intersect($cand_offers, $my_wants);
            if (!empty($intersection_get)) {
                $score += 40;
                foreach ($intersection_get as $sid) {
                    $matched_get[] = $cand_offer_names[$sid] ?? 'Skill';
                }
            }

            // Komponen B: Dia ingin mempelajari apa yang saya bisa (+30 poin) -> Swap Timbal Balik!
            $intersection_give = array_intersect($cand_wants, $my_offers);
            if (!empty($intersection_give)) {
                $score += 30;
                // cari nama skill saya
                $q_sname = $conn->query("SELECT name FROM skills WHERE id IN (" . implode(',', $intersection_give) . ")");
                if ($q_sname) {
                    while ($sn = $q_sname->fetch_assoc()) {
                        $matched_give[] = $sn['name'];
                    }
                }
            }

            // Komponen C: Jadwal luang beririsan (+15 poin)
            $has_schedule_overlap = false;
            $q_cand_av = $conn->query("SELECT day, start_time, end_time FROM availabilities WHERE user_id = $cand_id");
            if ($q_cand_av) {
                while ($c_av = $q_cand_av->fetch_assoc()) {
                    foreach ($my_schedule as $m_av) {
                        if ($c_av['day'] === $m_av['day']) {
                            // cek irisan jam
                            if ($c_av['start_time'] < $m_av['end_time'] && $c_av['end_time'] > $m_av['start_time']) {
                                $has_schedule_overlap = true;
                                break 2;
                            }
                        }
                    }
                }
            }
            if ($has_schedule_overlap) {
                $score += 15;
            }

            // Komponen D: Reputasi & rating (+15 poin)
            $cand_rating = (float)$candidate['avg_rating'];
            $reputation_pts = round(15 * ($cand_rating / 5.0));
            $score += $reputation_pts;

            // Hanya masukkan kandidat yang punya kecocokan skill (Komponen A atau B terpenuhi, skor >= 30)
            if (!empty($intersection_get) || !empty($intersection_give)) {
                $candidate['match_score'] = min(100, $score);
                $candidate['matched_get'] = $matched_get;
                $candidate['matched_give'] = $matched_give;
                $candidate['is_perfect_swap'] = (!empty($intersection_get) && !empty($intersection_give));
                $candidates[] = $candidate;
            }
        }
    }

    // Urutkan kandidat dari skor kecocokan tertinggi
    usort($candidates, function($a, $b) {
        return $b['match_score'] <=> $a['match_score'];
    });
}
?>

<div class="container" style="padding: 40px 16px 80px;">

    <!-- Header Halaman SkillMatch -->
    <div style="text-align: center; max-width: 720px; margin: 0 auto 40px;">
        <span class="section-tag" style="background: var(--lime); color: var(--lime-dark);">✨ Algoritma Rekomendasi</span>
        <h1 style="font-size: 32px; font-weight: 800; color: var(--ink); margin-top: 10px;">
            SkillMatch™ Partner Finder 🎯
        </h1>
        <p style="font-size: 15px; color: var(--gray-600); margin-top: 8px;">
            Sistem otomatis mencocokkan keahlianmu dengan mahasiswa lain yang memiliki kebutuhan saling melengkapi, jadwal sinkron, dan reputasi terpercaya.
        </p>
    </div>

    <!-- Peringatan Kalo User Belum Nambahin Skill -->
    <?php if (empty($my_offers) && empty($my_wants)): ?>
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 50px 20px; text-align: center; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); max-width: 600px; margin: 0 auto;">
            <div style="font-size: 48px; margin-bottom: 12px;">🌱</div>
            <h3 style="font-size: 20px; font-weight: 800; color: var(--ink);">Profil Skill Kamu Masih Kosong</h3>
            <p style="font-size: 14px; color: var(--gray-600); margin: 8px 0 24px;">
                Tambahkan minimal 1 skill yang bisa kamu ajarkan dan 1 skill yang ingin kamu pelajari agar SkillMatch bisa menghitung persentase kecocokanmu!
            </p>
            <a href="my-skills.php" class="btn btn-primary">Atur Skill Saya Sekarang <span class="btn-arrow">↗</span></a>
        </div>
    <?php elseif (empty($candidates)): ?>
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 50px 20px; text-align: center; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); max-width: 600px; margin: 0 auto;">
            <div style="font-size: 48px; margin-bottom: 12px;">🤝</div>
            <h3 style="font-size: 20px; font-weight: 800; color: var(--ink);">Belum Menemukan Match yang Cocok</h3>
            <p style="font-size: 14px; color: var(--gray-600); margin: 8px 0 24px;">
                Coba tambahkan lebih banyak variasi skill incaran di menu Skill Saya, atau telusuri semua penawaran di menu Cari Skill.
            </p>
            <div style="display: flex; justify-content: center; gap: 12px;">
                <a href="my-skills.php" class="btn btn-outline btn-sm">Perbarui Skill Saya</a>
                <a href="discover.php" class="btn btn-primary btn-sm">Jelajahi Semua Partner</a>
            </div>
        </div>
    <?php else: ?>
        <!-- DAFTAR KARTU REKOMENDASI MATCH (SWAP CARDS) -->
        <div style="display: flex; flex-direction: column; gap: 20px; max-width: 900px; margin: 0 auto;">
            <?php foreach ($candidates as $cand): 
                $score = $cand['match_score'];
                $is_perfect = $cand['is_perfect_swap'];
            ?>
                <div style="background: var(--white); border-radius: var(--radius-xl); padding: 28px; box-shadow: var(--shadow-sm); border: 1px solid <?= $is_perfect ? 'var(--blue-400)' : 'var(--gray-200)' ?>; position: relative; overflow: hidden; transition: transform 0.2s ease;">
                    
                    <!-- Pita Highlight untuk Swap Sempurna (Mutual) -->
                    <?php if ($is_perfect): ?>
                        <div style="position: absolute; top: 0; right: 0; background: var(--lime); color: var(--lime-dark); font-size: 11px; font-weight: 800; padding: 6px 18px; border-radius: 0 0 0 var(--radius-md); text-transform: uppercase;">
                            ⚡ Swap Sempurna (Saling Membutuhkan)
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px;">
                        
                        <!-- Info Partner Kiri -->
                        <div style="display: flex; align-items: center; gap: 18px; flex: 1; min-width: 280px;">
                            <div style="width: 64px; height: 64px; border-radius: 50%; overflow: hidden; background: var(--blue-500); color: var(--white); display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; flex-shrink: 0;">
                                <?php if (!empty($cand['photo']) && file_exists(__DIR__ . '/uploads/avatars/' . $cand['photo'])): ?>
                                    <img src="uploads/avatars/<?= e($cand['photo']) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <?= strtoupper(substr($cand['name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>

                            <div>
                                <h3 style="font-size: 18px; font-weight: 800; color: var(--ink);">
                                    <?= e($cand['name']) ?>
                                </h3>
                                <p style="font-size: 13px; color: var(--gray-600); margin-top: 2px;">
                                    <?= e($cand['major']) ?> • Angkatan <?= e($cand['batch_year'] ?? '-') ?>
                                </p>
                                <div style="display: flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 12px;">
                                    <span style="color: var(--star);">★</span>
                                    <strong><?= number_format((float)$cand['avg_rating'], 1) ?></strong>
                                    <span style="color: var(--gray-400);">• <?= (int)$cand['review_count'] ?> sesi selesai</span>
                                </div>
                            </div>
                        </div>

                        <!-- Cincin Persentase Skor Tengah -->
                        <div style="text-align: center; padding: 0 16px;">
                            <div style="font-size: 34px; font-weight: 800; color: <?= ($score >= 80) ? '#16a34a' : 'var(--blue-600)' ?>; line-height: 1;">
                                <?= $score ?>%
                            </div>
                            <span style="font-size: 11px; font-weight: 700; color: var(--gray-600); text-transform: uppercase;">Match Score</span>
                        </div>

                        <!-- Tombol Aksi Kanan -->
                        <div style="display: flex; gap: 10px;">
                            <a href="user.php?id=<?= (int)$cand['id'] ?>" class="btn btn-outline btn-sm">Lihat Profil</a>
                            <a href="request.php?receiver_id=<?= (int)$cand['id'] ?>" class="btn btn-primary btn-sm">
                                Ajak Barter <span class="btn-arrow">↗</span>
                            </a>
                        </div>
                    </div>

                    <!-- Kotak Visual Saling Barter (Kiri: Kamu dapat, Kanan: Kamu beri) -->
                    <div style="margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--gray-100); display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                        
                        <!-- Apa yang bisa kamu pelajari dari dia -->
                        <div style="background: var(--blue-50); border-radius: var(--radius-md); padding: 12px 16px;">
                            <span style="font-size: 11px; font-weight: 700; color: var(--blue-600); text-transform: uppercase;">
                                🎓 Kamu Akan Pelajari:
                            </span>
                            <div style="font-size: 13px; font-weight: 700; color: var(--ink); margin-top: 4px;">
                                <?= !empty($cand['matched_get']) ? e(implode(', ', $cand['matched_get'])) : '<span style="color:var(--gray-400); font-weight:400;">Pilihan terbuka (kamu ajari dia)</span>' ?>
                            </div>
                        </div>

                        <!-- Apa yang bisa kamu ajarkan ke dia -->
                        <div style="background: #f7fee7; border-radius: var(--radius-md); padding: 12px 16px;">
                            <span style="font-size: 11px; font-weight: 700; color: #65a30d; text-transform: uppercase;">
                                💡 Kamu Akan Ajarkan:
                            </span>
                            <div style="font-size: 13px; font-weight: 700; color: var(--ink); margin-top: 4px;">
                                <?= !empty($cand['matched_give']) ? e(implode(', ', $cand['matched_give'])) : '<span style="color:var(--gray-400); font-weight:400;">Bisa gunakan sistem 15 Poin</span>' ?>
                            </div>
                        </div>

                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
