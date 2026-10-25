<?php
// ==============================================================
// includes/footer.php
// template footer hitam modern & pemanggil script JS di paling bawah
// ==============================================================
?>

<!-- Footer Modern Hitam Gelap -->
<footer class="footer">
    <div class="container">
        
        <!-- Showcase Komitmen SDG: Plain border-radius container (Sesuai Referensi Pengguna) -->
        <div class="footer-sdg-showcase" style="margin-bottom: 48px; padding-bottom: 44px; border-bottom: 1px solid rgba(255, 255, 255, 0.12);">
            <div class="sdg-showcase-header" style="text-align: center; max-width: 680px; margin: 0 auto 26px;">
                <span class="sdg-subheading" style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #38bdf8; margin-bottom: 8px;">Platform Inklusif Mahasiswa Indonesia</span>
                <h3 class="sdg-main-heading" style="font-size: 24px; font-weight: 900; color: #ffffff; letter-spacing: -0.5px; margin: 0 0 8px;">Komitmen Nyata Sustainable Development Goals</h3>
                <p class="sdg-caption" style="font-size: 13px; color: #94a3b8; line-height: 1.6; margin: 0;">TukarSkill dibangun di atas prinsip pemerataan akses pendidikan, menghapus batasan biaya les privat antar mahasiswa.</p>
            </div>

            <!-- Plain border-radius container dengan grid info kotak (Clean White Box persis referensi) -->
            <div class="sdg-plain-container" style="background: #ffffff; border-radius: 18px; padding: 22px 24px; display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; box-shadow: 0 14px 34px rgba(0, 0, 0, 0.28); border: 1px solid #e2e8f0;">
                <div class="sdg-slot-item" style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 10px 6px; border-radius: 12px; transition: transform 0.2s ease;">
                    <div class="sdg-slot-icon" style="color: #2563eb; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; height: 36px;"><?= icon('graduation-cap', '', 26) ?></div>
                    <strong class="sdg-slot-title" style="color: #0f172a; font-size: 13px; font-weight: 800; display: block; line-height: 1.2;">SDG 4</strong>
                    <span class="sdg-slot-desc" style="color: #64748b; font-size: 11px; font-weight: 600; display: block; margin-top: 4px; line-height: 1.3;">Quality Education</span>
                </div>
                <div class="sdg-slot-item" style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 10px 6px; border-radius: 12px; transition: transform 0.2s ease;">
                    <div class="sdg-slot-icon" style="color: #2563eb; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; height: 36px;"><?= icon('target', '', 26) ?></div>
                    <strong class="sdg-slot-title" style="color: #0f172a; font-size: 13px; font-weight: 800; display: block; line-height: 1.2;">Target 4.4</strong>
                    <span class="sdg-slot-desc" style="color: #64748b; font-size: 11px; font-weight: 600; display: block; margin-top: 4px; line-height: 1.3;">Kesiapan Kerja</span>
                </div>
                <div class="sdg-slot-item" style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 10px 6px; border-radius: 12px; transition: transform 0.2s ease;">
                    <div class="sdg-slot-icon" style="color: #2563eb; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; height: 36px;"><?= icon('scale', '', 26) ?></div>
                    <strong class="sdg-slot-title" style="color: #0f172a; font-size: 13px; font-weight: 800; display: block; line-height: 1.2;">SDG 10</strong>
                    <span class="sdg-slot-desc" style="color: #64748b; font-size: 11px; font-weight: 600; display: block; margin-top: 4px; line-height: 1.3;">Akses Merata</span>
                </div>
                <div class="sdg-slot-item" style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 10px 6px; border-radius: 12px; transition: transform 0.2s ease;">
                    <div class="sdg-slot-icon" style="color: #2563eb; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; height: 36px;"><?= icon('trending-up', '', 26) ?></div>
                    <strong class="sdg-slot-title" style="color: #0f172a; font-size: 13px; font-weight: 800; display: block; line-height: 1.2;">SDG 8</strong>
                    <span class="sdg-slot-desc" style="color: #64748b; font-size: 11px; font-weight: 600; display: block; margin-top: 4px; line-height: 1.3;">Skill Relevan</span>
                </div>
                <div class="sdg-slot-item" style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 10px 6px; border-radius: 12px; transition: transform 0.2s ease;">
                    <div class="sdg-slot-icon" style="color: #2563eb; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; height: 36px;"><?= icon('repeat', '', 26) ?></div>
                    <strong class="sdg-slot-title" style="color: #0f172a; font-size: 13px; font-weight: 800; display: block; line-height: 1.2;">Peer Barter</strong>
                    <span class="sdg-slot-desc" style="color: #64748b; font-size: 11px; font-weight: 600; display: block; margin-top: 4px; line-height: 1.3;">100% Bebas Biaya</span>
                </div>
                <div class="sdg-slot-item" style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 10px 6px; border-radius: 12px; transition: transform 0.2s ease;">
                    <div class="sdg-slot-icon" style="color: #2563eb; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; height: 36px;"><?= icon('shield-check', '', 26) ?></div>
                    <strong class="sdg-slot-title" style="color: #0f172a; font-size: 13px; font-weight: 800; display: block; line-height: 1.2;">Sertifikasi</strong>
                    <span class="sdg-slot-desc" style="color: #64748b; font-size: 11px; font-weight: 600; display: block; margin-top: 4px; line-height: 1.3;">Verifikasi Portofolio</span>
                </div>
            </div>
        </div>

        <div class="footer-grid">
            <!-- Kolom Brand & Tagline -->
            <div class="footer-brand">
                <a href="index.php" class="brand-logo" style="color: #ffffff; margin-bottom: 14px; display: inline-flex; align-items: center; gap: 10px; text-decoration: none;">
                    <div class="logo-icon" style="background: var(--blue-500); width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; color: #ffffff; box-shadow: 0 4px 14px rgba(37,99,235,0.35);">
                        <?= icon('handshake', '', 20) ?>
                    </div>
                    <span style="color: #ffffff !important; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;">Tukar<strong style="color: #60a5fa !important;">Skill</strong></span>
                </a>
                <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin: 0 0 16px;">Platform pertukaran kemampuan antar mahasiswa pertama di Indonesia. Saling mengajar, saling belajar, tanpa biaya kursus.</p>
                
                <!-- Kotak Info Ringkas SDG (Plain Border-Radius Container) -->
                <div class="footer-sdg-compact-box" style="background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 12px; padding: 12px 14px; display: flex; align-items: center; gap: 12px; max-width: 320px;">
                    <div class="compact-box-icon" style="width: 34px; height: 34px; border-radius: 8px; background: rgba(56, 189, 248, 0.12); color: #38bdf8; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><?= icon('award', '', 18) ?></div>
                    <div>
                        <strong style="color: #ffffff; font-size: 12px; font-weight: 800; display: block; line-height: 1.2;">SDG 4 & 10 Certified Initiative</strong>
                        <span style="color: #94a3b8; font-size: 11px; display: block; margin-top: 2px; line-height: 1.3;">Akses pembelajaran setara tanpa komersialisasi</span>
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
