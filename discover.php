<?php
// ==============================================================
// discover.php
// halaman eksplorasi & pencarian teman barter skill antar mahasiswa
// ==============================================================

$page_title = "Cari Partner Skill";
require_once __DIR__ . '/includes/header.php';

// Tangkap parameter filter & pencarian dari URL ($_GET)
$q = trim($_GET['q'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$level_filter = trim($_GET['level'] ?? '');
$day_filter = trim($_GET['day'] ?? '');

// Ambil list semua kategori untuk opsi filter pill
$all_categories = [];
if (is_db_connected()) {
    $c_res = $conn->query("SELECT id, name, icon FROM categories ORDER BY id ASC");
    if ($c_res) {
        while ($row = $c_res->fetch_assoc()) {
            $all_categories[] = $row;
        }
    }
}

// Susun query dinamis berdasarkan filter yang dipilih
$results = [];
if (is_db_connected()) {
    $sql = "
        SELECT 
            u.id AS user_id,
            u.name,
            u.major,
            u.batch_year,
            u.photo,
            us.id AS user_skill_id,
            us.level,
            us.description AS skill_desc,
            s.id AS skill_id,
            s.name AS skill_name,
            c.id AS category_id,
            c.name AS category_name,
            c.slug AS category_slug,
            c.icon AS category_icon,
            COALESCE(AVG(r.rating), 5.0) AS avg_rating,
            COUNT(DISTINCT r.id) AS review_count,
            (
                SELECT s_want.name 
                FROM user_skills us_w 
                JOIN skills s_want ON us_w.skill_id = s_want.id 
                WHERE us_w.user_id = u.id AND us_w.type = 'want' 
                LIMIT 1
            ) AS wanted_skill_name
        FROM user_skills us
        JOIN users u ON us.user_id = u.id
        JOIN skills s ON us.skill_id = s.id
        JOIN categories c ON s.category_id = c.id
        LEFT JOIN reviews r ON r.reviewee_id = u.id AND r.is_hidden = 0
        LEFT JOIN availabilities a ON a.user_id = u.id
        WHERE us.type = 'offer' AND u.is_active = 1
    ";

    $params = [];
    $types = '';

    // Filter kata kunci pencarian (nama mahasiswa, nama skill, atau bio)
    if (!empty($q)) {
        $sql .= " AND (s.name LIKE ? OR u.name LIKE ? OR u.bio LIKE ?)";
        $search_like = "%$q%";
        $params[] = $search_like;
        $params[] = $search_like;
        $params[] = $search_like;
        $types .= 'sss';
    }

    // Filter kategori
    if ($category_filter > 0) {
        $sql .= " AND c.id = ?";
        $params[] = $category_filter;
        $types .= 'i';
    }

    // Filter level penguasaan
    if (in_array($level_filter, ['pemula', 'menengah', 'mahir'])) {
        $sql .= " AND us.level = ?";
        $params[] = $level_filter;
        $types .= 's';
    }

    // Filter hari luang
    if (in_array($day_filter, ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'])) {
        $sql .= " AND a.day = ?";
        $params[] = $day_filter;
        $types .= 's';
    }

    $sql .= " GROUP BY us.id ORDER BY avg_rating DESC, u.id ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $results[] = $row;
            }
        }
        $stmt->close();
    }
}
?>

