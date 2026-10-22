-- ==============================================================
-- TukarSkill — Database Schema & Seeder
-- Kelompok 3 · COSC6050 Programming for Business
-- "Tukar Skill. Dapat Skill."
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `tukarskill` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tukarskill`;

-- Matiin dulu pengecekan foreign key biar proses drop & create tabel lancar jaya
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `user_badges`;
DROP TABLE IF EXISTS `badges`;
DROP TABLE IF EXISTS `point_transactions`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `exchanges`;
DROP TABLE IF EXISTS `availabilities`;
DROP TABLE IF EXISTS `user_skills`;
DROP TABLE IF EXISTS `skills`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- 1. TABEL USERS
-- Ini tabel utama buat nyimpen data mahasiswa & admin
-- -------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `nim` CHAR(10) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `major` VARCHAR(100) NOT NULL,
  `batch_year` YEAR NULL,
  `bio` TEXT NULL,
  `photo` VARCHAR(255) NULL,
  `whatsapp` VARCHAR(20) NULL,
  `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  `points` INT NOT NULL DEFAULT 20,
  `total_points` INT NOT NULL DEFAULT 20,
  `profile_bonus_given` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `remember_token` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. TABEL CATEGORIES
-- Ini kategori skill biar terstruktur rapi (Design, Code, dll)
-- -------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `icon` VARCHAR(50) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. TABEL SKILLS
-- Master katalog skill yang tersedia di platform
-- -------------------------------------------------------------
CREATE TABLE `skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_skills_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. TABEL USER_SKILLS
-- Relasi many-to-many: skill yang BISA (offer) atau INGIN DIPELAJARI (want)
-- -------------------------------------------------------------
CREATE TABLE `user_skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `skill_id` INT NOT NULL,
  `type` ENUM('offer', 'want') NOT NULL,
  `level` ENUM('pemula', 'menengah', 'mahir') NOT NULL,
  `description` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_user_skill_type` (`user_id`, `skill_id`, `type`),
  CONSTRAINT `fk_userskills_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_userskills_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. TABEL AVAILABILITIES
-- Jadwal luang mahasiswa buat ketemuan/tukar skill
-- -------------------------------------------------------------
CREATE TABLE `availabilities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `day` ENUM('senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  CONSTRAINT `fk_availabilities_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. TABEL EXCHANGES
-- Ini inti proses pertukaran (Swap skill ↔ skill atau bayar pake Poin)
-- -------------------------------------------------------------
CREATE TABLE `exchanges` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `requester_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `type` ENUM('swap', 'points') NOT NULL DEFAULT 'swap',
  `requested_skill_id` INT NOT NULL,
  `offered_skill_id` INT NULL,
  `points_cost` INT NOT NULL DEFAULT 0,
  `message` TEXT NULL,
  `scheduled_at` DATETIME NOT NULL,
  `mode` ENUM('online', 'offline') NOT NULL DEFAULT 'online',
  `location` VARCHAR(255) NULL,
  `status` ENUM('pending', 'accepted', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
  `requester_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `receiver_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `reject_reason` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` DATETIME NULL,
  CONSTRAINT `fk_exchanges_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_exchanges_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_exchanges_req_skill` FOREIGN KEY (`requested_skill_id`) REFERENCES `skills` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_exchanges_off_skill` FOREIGN KEY (`offered_skill_id`) REFERENCES `skills` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. TABEL REVIEWS
-- Review & rating bintang 1-5 setelah sesi tukar selesai
-- -------------------------------------------------------------
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `exchange_id` INT NOT NULL,
  `reviewer_id` INT NOT NULL,
  `reviewee_id` INT NOT NULL,
  `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NULL,
  `is_hidden` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_exchange_reviewer` (`exchange_id`, `reviewer_id`),
  CONSTRAINT `fk_reviews_exchange` FOREIGN KEY (`exchange_id`) REFERENCES `exchanges` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_reviewee` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. TABEL POINT_TRANSACTIONS
-- Catatan histori keluar masuk poin mahasiswa
-- -------------------------------------------------------------
CREATE TABLE `point_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `exchange_id` INT NULL,
  `amount` INT NOT NULL,
  `type` ENUM('welcome', 'profile_complete', 'teach_complete', 'points_received', 'points_spent', 'points_refund', 'review_given', 'five_star', 'admin_adjust') NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_points_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_points_exchange` FOREIGN KEY (`exchange_id`) REFERENCES `exchanges` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 9. TABEL BADGES & USER_BADGES
