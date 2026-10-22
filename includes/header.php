<?php
// ==============================================================
// includes/header.php
// template header & navbar atas yang dipake di seluruh halaman
// ==============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
$logged_user = current_user();
$unread_notifs = $logged_user ? get_unread_notifications_count($logged_user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' — TukarSkill' : 'TukarSkill — Tukar Skill. Dapat Skill.' ?></title>
    
    <!-- Meta tag biar SEO & preview medsos cakep -->
    <meta name="description" content="TukarSkill adalah platform pertukaran kemampuan antar mahasiswa berbasis peer-to-peer tanpa biaya. Dukung SDG 4 Quality Education!">
    
    <!-- CSS Utama & Font Inter -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon sederhana berbasis emoji SVG -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🤝</text></svg>">
</head>
<body>

<!-- Peringatan kalo database XAMPP belum aktif atau belum diimport -->
<?php if (!is_db_connected()): ?>
    <div style="background: #fef2f2; color: #991b1b; padding: 12px 20px; font-size: 13px; text-align: center; border-bottom: 1px solid #fecaca; font-weight: 600;">
        ⚠️ <?= e($db_error_message) ?>
    </div>
<?php endif; ?>

<!-- Container Notifikasi Pop-up (Flash Message) -->
<div class="toast-container">
    <?php $flash = get_flash(); if ($flash): ?>
        <div class="toast toast-<?= e($flash['type']) ?>">
            <span><?= ($flash['type'] === 'success') ? '✅' : (($flash['type'] === 'danger') ? '❌' : 'ℹ️') ?></span>
            <div><?= e($flash['message']) ?></div>
        </div>
    <?php endif; ?>
</div>

<!-- Navbar Mengambang (Pill Style ala LearnNix) -->
<header class="navbar">
    <nav class="nav-pill">
        <!-- Logo Brand -->
        <a href="index.php" class="brand-logo">
            <div class="logo-icon">🤝</div>
            <span>Tukar<strong>Skill</strong></span>
        </a>

        <!-- Menu Navigasi Tengah (Bersih, Rapi & Tidak Menumpuk) -->
        <ul class="nav-links">
            <li><a href="index.php" class="<?= ($current_page === 'index.php') ? 'active' : '' ?>">Beranda</a></li>
            <li><a href="discover.php" class="<?= ($current_page === 'discover.php') ? 'active' : '' ?>">Cari Skill</a></li>
            <?php if (is_logged_in()): ?>
                <li><a href="skillmatch.php" class="<?= ($current_page === 'skillmatch.php') ? 'active' : '' ?>">SkillMatch</a></li>
                <li><a href="exchanges.php" class="<?= ($current_page === 'exchanges.php') ? 'active' : '' ?>">Barter Saya</a></li>
                <li><a href="leaderboard.php" class="<?= ($current_page === 'leaderboard.php') ? 'active' : '' ?>">Leaderboard</a></li>
            <?php else: ?>
                <li><a href="leaderboard.php" class="<?= ($current_page === 'leaderboard.php') ? 'active' : '' ?>">Leaderboard</a></li>
                <li><a href="index.php#cara-kerja">Cara Kerja</a></li>
                <li><a href="index.php#faq">FAQ</a></li>
            <?php endif; ?>
        </ul>

        <!-- Aksi Kanan: Tombol Masuk / Profil Pengguna -->
        <div class="nav-actions">
            <?php if (is_logged_in() && $logged_user): ?>
                
                <?php if ($logged_user['role'] === 'admin'): ?>
                    <a href="admin/index.php" class="btn btn-outline btn-sm" style="background: #fef9c3; border-color: #fde047; color: #854d0e; font-weight: 700; padding: 6px 10px; font-size: 12px;" title="Buka Panel Admin">
                        ⚙️ Admin
                    </a>
                <?php endif; ?>

                <!-- Lonceng Notifikasi In-App -->
                <a href="notifications.php" class="btn btn-outline btn-sm" style="padding: 6px 9px; position: relative;" title="Notifikasi">
                    🔔
                    <?php if ($unread_notifs > 0): ?>
                        <span style="position: absolute; top: -4px; right: -4px; background: #ef4444; color: white; border-radius: 999px; font-size: 10px; font-weight: 800; padding: 1px 5px; min-width: 15px; text-align: center; line-height: 1.2;">
                            <?= $unread_notifs > 99 ? '99+' : $unread_notifs ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Kalo udah login, tampilin info ringkas profil & poin -->
                <a href="dashboard.php" class="user-menu-pill" title="Buka Dashboard">
                    <div class="avatar">
                        <?= strtoupper(substr($logged_user['name'], 0, 1)) ?>
                    </div>
                    <span style="font-weight: 700; font-size: 13px;"><?= e(explode(' ', $logged_user['name'])[0]) ?></span>
                    <span class="points-badge" title="Saldo Poin Aktif">🪙 <?= (int)$logged_user['points'] ?></span>
                </a>
                
                <a href="logout.php" class="btn btn-outline btn-sm" style="padding: 6px 10px; font-size: 12.5px; color: var(--gray-600);" title="Keluar akun">Keluar</a>
            <?php else: ?>
                <!-- Kalo belom login, tampilin tombol Masuk dan Daftar -->
                <a href="login.php" class="btn btn-outline btn-sm">Masuk</a>
                <a href="register.php" class="btn btn-primary btn-sm">
                    Daftar <span class="btn-arrow">↗</span>
                </a>
            <?php endif; ?>

            <!-- Tombol Hamburger buat HP -->
            <button class="mobile-toggle" aria-label="Toggle Navigation">☰</button>
        </div>
    </nav>
</header>
