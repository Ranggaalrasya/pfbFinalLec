<?php
// ==============================================================
// config/db.php
// ini file buat nyambungin web kita ke database MySQL XAMPP pake MySQLi
// ==============================================================

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'tukarskill';
$db_port = 3306;

// nyalain error reporting mysqli biar kalo ada query typo langsung ketauan
mysqli_report(MYSQLI_REPORT_OFF);

// coba buka koneksi ke server mysql
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// kalo database 'tukarskill' belom dibikin atau mysql belom nyala, jangan bikin web crash total
$db_connected = true;
$db_error_message = '';

if ($conn->connect_errno) {
    $db_connected = false;
    $db_error_message = $conn->connect_error;
    
    // coba cek apakah mysql nyala tapi databasenya doang yang belom diimport
    $test_server = @new mysqli($db_host, $db_user, $db_pass, '', $db_port);
    if (!$test_server->connect_errno) {
        $db_error_message = "Database 'tukarskill' belum ditemukan di phpMyAdmin! Silakan import file 'docs.sql' dulu ya.";
        $test_server->close();
    } else {
        $db_error_message = "MySQL di XAMPP belum aktif! Klik tombol 'Start' pada MySQL di XAMPP Control Panel.";
    }
} else {
    // set charset biar teks bahasa indonesia, emot, dan karakter khusus aman
    $conn->set_charset("utf8mb4");
}

// helper kecil biar gampang ngecek status koneksi dari file lain
function is_db_connected() {
    global $db_connected;
    return $db_connected;
}
