<?php
// ==============================================================
// my-skills.php
// halaman kelola skill yang BISA diajarkan & INGIN dipelajari + jadwal luang
// ==============================================================

$page_title = "Skill & Jadwal Saya";
require_once __DIR__ . '/includes/header.php';

require_login(); // satpam halaman: wajib login

$user = current_user();
$user_id = (int)$user['id'];

$success_msg = '';
$error_msg = '';

// --------------------------------------------------------------
// 1. TAMBAH SKILL BARU (Bisa / Ingin Belajar)
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_skill') {
    verify_csrf();

    $skill_id = (int)($_POST['skill_id'] ?? 0);
    $type = $_POST['type'] ?? 'offer'; // 'offer' (bisa) atau 'want' (ingin)
    $level = $_POST['level'] ?? 'menengah';
    $description = trim($_POST['description'] ?? '');

    if ($skill_id <= 0) {
        $error_msg = 'Pilih salah satu skill dari katalog yang tersedia.';
    } elseif (!in_array($type, ['offer', 'want'])) {
        $error_msg = 'Tipe skill tidak valid.';
    } elseif (!in_array($level, ['pemula', 'menengah', 'mahir'])) {
        $error_msg = 'Level skill tidak valid.';
    } elseif (is_db_connected()) {
        // Cek apakah user udah pernah nambahin skill yang sama di tipe yang sama
        $stmt_check = $conn->prepare("SELECT id FROM user_skills WHERE user_id = ? AND skill_id = ? AND type = ?");
        $stmt_check->bind_param("iis", $user_id, $skill_id, $type);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check && $res_check->num_rows > 0) {
            $error_msg = 'Kamu sudah pernah menambahkan skill ini sebelumnya.';
        } else {
            $stmt_ins = $conn->prepare("INSERT INTO user_skills (user_id, skill_id, type, level, description) VALUES (?, ?, ?, ?, ?)");
            if ($stmt_ins) {
                $stmt_ins->bind_param("iisss", $user_id, $skill_id, $type, $level, $description);
                if ($stmt_ins->execute()) {
                    $success_msg = 'Skill berhasil ditambahkan ke profilmu!';

                    // Cek apakah profil sekarang udah lengkap (ada bio, ada minimal 1 offer, ada minimal 1 want)
                    $q_user = $conn->query("SELECT bio, profile_bonus_given FROM users WHERE id = $user_id");
                    if ($q_user && $u_row = $q_user->fetch_assoc()) {
                        if ($u_row['profile_bonus_given'] == 0 && !empty($u_row['bio'])) {
                            $q_off = $conn->query("SELECT 1 FROM user_skills WHERE user_id = $user_id AND type = 'offer'");
                            $q_wnt = $conn->query("SELECT 1 FROM user_skills WHERE user_id = $user_id AND type = 'want'");
                            if ($q_off->num_rows > 0 && $q_wnt->num_rows > 0) {
                                add_points($user_id, 10, 'profile_complete', 'Bonus kelengkapan profil & skill pertama kali');
                                $conn->query("UPDATE users SET profile_bonus_given = 1 WHERE id = $user_id");
                                $success_msg .= ' 🎉 Selamat! Kamu dapet bonus +10 Poin kelengkapan profil!';
                            }
                        }
                    }
                } else {
                    $error_msg = 'Gagal menyimpan skill: ' . $conn->error;
                }
                $stmt_ins->close();
            }
        }
        $stmt_check->close();
    }
}

// --------------------------------------------------------------
// 2. HAPUS SKILL
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_skill') {
    verify_csrf();
    $user_skill_id = (int)($_POST['user_skill_id'] ?? 0);

    if ($user_skill_id > 0 && is_db_connected()) {
        $stmt_del = $conn->prepare("DELETE FROM user_skills WHERE id = ? AND user_id = ?");
        if ($stmt_del) {
            $stmt_del->bind_param("ii", $user_skill_id, $user_id);
            $stmt_del->execute();
            $stmt_del->close();
            $success_msg = 'Skill berhasil dihapus dari daftarmu.';
        }
    }
}

// --------------------------------------------------------------
// 3. TAMBAH JADWAL LUANG (Availability)
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_availability') {
    verify_csrf();

    $day = $_POST['day'] ?? 'senin';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    $allowed_days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    if (!in_array($day, $allowed_days)) {
        $error_msg = 'Hari tidak valid.';
    } elseif (empty($start_time) || empty($end_time)) {
        $error_msg = 'Jam mulai dan jam selesai wajib diisi.';
    } elseif (strtotime($end_time) <= strtotime($start_time)) {
        $error_msg = 'Jam selesai harus lebih akhir dari jam mulai dong.';
    } elseif (is_db_connected()) {
        $stmt_av = $conn->prepare("INSERT INTO availabilities (user_id, day, start_time, end_time) VALUES (?, ?, ?, ?)");
        if ($stmt_av) {
            $stmt_av->bind_param("isss", $user_id, $day, $start_time, $end_time);
            if ($stmt_av->execute()) {
                $success_msg = 'Jadwal ketersediaan berhasil ditambahkan!';
            } else {
                $error_msg = 'Gagal menyimpan jadwal: ' . $conn->error;
            }
            $stmt_av->close();
        }
    }
}

