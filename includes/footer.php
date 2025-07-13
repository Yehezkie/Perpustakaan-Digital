<?php
// Dapatkan tahun saat ini untuk copyright
$current_year = date('Y');
?>

</main><!-- Tutup tag main yang dibuka di header.php -->

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Tentang Perpustakaan -->
            <div class="footer-section">
                <h5><i class="footer-icon library-icon"></i> Tentang Kami</h5>
                <p>Perpustakaan Digital kami berkomitmen untuk menyediakan akses ke pengetahuan dan inspirasi melalui
                    koleksi buku yang beragam dan teknologi modern.</p>
            </div>

            <!-- Jam Operasional -->
            <div class="footer-section">
                <h5><i class="footer-icon clock-icon"></i> Jam Operasional</h5>
                <ul class="footer-list">
                    <li>Senin - Jumat: 07:00 - 16:00</li>
                    <li>Sabtu & Minggu: Tutup</li>
                </ul>
            </div>

            <!-- Tautan Sosial -->
            <div class="footer-section">
                <h5><i class="footer-icon social-icon"></i> Ikuti Kami</h5>
                <ul class="footer-list social-links">
                    <li><a href="https://instagram.com" target="_blank"><i class="footer-icon instagram-icon"></i>
                            Instagram</a></li>
                    <li><a href="https://twitter.com" target="_blank"><i class="footer-icon twitter-icon"></i>
                            Twitter</a></li>
                    <li><a href="https://facebook.com" target="_blank"><i class="footer-icon facebook-icon"></i>
                            Facebook</a></li>
                </ul>
            </div>

            <!-- Link Cepat -->
            <div class="footer-section">
                <h5><i class="footer-icon link-icon"></i> Link Cepat</h5>
                <ul class="footer-list">
                    <li><a href="<?= BASE_URL ?>/admin/buku.php">Daftar Buku</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/siswa.php">Data Siswa</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/laporan.php">Laporan</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/log_whatsapp.php">Notifikasi WhatsApp</a></li>
                </ul>
            </div>
        </div>

        <!-- Copyright & Info Sistem -->
        <div class="footer-bottom">
            <p class="copyright">
                © <?= $current_year ?> <?= SITE_NAME ?>. All rights reserved.

            </p>

        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top">
    <i class="arrow-up-icon"></i>
</button>

