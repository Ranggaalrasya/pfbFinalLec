<?php
// ==============================================================
// admin/skills.php
// kelola master katalog skill mahasiswa TukarSkill
// tambah skill baru, filter per kategori & hapus skill
// ==============================================================

$page_title = "Katalog Skill";
require_once __DIR__ . '/header.php';

// --------------------------------------------------------------
// PROSES AKSI CRUD SKILL
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_db_connected()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // TAMBAH SKILL BARU
    if ($action === 'add') {
        $category_id = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($category_id <= 0 || empty($name)) {
            set_flash('danger', 'Kategori dan nama skill wajib diisi.');
        } else {
            $stmt = $conn->prepare("INSERT INTO skills (category_id, name, description, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iss", $category_id, $name, $description);
            if ($stmt->execute()) {
                set_flash('success', "Skill '$name' berhasil ditambahkan ke katalog!");
            } else {
                set_flash('danger', 'Gagal menambahkan skill. Pastikan nama tidak duplikat.');
            }
            $stmt->close();
        }
    }

    // HAPUS SKILL
    elseif ($action === 'delete') {
        $skill_id = (int)($_POST['skill_id'] ?? 0);
        if ($skill_id > 0) {
            // Cek apakah skill ini sedang digunakan dalam transaksi pertukaran
            $chk = $conn->query("SELECT COUNT(*) AS total FROM exchanges WHERE requested_skill_id = $skill_id OR offered_skill_id = $skill_id");
            $row = $chk ? $chk->fetch_assoc() : ['total' => 0];

            if ($row['total'] > 0) {
                set_flash('danger', "Tidak bisa menghapus skill ini karena tercatat di {$row['total']} transaksi pertukaran mahasiswa.");
            } else {
                // Hapus juga asosiasi di user_skills
                $conn->query("DELETE FROM user_skills WHERE skill_id = $skill_id");
                $conn->query("DELETE FROM skills WHERE id = $skill_id");
                set_flash('success', "Skill berhasil dihapus dari katalog.");
            }
        }
    }

    header('Location: skills.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// --------------------------------------------------------------
// AMBIL KATEGORI BUAT SELECT & FILTER
// --------------------------------------------------------------
$categories = [];
if (is_db_connected()) {
    $c_res = $conn->query("SELECT id, name, icon FROM categories ORDER BY name ASC");
    if ($c_res) {
        while ($row = $c_res->fetch_assoc()) $categories[] = $row;
    }
}

// --------------------------------------------------------------
// QUERY DAFTAR SKILL DENGAN SEARCH & FILTER KATEGORI
// --------------------------------------------------------------
$q = trim($_GET['q'] ?? '');
$cat_filter = (int)($_GET['category'] ?? 0);

$skills = [];
if (is_db_connected()) {
    $where_clauses = ["1=1"];
    $types = "";
    $params = [];

    if (!empty($q)) {
        $where_clauses[] = "(s.name LIKE ? OR s.description LIKE ?)";
        $search_wild = "%$q%";
        $types .= "ss";
        $params[] = $search_wild;
        $params[] = $search_wild;
    }

    if ($cat_filter > 0) {
        $where_clauses[] = "s.category_id = ?";
        $types .= "i";
        $params[] = $cat_filter;
    }

    $where_sql = implode(" AND ", $where_clauses);
    $sql = "
        SELECT 
            s.*,
            c.name AS category_name, c.icon AS category_icon,
            (SELECT COUNT(*) FROM user_skills us WHERE us.skill_id = s.id AND us.type = 'offer') AS offers_count,
            (SELECT COUNT(*) FROM user_skills us WHERE us.skill_id = s.id AND us.type = 'want') AS wants_count
        FROM skills s
        JOIN categories c ON s.category_id = c.id
        WHERE $where_sql
        ORDER BY s.id DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $skills[] = $row;
    }
    $stmt->close();
}
?>