// --------------------------------------------------------------
// 4. HAPUS JADWAL LUANG
// --------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_availability') {
    verify_csrf();
    $avail_id = (int)($_POST['avail_id'] ?? 0);

    if ($avail_id > 0 && is_db_connected()) {
        $stmt_del_av = $conn->prepare("DELETE FROM availabilities WHERE id = ? AND user_id = ?");
        if ($stmt_del_av) {
            $stmt_del_av->bind_param("ii", $avail_id, $user_id);
            $stmt_del_av->execute();
            $stmt_del_av->close();
            $success_msg = 'Jadwal luang berhasil dihapus.';
        }
    }
}

// Ambil semua katalog master skill untuk opsi dropdown
$master_skills = [];
if (is_db_connected()) {
    $q_skills = $conn->query("
        SELECT s.id, s.name, c.name AS category_name 
        FROM skills s 
        JOIN categories c ON s.category_id = c.id 
        ORDER BY c.name ASC, s.name ASC
    ");
    if ($q_skills) {
        while ($row = $q_skills->fetch_assoc()) {
            $master_skills[$row['category_name']][] = $row;
        }
    }
}

// Ambil skill yang dimiliki user (Offer & Want)
$offer_skills = [];
$want_skills = [];

if (is_db_connected()) {
    $q_my = $conn->query("
        SELECT us.id, us.type, us.level, us.description, s.name AS skill_name, c.name AS category_name, c.icon AS category_icon
        FROM user_skills us
        JOIN skills s ON us.skill_id = s.id
        JOIN categories c ON s.category_id = c.id
        WHERE us.user_id = $user_id
        ORDER BY us.id DESC
    ");
    if ($q_my) {
        while ($row = $q_my->fetch_assoc()) {
            if ($row['type'] === 'offer') {
                $offer_skills[] = $row;
            } else {
                $want_skills[] = $row;
            }
        }
    }
}

// Ambil jadwal availability user
$my_availabilities = [];
if (is_db_connected()) {
    $q_av = $conn->query("SELECT * FROM availabilities WHERE user_id = $user_id ORDER BY FIELD(day, 'senin','selasa','rabu','kamis','jumat','sabtu','minggu'), start_time ASC");
    if ($q_av) {
        while ($row = $q_av->fetch_assoc()) {
            $my_availabilities[] = $row;
        }
    }
}
?>

<div class="container" style="padding: 40px 16px 80px;">

    <!-- Header Halaman -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 32px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: var(--ink);">Katalog Skill & Jadwal Saya</h1>
            <p style="font-size: 14px; color: var(--gray-600); margin-top: 4px;">
                Atur kemampuan yang bisa kamu ajarkan, skill yang ingin kamu pelajari, dan jam luangmu.
            </p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="dashboard.php" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <?= icon('arrow-left', '', '14px') ?> Dashboard
            </a>
            <a href="skillmatch.php" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <?= icon('sparkles', '', '14px') ?> Lihat SkillMatch →
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi Sukses / Gagal -->
    <?php if (!empty($success_msg)): ?>
        <div style="background: var(--mint); border-left: 4px solid #10b981; color: var(--mint-dark); padding: 14px 20px; border-radius: var(--radius-sm); margin-bottom: 24px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <?= icon('check-circle', '', '18px') ?>
            <span><?= e($success_msg) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div style="background: #fee2e2; border-left: 4px solid var(--danger); color: #991b1b; padding: 14px 20px; border-radius: var(--radius-sm); margin-bottom: 24px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;">
            <?= icon('alert-circle', '', '18px') ?>
            <span><?= e($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <!-- DUA KOLOM: Form Tambah Skill (Kiri) + Daftar Skill Saya (Kanan) -->
    <div style="display: grid; grid-template-columns: 1.1fr 1.9fr; gap: 28px; align-items: start; margin-bottom: 40px;">
        
        <!-- KOLOM KIRI: Form Tambah Skill -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
            <h3 style="font-size: 18px; font-weight: 800; color: var(--ink); margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
                <?= icon('plus', '', '18px') ?> Tambah Skill Baru
            </h3>

            <form action="my-skills.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_skill">

                <!-- Pilihan Tipe Skill (Bisa vs Ingin Belajar) -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px;">Tipe Skill</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label style="background: var(--blue-50); border: 1px solid var(--blue-400); padding: 10px; border-radius: var(--radius-md); font-size: 13px; font-weight: 700; color: var(--blue-600); cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <input type="radio" name="type" value="offer" checked>
                            <span>Bisa Diajarkan (Offer)</span>
                        </label>
                        <label style="background: var(--lime); border: 1px solid #d9f99d; padding: 10px; border-radius: var(--radius-md); font-size: 13px; font-weight: 700; color: var(--lime-dark); cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <input type="radio" name="type" value="want">
                            <span>Ingin Belajar (Want)</span>
                        </label>
                    </div>
                </div>

                <!-- Dropdown Pilih Skill dari Master Katalog -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Pilih Skill</label>
                    <select name="skill_id" required style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none; background: var(--white);">
                        <option value="">-- Pilih dari Katalog Keahlian --</option>
                        <?php foreach ($master_skills as $cat_name => $skills_in_cat): ?>
                            <optgroup label="<?= e($cat_name) ?>">
                                <?php foreach ($skills_in_cat as $sk): ?>
                                    <option value="<?= (int)$sk['id'] ?>"><?= e($sk['name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tingkat / Level Penguasaan -->
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">Tingkat Kemahiran</label>
                    <select name="level" style="width: 100%; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 14px; outline: none; background: var(--white);">
                        <option value="pemula">Pemula (Dasar / Fundamental)</option>
                        <option value="menengah" selected>Menengah (Bisa praktik mandiri)</option>
                        <option value="mahir">Mahir (Advanced / Siap bimbingan tugas)</option>
                    </select>
                </div>

                <!-- Catatan / Spesialisasi -->
                <div style="margin-bottom: 22px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px;">
                        Catatan Tambahan <small style="font-weight: 400; color: var(--gray-400);">(opsional)</small>
                    </label>
                    <textarea name="description" rows="2" placeholder="Contoh: Fokus di pembuatan portofolio, auto-layout Figma, atau latihan TOEFL listening..."
                              style="width: 100%; padding: 10px 14px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 13px; outline: none; resize: vertical;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Simpan Skill ke Profil <span class="btn-arrow">+</span>
                </button>
            </form>
        </div>

        <!-- KOLOM KANAN: Tab Skill yang Ditawarkan vs Skill yang Diincar -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            
            <!-- 1. Daftar Skill yang BISA Diajarkan (Offer) -->
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; color: var(--blue-600);">
                            Skill yang Bisa Diajarkan (<?= count($offer_skills) ?>)
                        </h3>
                        <p style="font-size: 12px; color: var(--gray-400);">Keahlian yang bisa kamu barterkan ke mahasiswa lain.</p>
                    </div>
                    <span class="badge-tag" style="background: var(--blue-100); color: var(--blue-600);">Guru / Mentor</span>
                </div>

                <?php if (empty($offer_skills)): ?>
                    <p style="font-size: 13px; color: var(--gray-400); font-style: italic; padding: 16px 0;">
                        Belum ada skill yang kamu tawarkan. Tambahkan skill di form sebelah kiri ya!
                    </p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($offer_skills as $os): ?>
                            <div style="padding: 14px 18px; background: var(--blue-50); border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="display: inline-flex; align-items: center; color: var(--blue-600);"><?= category_icon_svg($os['category_icon'], '16px') ?></span>
                                        <strong style="font-size: 14px; color: var(--ink);"><?= e($os['skill_name']) ?></strong>
                                        <span class="badge-tag" style="background: var(--white); color: var(--blue-600); font-size: 11px;">
                                            <?= ucfirst(e($os['level'])) ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($os['description'])): ?>
                                        <p style="font-size: 12px; color: var(--gray-600); margin-top: 4px;"><?= e($os['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <form action="my-skills.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus skill ini?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_skill">
                                    <input type="hidden" name="user_skill_id" value="<?= (int)$os['id'] ?>">
                                    <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer; display: inline-flex; align-items: center; padding: 6px; border-radius: var(--radius-sm);" title="Hapus">
                                        <?= icon('trash', '', '16px') ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2. Daftar Skill yang INGIN Dipelajari (Want) -->
            <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; color: #65a30d;">
                            Skill yang Ingin Dipelajari (<?= count($want_skills) ?>)
                        </h3>
                        <p style="font-size: 12px; color: var(--gray-400);">SkillMatch akan mencari partner yang menguasai skill ini.</p>
                    </div>
                    <span class="badge-tag" style="background: var(--lime); color: var(--lime-dark);">Murid / Learner</span>
                </div>

                <?php if (empty($want_skills)): ?>
                    <p style="font-size: 13px; color: var(--gray-400); font-style: italic; padding: 16px 0;">
                        Belum ada skill incaran. Tambahkan skill yang ingin kamu pelajari agar sistem bisa mencarikan partner!
                    </p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($want_skills as $ws): ?>
                            <div style="padding: 14px 18px; background: #f7fee7; border-radius: var(--radius-md); display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="display: inline-flex; align-items: center; color: #65a30d;"><?= category_icon_svg($ws['category_icon'], '16px') ?></span>
                                        <strong style="font-size: 14px; color: var(--ink);"><?= e($ws['skill_name']) ?></strong>
                                        <span class="badge-tag" style="background: var(--white); color: #65a30d; font-size: 11px;">
                                            Target: <?= ucfirst(e($ws['level'])) ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($ws['description'])): ?>
                                        <p style="font-size: 12px; color: var(--gray-600); margin-top: 4px;"><?= e($ws['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <form action="my-skills.php" method="POST" onsubmit="return confirm('Hapus skill incaran ini?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_skill">
                                    <input type="hidden" name="user_skill_id" value="<?= (int)$ws['id'] ?>">
                                    <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer; display: inline-flex; align-items: center; padding: 6px; border-radius: var(--radius-sm);" title="Hapus">
                                        <?= icon('trash', '', '16px') ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- SECTION BAWAH: JADWAL KETERSEDIAAN MINGGUAN (Availability) -->
    <div style="background: var(--white); border-radius: var(--radius-xl); padding: 36px; box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
            <div>
                <h3 style="font-size: 20px; font-weight: 800; color: var(--ink); display: flex; align-items: center; gap: 8px;">
                    <?= icon('calendar', '', '20px') ?> Jadwal Ketersediaan Barter
                </h3>
                <p style="font-size: 13px; color: var(--gray-600); margin-top: 2px;">
                    Tentukan hari dan jam luangmu di luar jam kuliah agar calon partner tahu kapan kamu bisa diajak sesi belajar.
                </p>
            </div>
        </div>

        <!-- Form Tambah Jadwal Luang -->
        <form action="my-skills.php" method="POST" style="background: var(--gray-100); padding: 20px; border-radius: var(--radius-lg); margin-bottom: 24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_availability">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)) 120px; gap: 12px; align-items: flex-end;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Pilih Hari</label>
                    <select name="day" required style="width: 100%; padding: 10px 14px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 13px; outline: none; background: var(--white);">
                        <option value="senin">Senin</option>
                        <option value="selasa">Selasa</option>
                        <option value="rabu">Rabu</option>
                        <option value="kamis">Kamis</option>
                        <option value="jumat">Jumat</option>
                        <option value="sabtu">Sabtu</option>
                        <option value="minggu">Minggu</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Jam Mulai</label>
                    <input type="time" name="start_time" value="13:00" required
                           style="width: 100%; padding: 10px 14px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 13px; outline: none; background: var(--white);">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Jam Selesai</label>
                    <input type="time" name="end_time" value="16:00" required
                           style="width: 100%; padding: 10px 14px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 13px; outline: none; background: var(--white);">
                </div>
                <div>
                    <button type="submit" class="btn btn-dark btn-sm" style="width: 100%; padding: 11px;">
                        + Tambah
                    </button>
                </div>
            </div>
        </form>

        <!-- Daftar Slot Waktu yang Sudah Didaftarkan -->
        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            <?php if (empty($my_availabilities)): ?>
                <p style="font-size: 13px; color: var(--gray-400); font-style: italic;">
                    Belum ada jadwal ketersediaan. Tambahkan jam luangmu di atas ya!
                </p>
            <?php else: ?>
                <?php foreach ($my_availabilities as $av): ?>
                    <div style="background: var(--blue-50); border: 1px solid var(--blue-100); padding: 10px 16px; border-radius: var(--radius-pill); display: inline-flex; align-items: center; gap: 10px; font-size: 13px;">
                        <strong><?= ucfirst(e($av['day'])) ?></strong>: <?= substr(e($av['start_time']), 0, 5) ?> - <?= substr(e($av['end_time']), 0, 5) ?> WIB
                        <form action="my-skills.php" method="POST" onsubmit="return confirm('Hapus slot jadwal ini?');" style="display: inline; margin: 0;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_availability">
                            <input type="hidden" name="avail_id" value="<?= (int)$av['id'] ?>">
                            <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer; display: inline-flex; align-items: center; padding: 2px;" title="Hapus">
                                <?= icon('x', '', '14px') ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
