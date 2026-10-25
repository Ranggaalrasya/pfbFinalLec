<?php
// ==============================================================
// login.php
// gerbang masuk mahasiswa & admin ke platform TukarSkill
// ==============================================================

$page_title = "Masuk ke Akun";
require_once __DIR__ . '/includes/header.php';

// kalo ternyata user udah login, langsung bawa ke dashboard
if (is_logged_in()) {
    header('Location: ' . (is_admin() ? 'admin/index.php' : 'dashboard.php'));
    exit;
}

$error = '';
$identifier = ''; // bisa diisi email atau NIM

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); // amankan dari serangan csrf

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($identifier) || empty($password)) {
        $error = 'Email/NIM dan password harus diisi lengkap ya.';
    } elseif (!is_db_connected()) {
        $error = 'Database belum terhubung. Pastikan MySQL di XAMPP sudah aktif dan docs.sql sudah diimport.';
    } else {
        // cari user berdasarkan email ATAU nim
        $stmt = $conn->prepare("SELECT id, name, nim, email, password, role, is_active FROM users WHERE email = ? OR nim = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res && $user = $res->fetch_assoc()) {
                // cek apakah akun dibekukan admin
                if (!$user['is_active']) {
                    $error = 'Akun kamu sedang dinonaktifkan oleh admin. Silakan hubungi pengelola sistem.';
                } 
                // cek kecocokan password pake bcrypt
                elseif (password_verify($password, $user['password'])) {
                    // sukses login! ganti session id biar aman dari session fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];

                    // kalo user nyentang "Ingat Saya", bikin cookie token 30 hari
                    if ($remember) {
                        $raw_token = bin2hex(random_bytes(32));
                        $token_hash = hash('sha256', $raw_token);

                        // simpen hash token di database
                        $update_tok = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        if ($update_tok) {
                            $update_tok->bind_param("si", $token_hash, $user['id']);
                            $update_tok->execute();
                            $update_tok->close();
                        }

                        // set cookie di browser selama 30 hari
                        setcookie(
                            'tukarskill_remember',
                            $raw_token,
                            time() + (86400 * 30),
                            '/',
                            '',
                            false,
                            true // httponly biar aman dari intip javascript
                        );
                    }

                    set_flash('success', 'Selamat datang kembali, ' . $user['name'] . '! 👋');

                    // arahkan sesuai role (admin masuk ke panel admin, mahasiswa ke dashboard)
                    if ($user['role'] === 'admin') {
                        header('Location: admin/index.php');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                } else {
                    $error = 'Password yang kamu masukkan salah. Coba periksa lagi ya.';
                }
            } else {
                $error = 'Akun dengan Email atau NIM tersebut tidak ditemukan.';
            }
            $stmt->close();
        }
    }
}
?>

<div class="container" style="padding: 50px 16px 90px;">
    <div style="max-width: 480px; margin: 0 auto;">
        
        <!-- Kartu Form Login Putih Membulat -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 40px; box-shadow: var(--shadow-md); border: 1px solid var(--gray-200);">
            
            <!-- Header Form -->
            <div style="text-align: center; margin-bottom: 30px;">
                <div class="logo-icon" style="margin: 0 auto 14px; width: 48px; height: 48px; border-radius: 16px; display: flex; align-items: center; justify-content: center; background: var(--blue-500); color: #fff;">
                    <?= icon('handshake', '', '26px') ?>
                </div>
                <h1 style="font-size: 26px; font-weight: 800; color: var(--ink);">Masuk ke TukarSkill</h1>
                <p style="font-size: 14px; color: var(--gray-600); margin-top: 6px;">
                    Lanjutkan barter kemampuan dan kumpulkan poinmu.
                </p>
            </div>

            <!-- Pesan Error Kalo Gagal Login -->
            <?php if (!empty($error)): ?>
                <div style="background: #fee2e2; border-left: 4px solid var(--danger); color: #991b1b; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 24px; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                    <?= icon('alert-circle', '', '16px') ?>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="login.php" method="POST">
                <?= csrf_field() ?>

                <!-- Input Email atau NIM -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                        Email atau NIM
                    </label>
                    <input type="text" name="identifier" value="<?= e($identifier) ?>" placeholder="nama@binus.ac.id atau 2902xxxxxx" required autofocus
                           style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none;">
                </div>

                <!-- Input Password -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label style="font-size: 13px; font-weight: 700; color: var(--ink);">
                            Password
                        </label>
                        <span style="font-size: 12px; color: var(--gray-400);">Lupa? Hubungi admin</span>
                    </div>
                    <input type="password" name="password" placeholder="Masukkan password akunmu" required
                           style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none;">
                </div>

                <!-- Checkbox Ingat Saya -->
                <div style="margin-bottom: 26px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--gray-600); cursor: pointer;">
                        <input type="checkbox" name="remember" value="1" checked>
                        <span>Ingat saya di perangkat ini (30 hari)</span>
                    </label>
                </div>

                <!-- Tombol Submit -->
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px;">
                    Masuk Sekarang <span class="btn-arrow">↗</span>
                </button>
            </form>

            <!-- Info Akun Cepat Buat Demo Ujian / Dosen -->
            <div style="background: var(--blue-50); border: 1px solid var(--blue-100); border-radius: var(--radius-md); padding: 12px 16px; margin-top: 24px; font-size: 12px; color: var(--blue-600);">
                <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; margin-bottom: 4px;">
                    <?= icon('info', '', '14px') ?> Akun Uji Coba Demo:
                </div>
                • <strong>Mahasiswa:</strong> <code>rangga.alrasya@binus.ac.id</code> / <code>password123</code><br>
                • <strong>Admin:</strong> <code>admin@tukarskill.id</code> / <code>admin123</code>
            </div>

            <!-- Link Daftar Akun Baru -->
            <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--gray-200); font-size: 14px; color: var(--gray-600);">
                Belum punya akun TukarSkill? 
                <a href="register.php" style="color: var(--blue-600); font-weight: 700;">Daftar gratis di sini</a>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
