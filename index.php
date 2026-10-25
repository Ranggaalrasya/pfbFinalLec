<?php
// ==============================================================
// index.php
// halaman utama (landing page) TukarSkill dengan visual LearnNix
// ==============================================================

$page_title = "Beranda";
require_once __DIR__ . '/includes/header.php';

// Ambil data kategori dari database kalo koneksi nyala
$categories = [];
if (is_db_connected()) {
    $cat_query = $conn->query("SELECT c.*, COUNT(s.id) AS skill_count FROM categories c LEFT JOIN skills s ON s.category_id = c.id GROUP BY c.id ORDER BY c.id ASC");
    if ($cat_query) {
        while ($row = $cat_query->fetch_assoc()) {
            $categories[] = $row;
        }
    }
}

// Kalo database belom ada datanya, siapin fallback data biar tampilan tetep cakep pas demo
if (empty($categories)) {
    $categories = [
        ['id' => 1, 'name' => 'Pemrograman & Web', 'icon' => '💻', 'skill_count' => 5],
        ['id' => 2, 'name' => 'Desain Grafis & UI/UX', 'icon' => '🎨', 'skill_count' => 4],
        ['id' => 3, 'name' => 'Bahasa Asing & TOEFL', 'icon' => '🗣️', 'skill_count' => 4],
        ['id' => 4, 'name' => 'Fotografi & Videografi', 'icon' => '📸', 'skill_count' => 3],
        ['id' => 5, 'name' => 'Public Speaking', 'icon' => '🎤', 'skill_count' => 3],
        ['id' => 6, 'name' => 'Tutoring Matkul', 'icon' => '📚', 'skill_count' => 4],
    ];
}

// Ambil daftar penawar skill (mentors) teratas dari database
$featured_offers = [];
if (is_db_connected()) {
    $offer_sql = "
        SELECT 
            us.id AS user_skill_id,
            us.level,
            us.description,
            u.id AS user_id,
            u.name,
            u.major,
            u.photo,
            u.batch_year,
            s.id AS skill_id,
            s.name AS skill_name,
            c.id AS category_id,
            c.name AS category_name,
            c.slug AS category_slug,
            c.icon AS category_icon,
            COALESCE(AVG(r.rating), 5.0) AS avg_rating,
            COUNT(r.id) AS review_count,
            (
                SELECT s2.name 
                FROM user_skills us2 
                JOIN skills s2 ON us2.skill_id = s2.id 
                WHERE us2.user_id = u.id AND us2.type = 'want' 
                LIMIT 1
            ) AS want_skill_name
        FROM user_skills us
        JOIN users u ON us.user_id = u.id
        JOIN skills s ON us.skill_id = s.id
        JOIN categories c ON s.category_id = c.id
        LEFT JOIN reviews r ON r.reviewee_id = u.id AND r.is_hidden = 0
        WHERE us.type = 'offer' AND u.is_active = 1
        GROUP BY us.id
        ORDER BY avg_rating DESC, u.id ASC
        LIMIT 6
    ";
    $offer_query = $conn->query($offer_sql);
    if ($offer_query) {
        while ($row = $offer_query->fetch_assoc()) {
            $featured_offers[] = $row;
        }
    }
}

// Fallback data penawar skill jika database masih kosong
if (empty($featured_offers)) {
    $featured_offers = [
        [
            'user_id' => 3,
            'name' => 'Jennifer Lovinda',
            'major' => 'Information Systems',
            'skill_name' => 'UI/UX Design (Figma)',
            'category_name' => 'Desain Grafis & UI/UX',
            'category_id' => 2,
            'level' => 'mahir',
            'avg_rating' => 4.9,
            'review_count' => 12,
            'want_skill_name' => 'Web Development (PHP)'
        ],
        [
            'user_id' => 2,
            'name' => 'Rangga Alrasya',
            'major' => 'Computer Science',
            'skill_name' => 'Web Dev (HTML, CSS, PHP)',
            'category_name' => 'Pemrograman & Web',
            'category_id' => 1,
            'level' => 'mahir',
            'avg_rating' => 4.8,
            'review_count' => 15,
            'want_skill_name' => 'UI/UX Design di Figma'
        ],
        [
            'user_id' => 7,
            'name' => 'Syifa Az-Zahra',
            'major' => 'English Literature',
            'skill_name' => 'Bahasa Inggris (TOEFL 620)',
            'category_name' => 'Bahasa Asing',
            'category_id' => 3,
            'level' => 'mahir',
            'avg_rating' => 5.0,
            'review_count' => 24,
            'want_skill_name' => 'Desain Poster & Ilustrasi'
        ],
        [
            'user_id' => 10,
            'name' => 'Dimas Pratama',
            'major' => 'Film & Animation',
            'skill_name' => 'Video Editing (Premiere Pro)',
            'category_name' => 'Fotografi & Videografi',
            'category_id' => 4,
            'level' => 'mahir',
            'avg_rating' => 4.9,
            'review_count' => 18,
            'want_skill_name' => 'Scripting Python Data'
        ]
    ];
}
?>