<div class="container" style="padding: 40px 16px 80px;">

    <!-- Header & Search Bar -->
    <div style="text-align: center; max-width: 720px; margin: 0 auto 36px;">
        <span class="section-tag">Eksplorasi Komunitas</span>
        <h1 style="font-size: 32px; font-weight: 800; color: var(--ink); margin-top: 10px;">
            Cari Partner Barter Skill 🤝
        </h1>
        <p style="font-size: 15px; color: var(--gray-600); margin-top: 8px;">
            Temukan mahasiswa dengan keahlian yang kamu butuhkan, dan ajukan sesi tukar skill sekarang.
        </p>

        <!-- Form Pencarian Search Box -->
        <form action="discover.php" method="GET" style="margin-top: 24px; display: flex; gap: 8px; background: var(--white); padding: 8px; border-radius: var(--radius-pill); box-shadow: var(--shadow-md); border: 1px solid var(--gray-200);">
            <?php if ($category_filter > 0): ?>
                <input type="hidden" name="category" value="<?= $category_filter ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Ketik skill (contoh: UI/UX, PHP, TOEFL) atau nama mahasiswa..." 
                   style="flex: 1; border: none; outline: none; padding: 10px 18px; font-family: inherit; font-size: 14px; background: transparent; box-shadow: none;">
            <button type="submit" class="btn btn-dark btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <?= icon('search', '', 14) ?> <span>Cari Partner</span> <span class="btn-arrow"><?= icon('arrow-up-right', '', 12) ?></span>
            </button>
        </form>
    </div>

    <!-- FILTER BAR: Pills Kategori + Dropdown Level & Hari -->
    <div style="background: var(--white); border-radius: var(--radius-xl); padding: 24px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); margin-bottom: 36px;">
        
        <!-- Filter Kategori (Pill Chips) -->
        <div style="margin-bottom: 20px;">
            <span style="font-size: 12px; font-weight: 700; color: var(--gray-600); text-transform: uppercase; display: block; margin-bottom: 10px;">Kategori Keahlian:</span>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <a href="discover.php?<?= http_build_query(array_merge($_GET, ['category' => 0])) ?>" 
                   class="filter-pill <?= ($category_filter === 0) ? 'active' : '' ?>">
                    Semua Kategori
                </a>
                <?php foreach ($all_categories as $c): ?>
                    <a href="discover.php?<?= http_build_query(array_merge($_GET, ['category' => $c['id']])) ?>" 
                       class="filter-pill <?= ($category_filter === (int)$c['id']) ? 'active' : '' ?>" style="display: inline-flex; align-items: center; gap: 6px;">
                        <?= category_icon_svg($c['slug'] ?? $c['name'], 14) ?> <span><?= e($c['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filter Tambahan: Level & Hari Luang -->
        <form action="discover.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center; padding-top: 16px; border-top: 1px solid var(--gray-100);">
            <?php if (!empty($q)): ?>
                <input type="hidden" name="q" value="<?= e($q) ?>">
            <?php endif; ?>
            <?php if ($category_filter > 0): ?>
                <input type="hidden" name="category" value="<?= $category_filter ?>">
            <?php endif; ?>

            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 13px; font-weight: 600; color: var(--gray-600);">Level:</span>
                <select name="level" onchange="this.form.submit()" style="padding: 6px 12px; border-radius: var(--radius-pill); border: 1px solid var(--gray-200); font-family: inherit; font-size: 13px; background: var(--white); outline: none;">
                    <option value="">Semua Level</option>
                    <option value="pemula" <?= ($level_filter === 'pemula') ? 'selected' : '' ?>>Pemula</option>
                    <option value="menengah" <?= ($level_filter === 'menengah') ? 'selected' : '' ?>>Menengah</option>
                    <option value="mahir" <?= ($level_filter === 'mahir') ? 'selected' : '' ?>>Mahir</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 13px; font-weight: 600; color: var(--gray-600);">Hari Ketersediaan:</span>
                <select name="day" onchange="this.form.submit()" style="padding: 6px 12px; border-radius: var(--radius-pill); border: 1px solid var(--gray-200); font-family: inherit; font-size: 13px; background: var(--white); outline: none;">
                    <option value="">Semua Hari</option>
                    <option value="senin" <?= ($day_filter === 'senin') ? 'selected' : '' ?>>Senin</option>
                    <option value="selasa" <?= ($day_filter === 'selasa') ? 'selected' : '' ?>>Selasa</option>
                    <option value="rabu" <?= ($day_filter === 'rabu') ? 'selected' : '' ?>>Rabu</option>
                    <option value="kamis" <?= ($day_filter === 'kamis') ? 'selected' : '' ?>>Kamis</option>
                    <option value="jumat" <?= ($day_filter === 'jumat') ? 'selected' : '' ?>>Jumat</option>
                    <option value="sabtu" <?= ($day_filter === 'sabtu') ? 'selected' : '' ?>>Sabtu</option>
                    <option value="minggu" <?= ($day_filter === 'minggu') ? 'selected' : '' ?>>Minggu</option>
                </select>
            </div>

            <?php if (!empty($q) || $category_filter > 0 || !empty($level_filter) || !empty($day_filter)): ?>
                <a href="discover.php" style="font-size: 13px; color: var(--danger); font-weight: 600; text-decoration: underline;">
                    Reset Filter ✕
                </a>
            <?php endif; ?>

            <div style="margin-left: auto; font-size: 13px; color: var(--gray-400); font-weight: 600;">
                Ditemukan: <?= count($results) ?> Penawaran Skill
            </div>
        </form>

    </div>

    <!-- HASIL GRID PENAWARAN SKILL (Kartu Modern ala LearnNix) -->
    <?php if (empty($results)): ?>
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 60px 20px; text-align: center; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <div style="font-size: 48px; margin-bottom: 12px;">🔍</div>
            <h3 style="font-size: 20px; font-weight: 800; color: var(--ink);">Tidak ditemukan penawaran skill yang cocok</h3>
            <p style="font-size: 14px; color: var(--gray-600); max-width: 460px; margin: 8px auto 20px;">
                Coba gunakan kata kunci yang lebih umum atau reset filter kategori & level.
            </p>
            <a href="discover.php" class="btn btn-primary btn-sm">Tampilkan Semua Skill</a>
        </div>
    <?php else: ?>
        <div class="skills-grid">
            <?php foreach ($results as $item): 
                $cat_slug = strtolower($item['category_slug'] ?? '');
                $cat_class = 'cat-default';
                if (strpos($cat_slug, 'tekno') !== false || strpos($cat_slug, 'program') !== false) $cat_class = 'cat-tech';
                elseif (strpos($cat_slug, 'desain') !== false || strpos($cat_slug, 'kreatif') !== false) $cat_class = 'cat-design';
                elseif (strpos($cat_slug, 'bahasa') !== false) $cat_class = 'cat-lang';
                elseif (strpos($cat_slug, 'bisnis') !== false || strpos($cat_slug, 'karir') !== false) $cat_class = 'cat-biz';
                elseif (strpos($cat_slug, 'akademik') !== false) $cat_class = 'cat-academic';
                elseif (strpos($cat_slug, 'media') !== false || strpos($cat_slug, 'audio') !== false) $cat_class = 'cat-media';
            ?>
                <div class="skill-card">
                    <!-- Baris Atas: Kategori Tag & Rating Bintang -->
                    <div class="card-meta-bar">
                        <span class="cat-pill <?= $cat_class ?>">
                            <?= category_icon_svg($item['category_slug'] ?? $item['category_name'], 13) ?> <?= e($item['category_name']) ?>
                        </span>
                        <div class="card-rating-pill" title="Rating Ulasan Partner">
                            <span class="star-icon"><?= icon('star', '', 12) ?></span>
                            <span><?= number_format((float)$item['avg_rating'], 1) ?></span>
                            <span class="count">(<?= (int)$item['review_count'] ?>)</span>
                        </div>
                    </div>

                    <!-- Judul Skill yang Diajarkan -->
                    <h3 class="skill-main-title">
                        <?= e($item['skill_name']) ?>
                    </h3>

                    <!-- Profil Mahasiswa Pengajar -->
                    <div class="mentor-row">
                        <?= render_avatar($item['photo'] ?? '', $item['name'], 38) ?>
                        <div class="mentor-text">
                            <span class="mentor-name"><?= e($item['name']) ?></span>
                            <span class="mentor-badge-sub">
                                <?= e($item['major']) ?> • <strong class="level-tag"><?= ucfirst(e($item['level'])) ?></strong>
                            </span>
                        </div>
                    </div>

                    <!-- Widget Pertukaran Barter Dua Arah (The Soul of TukarSkill) -->
                    <div class="barter-flow-card">
                        <div class="barter-direction">
                            <span class="barter-role-tag"><?= icon('lightbulb', '', 11) ?> Diajarkan</span>
                            <span class="barter-skill-highlight"><?= e($item['skill_name']) ?></span>
                        </div>
                        
                        <div class="barter-connector">
                            <div class="swap-circle" title="Tukar Timbal Balik"><?= icon('arrow-swap', '', 11) ?></div>
                        </div>

                        <div class="barter-direction">
                            <span class="barter-role-tag want-role"><?= icon('book-open', '', 11) ?> Ingin Belajar</span>
                            <span class="barter-skill-highlight want-highlight">
                                <?= !empty($item['wanted_skill_name']) ? e($item['wanted_skill_name']) : 'Terbuka barter skill apa saja' ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($item['skill_desc'])): ?>
                        <p class="skill-brief-desc">
                            "<?= e(mb_strimwidth($item['skill_desc'], 0, 80, '...')) ?>"
                        </p>
                    <?php endif; ?>

                    <!-- Footer Aksi -->
                    <div class="card-action-footer">
                        <span class="swap-method-badge">
                            <span class="pulse-dot"></span> 1-on-1 Peer
                        </span>
                        <a href="user.php?id=<?= (int)$item['user_id'] ?>" class="btn-swap-cta">
                            <span>Ajak Tukar</span>
                            <span class="cta-arrow"><?= icon('arrow-up-right', '', 13) ?></span>
                        </a>
                    </div>
                </div>
                            <span>Ajak Tukar</span>
                            <span class="cta-arrow">↗</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
