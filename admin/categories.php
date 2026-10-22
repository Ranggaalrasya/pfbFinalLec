<?php
// ==============================================================
// admin/categories.php
// kelola kategori bidang keahlian di TukarSkill
// tambah kategori baru, ubah nama/icon emoji, dan hapus kategori
// ==============================================================

$page_title = "Kelola Kategori Skill";
require_once __DIR__ . '/header.php';

// --------------------------------------------------------------
// PROSES AKSI CRUD KATEGORI
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_db_connected()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // TAMBAH KATEGORI BARU
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '💡');
        $description = trim($_POST['description'] ?? '');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        if (empty($name)) {
            set_flash('danger', 'Nama kategori tidak boleh kosong.');
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, icon, description, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $name, $slug, $icon, $description);
            if ($stmt->execute()) {
                set_flash('success', "Kategori '$name' berhasil ditambahkan!");
            } else {
                set_flash('danger', 'Gagal menambahkan kategori. Pastikan nama tidak duplikat.');
            }
            $stmt->close();
        }
    }

    // UPDATE KATEGORI
    elseif ($action === 'edit') {
        $id = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '💡');
        $description = trim($_POST['description'] ?? '');

        if ($id > 0 && !empty($name)) {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, icon = ?, description = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $icon, $description, $id);
            $stmt->execute();
            $stmt->close();
            set_flash('success', "Kategori berhasil diperbarui!");
        }
    }

    // HAPUS KATEGORI
    elseif ($action === 'delete') {
        $id = (int)($_POST['category_id'] ?? 0);
        if ($id > 0) {
            // Cek apakah ada skill yang masih menginduk ke kategori ini
            $chk = $conn->query("SELECT COUNT(*) AS total FROM skills WHERE category_id = $id");
            $row = $chk ? $chk->fetch_assoc() : ['total' => 0];

            if ($row['total'] > 0) {
                set_flash('danger', "Tidak bisa menghapus kategori ini karena masih memiliki {$row['total']} skill di dalamnya. Pindahkan atau hapus skill terlebih dahulu.");
            } else {
                $conn->query("DELETE FROM categories WHERE id = $id");
                set_flash('success', "Kategori berhasil dihapus.");
            }
        }
    }

    header('Location: categories.php');
    exit;
}

// Ambil daftar kategori dengan total skill di dalamnya
$categories = [];
if (is_db_connected()) {
    $res = $conn->query("
        SELECT c.*, COUNT(s.id) AS skill_count 
        FROM categories c 
        LEFT JOIN skills s ON c.id = s.category_id 
        GROUP BY c.id 
        ORDER BY c.id ASC
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) $categories[] = $row;
    }
}
?>

<div style="max-width: 1280px; margin: 0 auto;">

    <!-- HEADER TITLE -->
    <div style="margin-bottom: 28px;">
        <h1 style="font-size: 26px; font-weight: 800; color: #0f172a;">
            Kelola Kategori Bidang Keahlian
        </h1>
        <p style="font-size: 13px; color: #64748b; margin-top: 4px;">
            Atur pengelompokan skill mahasiswa agar proses penemuan dan pencarian makin mudah dan terstruktur.
        </p>
    </div>

    <!-- DUA KOLOM: FORM TAMBAH KATEGORI & TABEL DAFTAR KATEGORI -->
    <div style="display: grid; grid-template-columns: 360px 1fr; gap: 28px; align-items: start;">
        
        <!-- FORM TAMBAH KATEGORI -->
        <div style="background: white; border-radius: 12px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm);">
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 16px;">
                ➕ Tambah Kategori Baru
            </h3>

            <form action="categories.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Nama Kategori
                    </label>
                    <input type="text" name="name" required placeholder="Contoh: Seni Musik & Audio" 
                           style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Icon Emoji
                    </label>
                    <input type="text" name="icon" value="💡" required placeholder="Contoh: 💻, 🎨, 🎵" 
                           style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">
                        Deskripsi Singkat
                    </label>
                    <textarea name="description" rows="3" placeholder="Jelaskan bidang apa saja yang dicakup kategori ini..." 
                              style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; resize: vertical;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    Simpan Kategori Baru
                </button>
            </form>
        </div>

        <!-- TABEL LIST KATEGORI -->
        <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm); overflow: hidden;">
            <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">
                    Daftar Kategori Tersedia (<?= count($categories) ?>)
                </h3>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 12px; text-transform: uppercase;">
                            <th style="padding: 14px 16px; width: 50px;">Icon</th>
                            <th style="padding: 14px 16px;">Kategori</th>
                            <th style="padding: 14px 16px;">Deskripsi</th>
                            <th style="padding: 14px 16px; text-align: center;">Jumlah Skill</th>
                            <th style="padding: 14px 16px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8;">
                                    Belum ada kategori yang ditambahkan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 14px 16px; font-size: 24px; text-align: center;">
                                        <?= e($cat['icon']) ?>
                                    </td>
                                    <td style="padding: 14px 16px;">
                                        <strong style="color: #0f172a; font-size: 14px; display: block;">
                                            <?= e($cat['name']) ?>
                                        </strong>
                                        <span style="font-size: 11px; color: #94a3b8;">slug: <?= e($cat['slug']) ?></span>
                                    </td>
                                    <td style="padding: 14px 16px; color: #64748b; font-size: 12px; max-width: 280px;">
                                        <?= e($cat['description'] ?? '-') ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <span style="background: #e0f2fe; color: #0284c7; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px;">
                                            <?= (int)$cat['skill_count'] ?> Skill
                                        </span>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <?php if ((int)$cat['skill_count'] === 0): ?>
                                            <form action="categories.php" method="POST" onsubmit="return confirm('Hapus kategori <?= addslashes(e($cat['name'])) ?>?');" style="margin: 0; display: inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                <button type="submit" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid #fecaca; background: #fee2e2; color: #991b1b; cursor: pointer;">
                                                    Hapus
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: 11px; color: #94a3b8;" title="Kategori masih punya skill aktif">
                                                Terkunci 🔒
                                            </span>
                                        <?php endif; ?>
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

<?php require_once __DIR__ . '/footer.php'; ?>
