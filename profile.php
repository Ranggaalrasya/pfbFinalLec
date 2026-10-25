<?php
// ==============================================================
// profile.php
// halaman kelola & edit profil mahasiswa, foto, WhatsApp, dan ganti password
// ==============================================================

$page_title = "Profil Saya";
require_once __DIR__ . '/includes/header.php';

// hanya mahasiswa yang sudah login yang boleh buka halaman ini
require_login();

$user = current_user();
$user_id = $user['id'];

$success_msg = '';
$error_msg = '';

// --------------------------------------------------------------
// 1. PROSES UPDATE DATA PROFIL & UPLOAD AVATAR
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $major = trim($_POST['major'] ?? '');
    $batch_year = (int)($_POST['batch_year'] ?? date('Y'));
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if (empty($name) || empty($major)) {
        $error_msg = 'Nama lengkap dan jurusan tidak boleh dikosongkan ya.';
    } else {
        $new_photo_name = $user['photo'];

        // Cek apakah ada file foto avatar yang diupload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['photo']['tmp_name'];
            $file_name = $_FILES['photo']['name'];
            $file_size = $_FILES['photo']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($file_ext, $allowed_exts)) {
                $error_msg = 'Format foto harus berupa JPG, PNG, atau WEBP.';
            } elseif ($file_size > 2 * 1024 * 1024) {
                $error_msg = 'Ukuran foto maksimal 2 MB ya.';
            } else {
                // bikin nama file acak unik biar ga bentrok sama foto user lain
                $new_photo_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
                $upload_dir = __DIR__ . '/uploads/avatars/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($file_tmp, $upload_dir . $new_photo_name)) {
                    // hapus foto lama kalo ada dan bukan default
                    if (!empty($user['photo']) && file_exists($upload_dir . $user['photo'])) {
                        @unlink($upload_dir . $user['photo']);
                    }
                } else {
                    $error_msg = 'Gagal menyimpan foto avatar ke server.';
                }
            }
        }

        if (empty($error_msg) && is_db_connected()) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, major = ?, batch_year = ?, whatsapp = ?, bio = ?, photo = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssisssi", $name, $major, $batch_year, $whatsapp, $bio, $new_photo_name, $user_id);
                if ($stmt->execute()) {
                    $success_msg = 'Profil kamu berhasil diperbarui!';
                    
                    // Cek bonus kelengkapan profil (+10 poin) jika baru pertama kali melengkapi bio dan skill
                    $q_bonus = $conn->query("SELECT profile_bonus_given FROM users WHERE id = $user_id");
                    if ($q_bonus && $b_row = $q_bonus->fetch_assoc()) {
                        if ($b_row['profile_bonus_given'] == 0 && !empty($bio)) {
                            // hitung apakah udah punya skill offer & want
                            $q_offer = $conn->query("SELECT 1 FROM user_skills WHERE user_id = $user_id AND type = 'offer' LIMIT 1");
                            $q_want = $conn->query("SELECT 1 FROM user_skills WHERE user_id = $user_id AND type = 'want' LIMIT 1");
                            if ($q_offer->num_rows > 0 && $q_want->num_rows > 0) {
                                add_points($user_id, 10, 'profile_complete', 'Bonus melengkapi profil & skill pertama kali');
                                $conn->query("UPDATE users SET profile_bonus_given = 1 WHERE id = $user_id");
                                $success_msg .= ' 🎉 Selamat! Kamu dapet tambahan +10 Poin karena profilmu sudah lengkap!';
                            }
                        }
                    }

                    // refresh data current user
                    $_SESSION['user_name'] = $name;
                    $user = current_user();
                } else {
                    $error_msg = 'Gagal update database: ' . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}

