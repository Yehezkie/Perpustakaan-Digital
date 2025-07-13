<?php
require_once 'config.php';

// Start session jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(SITE_NAME) ?> - <?= htmlspecialchars($page_title ?? 'Perpustakaan Digital') ?></title>
    <style>
        /* ===== VARIABLES ===== */
        :root {
            --primary: #1B5E20;
            --primary-light: #4CAF50;
            --primary-lighter: #81C784;
            --primary-lightest: #C8E6C9;
            --secondary: #2E7D32;
            --accent: #FFD54F;
            --text: #212121;
            --text-light: #757575;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --success: #388E3C;
            --danger: #D32F2F;
            --warning: #F57C00;
            --info: #0288D1;
            --radius: 8px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        /* ===== BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        ul {
            list-style: none;
        }

        button {
            cursor: pointer;
            border: none;
            background: none;
            font-family: inherit;
        }

        /* ===== UTILITY CLASSES ===== */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .d-flex {
            display: flex;
        }

        .align-items-center {
            align-items: center;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .flex-column {
            flex-direction: column;
        }

        .gap-1 {
            gap: 0.5rem;
        }

        .gap-2 {
            gap: 1rem;
        }

        .gap-3 {
            gap: 1.5rem;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: var(--text-light);
        }

        .w-100 {
            width: 100%;
        }

        .mt-1 {
            margin-top: 0.5rem;
        }

        .mt-2 {
            margin-top: 1rem;
        }

        .mt-3 {
            margin-top: 1.5rem;
        }

        .mb-1 {
            margin-bottom: 0.5rem;
        }

        .mb-2 {
            margin-bottom: 1rem;
        }

        .mb-3 {
            margin-bottom: 1.5rem;
        }

        .p-2 {
            padding: 1rem;
        }

        .p-3 {
            padding: 1.5rem;
        }

        .rounded {
            border-radius: var(--radius);
        }

        .shadow {
            box-shadow: var(--shadow);
        }

        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius);
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-sm {
            padding: 0.3rem 0.7rem;
            font-size: 0.875rem;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-success {
            background-color: var(--success);
            color: white;
        }

        .badge-danger {
            background-color: var(--danger);
            color: white;
        }

        .badge-warning {
            background-color: var(--warning);
            color: white;
        }

        .badge-info {
            background-color: var(--info);
            color: white;
        }

        /* ===== NAVBAR STYLES ===== */
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary-lightest) 0%, var(--primary-lighter) 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .brand {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary);
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            transition: transform 0.3s;
            background-color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .brand-logo:hover {
            transform: rotate(-15deg);
        }

        .nav-toggler {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary);
            cursor: pointer;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            font-weight: 500;
            color: var(--primary);
            transition: var(--transition);
            gap: 0.5rem;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: var(--primary);
            color: white;
        }

        .dropdown {
            position: relative;
        }

        .dropdown-toggle::after {
            content: "▼";
            font-size: 0.6rem;
            margin-left: 0.3rem;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 200px;
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 0.5rem 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: var(--transition);
            z-index: 100;
        }

        .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: block;
            padding: 0.5rem 1.5rem;
            color: var(--text);
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background-color: var(--primary-lightest);
            color: var(--primary);
        }

        .wa-notification {
            position: relative;
        }

        .wa-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: var(--danger);
            color: white;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ===== ALERTS ===== */
        .alert-fixed {
            position: fixed;
            top: 70px;
            right: 20px;
            z-index: 1000;
            min-width: 300px;
            max-width: 90%;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            box-shadow: var(--shadow);
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger);
        }

        .alert-dismissible {
            position: relative;
            padding-right: 3rem;
        }

        .btn-close {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            transition: var(--transition);
        }

        .btn-close:hover {
            opacity: 1;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }

            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        /* ===== ICONS ===== */
        .icon {
            display: inline-block;
            width: 1em;
            height: 1em;
            vertical-align: middle;
            fill: currentColor;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .nav-toggler {
                display: block;
            }

            .nav-menu {
                position: fixed;
                top: 70px;
                left: -100%;
                width: 100%;
                background-color: var(--card-bg);
                flex-direction: column;
                padding: 1rem;
                box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
                transition: left 0.3s ease;
                height: calc(100vh - 70px);
                overflow-y: auto;
            }

            .nav-menu.active {
                left: 0;
            }

            .dropdown-menu {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                box-shadow: none;
                padding-left: 1rem;
                margin-top: 0.5rem;
            }
        }

        @media (max-width: 768px) {
            .alert-fixed {
                top: 60px;
                left: 50%;
                right: auto;
                transform: translateX(-50%);
                max-width: 95%;
            }
        }

        /* ===== MAIN CONTENT ===== */
        main {
            flex: 1;
            padding: 2rem 0;
        }
    </style>