<!-- ==============================================================
     HERO SECTION DENGAN CINCIN KONSENTRIS & ANIMASI CHIP
     ============================================================== -->
<section class="hero-wrapper">
    <div class="container">
        <div class="hero-card">
            <!-- Cincin riak konsentris di background -->
            <div class="hero-rings"></div>
            
            <!-- Sparkles hiasan -->
            <div class="sparkle sparkle-1">✦</div>
            <div class="sparkle sparkle-2">✦</div>

            <!-- Tag pill atas -->
            <div class="hero-chip-top">
                <span>🎓 100% Gratis Antar Mahasiswa • Tanpa Biaya Kursus</span>
            </div>

            <!-- Judul utama & slogan -->
            <h1 class="hero-title">
                Tukar Skill. Dapat Skill.<br>
                Empower Your Future.
            </h1>
            <p class="hero-subtitle">
                Platform barter kemampuan peer-to-peer antar mahasiswa. Ajarkan keahlian yang kamu miliki, dan pelajari skill baru yang kamu inginkan tanpa keluar uang sepeserpun.
            </p>

            <!-- Search box pencarian cepat -->
            <form action="discover.php" method="GET" class="hero-search-box">
                <input type="text" name="q" placeholder="Cari skill apa yang ingin kamu pelajari hari ini..." required>
                <button type="submit" class="btn btn-dark btn-sm">
                    Cari Skill <span class="btn-arrow">↗</span>
                </button>
            </form>

            <!-- Showcase tengah: ilustrasi mahasiswa & kartu melayang -->
            <div class="hero-showcase">
                <!-- Floating card kiri (Rating kepuasan) -->
                <div class="float-card-left">
                    <div class="rating-number">4.9</div>
                    <div class="stars" style="color: var(--star); display: flex; gap: 3px; justify-content: center; margin: 4px 0;">
                        <?= icon('star', '', 15) ?><?= icon('star', '', 15) ?><?= icon('star', '', 15) ?><?= icon('star', '', 15) ?><?= icon('star', '', 15) ?>
                    </div>
                    <p>Tingkat kepuasan mahasiswa dari 200+ sesi pertukaran skill yang sukses.</p>
                </div>

                <!-- Gambar ilustrasi mahasiswa lokal kelas enterprise -->
                <img src="assets/img/hero-student.svg" 
                     alt="Mahasiswa TukarSkill" 
                     class="hero-student-img" 
                     style="object-fit: contain; height: 350px;">

                <!-- Floating card kanan (Komunitas mahasiswa) -->
                <div class="float-card-right">
                    <div class="avatar-stack">
                        <div class="mini-avatar" style="background:#2563eb">R</div>
                        <div class="mini-avatar" style="background:#7c3aed">J</div>
                        <div class="mini-avatar" style="background:#059669">L</div>
                        <div class="mini-avatar" style="background:#ea580c">S</div>
                    </div>
                    <div class="stat-title" style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <?= icon('users', '', 20) ?> 500+
                    </div>
                    <p>Mahasiswa aktif saling barter ilmu dari berbagai jurusan.</p>
                </div>

                <!-- Chip animasi barter yang berubah kata tiap 3 detik -->
                <div class="swap-chip-interactive">
                    <span id="hero-swap-text">UI/UX Design <?= icon('arrow-swap', '', 14) ?> Web Dev PHP</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================
     SECTION: SDG & PLATFORM PILLARS (Sesuai Referensi Pengguna)
     Plain border-radius container square dengan info berbobot
     ============================================================== -->
