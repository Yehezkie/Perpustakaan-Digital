<?php
// ===================================================
// PROTEKSI AKSES LANGSUNG
// ===================================================
if (basename($_SERVER['PHP_SELF']) == 'config.php') {
    die('Direct access not allowed');
}

// ===================================================
// SESSION MANAGEMENT
// ===================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'name' => 'perpustakaan_session',
        'cookie_lifetime' => 0,
        'cookie_secure' => false,
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// ===================================================
// KONEKSI DATABASE
// ===================================================
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'perpustakaan_digital';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    // Jangan tampilkan error detail ke user
    error_log("DB Connection Error: " . mysqli_connect_error());
    die("System maintenance. Please try again later.");
}

// ===================================================
// TIMEZONE & LOCALIZATION
// ===================================================
date_default_timezone_set('Asia/Jakarta');
setlocale(LC_TIME, 'id_ID');

// ===================================================
// ERROR HANDLING
// ===================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ===================================================
// PATH & URL CONFIGURATION
// ===================================================
define('BASE_URL', 'http://localhost/digital_library');
define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));

// ===================================================
// APPLICATION CONSTANTS
// ===================================================
define('SITE_NAME', 'Perpustakaan Digital');
define('MAX_PINJAM_HARI', 3);
define('NOTIF_TERLAMBAT_HARI', 3);
define('MAX_BOOK_COPIES', 20);

// ===================================================
// SECURITY SETTINGS
// ===================================================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ===================================================
// WHATSAPP NOTIFICATION SETTINGS
// ===================================================
define('WA_API_ENABLED', true);
define('WA_API_URL', 'https://app.whacenter.com/api/send');
define('WA_DEVICE_ID', 'DEVICE_ID_ANDA'); // Ganti dengan device ID Anda
define('WA_API_KEY', 'API_KEY_ANDA');    // Ganti dengan API key Anda

// ===================================================
// DATABASE SETTINGS
// ===================================================
mysqli_set_charset($conn, 'utf8mb4');
mysqli_query($conn, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
mysqli_query($conn, "SET time_zone = '" . date('P') . "'");

// ===================================================
// UTILITY FUNCTIONS
// ===================================================
require_once 'utilities.php';

// ===================================================
// DDC CATEGORIES
// ===================================================
$ddc_categories = [
    '000' => 'Karya Umum',
    '100' => 'Filsafat',
    '200' => 'Agama',
    '300' => 'Ilmu Sosial',
    '400' => 'Bahasa',
    '500' => 'Ilmu Murni',
    '600' => 'Teknologi',
    '700' => 'Kesenian',
    '800' => 'Sastra',
    '900' => 'Sejarah & Geografi'
];