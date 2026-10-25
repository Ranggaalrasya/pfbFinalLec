# TukarSkill — Platform Pertukaran Kemampuan Antar Mahasiswa

> **"Tukar Skill. Dapat Skill."**  
> Proyek Akhir Mata Kuliah **COSC6050 — Programming for Business**  
> **Kelompok 3** · Universitas Bina Nusantara

---

## 👥 Tim Pengembang (Kelompok 3)

| No | Nama Lengkap | NIM | Peran Utama |
|---|---|---|---|
| 1 | **Rangga Alrasya Nazriel Alamsyah** | **2902691122** | Full-Stack Architect & Backend Engine |
| 2 | **Jennifer Lovinda Tampinongkol** | **2902662860** | UI/UX Designer & Frontend System |
| 3 | **Liftya Muthi Pradipta** | **2902666165** | Creative Asset & Graphic Illustrator |
| 4 | **Lucky Fadlan Kusuma Anggoro** | **2902656750** | Database & Recommendation Algorithm Engineer |
| 5 | **Revano Hartojo** | **2902703734** | Business Logic, QA & SDG Alignment |

---

## 🌍 Kontribusi Nyata terhadap Sustainable Development Goals (SDGs)

TukarSkill dirancang khusus sebagai solusi teknologi inklusif yang menjawab permasalahan mahasiswa dalam mengakses pembelajaran berkualitas tanpa kendala biaya:

1. **SDG 4 — Quality Education (Pilar Utama)**:
   - **Target 4.4**: Meningkatkan secara substansial jumlah mahasiswa yang memiliki keterampilan teknis dan kejuruan yang relevan untuk ketenagakerjaan, pekerjaan yang layak, dan kewirausahaan.
   - **Target 4.7**: Menjamin pemerataan pengetahuan lintas disiplin ilmu melalui pembelajaran kolaboratif (*peer-to-peer collaborative learning*).
   - Dilengkapi dengan fitur **Sertifikat Bukti Barter SDG 4** (`certificate.php`) siap cetak dengan tanda tangan digital & QR verifikasi.
2. **SDG 10 — Reduced Inequalities**: Menghilangkan kesenjangan akses ilmu privat berbayar mahal dengan sistem barter keahlian gratis.
3. **SDG 8 — Decent Work & Economic Growth**: Mempersiapkan portofolio nyata dan kesiapan kerja mahasiswa sebelum lulus kuliah.

---

## ✨ Fitur-Fitur Unggulan

### 1. 🎯 Algoritma Rekomendasi Cerdas — SkillMatch™ (`skillmatch.php`)
Sistem pencocokan otomatis yang menghitung skor kecocokan (0–100%) berdasarkan:
- **Kebutuhan Timbal Balik (Mutual Swap)**: Mahasiswa A menguasai apa yang dicari Mahasiswa B, dan sebaliknya.
- **Irisan Waktu & Jadwal (Availability Overlap)**: Sinkronisasi hari dan jam luang di luar jam perkuliahan.
- **Reputasi Komunitas**: Rating bintang dan riwayat sesi sukses.

### 2. ⚡ Alur Barter Skill Fleksibel (`request.php` & `exchanges.php`)
- **Metode 1: Barter Murni (Swap Skill)** — 100% gratis, saling mengajarkan 1 skill.
- **Metode 2: Sistem Skill Points (15 Poin)** — Belajar satu arah dengan sistem saldo poin yang ditahan dan dapat direfund jika ditolak/dibatalkan.
- **Verifikasi Dua Arah (Mutual Confirmation)**: Sesi hanya dinyatakan selesai jika **kedua belah pihak** menekan tombol konfirmasi untuk mencegah kecurangan.

### 3. 🧠 AI Study & Exchange Planner (`ai-planner.php`)
Asisten pedagogis cerdas yang merumuskan:
- Silabus belajar menit-per-menit (sesi 90 menit hingga mini-bootcamp 4 sesi).
- Rekomendasi mini-project portofolio.
- Rubrik penilaian evaluasi peer-to-peer.
- Pemetaan kompetensi terhadap SDG 4.