<section style="padding: 10px 0 50px;">
    <div class="container">
        <div style="background: var(--white); border-radius: var(--radius-lg); padding: 32px 36px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm);">
            <div style="text-align: center; max-width: 720px; margin: 0 auto 28px;">
                <span class="section-tag" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 6px;">
                    <?= icon('award', '', 14) ?> Global Goals Alignment
                </span>
                <h3 style="font-size: 24px; font-weight: 800; color: var(--ink); margin-top: 10px;">
                    Mewujudkan Pendidikan Berkualitas Tanpa Hambatan Biaya
                </h3>
                <p style="font-size: 14px; color: var(--gray-600); margin-top: 6px;">
                    TukarSkill berkomitmen mendukung Sustainable Development Goals (SDG) dengan memberdayakan mahasiswa saling bertukar ilmu dan keterampilan kerja nyata.
                </p>
            </div>

            <!-- The Plain Border-Radius Container dari referensi pengguna -->
            <div class="sdg-plain-container" style="box-shadow: none; border: 1px solid var(--gray-200); background: #f8fafc;">
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('graduation-cap', '', 26) ?></div>
                    <strong class="sdg-slot-title">SDG 4</strong>
                    <span class="sdg-slot-desc">Quality Education</span>
                </div>
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('target', '', 26) ?></div>
                    <strong class="sdg-slot-title">Target 4.4</strong>
                    <span class="sdg-slot-desc">Skill Ketenagakerjaan</span>
                </div>
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('scale', '', 26) ?></div>
                    <strong class="sdg-slot-title">SDG 10</strong>
                    <span class="sdg-slot-desc">Reduced Inequalities</span>
                </div>
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('trending-up', '', 26) ?></div>
                    <strong class="sdg-slot-title">SDG 8</strong>
                    <span class="sdg-slot-desc">Kesiapan Karir</span>
                </div>
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('repeat', '', 26) ?></div>
                    <strong class="sdg-slot-title">100% Gratis</strong>
                    <span class="sdg-slot-desc">Barter Tanpa Uang</span>
                </div>
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('shield-check', '', 26) ?></div>
                    <strong class="sdg-slot-title">Verifikasi</strong>
                    <span class="sdg-slot-desc">Sertifikat Resmi</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================
     SECTION: KATEGORI SKILL
     ============================================================== -->
<section class="section" id="kategori">
    <div class="container">
        <div class="section-head">
            <span class="section-tag">Katalog Keahlian</span>
            <h2 class="section-title">Eksplorasi Kategori Skill Pilihan</h2>
            <p class="section-desc">Pilih rumpun keahlian yang ingin kamu pelajari atau tawarkan kepada teman mahasiswa lainnya.</p>
        </div>

        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="discover.php?category=<?= (int)$cat['id'] ?>" class="category-card">
                    <div class="category-icon" style="color: var(--blue-600);">
                        <?= category_icon_svg($cat['slug'] ?? $cat['name'], 30) ?>
                    </div>
                    <div class="category-name"><?= e($cat['name']) ?></div>
                    <div class="category-count"><?= (int)($cat['skill_count'] ?? 0) ?> Skill Tersedia</div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================================================
     SECTION: BENTO PROBLEM & SOLUTION (Lime & Mint Style)
     ============================================================== -->
<section class="section" id="cara-kerja">
    <div class="container">
        <div class="section-head">
            <span class="section-tag">Latar Belakang & Solusi</span>
            <h2 class="section-title">Kenapa TukarSkill Berbeda?</h2>
            <p class="section-desc">Mengubah setiap mahasiswa menjadi guru sekaligus murid, mendukung akses pendidikan setara (SDG 4 & SDG 10).</p>
        </div>

        <div class="bento-grid">
            <!-- Kartu Masalah (Aksen Lime) -->
            <div class="bento-card bento-problem">
                <div>
                    <div class="bento-header">
                        <h3>Tantangan Mahasiswa <?= icon('alert-triangle', '', 20) ?></h3>
                        <div class="bento-arrow-btn">✕</div>
                    </div>
                    <p>
                        Banyak mahasiswa ingin mendalami keahlian praktis seperti coding, bahasa asing, atau desain tetapi terbentur biaya kursus yang mahal. Di sisi lain, skill yang sudah mereka kuasai tidak termanfaatkan dan sulit mencari partner belajar yang cocok secara manual via media sosial.
                    </p>
                </div>
                <div class="bento-stats">
                    <div class="bento-stat-box">
                        <div class="num">82%</div>
                        <div class="lbl">Terkendala Biaya Kursus</div>
                    </div>
                    <div class="bento-stat-box">
                        <div class="num">0</div>
                        <div class="lbl">Platform Barter Resmi</div>
                    </div>
                </div>
            </div>

            <!-- Kartu Solusi (Aksen Mint) -->
            <div class="bento-card bento-solution">
                <div>
                    <div class="bento-header">
                        <h3>Solusi TukarSkill <?= icon('sparkles', '', 20) ?></h3>
                        <div class="bento-arrow-btn">✓</div>
                    </div>
                    <p>
                        TukarSkill hadir dengan ekosistem <strong>Student ↔ Student</strong>. Kamu menukar skill yang kamu bisa untuk mendapatkan skill baru tanpa uang. Dilengkapi sistem kecocokan otomatis <em>SkillMatch</em>, sistem reputasi bintang 1-5, dan penghargaan poin gamifikasi.
                    </p>
                </div>
                <div class="bento-stats">
                    <div class="bento-stat-box">
                        <div class="num">100%</div>
                        <div class="lbl">Bebas Biaya Kursus</div>
                    </div>
                    <div class="bento-stat-box">
                        <div class="num">SDG 4</div>
                        <div class="lbl">Quality Education</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================
     SECTION: SKILLMATCH CONSTELLATION PREVIEW
     ============================================================== -->
