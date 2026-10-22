<?php
// ==============================================================
// logout.php
// keluar dari sesi akun dan bersihin semua cookie token
// ==============================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// kalo user login dan ada cookie remember, bersihin tokennya di database
if (isset($_SESSION['user_id']) && is_db_connected()) {
    $uid = (int)$_SESSION['user_id'];
    $conn->query("UPDATE users SET remember_token = NULL WHERE id = $uid");
}

// hapus cookie "remember me" dari browser
if (isset($_COOKIE['tukarskill_remember'])) {
    setcookie('tukarskill_remember', '', time() - 3600, '/');
}

// bersihin seluruh session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// nyalain session baru cuma buat naro pesan flash selamat tinggal
session_start();
set_flash('info', 'Kamu telah berhasil keluar dari akun TukarSkill. Sampai jumpa lagi! 👋');

header('Location: index.php');
exit;