### 4. 🏆 Gamifikasi & Sistem Reputasi (`points.php` & `leaderboard.php`)
- **Tingkatan Badge Pertumbuhan**:
  - 🌱 **Bibit** (0 Poin) — Mahasiswa baru terdaftar.
  - 🌿 **Tunas** (50 Poin) — Mulai aktif bertukar skill.
  - 🌳 **Pohon** (150 Poin) — Mentor favorit kampus.
  - 🏆 **Mentor** (300 Poin) — Pahlawan edukasi komunitas.
- **Leaderboard Kampus**: Peringkat bulanan dan sepanjang masa mahasiswa teraktif.

### 5. 🛡️ Panel Administrator Lengkap (`admin/`)
- Dashboard KPI real-time (total user, transaksi barter, jam dampak SDG 4, peredaran poin).
- Manajemen Pengguna (aktivasi/nonaktivasi akun, ubah role admin/mahasiswa).
- Master Katalog Skill & Kategori.
- Moderasi ulasan komunitas untuk menjaga etika belajar.

---

## 🛠️ Arsitektur Teknologi & Dependensi

Platform dibangun dengan prinsip **High Reliability & Zero External CDN Dependencies** agar 100% berjalan lancar di lingkungan pengujian offline kampus:

- **Bahasa**: Native PHP 8.2 (Procedural-modular, `mysqli` prepared statements)
- **Database**: MySQL / MariaDB (InnoDB, Full Foreign Key Constraints, Cascading Rules)
- **Frontend**: Vanilla HTML5, Modern CSS3 (Custom Design System terinspirasi dari LearnNix/Linear/Stripe), Vanilla JavaScript
- **Ikonografi**: In-house Enterprise Vector SVG Icons (35+ vektor Lucide/Tabler style di `includes/icons.php`)
- **Aset Visual**: 100% File Vektor Lokal SVG (Avatar anggota Kelompok 3 & Landing Illustration)

---

## 🚀 Panduan Instalasi & Menjalankan di XAMPP

### 1. Persiapan Folder
Clone repositori ke dalam direktori `htdocs` XAMPP kamu:
```bash
cd C:\xampp\htdocs
git clone https://github.com/Ranggaalrasya/pfbFinalLec.git
```
*(Atau buka folder `C:\xampp\htdocs\pfbFinalLec` jika sudah di-mirror)*.