<div style="max-width: 1280px; margin: 0 auto;">

    <!-- HEADER TITLE -->
    <div style="margin-bottom: 28px;">
        <h1 style="font-size: 26px; font-weight: 800; color: #0f172a;">
            Katalog Master Skill (<?= count($skills) ?>)
        </h1>
        <p style="font-size: 13px; color: #64748b; margin-top: 4px;">
            Koleksi lengkap kemampuan yang dapat ditawarkan dan dipelajari mahasiswa di platform TukarSkill.
        </p>
    </div>

    <!-- DUA KOLOM: FORM TAMBAH SKILL & TABEL KATALOG -->
    <div style="display: grid; grid-template-columns: 360px 1fr; gap: 28px; align-items: start;">
        
        <!-- FORM TAMBAH SKILL -->
        <div style="background: white; border-radius: 12px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <?= icon('plus', '', '16px') ?> Tambah Skill Baru
            </h3>

            <form action="skills.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Kategori Bidang
                    </label>
                    <select name="category_id" required style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; background: white;">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e($cat['icon']) ?> <?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Nama Skill
                    </label>
                    <input type="text" name="name" required placeholder="Contoh: Flutter Mobile Dev" 
                           style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Deskripsi Penjelasan
                    </label>
                    <textarea name="description" rows="3" placeholder="Jelaskan ruang lingkup materi yang dipelajari..." 
                              style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; resize: vertical;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    Simpan ke Katalog
                </button>
            </form>
        </div>

        <!-- DAFTAR KATALOG & FILTER -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
            
            <!-- FILTER BAR -->
            <div style="background: white; border-radius: 12px; padding: 16px 20px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
                <form action="skills.php" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap;">
                    
                    <div style="flex: 2; min-width: 180px;">
                        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama skill..." 
                               style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; outline: none;">
                    </div>

                    <div style="flex: 1; min-width: 150px;">
                        <select name="category" style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; background: white;">
                            <option value="0">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($cat_filter === (int)$cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['icon']) ?> <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm" style="padding: 8px 16px;">
                        Filter
                    </button>
                    <?php if (!empty($q) || $cat_filter > 0): ?>
                        <a href="skills.php" class="btn btn-outline btn-sm" style="padding: 8px 12px;">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- TABEL LIST SKILL -->
            <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm); overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 12px; text-transform: uppercase;">
                                <th style="padding: 12px 16px; width: 50px;">ID</th>
                                <th style="padding: 12px 16px;">Nama Skill</th>
                                <th style="padding: 12px 16px;">Kategori</th>
                                <th style="padding: 12px 16px; text-align: center;">Penawar</th>
                                <th style="padding: 12px 16px; text-align: center;">Peminat</th>
                                <th style="padding: 12px 16px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($skills)): ?>
                                <tr>
                                    <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">
                                        Tidak ada skill yang cocok dengan pencarian.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($skills as $sk): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 12px 16px; color: #94a3b8; font-weight: 700;">
                                            #<?= $sk['id'] ?>
                                        </td>
                                        <td style="padding: 12px 16px;">
                                            <strong style="color: #0f172a; display: block; font-size: 14px;">
                                                <?= e($sk['name']) ?>
                                            </strong>
                                            <span style="font-size: 11px; color: #64748b; line-height: 1.4;">
                                                <?= e($sk['description'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 16px; white-space: nowrap;">
                                            <span style="font-size: 12px; font-weight: 600; color: #334155; display: inline-flex; align-items: center; gap: 6px;">
                                                <span style="display: inline-flex; color: var(--blue-600);"><?= category_icon_svg($sk['category_icon'], '14px') ?></span>
                                                <?= e($sk['category_name']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 16px; text-align: center;">
                                            <span style="background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px;">
                                                <?= (int)$sk['offers_count'] ?> siap ajar
                                            </span>
                                        </td>
                                        <td style="padding: 12px 16px; text-align: center;">
                                            <span style="background: #e0f2fe; color: #0284c7; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px;">
                                                <?= (int)$sk['wants_count'] ?> butuh
                                            </span>
                                        </td>
                                        <td style="padding: 12px 16px; text-align: center;">
                                            <form action="skills.php" method="POST" onsubmit="return confirm('Hapus skill <?= addslashes(e($sk['name'])) ?> dari katalog?');" style="margin: 0; display: inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="skill_id" value="<?= $sk['id'] ?>">
                                                <button type="submit" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid #fecaca; background: #fee2e2; color: #991b1b; cursor: pointer;">
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
