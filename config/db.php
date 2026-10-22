<?php
// ==============================================================
// config/db.php
// ini file buat nyambungin web kita ke database MySQL XAMPP pake MySQLi
// ==============================================================

// pake 127.0.0.1 biar langsung TCP IPv4, ga kena delay lookup localhost di Windows
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'tukarskill';
$db_port = 3306;

// matiin reporting default biar kita handle error koneksi sendiri secara elegan
mysqli_report(MYSQLI_REPORT_OFF);

$db_connected = false;
$db_error_message = '';

// inisialisasi koneksi dengan timeout 2 detik biar ga pernah bikin web nge-hang
$conn = mysqli_init();
if ($conn) {
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 2);
    
    // coba konek langsung ke database 'tukarskill'
    $connected = @mysqli_real_connect($conn, $db_host, $db_user, $db_pass, $db_name, $db_port);
    
    if ($connected) {
        $db_connected = true;
        // set charset utf8mb4 biar teks indonesia, emoji dan simbol lancar
        $conn->set_charset("utf8mb4");
    } else {
        $errno = mysqli_connect_errno();
        $error = mysqli_connect_error();
        
        // cek apakah server mysql aktif tapi database 'tukarskill' belum diimport di phpMyAdmin
        $test_conn = mysqli_init();
        mysqli_options($test_conn, MYSQLI_OPT_CONNECT_TIMEOUT, 1);
        if (@mysqli_real_connect($test_conn, $db_host, $db_user, $db_pass, '', $db_port)) {
            $db_error_message = "Database 'tukarskill' belum ditemukan di phpMyAdmin. Silakan import file 'docs.sql' di phpMyAdmin ya!";
            @mysqli_close($test_conn);
        } else {
            $db_error_message = "MySQL di XAMPP belum aktif. Klik tombol 'Start' pada MySQL di XAMPP Control Panel.";
        }
    }
}

// helper kecil buat cek status database dari file mana pun
function is_db_connected() {
    global $db_connected;
    return $db_connected;
}
