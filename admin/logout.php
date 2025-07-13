<?php
session_start();
require_once '../includes/config.php';

// Hapus semua data sesi
session_unset();
session_destroy();

// Redirect ke halaman login setelah 3 detik
header("refresh:3;url=../index.php");
?>

<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Sistem Perpustakaan Digital</title>
    <style>
        :root {
            --primary-dark: #1B5E20;
            --primary-medium: #43A047;
            --primary-light: #C8E6C9;
            --accent-color: #FFD54F;
            --white: #FFFFFF;
            --light-gray: #F8F9FA;
            --dark-gray: #343A40;
            --success: #28A745;
            --transition: all 0.4s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.6;
            color: var(--white);
            padding: 20px;
            overflow: hidden;
        }

        .logout-container {
            max-width: 500px;
            width: 100%;
            text-align: center;
            z-index: 10;
        }

        .logout-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
            padding: 50px 30px;
            position: relative;
            overflow: hidden;
            transform: scale(0.95);
            animation: scaleIn 0.6s forwards;
        }

        @keyframes scaleIn {
            to {
                transform: scale(1);
            }
        }

        .logout-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, var(--primary-medium), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-15px);
            }

            100% {
                transform: translateY(0);
            }
        }

        .logout-icon svg {
            width: 60px;
            height: 60px;
            stroke: var(--white);
            stroke-width: 2;
        }

        .logout-title {
            font-size: 32px;
            margin-bottom: 15px;
            color: var(--primary-dark);
        }

        .logout-message {
            font-size: 18px;
            margin-bottom: 30px;
            color: var(--dark-gray);
        }

        .countdown {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-medium);
            margin-bottom: 30px;
        }

        .redirect-message {
            font-size: 14px;
            color: #666;
            margin-top: 20px;
        }

        .background-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            top: -100px;
            right: -100px;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: -80px;
            left: -50px;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            top: 30%;
            left: 10%;
        }

        .shape-4 {
            width: 100px;
            height: 100px;
            bottom: 20%;
            right: 20%;
        }

        .progress-bar {
            height: 8px;
            background: var(--light-gray);
            border-radius: 4px;
            overflow: hidden;
            margin: 0 auto;
            max-width: 300px;
        }

        .progress {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--primary-light), var(--primary-medium));
            animation: progressBar 3s linear forwards;
        }

        @keyframes progressBar {
            0% {
                width: 0%;
            }

            100% {
                width: 100%;
            }
        }

        .login-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: var(--primary-dark);
            color: var(--white);
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
            border: 2px solid var(--primary-dark);
        }

        .login-link:hover {
            background: transparent;
            color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Responsiveness */
        @media (max-width: 576px) {
            .logout-card {
                padding: 40px 20px;
            }

            .logout-title {
                font-size: 26px;
            }

            .logout-message {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <div class="logout-container">
        <div class="logout-card">
            <div class="logout-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </div>

            <h1 class="logout-title">Anda Telah Logout</h1>
            <p class="logout-message">Sesi Anda telah berakhir dengan aman. Terima kasih telah menggunakan sistem
                perpustakaan digital kami.</p>

            <div class="countdown">
                <span id="countdown">3</span> detik menuju halaman login
            </div>

            <div class="progress-bar">
                <div class="progress"></div>
            </div>

            <p class="redirect-message">Jika tidak terjadi pengalihan otomatis, silakan klik tombol di bawah</p>
            <a href="../index.php" class="login-link">Kembali ke Login</a>
        </div>
    </div>

    <script>
        // Countdown timer
        let seconds = 3;
        const countdownElement = document.getElementById('countdown');

        const countdownInterval = setInterval(() => {
            seconds--;
            countdownElement.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    </script>
</body>

</html>