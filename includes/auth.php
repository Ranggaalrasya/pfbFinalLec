<?php
// ==============================================================
// includes/auth.php
// ini penjaga pintu masuk login otomatis via cookie "Ingat Saya" (Remember Me)
// ==============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// kalo user belom login tapi dia punya cookie "remember_token" di komputernya
if (!is_logged_in() && isset($_COOKIE['tukarskill_remember']) && is_db_connected()) {
    $raw_token = $_COOKIE['tukarskill_remember'];
    
    // kita hash token dari cookie pake sha256 biar cocok sama yang disimpen di database
    $token_hash = hash('sha256', $raw_token);
    
    $stmt = $conn->prepare("SELECT id, name, role, is_active FROM users WHERE remember_token = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res && $row = $res->fetch_assoc()) {
            if ($row['is_active']) {
                // sukses validasi! login-in user ke session
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_role'] = $row['role'];
            } else {
                // akun dibekukan/dinonaktifkan admin, hapus cookie
                setcookie('tukarskill_remember', '', time() - 3600, '/');
            }
        } else {
            // token ga cocok atau udah expired, bersihin cookie palsu
            setcookie('tukarskill_remember', '', time() - 3600, '/');
        }
        $stmt->close();
    }
}
