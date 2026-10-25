<?php
// ==============================================================
// user.php
// halaman profil publik mahasiswa (bisa dilihat guest & user lain)
// ==============================================================

require_once __DIR__ . '/includes/header.php';

$target_id = (int)($_GET['id'] ?? 0);
if ($target_id <= 0) {
    set_flash('danger', 'Profil mahasiswa tidak ditemukan.');
    header('Location: discover.php');
    exit;
}

$target_user = null;
if (is_db_connected()) {
    $stmt = $conn->prepare("SELECT id, name, nim, major, batch_year, bio, photo, points, total_points FROM users WHERE id = ? AND is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $target_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $target_user = $res->fetch_assoc();
        $stmt->close();
    }
}

if (!$target_user) {
    set_flash('danger', 'Akun mahasiswa tidak ditemukan atau sedang nonaktif.');
    header('Location: discover.php');
    exit;
}

$page_title = "Profil " . $target_user['name'];

// Ambil rating rata-rata & jumlah review
$avg_rating = 5.0;
$review_count = 0;
if (is_db_connected()) {
    $q_r = $conn->query("SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM reviews WHERE reviewee_id = $target_id AND is_hidden = 0");
    if ($q_r && $row_r = $q_r->fetch_assoc()) {
        if ($row_r['cnt'] > 0) {
            $avg_rating = round((float)$row_r['avg_r'], 1);
            $review_count = (int)$row_r['cnt'];
        }
    }
}

