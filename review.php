<?php
// ==============================================================
// review.php
// endpoint buat proses submit rating & ulasan dari exchange-detail
// dapet bonus +5 poin buat reviewer & +10 poin buat reviewee kalo bintang 5!
// ==============================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login(); // satpam halaman: wajib login bro

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exchanges.php');
    exit;
}

verify_csrf(); // cek token anti bajak formulir

$user = current_user();
$user_id = (int)$user['id'];

// Tangkap input dari form
$exchange_id = (int)($_POST['exchange_id'] ?? 0);
$reviewee_id = (int)($_POST['reviewee_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 5);
$comment = trim($_POST['comment'] ?? '');

// Validasi simpel
if ($exchange_id <= 0 || $reviewee_id <= 0) {
    set_flash('danger', 'Data pertukaran atau partner tidak valid.');
    header('Location: exchanges.php');
    exit;
}

if ($rating < 1 || $rating > 5) {
    set_flash('danger', 'Rating harus bernilai antara 1 sampai 5 bintang.');
    header("Location: exchange-detail.php?id=$exchange_id");
    exit;
}

if (empty($comment)) {
    set_flash('danger', 'Mohon isi komentar ulasan tentang pengalaman belajarmu.');
    header("Location: exchange-detail.php?id=$exchange_id#review");
    exit;
}

if ($reviewee_id === $user_id) {
    set_flash('danger', 'Kamu tidak bisa mereview dirimu sendiri dong!');
    header("Location: exchange-detail.php?id=$exchange_id");
    exit;
}

// Pastikan DB nyala
if (!is_db_connected()) {
    set_flash('warning', 'Database offline. Review kamu belum dapat disimpan saat ini.');
    header("Location: exchange-detail.php?id=$exchange_id");
    exit;
}

// Cek apakah pertukaran memang berstatus completed dan user adalah salah satu partisipannya
$stmt = $conn->prepare("
    SELECT id, status, requester_id, receiver_id 
    FROM exchanges 
    WHERE id = ? AND (requester_id = ? OR receiver_id = ?)
    LIMIT 1
");
$stmt->bind_param("iii", $exchange_id, $user_id, $user_id);
$stmt->execute();
$exch = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exch || $exch['status'] !== 'completed') {
    set_flash('danger', 'Kamu hanya bisa menulis review jika sesi sudah berstatus selesai!');
    header("Location: exchange-detail.php?id=$exchange_id");
    exit;
}

// Cek apakah user udah pernah nulis review di exchange ini (biar ga spamming)
$chk = $conn->prepare("SELECT id FROM reviews WHERE exchange_id = ? AND reviewer_id = ? LIMIT 1");
$chk->bind_param("ii", $exchange_id, $user_id);
$chk->execute();
if ($chk->get_result()->fetch_assoc()) {
    $chk->close();
    set_flash('info', 'Kamu sudah pernah mengirimkan review untuk sesi ini.');
    header("Location: exchange-detail.php?id=$exchange_id#review");
    exit;
}
$chk->close();

// Simpan review ke database
$ins = $conn->prepare("INSERT INTO reviews (exchange_id, reviewer_id, reviewee_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$ins->bind_param("iiiis", $exchange_id, $user_id, $reviewee_id, $rating, $comment);

if ($ins->execute()) {
    $ins->close();

    // 1. Reward reviewer +5 poin karena aktif ngasih feedback
    add_points($user_id, 5, 'review_bonus', 'Bonus menulis ulasan untuk partner sesi', $exchange_id);

    // 2. Kalo dapet bintang 5 (sempurna!), kasih bonus apresiasi +10 poin ke partner
    if ($rating === 5) {
        add_points($reviewee_id, 10, 'five_star_bonus', 'Apresiasi rating 5 bintang dari partner', $exchange_id);
    }

    // 3. Update rata-rata rating reviewee di tabel users
    $upd_stmt = $conn->prepare("
        UPDATE users 
        SET rating = (SELECT ROUND(AVG(rating), 2) FROM reviews WHERE reviewee_id = ?) 
        WHERE id = ?
    ");
    $upd_stmt->bind_param("ii", $reviewee_id, $reviewee_id);
    $upd_stmt->execute();
    $upd_stmt->close();

    // 4. Kirim notifikasi manis ke reviewee
    $stars_str = str_repeat('★', $rating);
    add_notification(
        $reviewee_id, 
        $user['name'] . " memberimu ulasan $stars_str: \"" . mb_strimwidth($comment, 0, 45, '...') . "\"", 
        "exchange-detail.php?id=$exchange_id#review"
    );

    set_flash('success', 'Ulasanmu berhasil dikirim! Kamu mendapatkan +5 Poin atas kontribusimu.');
} else {
    $ins->close();
    set_flash('danger', 'Gagal menyimpan ulasan. Silakan coba beberapa saat lagi.');
}

header("Location: exchange-detail.php?id=$exchange_id#review");
exit;