// --------------------------------------------------------------
// 2. PROSES GANTI PASSWORD
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    verify_csrf();

    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($current_pass) || empty($new_pass)) {
        $error_msg = 'Semua kolom password wajib diisi.';
    } elseif (strlen($new_pass) < 8) {
        $error_msg = 'Password baru minimal harus 8 karakter.';
    } elseif ($new_pass !== $confirm_pass) {
        $error_msg = 'Konfirmasi password baru tidak cocok.';
    } elseif (is_db_connected()) {
        // verifikasi password saat ini
        $q_pass = $conn->query("SELECT password FROM users WHERE id = $user_id");
        if ($q_pass && $row_pass = $q_pass->fetch_assoc()) {
            if (password_verify($current_pass, $row_pass['password'])) {
                $hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt_up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt_up) {
                    $stmt_up->bind_param("si", $hash, $user_id);
                    $stmt_up->execute();
                    $stmt_up->close();
                    $success_msg = 'Password kamu berhasil diganti!';
                }
            } else {
                $error_msg = 'Password saat ini yang kamu masukkan salah.';
            }
        }
    }
}

// Ambil info badges yang dimiliki user
$user_badges = [];
if (is_db_connected()) {
    $b_res = $conn->query("
        SELECT b.name, b.icon, b.description, ub.earned_at 
        FROM user_badges ub 
        JOIN badges b ON ub.badge_id = b.id 
        WHERE ub.user_id = $user_id 
        ORDER BY b.min_points DESC
    ");
    if ($b_res) {
        while ($row = $b_res->fetch_assoc()) {
            $user_badges[] = $row;
        }
    }
}
?>

<div class="container" style="padding: 40px 16px 80px;">
    
    <!-- Header Halaman Profil -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 32px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: var(--ink);">Profil & Akun Saya</h1>
            <p style="font-size: 14px; color: var(--gray-600); margin-top: 4px;">
                Kelola informasi biodata, kontak WhatsApp, dan keamanan akunmu.
            </p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="dashboard.php" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <?= icon('arrow-left', '', '14px') ?> Dashboard
            </a>
            <a href="my-skills.php" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                Kelola Skill <?= icon('arrow-right', '', '14px') ?>
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    <?php if (!empty($success_msg)): ?>
        <div style="background: var(--mint); border-left: 4px solid #10b981; color: var(--mint-dark); padding: 14px 20px; border-radius: var(--radius-sm); margin-bottom: 24px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <?= icon('check-circle', '', '18px') ?>
            <span><?= e($success_msg) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div style="background: #fee2e2; border-left: 4px solid var(--danger); color: #991b1b; padding: 14px 20px; border-radius: var(--radius-sm); margin-bottom: 24px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <?= icon('alert-circle', '', '18px') ?>
            <span><?= e($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Layout Grid Profil: Kiri (Kartu Ringkas) + Kanan (Formulir Edit) -->
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 28px; align-items: start;">
        
        <!-- KOLOM KIRI: Kartu Ringkasan Mahasiswa -->
        <div>
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); text-align: center;">
                
                <!-- Avatar Profil dengan render_avatar -->
                <div style="display: flex; justify-content: center; margin-bottom: 16px;">
                    <?= render_avatar($user['photo'], $user['name'], 100) ?>
                </div>

                <h2 style="font-size: 20px; font-weight: 800; color: var(--ink);"><?= e($user['name']) ?></h2>
                <p style="font-size: 13px; color: var(--gray-600); margin-top: 2px;">NIM: <?= e($user['nim']) ?></p>
                <p style="font-size: 13px; color: var(--blue-600); font-weight: 600; margin-top: 2px;"><?= e($user['major']) ?> (<?= e($user['batch_year'] ?? '-') ?>)</p>

                <!-- Status Saldo Poin & Badge -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                    <div style="background: var(--blue-50); padding: 12px; border-radius: var(--radius-md);">
                        <span style="font-size: 11px; font-weight: 700; color: var(--blue-600); text-transform: uppercase;">Saldo Poin</span>
                        <div style="font-size: 20px; font-weight: 800; color: var(--ink); margin-top: 4px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <span style="color: var(--blue-600); display: inline-flex;"><?= icon('sprout', '', '18px') ?></span>
                            <?= (int)$user['points'] ?>
                        </div>
                    </div>
                    <div style="background: var(--lime); padding: 12px; border-radius: var(--radius-md);">
                        <span style="font-size: 11px; font-weight: 700; color: var(--lime-dark); text-transform: uppercase;">Total Karir</span>
                        <div style="font-size: 20px; font-weight: 800; color: var(--lime-dark); margin-top: 4px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <span style="color: var(--lime-dark); display: inline-flex;"><?= icon('star', '', '18px') ?></span>
                            <?= (int)$user['total_points'] ?>
                        </div>
                    </div>
                </div>

                <!-- Koleksi Badge yang Diraih -->
                <div style="margin-top: 24px; text-align: left;">
                    <h4 style="font-size: 13px; font-weight: 700; color: var(--gray-600); text-transform: uppercase; margin-bottom: 10px;">Badge Dimiliki:</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php if (empty($user_badges)): ?>
                            <span style="font-size: 12px; color: var(--gray-400);">Belum ada badge</span>
                        <?php else: ?>
                            <?php foreach ($user_badges as $ub): ?>
                                <span style="background: var(--gray-100); border: 1px solid var(--gray-200); padding: 5px 12px; border-radius: var(--radius-pill); font-size: 12px; font-weight: 700; color: var(--ink); display: inline-flex; align-items: center; gap: 6px;" title="<?= e($ub['description']) ?>">
                                    <?= badge_icon_svg($ub['icon'], '14px') ?> <?= e($ub['name']) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- KOLOM KANAN: Form Edit Profil & Keamanan -->
        <div style="display: flex; flex-direction: column; gap: 28px;">
            
            <!-- 1. Formulir Biodata -->
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: 36px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
                <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 20px;">Informasi Pribadi</h3>

                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <!-- Nama & Email -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Nama Lengkap</label>
                            <input type="text" name="name" value="<?= e($user['name']) ?>" required
                                   style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Email Kampus (Tidak dapat diubah)</label>
                            <input type="email" value="<?= e($user['email']) ?>" disabled
                                   style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); background: var(--gray-100); color: var(--gray-600); font-family: inherit; font-size: 14px;">
                        </div>
                    </div>

                    <!-- Jurusan & Angkatan -->
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Jurusan Kuliah</label>
                            <input type="text" name="major" value="<?= e($user['major']) ?>" required
                                   style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Angkatan</label>
                            <input type="number" name="batch_year" value="<?= e($user['batch_year']) ?>"
                                   style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none;">
                        </div>
                    </div>

                    <!-- Nomor WhatsApp -->
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">
                            Nomor WhatsApp <small style="font-weight: 400; color: var(--gray-400);">(Hanya dibagikan ke partner setelah request disetujui)</small>
                        </label>
                        <input type="text" name="whatsapp" value="<?= e($user['whatsapp'] ?? '') ?>" placeholder="08123456789"
                               style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none;">
                    </div>

                    <!-- Bio Singkat -->
                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">
                            Bio & Minat Belajar
                        </label>
                        <textarea name="bio" rows="3" placeholder="Ceritakan keahlian yang kamu kuasai dan hal seru apa yang sedang ingin kamu pelajari..."
                                  style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none; resize: vertical;"><?= e($user['bio'] ?? '') ?></textarea>
                    </div>

                    <!-- Upload Foto Avatar Baru -->
                    <div style="margin-bottom: 26px;">
                        <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">
                            Foto Profil Baru (Maks 2 MB, JPG/PNG/WEBP)
                        </label>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                               style="font-size: 13px; color: var(--gray-600);">
                    </div>

                    <!-- Tombol Simpan Perubahan -->
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan Profil <span class="btn-arrow">✓</span>
                    </button>
                </form>
            </div>

            <!-- 2. Formulir Ganti Password -->
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: 36px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
                <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 8px;">Ganti Password</h3>
                <p style="font-size: 13px; color: var(--gray-600); margin-bottom: 20px;">Pastikan gunakan kombinasi password yang kuat dan mudah kamu ingat.</p>

                <form action="profile.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Password Saat Ini</label>
                        <input type="password" name="current_password" required
                               style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Password Baru</label>
                            <input type="password" name="new_password" placeholder="Min. 8 karakter" required
                                   style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Ulangi Password Baru</label>
                            <input type="password" name="confirm_password" required
                                   style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none;">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark btn-sm">
                        Perbarui Password
                    </button>
                </form>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
