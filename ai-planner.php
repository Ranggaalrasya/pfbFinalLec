<?php
// ==============================================================
// ai-planner.php
// asisten AI cerdas untuk merancang kurikulum belajar & silabus sesi barter
// otomatis menghasilkan rundown menit-per-menit, mini-project, & evaluasi SDG 4
// ==============================================================

$page_title = "AI Study & Exchange Planner ✨";
require_once __DIR__ . '/includes/header.php';

require_login(); // satpam: wajib login

$user = current_user();

// Ambil semua daftar skill dari database untuk pilihan dropdown
$skills = [];
if (is_db_connected()) {
    $res = $conn->query("SELECT s.id, s.name, c.name AS cat_name, c.icon AS cat_icon FROM skills s JOIN categories c ON s.category_id = c.id ORDER BY c.name ASC, s.name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) $skills[] = $row;
    }
}

// Tangkap input dari form planner
$selected_skill = trim($_POST['skill_name'] ?? ($_GET['skill'] ?? ''));
$level = $_POST['level'] ?? 'beginner'; // beginner | intermediate | advanced
$session_count = (int)($_POST['session_count'] ?? 1); // 1 | 2 | 4 sesi
$learning_goal = trim($_POST['learning_goal'] ?? 'portfolio'); // portfolio | exam | career | fundamentals

$generated_plan = null;

