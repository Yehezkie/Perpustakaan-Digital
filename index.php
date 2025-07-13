<?php
session_start();
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // PERBAIKAN KEAMANAN: Gunakan prepared statement
    $stmt = $conn->prepare("SELECT * FROM staff WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $staff = $result->fetch_assoc();
        if (password_verify($password, $staff['password'])) {
            $_SESSION['staff_logged_in'] = true;
            $_SESSION['staff_username'] = $staff['username'];
            $_SESSION['staff_nama'] = $staff['nama'];
            header('Location: admin/dashboard.php');
            exit();
        } else {
            $error = "Kombinasi username dan password tidak valid";
        }
    } else {
        $error = "Pengguna tidak ditemukan";
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Perpustakaan Digital</title>
    <style>
        /* === VARIABLES === */
        :root {
            --primary-dark: #1B5E20;
            --primary-medium: #43A047;
            --primary-light: #C8E6C9;
            --accent-color: #FFD54F;
            --white: #FFFFFF;
            --light-gray: #F8F9FA;
            --medium-gray: #E0E0E0;
            --dark-gray: #343A40;
            --danger: #DC3545;
            --success: #28A745;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        /* === RESET & BASE STYLES === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-light) 0%, #E8F5E9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.6;
            color: var(--dark-gray);
            padding: 20px;
        }

        /* === FORM ELEMENTS === */
        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--medium-gray);
            border-radius: 12px;
            font-size: 16px;
            transition: var(--transition);
            background-color: var(--white);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary-medium);
            box-shadow: 0 0 0 4px rgba(67, 160, 71, 0.2);
        }

        .input-group {
            display: flex;
            margin-bottom: 20px;
            position: relative;
        }

        .input-group-text {
            background: var(--primary-light);
            border: none;
            border-radius: 12px 0 0 12px;
            padding: 0 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
        }

        .input-group input {
            border-radius: 0 12px 12px 0;
            flex: 1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-gray);
        }

        /* === BUTTONS === */
        .btn {
            display: inline-block;
            padding: 14px 24px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
        }

        .btn-login {
            background: var(--primary-dark);
            color: var(--white);
        }

        .btn-login:hover {
            background: var(--primary-medium);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* === ALERTS === */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 320px;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            box-shadow: var(--shadow);
            animation: slideIn 0.5s forwards, fadeOut 0.5s forwards 4.5s;
            opacity: 0;
            transform: translateX(100%);
        }

        .alert-danger {
            background-color: #F8D7DA;
            color: #721C24;
            border-left: 4px solid var(--danger);
        }

        .alert-success {
            background-color: #D4EDDA;
            color: #155724;
            border-left: 4px solid var(--success);
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 20px;
            margin-left: 15px;
            cursor: pointer;
            color: inherit;
        }

        /* === LAYOUT === */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .login-container {
            max-width: 400px;
            width: 100%;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            transition: transform 0.4s ease;
        }

        .login-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium));
            padding: 40px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .login-header::after {
            content: "";
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        /* === LOGO === */
        .library-logo {
            width: 90px;
            height: 90px;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
            animation: float 3s ease-in-out infinite;
            position: relative;
            z-index: 1;
        }

        /* === TYPOGRAPHY === */
        h1,
        h2,
        h3 {
            font-weight: 700;
            line-height: 1.2;
        }

        .login-header h2 {
            font-size: 28px;
            color: var(--white);
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .login-header p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .text-center {
            text-align: center;
        }

        .small {
            font-size: 14px;
        }

        .text-muted {
            color: #6C757D;
        }

        /* === CARD BODY === */
        .card-body {
            padding: 30px;
        }

        .mb-3 {
            margin-bottom: 24px;
        }

        .mb-4 {
            margin-bottom: 32px;
        }

        .mt-4 {
            margin-top: 32px;
        }

        .pt-2 {
            padding-top: 8px;
        }

        .border-top {
            border-top: 1px solid var(--medium-gray);
        }

        /* === ANIMATIONS === */
        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        /* === VALIDATION STYLES === */
        .needs-validation .form-control:invalid {
            border-color: var(--danger);
        }

        .needs-validation .form-control:invalid:focus {
            box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.2);
        }

        /* === ICON STYLES === */
        .icon {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            vertical-align: middle;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6C757D;
            z-index: 2;
        }

        /* === RESPONSIVE ADJUSTMENTS === */
        @media (max-width: 576px) {
            .login-container {
                padding: 10px;
            }

            .login-card {
                border-radius: 16px;
            }

            .login-header {
                padding: 30px 15px;
            }

            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <div class="d-flex align-items-center">
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12" y2="16"></line>
                </svg>
                <div><?= $error ?></div>
            </div>
            <button type="button" class="btn-close">&times;</button>
        </div>
    <?php endif; ?>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <svg class="library-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                    <path d="M9 10h6"></path>
                    <path d="M9 14h6"></path>
                    <path d="M9 2v20"></path>
                </svg>
                <h2 class="text-white mb-1">Perpustakaan Digital</h2>
                <p class="text-white-50 mb-0">Staff Login System</p>
            </div>

            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Username
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </span>
                            <input type="text" class="form-control" id="username" name="username"
                                placeholder="Masukkan username" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password">
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4">
                                    </path>
                                </svg>
                            </span>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Masukkan password" required>
                            <span class="password-toggle" id="passwordToggle">
                                <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login mb-3">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        Masuk ke Sistem
                    </button>

                    <div class="text-center mt-4 pt-2 border-top">
                        <p class="small text-muted mb-0">
                            © <?= date('Y') ?> Sistem Perpustakaan Digital v2.1
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Form Validation
        document.addEventListener('DOMContentLoaded', function () {
            // Close alert button
            document.querySelectorAll('.btn-close').forEach(button => {
                button.addEventListener('click', function () {
                    this.closest('.alert').style.display = 'none';
                });
            });

            // Form validation
            const forms = document.querySelectorAll('.needs-validation');

            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();

                        // Add shake animation to invalid fields
                        const invalidFields = form.querySelectorAll(':invalid');
                        invalidFields.forEach(field => {
                            field.style.animation = 'shake 0.5s';
                            setTimeout(() => {
                                field.style.animation = '';
                            }, 500);
                        });
                    }

                    form.classList.add('was-validated');
                }, false);
            });

            // Password toggle functionality
            const passwordField = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');

            if (passwordToggle) {
                passwordToggle.addEventListener('click', function () {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);

                    // Change icon
                    if (type === 'text') {
                        this.innerHTML = `
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        `;
                    } else {
                        this.innerHTML = `
                            <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        `;
                    }
                });
            }
        });
    </script>
</body>

</html>