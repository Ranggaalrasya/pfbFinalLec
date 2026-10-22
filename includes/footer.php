<?php
// ==============================================================
// includes/footer.php
// template footer hitam modern & pemanggil script JS di paling bawah
// ==============================================================
?>

<!-- Footer Modern Hitam Gelap -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Kolom Brand & Tagline -->
            <div class="footer-brand">
                <a href="index.php" class="brand-logo" style="color: var(--white); margin-bottom: 12px; display: inline-flex;">
                    <div class="logo-icon">🤝</div>
                    <span>Tukar<strong>Skill</strong></span>
                </a>
                <p>Platform pertukaran kemampuan antar mahasiswa pertama di Indonesia. Saling mengajar, saling belajar, tanpa biaya kursus.</p>
                <div style="margin-top: 16px;">
                    <span class="footer-sdg-tag">🎓 Mendukung SDG 4 — Quality Education</span>
                    <span class="footer-sdg-tag">⚖️ SDG 10 Reduced Inequalities</span>
                </div>
            </div>

            <!-- Kolom Navigasi Cepat -->
            <div class="footer-col">
                <h4>Navigasi</h4>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="discover.php">Cari Partner Skill</a></li>
                    <li><a href="index.php#cara-kerja">Cara Kerja Barter</a></li>
                    <li><a href="index.php#faq">Pertanyaan Umum (FAQ)</a></li>
                </ul>
            </div>

            <!-- Kolom Kategori Terpopuler -->
            <div class="footer-col">
                <h4>Kategori Utama</h4>
                <ul>
                    <li><a href="discover.php?category=1">Pemrograman & Web</a></li>
                    <li><a href="discover.php?category=2">Desain Grafis & UI/UX</a></li>
                    <li><a href="discover.php?category=3">Bahasa Asing & TOEFL</a></li>
                    <li><a href="discover.php?category=5">Public Speaking</a></li>
                </ul>
            </div>

            <!-- Kolom Tim Pengembang (Kelompok 3 PFB) -->
            <div class="footer-col">
                <h4>Kelompok 3 — PFB</h4>
                <p style="font-size: 13px; color: var(--gray-400); margin-bottom: 8px;">Dibuat untuk Final Project Mata Kuliah Programming for Business:</p>
                <ul style="font-size: 12px; color: var(--gray-400);">
                    <li>• Rangga Alrasya Nazriel A. (2902691122)</li>
                    <li>• Jennifer Lovinda T. (2902662860)</li>
                    <li>• Liftya Muthi Pradipta (2902666165)</li>
                    <li>• Lucky Fadlan Kusuma A. (2902656750)</li>
                    <li>• Revano Hartojo (2902703734)</li>
                </ul>
            </div>
        </div>

        <!-- Bagian Hak Cipta Paling Bawah -->
        <div class="footer-bottom">
            <div>
                © <?= date('Y') ?> <strong>TukarSkill</strong>. All rights reserved. Slogan: <em>Tukar Skill. Dapat Skill.</em>
            </div>
            <div>
                Built with Native PHP 8.2 & MySQLi • No Frameworks
            </div>
        </div>
    </div>
</footer>

<!-- Panggil file javascript interaktif kita -->
<script src="assets/js/script.js"></script>

</body>
</html>