</head>

<body>
    <!-- Alert Notifications -->
    <div class="alert-fixed">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" aria-label="Close">&times;</button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" aria-label="Close">&times;</button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </div>

    <!-- Navigation Bar -->
    <nav class="navbar-custom">
        <div class="navbar-container">
            <!-- Brand Logo -->
            <a class="brand" href="<?= BASE_URL ?>">
                <div class="brand-logo">PL</div>
                <span><?= SITE_NAME ?></span>
            </a>

            <!-- Mobile Toggle -->
            <button class="nav-toggler" id="navToggler">
                ☰
            </button>

            <!-- Navigation Items -->
            <ul class="nav-menu" id="navMenu">
                <?php if (isset($_SESSION['staff_logged_in'])): ?>
                    <!-- Admin Menu -->
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"
                            href="<?= BASE_URL ?>/admin/dashboard.php">
                            <span>📊 Dashboard</span>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['buku.php', 'siswa.php', 'log_whatsapp.php']) ? 'active' : '' ?>"
                            href="#">
                            <span>🗄 Master Data</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/admin/buku.php">
                                    <span>📚 Data Buku</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/admin/siswa.php">
                                    <span>👥 Data Siswa</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/admin/log_whatsapp.php">
                                    <span>📱 Log WhatsApp</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : '' ?>"
                            href="<?= BASE_URL ?>/admin/transaksi.php">
                            <span>🔄 Transaksi</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : '' ?>"
                            href="<?= BASE_URL ?>/admin/laporan.php">
                            <span>📈 Laporan</span>
                        </a>
                    </li>

                    <!-- Menu Notifikasi WhatsApp -->
                    <li class="nav-item">
                        <a class="nav-link wa-notification <?= basename($_SERVER['PHP_SELF']) == 'wa_notification.php' ? 'active' : '' ?>"
                            href="<?= BASE_URL ?>/admin/wa_notification.php">
                            <span>📨 Notifikasi</span>
                            <?php if (defined('WA_API_ENABLED') && WA_API_ENABLED): ?>
                                <span class="wa-badge">ON</span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#">
                            <span>👤 <?= $_SESSION['staff_username'] ?? 'Admin' ?></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <span>🚪 Logout</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php else: ?>
                    <!-- Guest Menu -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/index.php">
                            <span>🔑 Login</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <!-- CSRF Token -->
        <input type="hidden" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <script>
            // Fungsi utilitas umum
            const Utils = {
                debounce: (func, wait) => {
                    let timeout;
                    return (...args) => {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => func.apply(this, args), wait);
                    };
                },

                closeAlert: (element) => {
                    element.style.animation = 'slideOut 0.3s ease forwards';
                    setTimeout(() => {
                        element.remove();
                    }, 300);
                },

                initMobileMenu: () => {
                    const toggler = document.getElementById('navToggler');
                    const menu = document.getElementById('navMenu');

                    if (toggler && menu) {
                        toggler.addEventListener('click', (e) => {
                            e.stopPropagation();
                            menu.classList.toggle('active');
                        });
                    }

                    // Close menu when clicking outside
                    document.addEventListener('click', (e) => {
                        if (menu && !menu.contains(e.target) && toggler && !toggler.contains(e.target)) {
                            menu.classList.remove('active');
                        }
                    });
                },

                initDropdowns: () => {
                    document.querySelectorAll('.dropdown').forEach(dropdown => {
                        const toggle = dropdown.querySelector('.dropdown-toggle');
                        if (toggle) {
                            toggle.addEventListener('click', (e) => {
                                e.stopPropagation();
                                dropdown.classList.toggle('open');
                            });
                        }
                    });

                    // Close dropdowns when clicking outside
                    document.addEventListener('click', () => {
                        document.querySelectorAll('.dropdown').forEach(dropdown => {
                            dropdown.classList.remove('open');
                        });
                    });
                },

                initAlerts: () => {
                    document.querySelectorAll('.alert .btn-close').forEach(btn => {
                        btn.addEventListener('click', function () {
                            Utils.closeAlert(this.closest('.alert'));
                        });
                    });

                    // Auto close alerts after 5 seconds
                    document.querySelectorAll('.alert').forEach(alert => {
                        setTimeout(() => {
                            Utils.closeAlert(alert);
                        }, 5000);
                    });
                }
            };

            // Inisialisasi setelah DOM selesai dimuat
            document.addEventListener('DOMContentLoaded', () => {
                Utils.initMobileMenu();
                Utils.initDropdowns();
                Utils.initAlerts();
            });
        </script>