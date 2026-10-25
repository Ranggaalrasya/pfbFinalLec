<?php
// ==============================================================
// includes/functions.php
// kumpulan fungsi sakti & helper serbaguna buat seisi web TukarSkill
// ==============================================================

// nyalain session kalo belom nyala, biar ga error notice "session already started"
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// load pustaka icon enterprise SVG
require_once __DIR__ . '/icons.php';

/**
 * Ambil URL avatar user yang pasti valid dan tidak akan menghasilkan gambar rusak (404)
 */
function user_avatar_url($photo, $name = '') {
    if (!empty($photo) && file_exists(__DIR__ . '/../uploads/avatars/' . $photo)) {
        return 'uploads/avatars/' . htmlspecialchars($photo);
    }
    
    // Cek avatar SVG spesifik nama panggilan (contoh: rangga, jennifer, lucky, dll)
    $first_word = strtolower(explode(' ', trim((string)$name))[0] ?? '');
    if ($first_word && file_exists(__DIR__ . '/../uploads/avatars/' . $first_word . '.svg')) {
        return 'uploads/avatars/' . $first_word . '.svg';
    }

    if (file_exists(__DIR__ . '/../uploads/avatars/default.svg')) {
        return 'uploads/avatars/default.svg';
    }
    return '';
}

/**
 * Render avatar HTML yang elegan, tahan banting, dan ber-border radius bulat sempurna
 */
function render_avatar($photo, $name, $size = 40, $extra_class = '') {
    $url = user_avatar_url($photo, $name);
    $initial = strtoupper(substr(trim((string)$name), 0, 1) ?: 'U');
    $size_px = (int)$size;

    if ($url) {
        return '<img src="' . e($url) . '" alt="' . e($name) . '" class="ts-avatar ' . e($extra_class) . '" style="width:' . $size_px . 'px; height:' . $size_px . 'px; border-radius:50%; object-fit:cover; border:2px solid #ffffff; box-shadow:0 2px 8px rgba(15,23,42,0.06); flex-shrink:0;">';
    }

    // Fallback inisial dengan gradien modern
    return '<div class="ts-avatar-fallback ' . e($extra_class) . '" style="width:' . $size_px . 'px; height:' . $size_px . 'px; border-radius:50%; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; font-weight:800; font-size:' . round($size_px * 0.42) . 'px; display:inline-flex; align-items:center; justify-content:center; border:2px solid #ffffff; box-shadow:0 2px 8px rgba(15,23,42,0.06); flex-shrink:0;">' . e($initial) . '</div>';
}

/**
 * ini fungsi buat sanitize teks biar ga kena serangan XSS pas dicetak ke HTML
 */
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * buat nyimpen pesan flash (alert notifikasi sementara yang hilang setelah di-refresh)
 * tipe: 'success', 'danger', 'warning', 'info'
 */
function set_flash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * buat ngambil sekaligus ngehapus pesan flash biar ga nongol terus menerus
 */
function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * ini buat bikin token CSRF biar form kita ga bisa ditembak dari web orang lain
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * cetak input hidden csrf token langsung ke dalem tag <form>
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * verifikasi apakah token csrf dari form POST cocok sama yang ada di session
 */
function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            die('Aduh, sesi form kamu udah kadaluarsa atau tidak valid (CSRF token mismatch). Silakan refresh halaman.');
        }
    }
    return true;
}

/**
 * cek apakah mahasiswa atau admin udah login
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * ambil data profil user yang lagi login saat ini
 */
function current_user() {
    global $conn;
    if (!is_logged_in() || !is_db_connected()) {
        return null;
    }
    
    static $cached_user = null;
    if ($cached_user !== null) {
        return $cached_user;
    }
    
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, name, nim, email, major, batch_year, bio, photo, whatsapp, role, points, total_points, is_active FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cached_user = $result->fetch_assoc();
        $stmt->close();
    }
    return $cached_user;
}

/**
 * cek apakah user yang lagi login adalah admin sistem
 */
function is_admin() {
    $user = current_user();
    return $user && ($user['role'] ?? '') === 'admin';
}

/**
 * satpam halaman: tendang ke halaman login kalo belom login
 */
function require_login($redirect_to = 'login.php') {
    if (!is_logged_in()) {
        set_flash('warning', 'Kamu harus login terlebih dahulu buat mengakses halaman ini.');
        header("Location: $redirect_to");
        exit;
    }
}