<style>
    /* ===== VARIABLES ===== */
    :root {
        --primary-color: #1b5e20;
        --secondary-color: #388e3c;
        --accent-color: #81c784;
        --light-color: #f5f5f5;
        --dark-color: #263238;
        --text-light: #ffffff;
        --text-dark: #212121;
        --shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        --transition: all 0.3s ease-in-out;
        --border-radius: 12px;
    }

    /* ===== RESET & BASE STYLES ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        line-height: 1.7;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    main {
        flex: 1;
    }

    a {
        text-decoration: none;
        color: inherit;
        transition: var(--transition);
    }

    ul {
        list-style: none;
    }

    /* ===== FOOTER STYLES ===== */
    .footer {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: var(--text-light);
        padding: 3rem 0 2rem;
        box-shadow: var(--shadow);
        margin-top: auto;
    }

    .container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 24px;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 2.5rem;
        margin-bottom: 2.5rem;
    }

    .footer-section {
        padding: 1rem;
    }

    .footer-section h5 {
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        position: relative;
        padding-bottom: 0.75rem;
        font-weight: 600;
    }

    .footer-section h5::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 4px;
        background-color: var(--accent-color);
        border-radius: 4px;
    }

    .footer-section p {
        font-size: 0.95rem;
        line-height: 1.8;
        opacity: 0.9;
    }

    .footer-list {
        padding: 0;
        margin: 0;
    }

    .footer-list li {
        margin-bottom: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: var(--transition);
    }

    .footer-list li:hover {
        transform: translateX(8px);
    }

    .footer-list a {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.95rem;
    }

    .footer-list a:hover {
        color: var(--accent-color);
    }

    .social-links a:hover {
        color: var(--light-color);
        transform: scale(1.05);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.15);
    }

    .copyright {
        margin-bottom: 0.75rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        font-size: 1rem;
    }

    .copyright span {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .system-info {
        font-size: 0.85rem;
        opacity: 0.7;
        margin-bottom: 0;
    }

    /* ===== ICON STYLES ===== */
    .footer-icon {
        display: inline-block;
        width: 22px;
        height: 22px;
    }

    .library-icon {
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M12 2.5C7.86 2.5 4.5 5.86 4.5 10c0 5.25 7.5 12 7.5 12s7.5-6.75 7.5-12c0-4.14-3.36-7.5-7.5-7.5zm0 10a2.5 2.5 0 110-5 2.5 2.5 0 010 5z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .clock-icon {
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .social-icon {
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .instagram-icon {
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M12 2.16c3.21 0 3.58.01 4.84.07 1.17.06 1.81.25 2.23.42.56.22.96.49 1.38.91.42.42.69.82.91 1.38.17.42.36 1.06.42 2.23.06 1.26.07 1.63.07 4.84s-.01 3.58-.07 4.84c-.06 1.17-.25 1.81-.42 2.23-.22.56-.49.96-.91 1.38-.42.42-.82.69-1.38.91-.42.17-1.06.36-2.23.42-1.26.06-1.63.07-4.84.07s-3.58-.01-4.84-.07c-1.17-.06-1.81-.25-2.23-.42-.56-.22-.96-.49-1.38-.91-.42-.42-.69-.82-.91-1.38-.17-.42-.36-1.06-.42-2.23-.06-1.26-.07-1.63-.07-4.84s.01-3.58.07-4.84c.06-1.17.25-1.81.42-2.23.22-.56.49-.96.91-1.38.42-.42.82-.69 1.38-.91.42-.17 1.06-.36 2.23-.42 1.26-.06 1.63-.07 4.84-.07m0-2.16c-3.25 0-3.66.01-4.94.07-1.28.06-2.16.26-2.93.56-.79.31-1.46.72-2.12 1.38-.66.66-1.07 1.33-1.38 2.12-.3.77-.5 1.65-.56 2.93-.06 1.28-.07 1.69-.07 4.94s.01 3.66.07 4.94c.06 1.28.26 2.16.56 2.93.31.79.72 1.46 1.38 2.12.66.66 1.33 1.07 2.12 1.38.77.3 1.65.5 2.93.56 1.28.06 1.69.07 4.94.07s3.66-.01 4.94-.07c1.28-.06 2.16-.26 2.93-.56.79-.31 1.46-.72 2.12-1.38.66-.66 1.07-1.33 1.38-2.12.3-.77.5-1.65.56-2.93.06-1.28.07-1.69.07-4.94s-.01-3.66-.07-4.94c-.06-1.28-.26-2.16-.56-2.93-.31-.79-.72-1.46-1.38-2.12-.66-.66-1.33-1.07-2.12-1.38-.77-.3-1.65-.5-2.93-.56-1.28-.06-1.69-.07-4.94-.07zm0 5.84c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm4.69-10.69c-.78 0-1.41.63-1.41 1.41s.63 1.41 1.41 1.41 1.41-.63 1.41-1.41-.63-1.41-1.41-1.41z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .twitter-icon {
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05-.78-.83-1.89-1.34-3.12-1.34-2.36 0-4.28 1.92-4.28 4.28 0 .34.04.67.11 1-3.56-.18-6.72-1.89-8.84-4.48-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.8 1.89 3.56-.69-.02-1.34-.21-1.91-.53v.05c0 2.08 1.48 3.82 3.44 4.21-.36.1-.74.15-1.13.15-.28 0-.55-.03-.81-.08.55 1.72 2.15 2.97 4.05 3.01-1.48 1.16-3.34 1.85-5.36 1.85-.35 0-.69-.02-1.03-.06 1.91 1.23 4.18 1.94 6.61 1.94 7.93 0 12.27-6.57 12.27-12.27 0-.19 0-.37-.01-.56.84-.61 1.57-1.37 2.15-2.24z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .facebook-icon {
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.99 3.66 9.13 8.44 9.88v-6.98h-2.54v-2.9h2.54v-2.21c0-2.51 1.49-3.89 3.78-3.89 1.09 0 2.23.19 2.23.19v2.46h-1.26c-1.24 0-1.62.77-1.62 1.56v1.88h2.76l-.44 2.9h-2.32v6.98c4.78-.75 8.44-4.89 8.44-9.88z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .link-icon {
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    .heart-icon {
        display: inline-block;
        width: 18px;
        height: 18px;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23ff8a80'%3E%3Cpath d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    /* ===== BACK TO TOP BUTTON ===== */
    .back-to-top {
        position: fixed;
        bottom: 40px;
        right: 40px;
        width: 60px;
        height: 60px;
        background-color: var(--primary-color);
        color: var(--text-light);
        border: none;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: var(--shadow);
        transition: var(--transition);
        display: none;
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .back-to-top:hover {
        background-color: var(--accent-color);
        transform: translateY(-8px);
    }

    .arrow-up-icon {
        display: inline-block;
        width: 28px;
        height: 28px;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M7 14l5-5 5 5z'/%3E%3C/svg%3E") center/contain no-repeat;
    }

    /* ===== RESPONSIVE STYLES ===== */
    @media (max-width: 1024px) {
        .footer-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }
    }

    @media (max-width: 768px) {
        .footer-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .footer-section {
            padding: 0.75rem;
        }

        .footer-section h5 {
            font-size: 1.2rem;
        }

        .copyright span {
            display: block;
            margin-top: 0.75rem;
        }
    }

    @media (max-width: 480px) {
        .footer {
            padding: 2rem 0 1.5rem;
        }

        .footer-section h5 {
            font-size: 1.1rem;
        }

        .footer-section p,
        .footer-list li,
        .footer-list a {
            font-size: 0.9rem;
        }

        .system-info {
            font-size: 0.8rem;
        }

        .back-to-top {
            width: 50px;
            height: 50px;
            bottom: 20px;
            right: 20px;
        }

        .arrow-up-icon {
            width: 24px;
            height: 24px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Back to Top Button
        const backToTop = document.querySelector('.back-to-top');

        function toggleBackToTop() {
            if (window.scrollY > 300) {
                backToTop.style.display = 'flex';
            } else {
                backToTop.style.display = 'none';
            }
        }

        window.addEventListener('scroll', toggleBackToTop);
        toggleBackToTop(); // Initial check

        backToTop.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Auto-close alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            }, 5000);
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const targetPosition = targetElement.getBoundingClientRect().top + window.scrollY;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    });
</script>