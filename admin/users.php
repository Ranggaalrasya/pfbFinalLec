<?php
// ==============================================================
// admin/users.php
// kelola data mahasiswa & admin platform TukarSkill
// pencarian, filter role/status, ubah status aktif, & atur role
// ==============================================================

$page_title = "Kelola Pengguna";
require_once __DIR__ . '/header.php';

$current_admin_id = (int)$admin['id'];

// --------------------------------------------------------------
// PROSES AKSI ADMIN (POST DENGAN CSRF)
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_db_connected()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $target_id = (int)($_POST['target_id'] ?? 0);

    if ($target_id > 0) {
        if ($action === 'toggle_status') {
            // Cegah admin menonaktifkan akunnya sendiri
            if ($target_id === $current_admin_id) {
                set_flash('danger', 'Kamu tidak bisa menonaktifkan akunmu sendiri!');
            } else {
                $conn->query("UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = $target_id");
                set_flash('success', 'Status keaktifan pengguna berhasil diperbarui.');
            }
        } elseif ($action === 'change_role') {
            $new_role = ($_POST['role'] === 'admin') ? 'admin' : 'student';
            if ($target_id === $current_admin_id && $new_role !== 'admin') {
                set_flash('danger', 'Kamu tidak bisa menurunkan role admin milikmu sendiri!');
            } else {
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $new_role, $target_id);
                $stmt->execute();
                $stmt->close();
                set_flash('success', "Role pengguna berhasil diubah menjadi $new_role.");
            }
        } elseif ($action === 'adjust_points') {
            $points_delta = (int)($_POST['points_delta'] ?? 0);
            $reason = trim($_POST['reason'] ?? 'Penyesuaian oleh Administrator');
            if ($points_delta !== 0) {
                add_points($target_id, $points_delta, 'admin_adjustment', $reason);
                set_flash('success', "Poin pengguna berhasil disesuaikan sebesar $points_delta poin.");
            }
        }
    }

    header('Location: users.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// --------------------------------------------------------------
// FILTER & SEARCH QUERY PENGGUNA
// --------------------------------------------------------------
$q = trim($_GET['q'] ?? '');
$role_filter = $_GET['role'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

$users = [];
if (is_db_connected()) {
    $where_clauses = ["1=1"];
    $types = "";
    $params = [];

    if (!empty($q)) {
        $where_clauses[] = "(name LIKE ? OR nim LIKE ? OR email LIKE ? OR major LIKE ?)";
        $search_wild = "%$q%";
        $types .= "ssss";
        $params[] = $search_wild;
        $params[] = $search_wild;
        $params[] = $search_wild;
        $params[] = $search_wild;
    }

    if ($role_filter === 'student' || $role_filter === 'admin') {
        $where_clauses[] = "role = ?";
        $types .= "s";
        $params[] = $role_filter;
    }

    if ($status_filter === '1' || $status_filter === '0') {
        $where_clauses[] = "is_active = ?";
        $types .= "i";
        $params[] = (int)$status_filter;
    }

    $where_sql = implode(" AND ", $where_clauses);
    $sql = "SELECT * FROM users WHERE $where_sql ORDER BY id DESC LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}
?>

<div style="max-width: 1280px; margin: 0 auto;">

    <!-- HEADER TITLE & STATS -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 800; color: #0f172a;">
                Manajemen Pengguna (<?= count($users) ?>)
            </h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 4px;">
                Pantau seluruh mahasiswa dan admin terdaftar. Kamu bisa mengaktifkan/menonaktifkan akun atau menyesuaikan poin reward.
            </p>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
        <form action="users.php" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap;">
            
            <!-- Input Cari -->
            <div style="flex: 2; min-width: 200px;">
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama, NIM, email, atau jurusan..." 
                       style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none;">
            </div>

            <!-- Filter Role -->
            <div style="flex: 1; min-width: 140px;">
                <select name="role" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; background: white;">
                    <option value="all">Semua Role</option>
                    <option value="student" <?= ($role_filter === 'student') ? 'selected' : '' ?>>Mahasiswa</option>
                    <option value="admin" <?= ($role_filter === 'admin') ? 'selected' : '' ?>>Administrator</option>
                </select>
            </div>

            <!-- Filter Status -->
            <div style="flex: 1; min-width: 140px;">
                <select name="status" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; background: white;">
                    <option value="all">Semua Status</option>
                    <option value="1" <?= ($status_filter === '1') ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= ($status_filter === '0') ? 'selected' : '' ?>>Nonaktif / Banned</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">
                Terapkan Filter
            </button>
            <?php if (!empty($q) || $role_filter !== 'all' || $status_filter !== 'all'): ?>
                <a href="users.php" class="btn btn-outline" style="padding: 10px 16px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABEL DATA PENGGUNA -->
    <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm); overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 12px; text-transform: uppercase;">
                        <th style="padding: 14px 16px; width: 60px;">ID</th>
                        <th style="padding: 14px 16px;">Mahasiswa / Profil</th>
                        <th style="padding: 14px 16px;">NIM & Email</th>
                        <th style="padding: 14px 16px;">Jurusan</th>
                        <th style="padding: 14px 16px; text-align: center;">Role</th>
                        <th style="padding: 14px 16px; text-align: right;">Saldo Poin</th>
                        <th style="padding: 14px 16px; text-align: center;">Status</th>
                        <th style="padding: 14px 16px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" style="padding: 40px; text-align: center; color: #94a3b8;">
                                Tidak ada data pengguna yang sesuai dengan filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): 
                            $is_self = ($u['id'] == $current_admin_id);
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; background: <?= $is_self ? '#eff6ff' : 'transparent' ?>;">
                                <td style="padding: 14px 16px; color: #94a3b8; font-weight: 700;">
                                    #<?= $u['id'] ?>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; border-radius: 50%; background: #3b82f6; color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; flex-shrink: 0;">
                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <a href="../user.php?id=<?= $u['id'] ?>" target="_blank" style="font-weight: 700; color: #0f172a; text-decoration: none;">
                                                <?= e($u['name']) ?>
                                            </a>
                                            <?php if ($is_self): ?>
                                                <span style="font-size: 10px; background: #3b82f6; color: white; padding: 1px 6px; border-radius: 999px;">Akun Kamu</span>
                                            <?php endif; ?>
                                            <span style="font-size: 11px; color: #64748b; display: block;">
                                                ⭐ <?= number_format($u['rating'], 1) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <strong style="color: #0f172a; display: block;"><?= e($u['nim']) ?></strong>
                                    <span style="font-size: 11px; color: #64748b;"><?= e($u['email']) ?></span>
                                </td>
                                <td style="padding: 14px 16px; color: #334155;">
                                    <?= e($u['major'] ?? '-') ?>
                                    <span style="font-size: 11px; color: #94a3b8; display: block;">Angkatan <?= e($u['batch_year'] ?? '-') ?></span>
                                </td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <span style="font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px; <?= ($u['role'] === 'admin') ? 'background: #fef08a; color: #854d0e;' : 'background: #e2e8f0; color: #475569;' ?>">
                                        <?= strtoupper(e($u['role'])) ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 16px; text-align: right; font-weight: 800; color: #2563eb;">
                                    🪙 <?= number_format($u['points']) ?>
                                </td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <?php if ($u['is_active'] == 1): ?>
                                        <span style="background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px;">
                                            Aktif
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #b91c1c; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px;">
                                            Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        
                                        <!-- Tombol Toggle Aktif/Nonaktif -->
                                        <?php if (!$is_self): ?>
                                            <form action="users.php" method="POST" onsubmit="return confirm('Apakah kamu yakin ingin mengubah status keaktifan akun ini?');" style="margin: 0;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                                <button type="submit" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid <?= ($u['is_active'] == 1) ? '#fecaca' : '#bbf7d0' ?>; background: <?= ($u['is_active'] == 1) ? '#fee2e2' : '#dcfce7' ?>; color: <?= ($u['is_active'] == 1) ? '#991b1b' : '#15803d' ?>; cursor: pointer;">
                                                    <?= ($u['is_active'] == 1) ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                </button>
                                            </form>

                                            <!-- Tombol Ubah Role -->
                                            <form action="users.php" method="POST" style="margin: 0;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="change_role">
                                                <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="role" value="<?= ($u['role'] === 'admin') ? 'student' : 'admin' ?>">
                                                <button type="submit" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid #cbd5e1; background: #f8fafc; color: #334155; cursor: pointer;" title="Ubah role menjadi <?= ($u['role'] === 'admin') ? 'student' : 'admin' ?>">
                                                    <?= ($u['role'] === 'admin') ? 'Jadikan Mhs' : 'Jadikan Admin' ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: 11px; color: #94a3b8; font-style: italic;">Akun Sendiri</span>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
