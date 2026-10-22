<?php
// ==============================================================
// register.php
// halaman pendaftaran mahasiswa baru di platform TukarSkill
// ==============================================================

$page_title = "Daftar Akun Baru";
require_once __DIR__ . '/includes/header.php';

// kalo user udah login, langsung lempar aja ke dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$name = '';
$nim = '';
$email = '';
$major = '';
$batch_year = date('Y');

// proses form pas tombol submit ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); // amankan dari serangan csrf

    $name = trim($_POST['name'] ?? '');
    $nim = trim($_POST['nim'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $major = trim($_POST['major'] ?? '');
    $batch_year = (int)($_POST['batch_year'] ?? date('Y'));
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $agree = isset($_POST['agree']);

    // validasi form server-side biar data yang masuk beneran bersih
    if (empty($name)) {
        $errors['name'] = 'Nama lengkap wajib diisi ya!';
    } elseif (strlen($name) < 3) {
        $errors['name'] = 'Nama kependekan nih, minimal 3 karakter.';
    }

    if (empty($nim)) {
        $errors['nim'] = 'NIM mahasiswa wajib diisi.';
    } elseif (!preg_match('/^[0-9]{10}$/', $nim)) {
        $errors['nim'] = 'Format NIM harus berupa 10 digit angka.';
    }

    if (empty($email)) {
        $errors['email'] = 'Email kampus wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email kamu kelihatannya kurang valid nih.';
    }

    if (empty($major)) {
        $errors['major'] = 'Pilih atau ketik jurusan kuliahmu.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password minimal harus 8 karakter biar aman.';
    }

    if ($password !== $password_confirm) {
        $errors['password_confirm'] = 'Konfirmasi password tidak cocok dengan password di atas.';
    }

    if (!$agree) {
        $errors['agree'] = 'Kamu harus menyetujui komitmen saling belajar dan berbagi di TukarSkill.';
    }

    // kalo validasi dasar lolos, cek ke database apakah NIM atau Email udah pernah kedaftar
    if (empty($errors)) {
        if (!is_db_connected()) {
            $errors['general'] = 'Database belum terhubung. Pastikan MySQL di XAMPP sudah aktif dan docs.sql sudah diimport.';
        } else {
            $check_stmt = $conn->prepare("SELECT id, email, nim FROM users WHERE email = ? OR nim = ? LIMIT 1");
            if ($check_stmt) {
                $check_stmt->bind_param("ss", $email, $nim);
                $check_stmt->execute();
                $res = $check_stmt->get_result();
                if ($res && $existing = $res->fetch_assoc()) {
                    if ($existing['email'] === $email) {
                        $errors['email'] = 'Email ini sudah terdaftar. Silakan login atau gunakan email lain.';
                    }
                    if ($existing['nim'] === $nim) {
                        $errors['nim'] = 'NIM ini sudah terdaftar di sistem TukarSkill.';
                    }
                }
                $check_stmt->close();
            }

            // kalo beneran aman semua, simpen data mahasiswa baru ke database!
            if (empty($errors)) {
                // hash password pake bcrypt bawaan php yang udah teruji aman
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $initial_points = 20; // bonus selamat datang

                $insert_stmt = $conn->prepare("
                    INSERT INTO users (name, nim, email, password, major, batch_year, role, points, total_points, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, 'user', ?, ?, 1)
                ");

                if ($insert_stmt) {
                    $insert_stmt->bind_param("sssssiii", $name, $nim, $email, $password_hash, $major, $batch_year, $initial_points, $initial_points);
                    if ($insert_stmt->execute()) {
                        $new_user_id = $insert_stmt->insert_id;
                        $insert_stmt->close();

                        // catat transaksi poin bonus selamat datang
                        add_points($new_user_id, 20, 'welcome', 'Bonus selamat datang akun mahasiswa baru TukarSkill');

                        // kasih badge pertama: Bibit 🌱
                        $conn->query("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES ($new_user_id, 1)");

                        // set pesan sukses & arahkan ke halaman login
                        set_flash('success', 'Akun kamu berhasil dibuat! Bonus +20 Poin selamat datang sudah masuk. Silakan login ya!');
                        header('Location: login.php');
                        exit;
                    } else {
                        $errors['general'] = 'Gagal menyimpan data akun: ' . $conn->error;
                        $insert_stmt->close();
                    }
                }
            }
        }
    }
}
?>

<div class="container" style="padding: 40px 16px 80px;">
    <div style="max-width: 580px; margin: 0 auto;">
        
        <!-- Kartu Form Registrasi Putih Membulat -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 40px; box-shadow: var(--shadow-md); border: 1px solid var(--gray-200);">
            
            <!-- Header Form -->
            <div style="text-align: center; margin-bottom: 32px;">
                <span class="section-tag" style="background: var(--lime); color: var(--lime-dark);">✨ Bonus +20 Poin</span>
                <h1 style="font-size: 28px; font-weight: 800; color: var(--ink); margin-top: 10px;">Gabung TukarSkill</h1>
                <p style="font-size: 14px; color: var(--gray-600); margin-top: 6px;">
                    Mulai tukar skill dengan teman kampus. 100% gratis tanpa biaya les!
                </p>
            </div>

            <!-- Pesan Error Global -->
            <?php if (!empty($errors['general'])): ?>
                <div style="background: #fee2e2; border-left: 4px solid var(--danger); color: #991b1b; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 24px; font-size: 13px;">
                    <?= e($errors['general']) ?>
                </div>
            <?php endif; ?>

            <!-- Form Registrasi -->
            <form action="register.php" method="POST" novalidate>
                <?= csrf_field() ?>

                <!-- Nama Lengkap -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                        Nama Lengkap <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" name="name" value="<?= e($name) ?>" placeholder="Contoh: Rangga Alrasya" 
                           style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid <?= isset($errors['name']) ? 'var(--danger)' : 'var(--gray-200)' ?>; font-family: inherit; font-size: 14px; outline: none;">
                    <?php if (isset($errors['name'])): ?>
                        <span style="display: block; font-size: 12px; color: var(--danger); margin-top: 4px;"><?= e($errors['name']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Baris NIM & Angkatan (2 Kolom) -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px; margin-bottom: 18px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                            NIM (10 Digit) <span style="color: var(--danger);">*</span>
                        </label>
                        <input type="text" name="nim" value="<?= e($nim) ?>" placeholder="2902691122" maxlength="10"
                               style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid <?= isset($errors['nim']) ? 'var(--danger)' : 'var(--gray-200)' ?>; font-family: inherit; font-size: 14px; outline: none;">
                        <?php if (isset($errors['nim'])): ?>
                            <span style="display: block; font-size: 12px; color: var(--danger); margin-top: 4px;"><?= e($errors['nim']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                            Angkatan
                        </label>
                        <select name="batch_year" style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none; background: var(--white);">
                            <?php for ($y = date('Y') + 1; $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= ($batch_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- Email Kampus -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                        Email Mahasiswa <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="email" name="email" value="<?= e($email) ?>" placeholder="nama@binus.ac.id" 
                           style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid <?= isset($errors['email']) ? 'var(--danger)' : 'var(--gray-200)' ?>; font-family: inherit; font-size: 14px; outline: none;">
                    <?php if (isset($errors['email'])): ?>
                        <span style="display: block; font-size: 12px; color: var(--danger); margin-top: 4px;"><?= e($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Jurusan / Program Studi -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                        Jurusan Kuliah <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" name="major" value="<?= e($major) ?>" placeholder="Contoh: Computer Science / Desain Komunikasi Visual" 
                           style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid <?= isset($errors['major']) ? 'var(--danger)' : 'var(--gray-200)' ?>; font-family: inherit; font-size: 14px; outline: none;">
                    <?php if (isset($errors['major'])): ?>
                        <span style="display: block; font-size: 12px; color: var(--danger); margin-top: 4px;"><?= e($errors['major']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Password & Konfirmasi Password (2 Kolom) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 22px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                            Password <span style="color: var(--danger);">*</span>
                        </label>
                        <input type="password" name="password" placeholder="Min. 8 karakter" 
                               style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid <?= isset($errors['password']) ? 'var(--danger)' : 'var(--gray-200)' ?>; font-family: inherit; font-size: 14px; outline: none;">
                        <?php if (isset($errors['password'])): ?>
                            <span style="display: block; font-size: 12px; color: var(--danger); margin-top: 4px;"><?= e($errors['password']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                            Ulangi Password <span style="color: var(--danger);">*</span>
                        </label>
                        <input type="password" name="password_confirm" placeholder="Ketik ulang password" 
                               style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid <?= isset($errors['password_confirm']) ? 'var(--danger)' : 'var(--gray-200)' ?>; font-family: inherit; font-size: 14px; outline: none;">
                        <?php if (isset($errors['password_confirm'])): ?>
                            <span style="display: block; font-size: 12px; color: var(--danger); margin-top: 4px;"><?= e($errors['password_confirm']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Checkbox Persetujuan Komitmen Belajar -->
                <div style="margin-bottom: 28px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; font-size: 13px; color: var(--gray-600); cursor: pointer;">
                        <input type="checkbox" name="agree" value="1" style="margin-top: 3px;" <?= isset($_POST['agree']) ? 'checked' : '' ?>>
                        <span>Saya setuju untuk aktif saling menghargai, berbagi kemampuan, dan menjaga etika belajar-mengajar di komunitas TukarSkill.</span>
                    </label>
                    <?php if (isset($errors['agree'])): ?>
                        <span style="display: block; font-size: 12px; color: var(--danger); margin-top: 4px;"><?= e($errors['agree']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Tombol Submit -->
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px;">
                    Daftar Sekarang & Klaim 20 Poin <span class="btn-arrow">↗</span>
                </button>
            </form>

            <!-- Link Masuk Kalo Udah Punya Akun -->
            <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--gray-200); font-size: 14px; color: var(--gray-600);">
                Sudah punya akun sebelumnya? 
                <a href="login.php" style="color: var(--blue-600); font-weight: 700;">Masuk di sini</a>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
