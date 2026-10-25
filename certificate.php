<?php
// ==============================================================
// certificate.php
// sertifikat resmi bukti pertukaran skill & kontribusi SDG 4
// bisa dicetak atau diunduh sebagai PDF oleh kedua mahasiswa
// ==============================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login(); // satpam halaman: cuma user login yang boleh akses

$user = current_user();
$user_id = (int)$user['id'];

$exchange_id = (int)($_GET['id'] ?? 0);
if ($exchange_id <= 0) {
    set_flash('danger', 'ID pertukaran tidak valid.');
    header('Location: exchanges.php');
    exit;
}

// ambil data pertukaran dari database
$exchange = null;
if (is_db_connected()) {
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            u_req.name AS req_name, u_req.nim AS req_nim, u_req.major AS req_major, u_req.photo AS req_photo,
            u_rec.name AS rec_name, u_rec.nim AS rec_nim, u_rec.major AS rec_major, u_rec.photo AS rec_photo,
            s_req.name AS req_skill_name,
            s_off.name AS off_skill_name
        FROM exchanges e
        JOIN users u_req ON e.requester_id = u_req.id
        JOIN users u_rec ON e.receiver_id = u_rec.id
        JOIN skills s_req ON e.requested_skill_id = s_req.id
        LEFT JOIN skills s_off ON e.offered_skill_id = s_off.id
        WHERE e.id = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $exchange_id);
        $stmt->execute();
        $exchange = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$exchange) {
    set_flash('danger', 'Data pertukaran tidak ditemukan.');
    header('Location: exchanges.php');
    exit;
}

// pastikan user yang buka adalah salah satu partisipan atau admin
if ($user['role'] !== 'admin' && (int)$exchange['requester_id'] !== $user_id && (int)$exchange['receiver_id'] !== $user_id) {
    set_flash('danger', 'Kamu tidak memiliki izin untuk melihat sertifikat pertukaran ini.');
    header('Location: exchanges.php');
    exit;
}

// cuma sesi completed yang berhak dapet sertifikat
if ($exchange['status'] !== 'completed') {
    set_flash('info', 'Sertifikat baru dapat diunduh setelah sesi pertukaran selesai dan diverifikasi kedua pihak.');
    header('Location: exchange-detail.php?id=' . $exchange_id);
    exit;
}

