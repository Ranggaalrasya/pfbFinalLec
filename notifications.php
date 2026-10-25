<?php
// ==============================================================
// notifications.php
// pusat notifikasi in-app mahasiswa (proposal masuk, status barter, review, dll)
// ==============================================================

$page_title = "Pusat Notifikasi";
require_once __DIR__ . '/includes/header.php';

require_login(); // satpam halaman: wajib login bro

$user = current_user();
$user_id = (int)$user['id'];

// Aksi tandai semua sudah dibaca
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    verify_csrf();
    if (is_db_connected()) {
        $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
        set_flash('success', 'Semua notifikasi telah ditandai sebagai sudah dibaca.');
    }
    header('Location: notifications.php');
    exit;
}

// Aksi klik satu notifikasi untuk buka link & tandai read
if (isset($_GET['open'])) {
    $notif_id = (int)$_GET['open'];
    if (is_db_connected()) {
        $st = $conn->prepare("SELECT link FROM notifications WHERE id = ? AND user_id = ? LIMIT 1");
        $st->bind_param("ii", $notif_id, $user_id);
        $st->execute();
        $res = $st->get_result()->fetch_assoc();
        $st->close();

        if ($res) {
            $conn->query("UPDATE notifications SET is_read = 1 WHERE id = $notif_id");
            $target_url = !empty($res['link']) ? $res['link'] : 'notifications.php';
            header("Location: $target_url");
            exit;
        }
    }
}

// Ambil daftar notifikasi user
$notifications = [];
if (is_db_connected()) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
    }
}
?>

<div class="container" style="padding: 40px 16px 80px; max-width: 800px;">

    <!-- HEADER TITLE & TOMBOL MARK ALL READ -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; gap: 16px; flex-wrap: wrap;">
        <div>
            <span class="badge-tag" style="background: var(--blue-100); color: var(--blue-600); margin-bottom: 8px; display: inline-flex; align-items: center; gap: 6px;">
                <?= icon('bell', '', '14px') ?> Notifikasi & Aktivitas
            </span>
            <h1 style="font-size: 26px; font-weight: 800; color: var(--ink);">
                Pusat Pemberitahuan
            </h1>
            <p style="font-size: 13px; color: var(--gray-600); margin-top: 4px;">
                Update status proposal, konfirmasi sesi belajar, poin reward, dan ulasan partner.
            </p>
        </div>

        <?php if (!empty($notifications)): ?>
            <form action="notifications.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                    <?= icon('check', '', '14px') ?> Tandai Semua Dibaca
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- LIST NOTIFIKASI -->
    <div style="background: var(--white); border-radius: var(--radius-xl); border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm); overflow: hidden;">
        <?php if (empty($notifications)): ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--gray-400);">
                <div style="color: var(--gray-400); margin-bottom: 12px; display: flex; justify-content: center;">
                    <?= icon('bell', '', '44px') ?>
                </div>
                <strong style="font-size: 16px; color: var(--ink); display: block; margin-bottom: 4px;">
                    Kotak Notifikasimu Masih Bersih
                </strong>
                <p style="font-size: 13px; margin-bottom: 20px;">
                    Belum ada pemberitahuan baru saat ini. Coba ajukan pertukaran skill ke mahasiswa lain yuk!
                </p>
                <a href="discover.php" class="btn btn-primary btn-sm">Cari Partner Belajar ↗</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column;">
                <?php foreach ($notifications as $n): 
                    $is_unread = ((int)$n['is_read'] === 0);
                    $target_link = "notifications.php?open=" . (int)$n['id'];
                ?>
                    <a href="<?= e($target_link) ?>" style="display: flex; align-items: flex-start; gap: 16px; padding: 20px 24px; border-bottom: 1px solid var(--gray-100); text-decoration: none; background: <?= $is_unread ? '#f0f9ff' : 'var(--white)' ?>; transition: 0.15s ease;">
                        
                        <!-- Icon Notif -->
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $is_unread ? 'var(--blue-500)' : 'var(--gray-200)' ?>; color: <?= $is_unread ? 'var(--white)' : 'var(--gray-600)' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <?= $is_unread ? icon('bell', '', '18px') : icon('mail', '', '18px') ?>
                        </div>

                        <!-- Konten Pesan -->
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <strong style="font-size: 14px; color: var(--ink);">
                                    <?= $is_unread ? '<span style="display:inline-block; width:8px; height:8px; background:var(--blue-500); border-radius:50%; margin-right:6px;"></span>' : '' ?>
                                    Pemberitahuan Sistem
                                </strong>
                                <span style="font-size: 11px; color: var(--gray-400);">
                                    <?= date('d M Y, H:i', strtotime($n['created_at'])) ?>
                                </span>
                            </div>

                            <p style="font-size: 13px; color: <?= $is_unread ? 'var(--ink)' : 'var(--gray-600)' ?>; margin: 0; line-height: 1.5; font-weight: <?= $is_unread ? '600' : '400' ?>;">
                                <?= e($n['message']) ?>
                            </p>
                        </div>

                        <span style="color: var(--gray-400); font-size: 14px;">→</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