### 2. Impor Database MySQL
1. Buka aplikasi **XAMPP Control Panel**, lalu klik **Start** pada modul **Apache** dan **MySQL**.
2. Buka browser dan kunjungi `http://localhost/phpmyadmin`.
3. Buat database baru bernama `tukarskill`.
4. Klik tab **Import**, pilih file [`docs.sql`](file:///c:/Users/LENOVO/Downloads/PFB/pfbFinalLec/docs.sql) yang berada di dalam folder proyek, lalu klik **Go / Impor**.

### 3. Konfigurasi Koneksi (Opsional)
Pengaturan default database di [`config/db.php`](file:///c:/Users/LENOVO/Downloads/PFB/pfbFinalLec/config/db.php):
- **Host**: `localhost`
- **User**: `root`
- **Password**: *(kosong)*
- **Database**: `tukarskill`

### 4. Buka Aplikasi di Browser
Akses URL berikut:
```text
http://localhost/pfbFinalLec
```

---

## 🔑 Akun Uji Coba Demo

Untuk kemudahan demonstrasi saat presentasi dan penilaian dosen, sistem telah dilengkapi dengan data seeder:

| Role | Email Login | Password | Keterangan |
|---|---|---|---|
| **Mahasiswa (Rangga)** | `rangga.alrasya@binus.ac.id` | `password123` | Backend developer, menawarkan Web Dev |
| **Mahasiswa (Jennifer)** | `jennifer.lovinda@binus.ac.id` | `password123` | UI/UX designer, menawarkan Figma |
| **Mahasiswa (Liftya)** | `liftya.muthi@binus.ac.id` | `password123` | Illustrator DKV, menawarkan Illustrator |
| **Mahasiswa (Lucky)** | `lucky.fadlan@binus.ac.id` | `password123` | Data analyst, menawarkan Python |
| **Mahasiswa (Revano)** | `revano.hartojo@binus.ac.id` | `password123` | Bisnis, menawarkan Akuntansi UMKM |
| **Administrator** | `admin@tukarskill.id` | `admin123` | Akses penuh dashboard `/admin` |

*Catatan: Semua mahasiswa juga dapat login menggunakan **NIM** masing-masing.*

---

## 📁 Struktur Direktori Proyek

```text
pfbFinalLec/
├── admin/                     # Modul Panel Administrator
│   ├── categories.php         # Kelola master kategori keahlian
│   ├── footer.php             # Footer khusus admin
│   ├── header.php             # Navigasi & topbar admin
│   ├── index.php              # Dashboard statistik & KPI SDG 4
│   ├── reviews.php            # Moderasi review & rating komunitas
│   ├── skills.php             # Master katalog skill
│   └── users.php              # Kelola status pengguna & role
├── assets/                    # Aset Statis Lokal
│   ├── css/
│   │   └── style.css          # Design system & stylesheet responsif
│   └── img/
│       └── hero-student.svg   # Ilustrasi hero lokal bebas CDN
├── config/
│   └── db.php                 # Koneksi database MySQLi & safe handling
├── docs/                      # Dokumentasi Sistem Desain & ERD
│   ├── diagrams.html          # Viewer diagram arsitektur interaktif
│   ├── system-design.md       # Laporan analisis sistem lengkap
│   └── tukarskill.sql         # Backup skema & seeder SQL
├── includes/                  # Modul Pendukung Global
│   ├── auth.php               # Guard sesi, remember-me cookie & CSRF
│   ├── footer.php             # Footer utama platform
│   ├── functions.php          # Helper poin, avatar, format tanggal, notifikasi
│   ├── header.php             # Navigasi utama & drawer responsif
│   └── icons.php              # Library 35+ vektor SVG enterprise
├── uploads/
│   └── avatars/               # Direktori foto profil & SVG avatar mahasiswa
├── ai-planner.php             # Generator silabus & kurikulum barter AI
├── certificate.php            # Generator sertifikat barter resmi SDG 4
├── dashboard.php              # Dashboard beranda mahasiswa aktif
├── discover.php               # Jelajah skill & pencarian mahasiswa
├── exchange-detail.php        # Stepper sesi, WhatsApp & konfirmasi dua arah
├── exchanges.php              # Pusat kelola proposal (Masuk, Terkirim, Aktif, Riwayat)
├── index.php                  # Halaman landing page utama
├── leaderboard.php            # Peringkat mahasiswa & podium teraktif
├── login.php                  # Autentikasi masuk mahasiswa & admin
├── logout.php                 # Sesi keluar aman
├── my-skills.php              # Manajemen skill offer/want & jadwal luang
├── notifications.php          # Pusat notifikasi aktivitas in-app
├── points.php                 # Dompet saldo poin, riwayat & koleksi badge
├── profile.php                # Edit profil, bio, nomor WhatsApp & password
├── register.php               # Pendaftaran mahasiswa baru (+20 Poin bonus)
├── request.php                # Formulir pengajuan barter (Swap / Poin)
├── review.php                 # Handler submit rating & ulasan
├── skillmatch.php             # Rekomendasi partner berbasis skor kecocokan
├── user.php                   # Tampilan profil publik mahasiswa
├── docs.sql                   # SQL dump siap impor di phpMyAdmin
└── README.md                  # Dokumentasi resmi proyek
```

---

## 📜 Lisensi & Hak Cipta
Dikembangkan dengan penuh dedikasi oleh **Kelompok 3 — COSC6050 Programming for Business (2026)**. Seluruh kode sumber terbuka untuk keperluan evaluasi akademik dan pembelajaran.