// Kalo form di-submit, kita jalankan AI generator
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($selected_skill)) {
    verify_csrf();

    // --------------------------------------------------------------
    // MESIN AI PEDAGOGIS TUKARSKILL (CERDAS, TERSTRUKTUR & TANGGUH OFFLINE)
    // --------------------------------------------------------------
    $level_labels = [
        'beginner' => 'Tingkat Pemula (Mulai dari Nol)',
        'intermediate' => 'Tingkat Menengah (Sudah Paham Dasar)',
        'advanced' => 'Tingkat Lanjutan (Siap Portofolio & Industri)'
    ];

    $goal_labels = [
        'portfolio' => 'Membangun Studi Kasus Portofolio Nyata',
        'exam' => 'Persiapan Tugas Besar & Ujian Kuliah',
        'career' => 'Persiapan Magang & Interview Kerja',
        'fundamentals' => 'Penguasaan Konsep Inti & Best Practices'
    ];

    // Struktur silabus sesi berdasarkan jumlah sesi
    $sessions_breakdown = [];

    if ($session_count === 1) {
        $sessions_breakdown[] = [
            'sesi' => 'Sesi Tunggal (90 Menit Intensif)',
            'fokus' => 'Fondasi Cepat & Live Practice Bareng Partner',
            'rundown' => [
                'Menit 00 - 15' => 'Ice breaking, cek pemahaman awal, dan setup tools/workspace bersama',
                'Menit 15 - 45' => "Core Concept: Pemahaman pilar utama $selected_skill dan studi kasus dunia nyata",
                'Menit 45 - 75' => "Hands-on Practice: Partner memandu praktik langsung membuat mini-implementasi",
                'Menit 75 - 90' => 'Code/Design Review, tanya jawab kendala, tips eksplorasi lanjutan, dan tukar feedback'
            ]
        ];
    } elseif ($session_count === 2) {
        $sessions_breakdown[] = [
            'sesi' => 'Sesi 1: Pondasi Konsep & Bedah Kasus (90 Menit)',
            'fokus' => 'Memahami Arsitektur Dasar & Contoh Implementasi',
            'rundown' => [
                'Menit 00 - 20' => 'Kenalan latar belakang, tujuan belajar, dan instalasi software/tools yang dibutuhkan',
                'Menit 20 - 60' => "Eksplorasi fundamental $selected_skill dengan contoh interaktif dari mentor",
                'Menit 60 - 90' => 'Latihan terbimbing pertama + pemberian tantangan santai untuk dikerjakan sebelum Sesi 2'
            ]
        ];
        $sessions_breakdown[] = [
            'sesi' => 'Sesi 2: Praktik Mandiri & Review Proyek (90 Menit)',
            'fokus' => 'Penyelesaian Proyek Praktik & Tanya Jawab Masalah',
            'rundown' => [
                'Menit 00 - 20' => 'Review hasil eksplorasi mandiri dan diskusi bagian yang bikin bingung',
                'Menit 20 - 65' => 'Deep-dive topik spesifik, optimasi solusi, dan implementasi fitur lanjutan',
                'Menit 65 - 90' => 'Evaluasi hasil akhir, feedback dua arah, dan perencanaan roadmap belajar selanjutnya'
            ]
        ];
    } else { // 4 sesi mini bootcamp
        $sessions_breakdown[] = [
            'sesi' => 'Sesi 1: Fundamentals & Environment Setup (90 Menit)',
            'fokus' => 'Pengenalan syntax/tools, struktur folder, dan pola pikir utama',
            'rundown' => [
                'Menit 00 - 30' => 'Setup environment & perkenalan alur kerja profesional',
                'Menit 30 - 90' => 'Praktik komponen dasar pertama + tips menghindari kesalahan pemula'
            ]
        ];
        $sessions_breakdown[] = [
            'sesi' => 'Sesi 2: Intermediate Techniques & Workflow (90 Menit)',
            'fokus' => 'Teknik penyelesaian masalah umum dan standarisasi kualitas',
            'rundown' => [
                'Menit 00 - 30' => 'Review tugas sesi 1 & eksplorasi teknik menengah',
                'Menit 30 - 90' => 'Studi kasus fitur riil dengan panduan langsung partner'
            ]
        ];
        $sessions_breakdown[] = [
            'sesi' => 'Sesi 3: Real-World Mini Project (90 Menit)',
            'fokus' => 'Mengerjakan mini-project kolaboratif dari awal sampai jadi',
            'rundown' => [
                'Menit 00 - 45' => 'Brainstorming ide proyek & pembagian modul latihan',
                'Menit 45 - 90' => 'Live pair-working: eksekusi proyek sambil tanya jawab'
            ]
        ];
        $sessions_breakdown[] = [
            'sesi' => 'Sesi 4: Showcase, Code Review & Career Tips (90 Menit)',
            'fokus' => 'Evaluasi menyeluruh, portofolio presentation, & persiapan industri',
            'rundown' => [
                'Menit 00 - 45' => 'Presentasi mini-project dan feedback mendalam dari mentor',
                'Menit 45 - 90' => 'Tips interview kerja/lomba, roadmap karir, dan tukar review 5 bintang'
            ]
        ];
    }

    // Mini project rekomendasi AI
    $project_ideas = [
        'beginner' => "Mini Showcase: Bikin 1 hasil karya sederhana bertema '$selected_skill' yang bisa langsung dipamerkan di LinkedIn/GitHub.",
        'intermediate' => "Studi Kasus Solusi: Selesaikan 1 problem riil mahasiswa kampus menggunakan pendekatan modern '$selected_skill'.",
        'advanced' => "End-to-End Production: Bikin proyek terintegrasi lengkap dengan dokumentasi rapi dan presentasi singkat 5 menit."
    ];

    // Rubrik Evaluasi Peer-to-Peer
    $rubrics = [
        'Pemahaman Konsep' => 'Mampu menjelaskan kembali alur kerja kepada partner dengan bahasa sendiri tanpa membaca catatan.',
        'Kemandirian Praktik' => 'Dapat menyelesaikan tantangan latihan kecil tanpa harus didikte langkah per langkah.',
        'Problem Solving' => 'Mampu membaca pesan error / kendala teknis dan mencari referensi solusi secara kritis.',
        'Kolaborasi & Komunikasi' => 'Aktif bertanya, menghargai waktu belajar partner, dan memberikan feedback yang membangun.'
    ];

    $generated_plan = [
        'skill' => $selected_skill,
        'level_text' => $level_labels[$level],
        'goal_text' => $goal_labels[$learning_goal],
        'session_count' => $session_count,
        'sessions' => $sessions_breakdown,
        'project' => $project_ideas[$level],
        'rubrics' => $rubrics,
        'sdg_competency' => "Mendukung SDG 4.4 (Keterampilan relevan untuk ketenagakerjaan) dengan mencetak penguasaan $selected_skill yang siap pakai."
    ];
}
?>