-- Gamifikasi reward badge mahasiswa (Bibit, Tunas, Pohon, Mentor)
-- -------------------------------------------------------------
CREATE TABLE `badges` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `icon` VARCHAR(20) NOT NULL,
  `description` VARCHAR(255) NULL,
  `min_points` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_badges` (
  `user_id` INT NOT NULL,
  `badge_id` INT NOT NULL,
  `earned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `badge_id`),
  CONSTRAINT `fk_ub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ub_badge` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 10. TABEL NOTIFICATIONS
-- Notifikasi in-app pas ada request masuk atau status berubah
-- -------------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================================
-- SEED DATA AWAL
-- Biar platform langsung rame & siap dipake demo pas presentasi
-- ==============================================================

-- Seed Badges
INSERT INTO `badges` (`id`, `name`, `icon`, `description`, `min_points`) VALUES
(1, 'Bibit', '🌱', 'Mahasiswa baru terdaftar yang siap berbagi dan belajar', 0),
(2, 'Tunas', '🌿', 'Mulai aktif bertukar skill dan mengumpulkan reputasi', 50),
(3, 'Pohon', '🌳', 'Rutin mengajar dan menjadi teman belajar favorit di kampus', 150),
(4, 'Mentor', '🏆', 'Inspirasi komunitas dengan puluhan sesi tukar skill sukses', 300);

-- Seed Categories
INSERT INTO `categories` (`id`, `name`, `icon`) VALUES
(1, 'Pemrograman & Web', '💻'),
(2, 'Desain Grafis & UI/UX', '🎨'),
(3, 'Bahasa Asing', '🗣️'),
(4, 'Fotografi & Videografi', '📸'),
(5, 'Public Speaking & Komunikasi', '🎤'),
(6, 'Tutoring Mata Kuliah', '📚');

-- Seed Skills
INSERT INTO `skills` (`id`, `category_id`, `name`) VALUES
(1, 1, 'Web Development (HTML, CSS, PHP)'),
(2, 1, 'Python & Data Analysis'),
(3, 1, 'JavaScript & React'),
(4, 1, 'Database MySQL & Query Optimization'),
(5, 1, 'Git & GitHub Collaboration'),
(6, 2, 'UI/UX Design (Figma & Prototyping)'),
(7, 2, 'Graphic Design (Adobe Illustrator & Photoshop)'),
(8, 2, '3D Modeling (Blender)'),
(9, 2, 'Canva for Pitch Deck & Presentation'),
(10, 3, 'Bahasa Inggris (TOEFL & Daily Conversation)'),
(11, 3, 'Bahasa Jepang (JLPT N5-N4)'),
(12, 3, 'Bahasa Mandarin Dasar'),
(13, 3, 'Bahasa Korea (Hangul & Percakapan)'),
(14, 4, 'Fotografi Portrait & Lighting'),
(15, 4, 'Video Editing (Premiere Pro & CapCut)'),
(16, 4, 'Color Grading (DaVinci Resolve)'),
(17, 5, 'Public Speaking & Presentation Skills'),
(18, 5, 'Debat & Argumentasi Kritis'),
(19, 5, 'Interview Kerja & CV Review'),
(20, 6, 'Kalkulus & Aljabar Linear'),
(21, 6, 'Statistika & Probabilitas'),
(22, 6, 'Algoritma & Struktur Data'),
(23, 6, 'Akuntansi Dasar & Keuangan Bisnis');

