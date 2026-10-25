<?php
// ==============================================================
// admin/header.php
// navbar & template layout khusus admin panel TukarSkill
// ==============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin(); // satpam admin: cuma role admin yang boleh masuk!

$admin = current_user();
$current_admin_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' — Admin TukarSkill' : 'Admin Panel — TukarSkill' ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚙️</text></svg>">
</head>
<body style="background: #f8fafc; min-height: 100vh; display: flex; flex-direction: column;">

<!-- Alert DB kalo offline -->
<?php if (!is_db_connected()): ?>
    <div style="background: #fef2f2; color: #991b1b; padding: 12px 20px; font-size: 13px; text-align: center; border-bottom: 1px solid #fecaca; font-weight: 600;">
        ⚠️ <?= e($db_error_message) ?>
    </div>
<?php endif; ?>

<!-- Flash Toast -->
<div class="toast-container">
    <?php $flash = get_flash(); if ($flash): ?>
        <div class="toast toast-<?= e($flash['type']) ?>">
            <span><?= ($flash['type'] === 'success') ? '✅' : (($flash['type'] === 'danger') ? '❌' : 'ℹ️') ?></span>
            <div><?= e($flash['message']) ?></div>
        </div>
    <?php endif; ?>
</div>

<!-- TOPBAR ADMIN -->
<header style="background: #0f172a; border-bottom: 1px solid #1e293b; padding: 14px 24px; position: sticky; top: 0; z-index: 100;">
    <div style="max-width: 1280px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
        
        <!-- Logo & Admin Badge -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="index.php" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: white;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #3b82f6; display: flex; align-items: center; justify-content: center; color: white;">
                    <?= icon('handshake', '', '18px') ?>
                </div>
                <span style="font-size: 18px; font-weight: 800; color: white;">
                    Tukar<span style="color: #60a5fa;">Skill</span>
                </span>
            </a>
            <span style="background: #eab308; color: #713f12; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 999px; text-transform: uppercase;">
                Admin Mode
            </span>
        </div>

        <!-- Menu Navigasi Admin -->
        <nav style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
            <a href="index.php" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; color: <?= ($current_admin_page === 'index.php') ? '#ffffff' : '#94a3b8' ?>; background: <?= ($current_admin_page === 'index.php') ? '#1e293b' : 'transparent' ?>;">
                <?= icon('bar-chart', '', '14px') ?> Dashboard
            </a>
            <a href="users.php" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; color: <?= ($current_admin_page === 'users.php') ? '#ffffff' : '#94a3b8' ?>; background: <?= ($current_admin_page === 'users.php') ? '#1e293b' : 'transparent' ?>;">
                <?= icon('users', '', '14px') ?> Pengguna
            </a>
            <a href="categories.php" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; color: <?= ($current_admin_page === 'categories.php') ? '#ffffff' : '#94a3b8' ?>; background: <?= ($current_admin_page === 'categories.php') ? '#1e293b' : 'transparent' ?>;">
                <?= icon('folder', '', '14px') ?> Kategori
            </a>
            <a href="skills.php" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; color: <?= ($current_admin_page === 'skills.php') ? '#ffffff' : '#94a3b8' ?>; background: <?= ($current_admin_page === 'skills.php') ? '#1e293b' : 'transparent' ?>;">
                <?= icon('lightbulb', '', '14px') ?> Katalog Skill
            </a>
            <a href="reviews.php" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; color: <?= ($current_admin_page === 'reviews.php') ? '#ffffff' : '#94a3b8' ?>; background: <?= ($current_admin_page === 'reviews.php') ? '#1e293b' : 'transparent' ?>;">
                <?= icon('star', '', '14px') ?> Moderasi Ulasan
            </a>
        </nav>

        <!-- Sisi Kanan: Kembali ke App & Info Admin -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="../dashboard.php" style="padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; color: #38bdf8; text-decoration: none; border: 1px solid #0284c7; background: rgba(56, 189, 248, 0.1);">
                ← Web
            </a>
            <div style="display: flex; align-items: center; gap: 8px; color: white;">
                <?= render_avatar($admin['photo'], $admin['name'], 28) ?>
                <span style="font-size: 12px; font-weight: 700;"><?= e(explode(' ', $admin['name'])[0]) ?></span>
            </div>
            <a href="../logout.php" style="display: inline-flex; align-items: center; gap: 4px; color: #ef4444; font-size: 12px; font-weight: 700; text-decoration: none;" title="Keluar">
                <?= icon('log-out', '', '13px') ?> Keluar
            </a>
        </div>

    </div>
</header>

<main style="flex: 1; padding: 32px 16px;">