// Ambil badge yang dimiliki mahasiswa ini
$badges = [];
if (is_db_connected()) {
    $q_b = $conn->query("
        SELECT b.name, b.icon, b.description 
        FROM user_badges ub 
        JOIN badges b ON ub.badge_id = b.id 
        WHERE ub.user_id = $target_id 
        ORDER BY b.min_points DESC
    ");
    if ($q_b) {
        while ($row = $q_b->fetch_assoc()) {
            $badges[] = $row;
        }
    }
}

// Ambil daftar skill yang BISA diajarkan (offer)
$offers = [];
// Ambil daftar skill yang INGIN dipelajari (want)
$wants = [];
if (is_db_connected()) {
    $q_skills = $conn->query("
        SELECT us.id, us.type, us.level, us.description, s.name AS skill_name, c.name AS category_name, c.icon AS category_icon
        FROM user_skills us
        JOIN skills s ON us.skill_id = s.id
        JOIN categories c ON s.category_id = c.id
        WHERE us.user_id = $target_id
        ORDER BY us.type ASC, s.name ASC
    ");
    if ($q_skills) {
        while ($row = $q_skills->fetch_assoc()) {
            if ($row['type'] === 'offer') {
                $offers[] = $row;
            } else {
                $wants[] = $row;
            }
        }
    }
}

// Ambil jadwal luang availability
$availabilities = [];
if (is_db_connected()) {
    $q_av = $conn->query("SELECT * FROM availabilities WHERE user_id = $target_id ORDER BY FIELD(day, 'senin','selasa','rabu','kamis','jumat','sabtu','minggu'), start_time ASC");
    if ($q_av) {
        while ($row = $q_av->fetch_assoc()) {
            $availabilities[] = $row;
        }
    }
}

// Ambil review dari mahasiswa lain
$reviews = [];
if (is_db_connected()) {
    $q_revs = $conn->query("
        SELECT r.*, u.name AS reviewer_name, u.photo AS reviewer_photo, u.major AS reviewer_major
        FROM reviews r
        JOIN users u ON r.reviewer_id = u.id
        WHERE r.reviewee_id = $target_id AND r.is_hidden = 0
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    if ($q_revs) {
        while ($row = $q_revs->fetch_assoc()) {
            $reviews[] = $row;
        }
    }
}

$is_self = is_logged_in() && ($target_id === (int)$_SESSION['user_id']);
?>

<div class="container" style="padding: 40px 16px 80px;">

    <!-- Card Profil Header Atas -->
    <div style="background: var(--white); border-radius: var(--radius-xl); padding: 36px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); margin-bottom: 32px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 24px;">
        <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
            
            <!-- Avatar Foto dengan render_avatar helper -->
            <?= render_avatar($target_user['photo'], $target_user['name'], 90) ?>

            <!-- Biodata Singkat -->
            <div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h1 style="font-size: 24px; font-weight: 800; color: var(--ink);"><?= e($target_user['name']) ?></h1>
                    <?php foreach ($badges as $b): ?>
                        <span class="badge-tag" style="background: var(--lime); color: var(--lime-dark); display: inline-flex; align-items: center; gap: 6px;" title="<?= e($b['description']) ?>">
                            <?= badge_icon_svg($b['icon'], '14px') ?> <?= e($b['name']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <p style="font-size: 14px; color: var(--blue-600); font-weight: 600; margin-top: 4px;">
                    <?= e($target_user['major']) ?> (Angkatan <?= e($target_user['batch_year'] ?? '-') ?>)
                </p>

                <!-- Rating & Review -->
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 13px;">
                    <span style="color: var(--star); display: inline-flex; align-items: center;"><?= icon('star', '', '16px') ?></span>
                    <strong style="color: var(--ink);"><?= number_format($avg_rating, 1) ?></strong>
                    <span style="color: var(--gray-400);">•</span>
                    <span style="color: var(--gray-600);"><?= $review_count ?> ulasan sesi</span>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div>
            <?php if ($is_self): ?>
                <a href="profile.php" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 8px;">
                    <?= icon('settings', '', '16px') ?> Edit Profil Saya
                </a>
            <?php else: ?>
                <a href="request.php?receiver_id=<?= $target_id ?>" class="btn btn-primary" style="padding: 12px 24px;">
                    Ajak Tukar Skill Sekarang <span class="btn-arrow">↗</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bio Mahasiswa -->
    <?php if (!empty($target_user['bio'])): ?>
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 24px 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); margin-bottom: 32px;">
            <h3 style="font-size: 15px; font-weight: 700; color: var(--gray-600); text-transform: uppercase; margin-bottom: 8px;">Tentang Saya</h3>
            <p style="font-size: 15px; color: var(--ink); line-height: 1.6;">
                <?= nl2br(e($target_user['bio'])) ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- DUA KOLOM: Keahlian yang Ditawarkan (Kiri) & Jadwal + Ulasan (Kanan) -->
    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 28px; align-items: start;">
        
        <!-- KOLOM KIRI: Daftar Skill (Offer & Want) -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- 1. Skill yang BISA Diajarkan (Offer) -->
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--blue-600); display: flex; align-items: center; gap: 8px;">
                        <?= icon('graduation-cap', '', '20px') ?> Skill yang Bisa Diajarkan
                    </h3>
                    <span class="badge-tag" style="background: var(--blue-100); color: var(--blue-600);">Bisa Jadi Guru</span>
                </div>

                <?php if (empty($offers)): ?>
                    <p style="font-size: 13px; color: var(--gray-400); font-style: italic;">Mahasiswa ini belum menambahkan skill yang bisa diajarkan.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($offers as $o): ?>
                            <div style="padding: 16px; background: var(--blue-50); border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="display: inline-flex; align-items: center; color: var(--blue-600);"><?= category_icon_svg($o['category_icon'], '18px') ?></span>
                                        <strong style="font-size: 15px; color: var(--ink);"><?= e($o['skill_name']) ?></strong>
                                        <span class="badge-tag" style="background: var(--white); color: var(--blue-600); font-size: 11px;">
                                            <?= ucfirst(e($o['level'])) ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($o['description'])): ?>
                                        <p style="font-size: 13px; color: var(--gray-600); margin-top: 6px;"><?= e($o['description']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$is_self): ?>
                                    <a href="request.php?receiver_id=<?= $target_id ?>&requested_skill_id=<?= (int)$o['id'] ?>" class="btn btn-dark btn-sm" style="font-size: 12px; padding: 6px 12px; flex-shrink: 0;">
                                        Pilih Skill Ini →
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2. Skill yang INGIN Dipelajari (Want) -->
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h3 style="font-size: 18px; font-weight: 800; color: #65a30d; display: flex; align-items: center; gap: 8px;">
                        <?= icon('sparkles', '', '20px') ?> Skill yang Ingin Dipelajari
                    </h3>
                    <span class="badge-tag" style="background: var(--lime); color: var(--lime-dark);">Terbuka Barter</span>
                </div>

                <?php if (empty($wants)): ?>
                    <p style="font-size: 13px; color: var(--gray-400); font-style: italic;">Mahasiswa ini belum mencantumkan skill yang ingin dipelajari.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($wants as $w): ?>
                            <div style="padding: 14px 18px; background: #f7fee7; border-radius: var(--radius-md); display: flex; align-items: center; gap: 10px;">
                                <span style="display: inline-flex; align-items: center; color: #65a30d;"><?= category_icon_svg($w['category_icon'], '18px') ?></span>
                                <strong style="font-size: 14px; color: var(--ink);"><?= e($w['skill_name']) ?></strong>
                                <span class="badge-tag" style="background: var(--white); color: #65a30d; font-size: 11px;">
                                    Target: <?= ucfirst(e($w['level'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 3. Daftar Review & Testimoni Teman Kampus -->
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
                <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 20px;">
                    Ulasan & Reputasi (<?= count($reviews) ?>)
                </h3>

                <?php if (empty($reviews)): ?>
                    <p style="font-size: 13px; color: var(--gray-400); font-style: italic;">Belum ada ulasan untuk mahasiswa ini.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <?php foreach ($reviews as $rv): ?>
                            <div style="border-bottom: 1px solid var(--gray-200); padding-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?= render_avatar($rv['reviewer_photo'], $rv['reviewer_name'], 34) ?>
                                        <div>
                                            <strong style="font-size: 14px; color: var(--ink);"><?= e($rv['reviewer_name']) ?></strong>
                                            <span style="font-size: 11px; color: var(--gray-400); display: block;"><?= e($rv['reviewer_major']) ?></span>
                                        </div>
                                    </div>
                                    <div style="color: var(--star); font-size: 14px; display: inline-flex; align-items: center; gap: 2px;">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <?= icon('star', $s <= (int)$rv['rating'] ? 'text-star' : 'text-gray-300', '14px') ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php if (!empty($rv['comment'])): ?>
                                    <p style="font-size: 13px; color: var(--gray-600); line-height: 1.5; margin-left: 44px;">
                                        "<?= e($rv['comment']) ?>"
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- KOLOM KANAN: Jadwal Ketersediaan Luang -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <?= icon('calendar', '', '20px') ?> Jadwal Luang
            </h3>
            <p style="font-size: 13px; color: var(--gray-600); margin-bottom: 20px;">
                Waktu yang disarankan untuk mengadakan sesi belajar bersama <?= e(explode(' ', $target_user['name'])[0]) ?>:
            </p>

            <?php if (empty($availabilities)): ?>
                <p style="font-size: 13px; color: var(--gray-400); font-style: italic;">Belum ada jadwal khusus. Kamu bisa usulkan jadwal saat mengirim request.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($availabilities as $av): ?>
                        <div style="background: var(--blue-50); border: 1px solid var(--blue-100); padding: 12px 16px; border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center;">
                            <strong style="font-size: 13px; color: var(--ink);"><?= ucfirst(e($av['day'])) ?></strong>
                            <span style="font-size: 13px; color: var(--blue-600); font-weight: 600;">
                                <?= substr(e($av['start_time']), 0, 5) ?> - <?= substr(e($av['end_time']), 0, 5) ?> WIB
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$is_self): ?>
                <div style="margin-top: 28px;">
                    <a href="request.php?receiver_id=<?= $target_id ?>" class="btn btn-primary" style="width: 100%;">
                        Kirim Request Barter <span class="btn-arrow">↗</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