-- Seed Users
-- Password semua mahasiswa: password123
-- Password admin: admin123
INSERT INTO `users` (`id`, `name`, `nim`, `email`, `password`, `major`, `batch_year`, `bio`, `whatsapp`, `role`, `points`, `total_points`, `profile_bonus_given`) VALUES
(1, 'Admin TukarSkill', '0000000000', 'admin@tukarskill.id', '$2y$10$/OtuKCqS7zTB878T5LBRL.z0Vy8.fg65yMY9lKsGIa48CMCBcCgkq', 'Computer Science', 2024, 'Administrator sistem platform TukarSkill.', '081234567890', 'admin', 999, 999, 1),
(2, 'Rangga Alrasya Nazriel Alamsyah', '2902691122', 'rangga.alrasya@binus.ac.id', '$2y$10$LT4BGOYhxhfZKN2V674I.uDZ5dBoyEVPGyOevY.0TfVIlTQUVH47y', 'Computer Science', 2025, 'Passionate web developer & backend enthusiast. Suka ngoprek PHP dan database relational.', '081299887711', 'user', 85, 85, 1),
(3, 'Jennifer Lovinda Tampinongkol', '2902662860', 'jennifer.lovinda@binus.ac.id', '$2y$10$LT4BGOYhxhfZKN2V674I.uDZ5dBoyEVPGyOevY.0TfVIlTQUVH47y', 'Information Systems', 2025, 'UI/UX enthusiast yang fokus bikin user flow mulus dan visual clean. Ingin belajar public speaking.', '081299887722', 'user', 95, 95, 1),
(4, 'Liftya Muthi Pradipta', '2902666165', 'liftya.muthi@binus.ac.id', '$2y$10$LT4BGOYhxhfZKN2V674I.uDZ5dBoyEVPGyOevY.0TfVIlTQUVH47y', 'Visual Communication Design', 2025, 'Illustrator & visual storyteller. Mau tukar ilmu ilustrasi digital dengan coding dasar web!', '081299887733', 'user', 65, 65, 1),
(5, 'Lucky Fadlan Kusuma Anggoro', '2902656750', 'lucky.fadlan@binus.ac.id', '$2y$10$LT4BGOYhxhfZKN2V674I.uDZ5dBoyEVPGyOevY.0TfVIlTQUVH47y', 'Computer Science', 2025, 'Senang eksplorasi machine learning dan python. Butuh teman latihan conversational English.', '081299887744', 'user', 70, 70, 1),
(6, 'Revano Hartojo', '2902703734', 'revano.hartojo@binus.ac.id', '$2y$10$LT4BGOYhxhfZKN2V674I.uDZ5dBoyEVPGyOevY.0TfVIlTQUVH47y', 'Business Management', 2025, 'Fokus di entrepreneurship dan strategi bisnis. Pengen belajar bikin landing page sendiri.', '081299887755', 'user', 60, 60, 1),
(7, 'Syifa Az-Zahra', '2902611234', 'syifa.azzahra@binus.ac.id', '$2y$10$LT4BGOYhxhfZKN2V674I.uDZ5dBoyEVPGyOevY.0TfVIlTQUVH47y', 'English Literature', 2024, 'TOEFL 620 tutor. Siap bantu kamu lancar cas cis cus bahasa Inggris. Pengen belajar Figma!', '081299887766', 'user', 175, 175, 1),
(8, 'Budi Santoso', '2902623456', 'budi.santoso@binus.ac.id', '$2y$10$LT4BGOYhxhfZKN2V674I.uDZ5dBoyEVPGyOevY.0TfVIlTQUVH47y', 'Data Science', 2024, 'Data analyst & visualisasi tableau. Mau belajar video editing buat konten edukasi.', '081299887777', 'user', 110, 110, 1),
(9, 'Amanda Putri', '2902634567', 'amanda.putri@binus.ac.id', '$2y$10$LT4BGOYhxhfZKN2V674I.uDZ5dBoyEVPGyOevY.0TfVIlTQUVH47y', 'Marketing Communication', 2025, 'Public speaker kampus & juara lomba debat nasional. Pengen belajar fotografi produk.', '081299887788', 'user', 80, 80, 1),
(10, 'Dimas Pratama', '2902645678', 'dimas.pratama@binus.ac.id', '$2y$10$LT4BGOYhxhfZKN2V674I.uDZ5dBoyEVPGyOevY.0TfVIlTQUVH47y', 'Film & Animation', 2024, 'Videografer & editor video komersial. Siap ajarin CapCut & Premiere Pro sampai jago.', '081299887799', 'user', 160, 160, 1);