/**
 * satpam khusus admin: cuma yang rolenya 'admin' yang boleh masuk
 */
function require_admin($redirect_to = '../login.php') {
    require_login($redirect_to);
    if (!is_admin()) {
        set_flash('danger', 'Akses ditolak! Halaman ini khusus administrator platform.');
        header("Location: ../dashboard.php");
        exit;
    }
}

/**
 * fungsi otomatis buat nambahin poin & nyatet riwayat transaksi poin
 */
function add_points($user_id, $amount, $type, $description, $exchange_id = null) {
    global $conn;
    if (!is_db_connected()) return false;
    
    $user_id = (int)$user_id;
    $amount = (int)$amount;
    
    // catat di tabel histori transaksi poin
    $stmt = $conn->prepare("INSERT INTO point_transactions (user_id, exchange_id, amount, type, description) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiiss", $user_id, $exchange_id, $amount, $type, $description);
        $stmt->execute();
        $stmt->close();
    }
    
    // update saldo points dan total_points (total_points cuma nambah kalo dapet poin positif)
    if ($amount > 0) {
        $update = $conn->prepare("UPDATE users SET points = points + ?, total_points = total_points + ? WHERE id = ?");
        if ($update) {
            $update->bind_param("iii", $amount, $amount, $user_id);
            $update->execute();
            $update->close();
        }
    } else {
        // kalo poin kepake (minus), saldo points berkurang tapi total_points tetep utuh biar ga nurunin badge
        $update = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        if ($update) {
            $update->bind_param("ii", $amount, $user_id);
            $update->execute();
            $update->close();
        }
    }
    
    // otomatis cek apakah total_points udah cukup buat unlock badge baru
    check_and_award_badges($user_id);
    return true;
}

/**
 * cek & buka badge baru (Bibit, Tunas, Pohon, Mentor) berdasarkan total_points
 */
function check_and_award_badges($user_id) {
    global $conn;
    if (!is_db_connected()) return;
    
    $user_id = (int)$user_id;
    // ambil total_points user
    $q = $conn->query("SELECT total_points FROM users WHERE id = $user_id");
    if (!$q || !($row = $q->fetch_assoc())) return;
    $total = (int)$row['total_points'];
    
    // ambil badge yang syarat min_points-nya udah tercapai
    $badges = $conn->query("SELECT id, name, icon, min_points FROM badges WHERE min_points <= $total");
    if ($badges) {
        while ($b = $badges->fetch_assoc()) {
            $bid = (int)$b['id'];
            // cek apakah udah pernah dapet badge ini belom
            $check = $conn->query("SELECT 1 FROM user_badges WHERE user_id = $user_id AND badge_id = $bid");
            if ($check && $check->num_rows === 0) {
                // insert badge baru
                $conn->query("INSERT INTO user_badges (user_id, badge_id) VALUES ($user_id, $bid)");
                // kirim notifikasi selamat ke user
                add_notification($user_id, "Selamat! Kamu berhasil meraih badge " . $b['icon'] . " " . $b['name'] . "!", "points.php");
            }
        }
    }
}

/**
 * bikin notifikasi in-app baru buat user
 */
function add_notification($user_id, $message, $link = null) {
    global $conn;
    if (!is_db_connected()) return false;
    
    $user_id = (int)$user_id;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $message, $link);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    return false;
}

/**
 * hitung jumlah notifikasi yang belom dibaca
 */
function get_unread_notifications_count($user_id) {
    global $conn;
    if (!is_db_connected()) return 0;
    
    $user_id = (int)$user_id;
    $res = $conn->query("SELECT COUNT(*) AS total FROM notifications WHERE user_id = $user_id AND is_read = 0");
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row['total'];
    }
    return 0;
}

/**
 * format tanggal rapi ala Indonesia (contoh: 22 Oktober 2026)
 */
function format_tanggal_id($datetime_str) {
    if (!$datetime_str) return '-';
    $timestamp = strtotime($datetime_str);
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $d = date('d', $timestamp);
    $m = $bulan[(int)date('m', $timestamp)];
    $y = date('Y', $timestamp);
    $time = date('H:i', $timestamp);
    return "$d $m $y, $time WIB";
}
