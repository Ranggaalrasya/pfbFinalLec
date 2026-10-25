<?php
// ==============================================================
// admin/reviews.php
// moderasi ulasan & rating pertukaran skill antar mahasiswa
// pantau komentar spam / tidak pantas & aksi hapus ulasan
// ==============================================================

$page_title = "Moderasi Ulasan";
require_once __DIR__ . '/header.php';

// --------------------------------------------------------------
// PROSES AKSI HAPUS ULASAN
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_db_connected()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $review_id = (int)($_POST['review_id'] ?? 0);

    if ($action === 'delete' && $review_id > 0) {
        // Ambil reviewee_id sebelum dihapus buat recalculate rating
        $q_chk = $conn->query("SELECT reviewee_id FROM reviews WHERE id = $review_id LIMIT 1");
        if ($q_chk && $row = $q_chk->fetch_assoc()) {
            $reviewee_id = (int)$row['reviewee_id'];

            // Hapus review
            $conn->query("DELETE FROM reviews WHERE id = $review_id");

            // Hitung ulang rata-rata rating mahasiswa bersangkutan
            $conn->query("
                UPDATE users 
                SET rating = COALESCE((SELECT ROUND(AVG(rating), 2) FROM reviews WHERE reviewee_id = $reviewee_id), 0.0) 
                WHERE id = $reviewee_id
            ");

            set_flash('success', "Ulasan #$review_id berhasil dihapus dan rating mahasiswa telah disesuaikan kembali.");
        }
    }

    header('Location: reviews.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// --------------------------------------------------------------
// FILTER & SEARCH QUERY ULASAN
// --------------------------------------------------------------
$q = trim($_GET['q'] ?? '');
$star_filter = (int)($_GET['rating'] ?? 0);

$reviews = [];
if (is_db_connected()) {
    $where_clauses = ["1=1"];
    $types = "";
    $params = [];

    if (!empty($q)) {
        $where_clauses[] = "(r.comment LIKE ? OR u_rev.name LIKE ? OR u_ree.name LIKE ?)";
        $search_wild = "%$q%";
        $types .= "sss";
        $params[] = $search_wild;
        $params[] = $search_wild;
        $params[] = $search_wild;
    }

    if ($star_filter >= 1 && $star_filter <= 5) {
        $where_clauses[] = "r.rating = ?";
        $types .= "i";
        $params[] = $star_filter;
    }

    $where_sql = implode(" AND ", $where_clauses);
    $sql = "
        SELECT 
            r.*,
            u_rev.name AS reviewer_name, u_rev.major AS reviewer_major,
            u_ree.name AS reviewee_name, u_ree.major AS reviewee_major,
            e.id AS exchange_id, e.type AS exchange_type
        FROM reviews r
        JOIN users u_rev ON r.reviewer_id = u_rev.id
        JOIN users u_ree ON r.reviewee_id = u_ree.id
        LEFT JOIN exchanges e ON r.exchange_id = e.id
        WHERE $where_sql
        ORDER BY r.created_at DESC
        LIMIT 100
    ";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $reviews[] = $row;
    }
    $stmt->close();
}
?>

<div style="max-width: 1280px; margin: 0 auto;">

    <!-- HEADER TITLE -->
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 26px; font-weight: 800; color: #0f172a;">
            Moderasi Ulasan Komunitas (<?= count($reviews) ?>)
        </h1>
        <p style="font-size: 13px; color: #64748b; margin-top: 4px;">
            Jaga ekosistem belajar yang sehat, suportif, dan bebas dari kata-kata kasar atau ulasan spam.
        </p>
    </div>

    <!-- FILTER BAR -->
    <div style="background: white; border-radius: 12px; padding: 16px 20px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
        <form action="reviews.php" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap;">
            
            <div style="flex: 2; min-width: 200px;">
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari isi ulasan atau nama mahasiswa..." 
                       style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none;">
            </div>

            <div style="flex: 1; min-width: 150px;">
                <select name="rating" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; background: white;">
                    <option value="0">Semua Rating Bintang</option>
                    <option value="5" <?= ($star_filter === 5) ? 'selected' : '' ?>>★★★★★ (5 Bintang)</option>
                    <option value="4" <?= ($star_filter === 4) ? 'selected' : '' ?>>★★★★☆ (4 Bintang)</option>
                    <option value="3" <?= ($star_filter === 3) ? 'selected' : '' ?>>★★★☆☆ (3 Bintang)</option>
                    <option value="2" <?= ($star_filter === 2) ? 'selected' : '' ?>>★★☆☆☆ (2 Bintang)</option>
                    <option value="1" <?= ($star_filter === 1) ? 'selected' : '' ?>>★☆☆☆☆ (1 Bintang)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">
                Filter
            </button>
            <?php if (!empty($q) || $star_filter > 0): ?>
                <a href="reviews.php" class="btn btn-outline" style="padding: 10px 16px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABEL LIST REVIEW -->
    <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: var(--shadow-sm); overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 12px; text-transform: uppercase;">
                        <th style="padding: 14px 16px; width: 60px;">ID</th>
                        <th style="padding: 14px 16px;">Penulis (Reviewer)</th>
                        <th style="padding: 14px 16px;">Penerima (Reviewee)</th>
                        <th style="padding: 14px 16px; width: 100px;">Rating</th>
                        <th style="padding: 14px 16px;">Komentar Ulasan</th>
                        <th style="padding: 14px 16px; width: 130px;">Waktu</th>
                        <th style="padding: 14px 16px; text-align: center; width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="7" style="padding: 40px; text-align: center; color: #94a3b8;">
                                Belum ada ulasan yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $rv): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 14px 16px; color: #94a3b8; font-weight: 700;">
                                    #<?= $rv['id'] ?>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <a href="../user.php?id=<?= $rv['reviewer_id'] ?>" target="_blank" style="font-weight: 700; color: #0f172a; text-decoration: none;">
                                        <?= e($rv['reviewer_name']) ?>
                                    </a>
                                    <span style="font-size: 11px; color: #64748b; display: block;"><?= e($rv['reviewer_major']) ?></span>
                                </td>
                                <td style="padding: 14px 16px;">
                                    <a href="../user.php?id=<?= $rv['reviewee_id'] ?>" target="_blank" style="font-weight: 700; color: #0f172a; text-decoration: none;">
                                        <?= e($rv['reviewee_name']) ?>
                                    </a>
                                    <span style="font-size: 11px; color: #64748b; display: block;"><?= e($rv['reviewee_major']) ?></span>
                                </td>
                                <td style="padding: 14px 16px; color: #eab308; font-size: 14px; white-space: nowrap;">
                                    <span style="display: inline-flex; align-items: center; gap: 2px;">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <?= icon('star', $s <= (int)$rv['rating'] ? 'text-star' : 'text-gray-300', '13px') ?>
                                        <?php endfor; ?>
                                    </span>
                                    <span style="font-size: 11px; color: #64748b; font-weight: 700; margin-left: 4px;">(<?= (int)$rv['rating'] ?>)</span>
                                </td>
                                <td style="padding: 14px 16px; color: #334155; line-height: 1.5; font-style: italic;">
                                    "<?= e($rv['comment']) ?>"
                                    <?php if (!empty($rv['exchange_id'])): ?>
                                        <div style="margin-top: 4px;">
                                            <a href="../exchange-detail.php?id=<?= $rv['exchange_id'] ?>" target="_blank" style="font-size: 11px; color: #2563eb; text-decoration: none; font-style: normal; display: inline-flex; align-items: center; gap: 4px;">
                                                <?= icon('external-link', '', '11px') ?> Sesi #<?= $rv['exchange_id'] ?> (<?= strtoupper(e($rv['exchange_type'])) ?>)
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 16px; color: #94a3b8; font-size: 12px; white-space: nowrap;">
                                    <?= date('d M Y, H:i', strtotime($rv['created_at'])) ?>
                                </td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <form action="reviews.php" method="POST" onsubmit="return confirm('Apakah kamu yakin ingin menghapus ulasan ini? Rating mahasiswa penerima akan dihitung ulang secara otomatis.');" style="margin: 0;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="review_id" value="<?= $rv['id'] ?>">
                                        <button type="submit" style="padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid #fecaca; background: #fee2e2; color: #991b1b; cursor: pointer;">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
