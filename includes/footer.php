<?php
// ==============================================================
// includes/footer.php
// template footer hitam modern & pemanggil script JS di paling bawah
// ==============================================================
?>

<!-- Footer Modern Hitam Gelap -->
<footer class="footer">
    <div class="container">
        
        <!-- Showcase Komitmen SDG: Plain border-radius container (Sesuai Referensi) -->
        <div class="footer-sdg-showcase">
            <div class="sdg-showcase-header">
                <span class="sdg-subheading">Platform Inklusif Mahasiswa Indonesia</span>
                <h3 class="sdg-main-heading">Komitmen Nyata Sustainable Development Goals</h3>
                <p class="sdg-caption">TukarSkill dibangun di atas prinsip pemerataan akses pendidikan, menghapus batasan biaya les privat antar mahasiswa.</p>
            </div>

            <!-- Plain border-radius container dengan grid info kotak -->
            <div class="sdg-plain-container">
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('graduation-cap', '', 26) ?></div>
                    <strong class="sdg-slot-title">SDG 4</strong>
                    <span class="sdg-slot-desc">Quality Education</span>
                </div>
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('target', '', 26) ?></div>
                    <strong class="sdg-slot-title">Target 4.4</strong>
                    <span class="sdg-slot-desc">Kesiapan Kerja</span>
                </div>
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('scale', '', 26) ?></div>
                    <strong class="sdg-slot-title">SDG 10</strong>
                    <span class="sdg-slot-desc">Reduced Inequalities</span>
                </div>
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('trending-up', '', 26) ?></div>
                    <strong class="sdg-slot-title">SDG 8</strong>
                    <span class="sdg-slot-desc">Pertumbuhan Karir</span>
                </div>
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('repeat', '', 26) ?></div>
                    <strong class="sdg-slot-title">Peer-to-Peer</strong>
                    <span class="sdg-slot-desc">100% Bebas Biaya</span>
                </div>
                <div class="sdg-slot-item">
                    <div class="sdg-slot-icon"><?= icon('shield-check', '', 26) ?></div>
                    <strong class="sdg-slot-title">Verifikasi</strong>
                    <span class="sdg-slot-desc">Sertifikat Sesi Resmi</span>
                </div>
            </div>
        </div>

        <div class="footer-grid">
            <!-- Kolom Brand & Tagline -->
            <div class="footer-brand">
                <a href="index.php" class="brand-logo" style="color: var(--white); margin-bottom: 14px; display: inline-flex; align-items: center; gap: 10px;">
                    <div class="logo-icon" style="background: var(--blue-500); width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 4px 14px rgba(37,99,235,0.35);">
                        <?= icon('handshake', '', 20) ?>
                    </div>
                    <span>Tukar<strong style="color: #60a5fa;">Skill</strong></span>
                </a>
                <p>Platform pertukaran kemampuan antar mahasiswa pertama di Indonesia. Saling mengajar, saling belajar, tanpa biaya kursus.</p>
                
                <!-- Kotak Info Ringkas SDG (Plain Border-Radius Container) -->
                <div class="footer-sdg-compact-box">
                    <div class="compact-box-icon"><?= icon('award', '', 18) ?></div>
                    <div>
                        <strong>SDG 4 & 10 Certified Initiative</strong>
                        <span>Akses pembelajaran setara tanpa komersialisasi</span>
                    </div>
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