<div class="container" style="padding: 40px 16px 80px;">

    <!-- HEADER FITUR AI -->
    <div style="text-align: center; max-width: 680px; margin: 0 auto 36px;">
        <span class="badge-tag" style="background: linear-gradient(135deg, #dbeafe 0%, #ede9fe 100%); color: #4338ca; border: 1px solid #c7d2fe; margin-bottom: 12px; display: inline-flex; align-items: center; gap: 6px;">
            <?= icon('sparkles', '', '14px') ?> AI Pedagogical Assistant
        </span>
        <h1 style="font-size: 32px; font-weight: 800; color: var(--ink); margin-bottom: 8px;">
            AI Study & Exchange Planner
        </h1>
        <p style="font-size: 14px; color: var(--gray-600); line-height: 1.5;">
            Bingung mau ngajarin apa atau belajar dari mana pas barter skill? Biarkan asisten AI merancang silabus belajar menit-per-menit, ide mini project, dan rubrik evaluasinya dalam hitungan detik!
        </p>
    </div>

    <!-- DUA KOLOM: FORM GENERATOR & HASIL SILABUS -->
    <div style="display: grid; grid-template-columns: 360px 1fr; gap: 32px; align-items: start;">
        
        <!-- KOLOM KIRI: FORM PARAMETER AI -->
        <div style="background: var(--white); border-radius: var(--radius-xl); padding: 28px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm);">
            <h3 style="font-size: 16px; font-weight: 800; color: var(--ink); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <?= icon('settings', '', '16px') ?> Parameter Sesi Belajar
            </h3>

            <form action="ai-planner.php" method="POST">
                <?= csrf_field() ?>

                <!-- Pilih Skill -->
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                        Pilih Skill yang Dipelajari:
                    </label>
                    <select name="skill_name" required style="width: 100%; padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 13px; outline: none; background: var(--white);">
                        <option value="">-- Pilih Skill Katalog --</option>
                        <?php foreach ($skills as $sk): ?>
                            <option value="<?= e($sk['name']) ?>" <?= ($selected_skill === $sk['name']) ? 'selected' : '' ?>>
                                <?= e($sk['cat_icon']) ?> <?= e($sk['name']) ?> (<?= e($sk['cat_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                        <option value="UI/UX Design" <?= ($selected_skill === 'UI/UX Design') ? 'selected' : '' ?>>🎨 UI/UX Design (Figma)</option>
                        <option value="Web Development" <?= ($selected_skill === 'Web Development') ? 'selected' : '' ?>>💻 Web Development (PHP & JS)</option>
                        <option value="Python Data Analysis" <?= ($selected_skill === 'Python Data Analysis') ? 'selected' : '' ?>>📊 Python Data Analysis</option>
                        <option value="English Conversation" <?= ($selected_skill === 'English Conversation') ? 'selected' : '' ?>>🗣️ English Conversation</option>
                    </select>
                </div>

                <!-- Tingkat Kemahiran -->
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                        Tingkat Awal Mahasiswa:
                    </label>
                    <select name="level" style="width: 100%; padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 13px; outline: none; background: var(--white);">
                        <option value="beginner" <?= ($level === 'beginner') ? 'selected' : '' ?>>🌱 Pemula (Mulai dari Nol)</option>
                        <option value="intermediate" <?= ($level === 'intermediate') ? 'selected' : '' ?>>🌿 Menengah (Tahu Teori Dasar)</option>
                        <option value="advanced" <?= ($level === 'advanced') ? 'selected' : '' ?>>🌳 Lanjutan (Fokus Proyek Riil)</option>
                    </select>
                </div>

                <!-- Durasi / Jumlah Sesi -->
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                        Alokasi Pertemuan:
                    </label>
                    <select name="session_count" style="width: 100%; padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 13px; outline: none; background: var(--white);">
                        <option value="1" <?= ($session_count === 1) ? 'selected' : '' ?>>1 Sesi Kilat (90 Menit)</option>
                        <option value="2" <?= ($session_count === 2) ? 'selected' : '' ?>>2 Sesi Intensif (2 x 90 Menit)</option>
                        <option value="4" <?= ($session_count === 4) ? 'selected' : '' ?>>4 Sesi Mini-Bootcamp (1 Bulan)</option>
                    </select>
                </div>

                <!-- Target Belajar -->
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--ink); margin-bottom: 6px;">
                        Tujuan Utama yang Dikejar:
                    </label>
                    <select name="learning_goal" style="width: 100%; padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); font-family: inherit; font-size: 13px; outline: none; background: var(--white);">
                        <option value="portfolio" <?= ($learning_goal === 'portfolio') ? 'selected' : '' ?>>🎨 Bangun Proyek Portofolio</option>
                        <option value="exam" <?= ($learning_goal === 'exam') ? 'selected' : '' ?>>📚 Lolos Ujian / Tugas Besar Kampus</option>
                        <option value="career" <?= ($learning_goal === 'career') ? 'selected' : '' ?>>💼 Persiapan Magang & Kerja</option>
                        <option value="fundamentals" <?= ($learning_goal === 'fundamentals') ? 'selected' : '' ?>>🧠 Penguasaan Konsep Inti</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <?= icon('sparkles', '', '16px') ?> Hasilkan Silabus AI
                </button>
            </form>
        </div>

        <!-- KOLOM KANAN: HASIL SILABUS PEMBELAJARAN -->
        <div>
            <?php if (!$generated_plan): ?>
                <div style="background: var(--white); border-radius: var(--radius-xl); padding: 48px 32px; border: 1px dashed var(--gray-300); text-align: center; color: var(--gray-400);">
                    <div style="color: var(--gray-400); margin-bottom: 12px; display: flex; justify-content: center;">
                        <?= icon('brain', '', '48px') ?>
                    </div>
                    <strong style="font-size: 16px; color: var(--ink); display: block; margin-bottom: 6px;">
                        Pilih Skill & Klik "Hasilkan Silabus AI"
                    </strong>
                    <p style="font-size: 13px; color: var(--gray-600); max-width: 440px; margin: 0 auto;">
                        AI akan secara otomatis merumuskan breakdown jadwal, breakdown waktu mengajar, tantangan hands-on, dan rubrik evaluasi yang siap kamu pakai saat barter.
                    </p>
                </div>
            <?php else: ?>
                <div style="background: var(--white); border-radius: var(--radius-xl); padding: 32px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm);" id="plan-container">
                    
                    <!-- Header Rencana -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <span class="badge-tag" style="background: var(--mint); color: var(--mint-dark); margin-bottom: 8px; display: inline-flex; align-items: center; gap: 4px;">
                                <?= icon('check-circle', '', '13px') ?> Silabus AI Siap Digunakan
                            </span>
                            <h2 style="font-size: 24px; font-weight: 800; color: var(--ink);">
                                Kurikulum Barter: <?= e($generated_plan['skill']) ?>
                            </h2>
                            <span style="font-size: 13px; color: var(--gray-600);">
                                <?= e($generated_plan['level_text']) ?> • Target: <?= e($generated_plan['goal_text']) ?>
                            </span>
                        </div>
                        
                        <button onclick="copyPlanToClipboard()" class="btn btn-outline btn-sm" id="copy-btn">
                            Salin Teks Silabus
                        </button>
                    </div>

                    <!-- Breakdown Sesi demi Sesi -->
                    <div style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 32px;">
                        <?php foreach ($generated_plan['sessions'] as $idx => $s): ?>
                            <div style="border: 1px solid var(--gray-200); border-radius: var(--radius-lg); padding: 20px; background: #fafafa;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <strong style="font-size: 15px; color: var(--blue-600);">
                                        <?= e($s['sesi']) ?>
                                    </strong>
                                </div>
                                <span style="font-size: 12px; font-weight: 700; color: var(--ink); display: block; margin-bottom: 12px;">
                                    Fokus Utama: <?= e($s['fokus']) ?>
                                </span>

                                <div style="background: var(--white); border-radius: var(--radius-md); padding: 14px; border: 1px solid var(--gray-200); display: flex; flex-direction: column; gap: 8px;">
                                    <?php foreach ($s['rundown'] as $time_block => $desc): ?>
                                        <div style="display: grid; grid-template-columns: 130px 1fr; gap: 12px; font-size: 13px;">
                                            <strong style="color: var(--gray-600); font-size: 12px;"><?= e($time_block) ?></strong>
                                            <span style="color: var(--ink);"><?= e($desc) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Rekomendasi Mini Project -->
                    <div style="background: var(--blue-50); border: 1px solid var(--blue-200); border-radius: var(--radius-lg); padding: 20px; margin-bottom: 24px;">
                        <span style="font-size: 11px; font-weight: 800; color: var(--blue-600); text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                            <?= icon('lightbulb', '', '14px') ?> Rekomendasi Proyek Praktik Hands-on
                        </span>
                        <p style="font-size: 14px; font-weight: 700; color: var(--ink); margin: 6px 0 0;">
                            <?= e($generated_plan['project']) ?>
                        </p>
                    </div>

                    <!-- Rubrik Evaluasi Peer -->
                    <div style="margin-bottom: 28px;">
                        <h4 style="font-size: 15px; font-weight: 800; color: var(--ink); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                            <?= icon('book-open', '', '16px') ?> Rubrik Evaluasi Pertukaran (Penilaian Akhir)
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <?php foreach ($generated_plan['rubrics'] as $crit => $rubric_desc): ?>
                                <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-md); padding: 12px;">
                                    <strong style="font-size: 12px; color: var(--blue-600); display: block; margin-bottom: 4px;">
                                        <?= e($crit) ?>
                                    </strong>
                                    <p style="font-size: 12px; color: var(--gray-600); margin: 0; line-height: 1.4;">
                                        <?= e($rubric_desc) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Banner Kontribusi SDG 4 -->
                    <div style="padding: 14px 18px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-md); font-size: 12px; color: #15803d; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 10px;">
                        <span style="display: inline-flex; margin-top: 2px;"><?= icon('award', '', '18px') ?></span>
                        <div>
                            <strong>Dampak Pendidikan Berkualitas (SDG 4):</strong><br>
                            <?= e($generated_plan['sdg_competency']) ?>
                        </div>
                    </div>

                    <!-- CTA Gunakan di Request Pertukaran -->
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="discover.php?q=<?= urlencode($generated_plan['skill']) ?>" class="btn btn-primary" style="flex: 1; text-align: center;">
                            Cari Partner <?= e($generated_plan['skill']) ?> Sekarang ↗
                        </a>
                        <a href="exchanges.php" class="btn btn-outline" style="flex: 1; text-align: center;">
                            Ke Daftar Barter Saya
                        </a>
                    </div>

                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- SCRIPT UNTUK SALIN SILABUS -->
<script>
function copyPlanToClipboard() {
    const container = document.getElementById('plan-container');
    if (!container) return;
    
    // Ambil teks bersih dari silabus
    const textToCopy = `=== SILABUS TUKARSKILL AI ===\nSkill: <?= addslashes($generated_plan['skill'] ?? '') ?>\nLevel: <?= addslashes($generated_plan['level_text'] ?? '') ?>\nTarget: <?= addslashes($generated_plan['goal_text'] ?? '') ?>\n\nProyek Rekomendasi:\n<?= addslashes($generated_plan['project'] ?? '') ?>\n\n(Dihasilkan otomatis oleh TukarSkill AI Study Planner)`;
    
    navigator.clipboard.writeText(textToCopy).then(() => {
        const btn = document.getElementById('copy-btn');
        btn.innerText = '✓ Berhasil Disalin!';
        btn.style.borderColor = '#16a34a';
        btn.style.color = '#16a34a';
        setTimeout(() => {
            btn.innerText = '📋 Salin Teks Silabus';
            btn.style.borderColor = '';
            btn.style.color = '';
        }, 2500);
    }).catch(err => {
        alert('Gagal menyalin teks: ' + err);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