// bikin nomor seri sertifikat & kode hash verifikasi unik
$cert_number = 'TS-SDG4-' . date('Y', strtotime($exchange['completed_at'] ?? $exchange['created_at'])) . '-' . str_pad($exchange_id, 5, '0', STR_PAD_LEFT);
$verify_hash = strtoupper(substr(hash('sha256', $cert_number . '|' . $exchange['requester_id'] . '|' . $exchange['receiver_id'] . '|TUKARSKILL_SALT'), 0, 16));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Barter Skill — <?= e($cert_number) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Gaya khusus print & tampilan sertifikat */
        body {
            background: #f1f5f9;
            color: #0f172a;
            font-family: var(--font-sans);
            margin: 0;
            padding: 30px 16px;
        }

        .cert-actions {
            max-width: 960px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .cert-paper {
            max-width: 960px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.12);
            padding: 48px 56px;
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        /* Border dekoratif formal sertifikat */
        .cert-inner-frame {
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 36px 40px;
            position: relative;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
        }

        .cert-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            opacity: 0.035;
            pointer-events: none;
            width: 420px;
        }

        .cert-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 24px;
            margin-bottom: 28px;
        }

        .sdg-badge-box {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 8px 16px;
            border-radius: 10px;
        }

        .cert-title-group {
            text-align: center;
            margin-bottom: 30px;
        }

        .cert-main-title {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 1px;
            color: #0f172a;
            margin: 0 0 6px;
            text-transform: uppercase;
        }

        .cert-subtitle {
            font-size: 13px;
            font-weight: 700;
            color: #2563eb;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .participant-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 22px;
            flex: 1;
        }

        .seal-circle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: radial-gradient(circle, #fef08a 0%, #eab308 100%);
            border: 4px double #ca8a04;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(234, 179, 8, 0.35);
            color: #713f12;
            text-align: center;
            margin: 0 auto;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .cert-actions {
                display: none !important;
            }
            .cert-paper {
                box-shadow: none !important;
                border: none !important;
                padding: 10mm !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            @page {
                size: A4 landscape;
                margin: 8mm;
            }
        }
    </style>
</head>
<body>

    <!-- Tombol Aksi di Layar Komputer (Disembunyikan saat Print) -->
    <div class="cert-actions">
        <a href="exchange-detail.php?id=<?= $exchange_id ?>" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <?= icon('arrow-left', '', '14px') ?> Kembali ke Rincian Sesi
        </a>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <?= icon('award', '', '16px') ?> Cetak / Unduh PDF
            </button>
        </div>
    </div>

    <!-- Lembar Fisik Sertifikat -->
    <div class="cert-paper">
        <div class="cert-inner-frame">
            
            <!-- Watermark Lambang TukarSkill di Latar Belakang -->
            <svg class="cert-watermark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>

            <!-- KOP ATAS: Brand TukarSkill & SDG 4 -->
            <div class="cert-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center;">
                        <?= icon('handshake', '', '26px') ?>
                    </div>
                    <div>
                        <strong style="font-size: 18px; font-weight: 900; color: #0f172a; display: block; letter-spacing: -0.5px;">
                            TUKAR<span style="color: #2563eb;">SKILL</span>
                        </strong>
                        <span style="font-size: 11px; color: #64748b; font-weight: 600;">Platform Pertukaran Kemampuan Mahasiswa</span>
                    </div>
                </div>

                <div class="sdg-badge-box">
                    <div style="width: 32px; height: 32px; border-radius: 6px; background: #c21926; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 14px;">
                        4
                    </div>
                    <div>
                        <strong style="font-size: 12px; color: #15803d; display: block;">SDG 4: Quality Education</strong>
                        <span style="font-size: 10px; color: #166534;">Target 4.4 Keterampilan Kerja</span>
                    </div>
                </div>
            </div>

            <!-- JUDUL RESMI SERTIFIKAT -->
            <div class="cert-title-group">
                <span class="cert-subtitle">Certificate of Peer-to-Peer Learning</span>
                <h1 class="cert-main-title">Sertifikat Bukti Pertukaran Skill</h1>
                <p style="font-size: 12px; color: #64748b; margin: 4px 0 0;">
                    Nomor Sertifikat: <strong style="color: #0f172a;"><?= e($cert_number) ?></strong>
                </p>
            </div>

            <!-- NARASI PENGESAHAN -->
            <p style="text-align: center; font-size: 14px; color: #334155; line-height: 1.7; max-width: 740px; margin: 0 auto 28px;">
                Diberikan sebagai bukti resmi pengakuan bahwa kedua mahasiswa di bawah ini telah berhasil menyelesaikan sesi pembelajaran kolaboratif dua arah (peer-to-peer exchange), saling mentransfer ilmu secara beretika, dan berkontribusi langsung pada pencapaian <strong>SDG 4 — Pendidikan Berkualitas</strong>.
            </p>

            <!-- KARTU DUA PARTISIPAN MAHASISWA -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px;">
                
                <!-- Mahasiswa A (Requester) -->
                <div class="participant-card">
                    <span style="font-size: 10px; font-weight: 800; color: #2563eb; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">
                        Partisipan 1 (Pengaju)
                    </span>
                    <strong style="font-size: 16px; color: #0f172a; display: block;"><?= e($exchange['req_name']) ?></strong>
                    <span style="font-size: 12px; color: #64748b; display: block; margin-top: 2px;">
                        NIM: <?= e($exchange['req_nim']) ?> • <?= e($exchange['req_major']) ?>
                    </span>
                    <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 12px; color: #334155;">
                        <?php if ($exchange['type'] === 'swap'): ?>
                            <strong>Mengajarkan:</strong> <span style="color: #16a34a; font-weight: 700;"><?= e($exchange['off_skill_name']) ?></span>
                        <?php else: ?>
                            <strong>Metode:</strong> <span style="color: #2563eb; font-weight: 700;">Belajar Melalui Sistem 15 Skill Points</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mahasiswa B (Receiver) -->
                <div class="participant-card">
                    <span style="font-size: 10px; font-weight: 800; color: #16a34a; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">
                        Partisipan 2 (Mitra Belajar)
                    </span>
                    <strong style="font-size: 16px; color: #0f172a; display: block;"><?= e($exchange['rec_name']) ?></strong>
                    <span style="font-size: 12px; color: #64748b; display: block; margin-top: 2px;">
                        NIM: <?= e($exchange['rec_nim']) ?> • <?= e($exchange['rec_major']) ?>
                    </span>
                    <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 12px; color: #334155;">
                        <strong>Mengajarkan:</strong> <span style="color: #2563eb; font-weight: 700;"><?= e($exchange['requested_skill_name']) ?></span>
                    </div>
                </div>

            </div>

            <!-- RINCIAN SESI & DIGITAL STAMP -->
            <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 24px; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 22px;">
                
                <!-- Info Waktu & Verifikasi Integritas -->
                <div style="font-size: 12px; color: #64748b; line-height: 1.6;">
                    <div><strong>Waktu Selesai:</strong> <?= date('d F Y, H:i', strtotime($exchange['completed_at'] ?? $exchange['scheduled_at'])) ?> WIB</div>
                    <div><strong>Format Sesi:</strong> <?= ucfirst(e($exchange['mode'])) ?> <?= !empty($exchange['location']) ? ' (' . e($exchange['location']) . ')' : '' ?></div>
                    <div><strong>Status Verifikasi:</strong> <span style="color: #15803d; font-weight: 700;">Mutual Confirmed (Dua Arah Tervalidasi)</span></div>
                    <div style="margin-top: 6px; font-family: monospace; font-size: 11px; color: #475569;">
                        Digital Hash: <strong><?= e($verify_hash) ?></strong>
                    </div>
                </div>

                <!-- Cap Stempel Resmi Emas TukarSkill -->
                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 20px;">
                    <div style="text-align: center;">
                        <div class="seal-circle">
                            <span style="font-size: 9px; font-weight: 800; letter-spacing: 0.5px;">TUKARSKILL</span>
                            <span style="font-size: 18px; line-height: 1; margin: 2px 0;">★</span>
                            <span style="font-size: 8px; font-weight: 700; text-transform: uppercase;">VERIFIED</span>
                        </div>
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; display: block; margin-top: 6px;">Segel Digital Resmi</span>
                    </div>

                    <!-- Mockup QR Code Vector SVG -->
                    <div style="text-align: center;">
                        <svg width="68" height="68" viewBox="0 0 33 33" fill="#0f172a" style="border: 2px solid #e2e8f0; border-radius: 8px; padding: 4px; background: #fff;">
                            <path d="M0 0h11v11H0zm2 2h7v7H2zM4 4h3v3H4zM22 0h11v11H22zm2 2h7v7h-7zM26 4h3v3h-3zM0 22h11v11H0zm2 2h7v7H2zM4 26h3v3H4zM14 0h3v5h-3zm5 0h3v3h-3zm-5 7h3v4h-3zm5-2h3v6h-3zm-5 8h3v3h-3zm5 0h3v5h-3zm3 0h3v3h-3zm-8 5h3v4h-3zm3 0h3v6h-3zm11 0h3v3h-3zm-6 2h3v4h-3zm6 3h3v3h-3zm0 4h3v2h-3zm-3-1h3v2h-3zm-6 0h3v2h-3zm-3 0h3v2h-3z"/>
                        </svg>
                        <span style="font-size: 9px; color: #94a3b8; display: block; margin-top: 4px;">Pindai Validasi</span>
                    </div>
                </div>

            </div>

        </div>
    </div>

</body>
</html>