<section class="section">
    <div class="container">
        <div class="constellation-wrapper">
            <span class="section-tag">Algoritma Cerdas</span>
            <h2 class="section-title" style="margin-top: 10px;">Fitur Unggulan: SkillMatch™</h2>
            <p class="section-desc" style="max-width: 600px; margin: 10px auto 0;">
                Sistem rekomendasi otomatis yang mencocokkan apa yang kamu bisa dengan apa yang partner butuhkan, diselaraskan dengan jadwal kuliahmu.
            </p>

            <div class="constellation-graphic">
                <!-- Node Tengah: User yang login -->
                <div class="center-user-node">
                    <div style="font-size: 18px; color: var(--blue-600);"><?= icon('graduation-cap', '', 22) ?></div>
                    <span>Kamu</span>
                </div>

                <!-- Satelit Orbit: Partner yang cocok -->
                <div class="satellite-node node-1">
                    <span>Jennifer L.</span>
                    <span class="node-match-score">95% Match</span>
                </div>

                <div class="satellite-node node-2">
                    <span>Rangga A.</span>
                    <span class="node-match-score">90% Match</span>
                </div>

                <div class="satellite-node node-3">
                    <span>Syifa A.</span>
                    <span class="node-match-score">85% Match</span>
                </div>

                <div class="satellite-node node-4">
                    <span>Dimas P.</span>
                    <span class="node-match-score">80% Match</span>
                </div>
            </div>

            <div style="margin-top: 24px;">
                <?php if (is_logged_in()): ?>
                    <a href="skillmatch.php" class="btn btn-primary">
                        Buka Rekomendasi Partner Saya <span class="btn-arrow">↗</span>
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary">
                        Daftar untuk Coba SkillMatch <span class="btn-arrow">↗</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================
     SECTION: SKILL PALING DICARI (Cards Mahasiswa)
     ============================================================== -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="section-tag">Jelajahi Pengajar</span>
            <h2 class="section-title">Skill Paling Dicari Minggu Ini</h2>
            <p class="section-desc">Temukan mahasiswa yang siap mengajarkan skill impianmu sekarang juga.</p>
        </div>

        <!-- Filter Pills Kategori -->
        <div class="filter-pills">
            <button class="filter-pill active" data-category="all">Semua Skill</button>
            <button class="filter-pill" data-category="1">Pemrograman</button>
            <button class="filter-pill" data-category="2">Desain & UI/UX</button>
            <button class="filter-pill" data-category="3">Bahasa Asing</button>
            <button class="filter-pill" data-category="4">Multimedia</button>
        </div>

        <!-- Grid Kartu Penawar Skill -->
        <div class="skills-grid">
            <?php foreach ($featured_offers as $offer): 
                $cat_slug = strtolower($offer['category_slug'] ?? '');
                $cat_class = 'cat-default';
                if (strpos($cat_slug, 'tekno') !== false || strpos($cat_slug, 'program') !== false) $cat_class = 'cat-tech';
                elseif (strpos($cat_slug, 'desain') !== false || strpos($cat_slug, 'kreatif') !== false) $cat_class = 'cat-design';
                elseif (strpos($cat_slug, 'bahasa') !== false) $cat_class = 'cat-lang';
                elseif (strpos($cat_slug, 'bisnis') !== false || strpos($cat_slug, 'karir') !== false) $cat_class = 'cat-biz';
                elseif (strpos($cat_slug, 'akademik') !== false) $cat_class = 'cat-academic';
                elseif (strpos($cat_slug, 'media') !== false || strpos($cat_slug, 'audio') !== false) $cat_class = 'cat-media';
            ?>
                <div class="skill-card" data-category="<?= (int)$offer['category_id'] ?>">
                    <!-- Baris Atas: Kategori Tag & Rating Bintang -->
                    <div class="card-meta-bar">
                        <span class="cat-pill <?= $cat_class ?>">
                            <?= category_icon_svg($offer['category_slug'] ?? $offer['category_name'], 13) ?> <?= e($offer['category_name']) ?>
                        </span>
                        <div class="card-rating-pill" title="Rating Ulasan Partner">
                            <span class="star-icon"><?= icon('star', '', 12) ?></span>
                            <span><?= number_format((float)$offer['avg_rating'], 1) ?></span>
                            <?php if (!empty($offer['review_count'])): ?>
                                <span class="count">(<?= (int)$offer['review_count'] ?>)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Judul Skill yang Diajarkan -->
                    <h3 class="skill-main-title">
                        <?= e($offer['skill_name']) ?>
                    </h3>

                    <!-- Profil Mahasiswa Pengajar -->
                    <div class="mentor-row">
                        <?= render_avatar($offer['photo'] ?? '', $offer['name'], 38) ?>
                        <div class="mentor-text">
                            <span class="mentor-name"><?= e($offer['name']) ?></span>
                            <span class="mentor-badge-sub">
                                <?= e($offer['major']) ?> • <strong class="level-tag"><?= ucfirst(e($offer['level'])) ?></strong>
                            </span>
                        </div>
                    </div>

                    <!-- Widget Pertukaran Barter Dua Arah (The Soul of TukarSkill) -->
                    <div class="barter-flow-card">
                        <div class="barter-direction">
                            <span class="barter-role-tag"><?= icon('lightbulb', '', 11) ?> Diajarkan</span>
                            <span class="barter-skill-highlight"><?= e($offer['skill_name']) ?></span>
                        </div>
                        
                        <div class="barter-connector">
                            <div class="swap-circle" title="Tukar Timbal Balik"><?= icon('arrow-swap', '', 11) ?></div>
                        </div>

                        <div class="barter-direction">
                            <span class="barter-role-tag want-role"><?= icon('book-open', '', 11) ?> Ingin Belajar</span>
                            <span class="barter-skill-highlight want-highlight">
                                <?= e($offer['want_skill_name'] ?? 'Terbuka barter skill apa saja') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Footer Aksi -->
                    <div class="card-action-footer">
                        <span class="swap-method-badge">
                            <span class="pulse-dot"></span> 1-on-1 Peer
                        </span>
                        <a href="user.php?id=<?= (int)$offer['user_id'] ?>" class="btn-swap-cta">
                            <span>Ajak Tukar</span>
                            <span class="cta-arrow"><?= icon('arrow-up-right', '', 13) ?></span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="discover.php" class="btn btn-outline">
                Lihat Semua Partner & Skill (<?= count($featured_offers) ?>+ Mahasiswa) →
            </a>
        </div>
    </div>
