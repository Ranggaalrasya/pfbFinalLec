// ==============================================================
// assets/js/script.js
// script javascript interaktif buat animasi, menu mobile & accordion FAQ
// ==============================================================

document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------------
    // 1. Menu Mobile Hamburger Toggle
    // ----------------------------------------------------------
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', () => {
            navLinks.classList.toggle('show');
            const isOpen = navLinks.classList.contains('show');
            mobileToggle.setAttribute('aria-expanded', isOpen);
            mobileToggle.innerHTML = isOpen ? '✕' : '☰';
        });
    }

    // ----------------------------------------------------------
    // 2. FAQ Accordion (buka satu, tutup yang lain)
    // ----------------------------------------------------------
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const questionBtn = item.querySelector('.faq-question');
        if (questionBtn) {
            questionBtn.addEventListener('click', () => {
                const isActive = item.classList.contains('active');
                
                // tutup semua faq lain yang kebuka
                faqItems.forEach(other => {
                    if (other !== item) {
                        other.classList.remove('active');
                    }
                });

                // toggle item yang lagi diklik
                item.classList.toggle('active', !isActive);
            });
        }
    });

    // ----------------------------------------------------------
    // 3. Animasi Chip Barter di Hero (berganti pasangan skill tiap 3 detik)
    // ----------------------------------------------------------
    const swapChip = document.getElementById('hero-swap-text');
    if (swapChip) {
        const swapPairs = [
            '🎨 UI/UX Design ⇄ 💻 Web Dev PHP',
            '🗣️ TOEFL English ⇄ 🐍 Python Scripting',
            '🎬 Video Editing ⇄ 📊 Data Analysis',
            '🎤 Public Speaking ⇄ 📸 Fotografi Studio',
            '✨ Saling Belajar ⇄ 🚀 Nol Biaya Kursus'
        ];
        let pairIndex = 0;

        setInterval(() => {
            pairIndex = (pairIndex + 1) % swapPairs.length;
            swapChip.style.opacity = '0';
            swapChip.style.transform = 'translateY(6px)';
            
            setTimeout(() => {
                swapChip.textContent = swapPairs[pairIndex];
                swapChip.style.opacity = '1';
                swapChip.style.transform = 'translateY(0)';
            }, 250);
        }, 3000);
    }

    // ----------------------------------------------------------
    // 4. Auto-Dismiss Toast Flash Message
    // ----------------------------------------------------------
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        setTimeout(() => {
            toast.style.transition = 'all 0.4s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    });

    // ----------------------------------------------------------
    // 5. Filter Kategori di Beranda (Skill Paling Dicari)
    // ----------------------------------------------------------
    const filterPills = document.querySelectorAll('.filter-pill');
    const skillCards = document.querySelectorAll('.skill-card');

    filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
            filterPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');

            const category = pill.getAttribute('data-category');

            skillCards.forEach(card => {
                if (category === 'all' || card.getAttribute('data-category') === category) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