-- Seed Badges Pemain
INSERT INTO `user_badges` (`user_id`, `badge_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4),
(2, 1), (2, 2),
(3, 1), (3, 2),
(4, 1), (4, 2),
(5, 1), (5, 2),
(6, 1), (6, 2),
(7, 1), (7, 2), (7, 3),
(8, 1), (8, 2),
(9, 1), (9, 2),
(10, 1), (10, 2), (10, 3);

-- Seed User Skills (Bisa / Ingin Belajar)
INSERT INTO `user_skills` (`user_id`, `skill_id`, `type`, `level`, `description`) VALUES
-- Rangga
(2, 1, 'offer', 'mahir', 'Bisa ajarin dasar HTML, layout CSS modern, sampe koneksi database PHP MySQL'),
(2, 5, 'offer', 'menengah', 'Praktek Git branching, commit convention, pull request'),
(2, 6, 'want', 'pemula', 'Pengen ngerti cara bikin wireframe & komponen di Figma'),
(2, 10, 'want', 'menengah', 'Mau ningkatin kelancaran percakapan bahasa Inggris'),

-- Jennifer
(3, 6, 'offer', 'mahir', 'Figma dari nol, auto-layout, design tokens, sampai interactive prototype'),
(3, 9, 'offer', 'mahir', 'Bikin presentasi bisnis & pitch deck visual memukau di Canva'),
(3, 1, 'want', 'pemula', 'Pengen tahu cara slicing desain UI jadi kode HTML & CSS'),
(3, 17, 'want', 'pemula', 'Pengen percaya diri ngomong pas presentasi project'),

-- Liftya
(4, 7, 'offer', 'mahir', 'Vektor art, logo design, tipografi di Adobe Illustrator'),
(4, 1, 'want', 'pemula', 'Mau belajar bikin portofolio website pribadi pake PHP'),

-- Lucky
(5, 2, 'offer', 'menengah', 'Python scripting, pengolahan data pandas, jupyter notebook'),
(5, 10, 'want', 'pemula', 'Persiapan listening & reading buat tes TOEFL'),

-- Revano
(6, 23, 'offer', 'menengah', 'Pencatatan kas UMKM, laporan laba rugi sederhana'),
(6, 1, 'want', 'pemula', 'Mau bisa bikin halaman web produk sendiri'),

-- Syifa (Senior English Tutor)
(7, 10, 'offer', 'mahir', 'Skor TOEFL 620, siap drills speaking, grammar santai & vocabulary'),
(7, 6, 'want', 'pemula', 'Pengen bisa bikin desain poster & UI aplikasi simpel'),

-- Budi
(8, 4, 'offer', 'mahir', 'Perancangan ERD, normalisasi, query JOIN kompleks & indexing'),
(8, 15, 'want', 'pemula', 'Bikin video reels/tiktok edukasi data yang estetik'),

-- Amanda
(9, 17, 'offer', 'mahir', 'Teknik vokal, bahasa tubuh, mengatasi grogi saat bicara depan umum'),
(9, 14, 'want', 'pemula', 'Pengaturan shutter speed, aperture & lighting foto studio'),

-- Dimas
(10, 15, 'offer', 'mahir', 'Video editing dinamis, sound design, transisi smooth Premiere & CapCut'),
(10, 2, 'want', 'pemula', 'Automasi rendering & scripting data pake python');

-- Seed Availabilities
INSERT INTO `availabilities` (`user_id`, `day`, `start_time`, `end_time`) VALUES
(2, 'rabu', '14:00:00', '17:00:00'),
(2, 'sabtu', '10:00:00', '13:00:00'),
(3, 'selasa', '13:00:00', '16:00:00'),
(3, 'sabtu', '10:00:00', '14:00:00'),
(4, 'kamis', '15:00:00', '18:00:00'),
(5, 'jumat', '13:00:00', '16:00:00'),
(6, 'senin', '16:00:00', '19:00:00'),
(7, 'selasa', '14:00:00', '17:00:00'),
(7, 'sabtu', '09:00:00', '12:00:00'),
(8, 'rabu', '15:00:00', '18:00:00'),
(9, 'kamis', '13:00:00', '16:00:00'),
(10, 'minggu', '13:00:00', '17:00:00');

-- Seed Sample Exchanges
-- 1. Selesai (Completed): Rangga belajar UI/UX dari Jennifer, gantinya Rangga ajarin Web Dev
INSERT INTO `exchanges` (`id`, `requester_id`, `receiver_id`, `type`, `requested_skill_id`, `offered_skill_id`, `points_cost`, `message`, `scheduled_at`, `mode`, `location`, `status`, `requester_confirmed`, `receiver_confirmed`, `completed_at`) VALUES
(1, 2, 3, 'swap', 6, 1, 0, 'Halo Kak Jennifer! Boleh barter skill? Aku mau belajar Figma dasar, gantinya aku ajarin dasar web PHP.', '2026-10-18 10:00:00', 'online', 'Google Meet: meet.google.com/abc-tuka-rsk', 'completed', 1, 1, '2026-10-18 12:00:00');

-- 2. Diterima (Accepted): Jennifer belajar Public Speaking dari Amanda
INSERT INTO `exchanges` (`id`, `requester_id`, `receiver_id`, `type`, `requested_skill_id`, `offered_skill_id`, `points_cost`, `message`, `scheduled_at`, `mode`, `location`, `status`, `requester_confirmed`, `receiver_confirmed`) VALUES
(2, 3, 9, 'swap', 17, 6, 0, 'Hai Amanda! Aku mau belajar cara mengatasi grogi presentasi, nanti aku ajarin Figma bikin portofolio.', '2026-10-25 14:00:00', 'offline', 'Perpustakaan Kampus Lantai 3', 'accepted', 0, 0);

-- 3. Pending: Lucky minta sesi belajar TOEFL ke Syifa (tipe Poin)
INSERT INTO `exchanges` (`id`, `requester_id`, `receiver_id`, `type`, `requested_skill_id`, `offered_skill_id`, `points_cost`, `message`, `scheduled_at`, `mode`, `location`, `status`, `requester_confirmed`, `receiver_confirmed`) VALUES
(3, 5, 7, 'points', 10, NULL, 15, 'Halo Kak Syifa, mau ambil sesi TOEFL structure 1 jam via Poin ya kak. Makasih banyak!', '2026-10-28 15:00:00', 'online', 'Zoom Meeting Room', 'pending', 0, 0);

-- Seed Sample Reviews
INSERT INTO `reviews` (`id`, `exchange_id`, `reviewer_id`, `reviewee_id`, `rating`, `comment`) VALUES
(1, 1, 2, 3, 5, 'Kak Jennifer ngajarnya super sabar dan runtut banget! Sekarang aku udah paham sistem frame & auto layout di Figma. Recommended mentor!'),
(2, 1, 3, 2, 5, 'Rangga jelasin logika dasar PHP sama web pake analogi yang gampang dipahami anak desain. Mantap banget barternya!');

-- Seed Point Transactions
INSERT INTO `point_transactions` (`user_id`, `exchange_id`, `amount`, `type`, `description`) VALUES
(2, NULL, 20, 'welcome', 'Bonus selamat datang akun TukarSkill'),
(2, NULL, 10, 'profile_complete', 'Bonus melengkapi profil & skill pertama kali'),
(2, 1, 20, 'teach_complete', 'Mengajarkan Web Development ke Jennifer'),
(2, 1, 5, 'review_given', 'Memberikan review untuk Jennifer'),
(2, 1, 10, 'five_star', 'Menerima review bintang 5 dari Jennifer'),
(3, NULL, 20, 'welcome', 'Bonus selamat datang akun TukarSkill'),
(3, NULL, 10, 'profile_complete', 'Bonus melengkapi profil & skill pertama kali'),
(3, 1, 20, 'teach_complete', 'Mengajarkan UI/UX Design ke Rangga'),
(3, 1, 5, 'review_given', 'Memberikan review untuk Rangga'),
(3, 1, 10, 'five_star', 'Menerima review bintang 5 dari Rangga'),
(5, NULL, 20, 'welcome', 'Bonus selamat datang akun TukarSkill'),
(5, 3, -15, 'points_spent', 'Poin ditahan untuk request TOEFL ke Syifa'),
(7, NULL, 20, 'welcome', 'Bonus selamat datang akun TukarSkill');

-- Seed Notifications
INSERT INTO `notifications` (`user_id`, `message`, `link`, `is_read`) VALUES
(2, 'Pertukaran skill dengan Jennifer telah selesai! Kamu mendapatkan +35 poin total.', 'exchange-detail.php?id=1', 1),
(3, 'Rangga telah memberikan review bintang 5 untukmu! Cek profilmu.', 'profile.php', 1),
(9, 'Jennifer Lovinda mengirimkan request pertukaran skill: Public Speaking!', 'exchange-detail.php?id=2', 0),
(7, 'Lucky Fadlan mengirimkan request sesi TOEFL menggunakan 15 Poin.', 'exchange-detail.php?id=3', 0);