</section>

<!-- ==============================================================
     SECTION: CERITA SUKSES MAHASISWA (Testimoni)
     ============================================================== -->
<section class="section" style="background: var(--white); border-radius: var(--radius-xl); margin: 40px 0;">
    <div class="container" style="padding: 60px 24px;">
        <div class="section-head">
            <span class="section-tag">Testimoni Komunitas</span>
            <h2 class="section-title">Cerita dari Mereka yang Bertukar Skill</h2>
            <p class="section-desc">Pengalaman nyata mahasiswa yang mengasah portofolio dan koneksi baru lewat TukarSkill.</p>
        </div>

        <div class="stories-grid">
            <div class="story-card">
                <p class="story-quote">
                    "Awalnya ragu ada yang mau ngajarin Figma gratis. Ternyata dapet partner Kak Jennifer yang telaten banget! Gantinya aku ajarin dasar PHP buat tugas kuliahnya. Barter ilmu yang saling menguntungkan!"
                </p>
                <div class="story-author">
                    <?= render_avatar('rangga.svg', 'Rangga Alrasya', 42) ?>
                    <div class="story-details">
                        <h4>Rangga Alrasya</h4>
                        <span>Teknik Informatika (2025)</span>
                    </div>
                </div>
            </div>

            <div class="story-card">
                <p class="story-quote">
                    "Nilai TOEFL-ku naik dari 480 jadi 560 setelah latihan speaking 4 kali bareng Syifa! Dan asiknya aku cuma bayar pake sesi mentoring desain Canva buat dia. Hemat jutaan dibanding les privat."
                </p>
                <div class="story-author">
                    <?= render_avatar('lucky.svg', 'Lucky Fadlan', 42) ?>
                    <div class="story-details">
                        <h4>Lucky Fadlan</h4>
                        <span>Computer Science (2025)</span>
                    </div>
                </div>
            </div>

            <div class="story-card">
                <p class="story-quote">
                    "Selain dapet ilmu video editing dari Dimas, aku juga dapet temen networking baru lintas jurusan. Sistem poinnya juga bikin semangat buat terus berkontribusi ngajarin anak-anak lain."
                </p>
                <div class="story-author">
                    <?= render_avatar('', 'Amanda Putri', 42) ?>
                    <div class="story-details">
                        <h4>Amanda Putri</h4>
                        <span>Marketing Communication (2025)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================
     SECTION: FAQ (Pertanyaan yang Sering Diajukan)
     ============================================================== -->
<section class="section" id="faq">
    <div class="container">
        <div class="section-head">
            <span class="section-tag">Pusat Bantuan</span>
            <h2 class="section-title">Pertanyaan yang Sering Diajukan</h2>
            <p class="section-desc">Semua hal yang perlu kamu ketahui seputar sistem barter ilmu di TukarSkill.</p>
        </div>

        <div class="faq-list">
            <div class="faq-item active">
                <button class="faq-question">
                    <span>Apakah platform TukarSkill benar-benar 100% gratis?</span>
                    <div class="faq-icon"><?= icon('plus', '', 14) ?></div>
                </button>
                <div class="faq-answer">
                    Ya, sepenuhnya gratis! Tidak ada biaya langganan maupun komisi uang tunai. Transaksi dilakukan murni menggunakan pertukaran kemampuan (Swap Skill) atau menggunakan saldo Skill Points yang didapat dari kontribusi belajar-mengajar.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">
                    <span>Bagaimana jika kemampuan saya masih di tingkat pemula?</span>
                    <div class="faq-icon"><?= icon('plus', '', 14) ?></div>
                </button>
                <div class="faq-answer">
                    Tidak masalah sama sekali! Banyak mahasiswa yang baru memulai dan justru mencari teman belajar di tingkat dasar. Kamu bisa mencantumkan level skill kamu (Pemula, Menengah, Mahir) secara transparan di profil.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">
                    <span>Bagaimana jika saya tidak punya skill untuk ditukar balik saat ini?</span>
                    <div class="faq-icon"><?= icon('plus', '', 14) ?></div>
                </button>
                <div class="faq-answer">
                    Kamu bisa menggunakan fitur <strong>Request via Poin</strong> (15 Poin). Setiap mahasiswa baru otomatis mendapatkan +20 Poin selamat datang saat mendaftar, sehingga kamu bisa langsung mengajukan sesi belajar pertama tanpa harus mengajar balik.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">
                    <span>Di mana dan bagaimana sesi pertukaran skill diadakan?</span>
                    <div class="faq-icon"><?= icon('plus', '', 14) ?></div>
                </button>
                <div class="faq-answer">
                    Kalian bebas menentukan mode: <strong>Online</strong> (Google Meet / Zoom) atau <strong>Offline</strong> (perpustakaan kampus, kafe terdekat). Kontak WhatsApp partner hanya akan dibuka setelah request disetujui untuk menjaga privasi.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================================
     CTA PENUTUP DENGAN CINCIN KONSENTRIS
     ============================================================== -->
<section class="container">
    <div class="cta-box">
        <div class="hero-rings"></div>
        <h2>Siap Menukar Skill Pertamamu?</h2>
        <p>Bergabunglah bersama ratusan mahasiswa lainnya. Raih skill baru impianmu dan jadilah bagian dari revolusi peer-learning kampus.</p>
        <div class="cta-actions">
            <?php if (is_logged_in()): ?>
                <a href="discover.php" class="btn btn-dark">
                    Cari Partner Sekarang <span class="btn-arrow">↗</span>
                </a>
            <?php else: ?>
                <a href="register.php" class="btn btn-dark">
                    Daftar Akun Gratis Sekarang <span class="btn-arrow">↗</span>
                </a>
                <a href="login.php" class="btn btn-white">
                    Masuk ke Akun
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
