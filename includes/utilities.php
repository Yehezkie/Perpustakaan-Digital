<?php
/**
 * FILE: utilities.php
 * Kumpulan fungsi utilitas untuk aplikasi perpustakaan digital
 */

// ===================================================
// FUNGSI KEAMANAN
// ===================================================

/**
 * Membersihkan dan escape input data
 * @param string $data Input data
 * @return string Data yang sudah dibersihkan dan di-escape
 */
function clean_input($data)
{
    global $conn;

    if (!isset($data) || $data === null) {
        return '';
    }

    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    // Escape hanya jika koneksi database tersedia
    if ($conn) {
        return mysqli_real_escape_string($conn, $data);
    }

    return $data;
}

/**
 * Validasi CSRF token
 * @param string $token Token dari form
 * @return bool True jika valid
 */
function validate_csrf_token($token)
{
    return isset($_SESSION['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $token);
}

// ===================================================
// FUNGSI FORMATTING
// ===================================================

/**
 * Format tanggal ke bahasa Indonesia
 * @param string $date Tanggal dalam format Y-m-d
 * @return string Tanggal yang diformat (contoh: 12 Januari 2023)
 */
function format_date($date)
{
    if (empty($date) || $date == '0000-00-00')
        return '-';

    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($date);
    return date('j', $timestamp) . ' ' .
        $bulan[(int) date('n', $timestamp)] . ' ' .
        date('Y', $timestamp);
}

// Perbaikan: Cek apakah fungsi sudah didefinisikan sebelumnya
if (!function_exists('format_ddc_category')) {
    /**
     * Format kode DDC menjadi nama kategori
     * @param string $kode_ddc Kode DDC 3 digit
     * @return string Nama kategori
     */
    function format_ddc_category($kode_ddc)
    {
        global $ddc_categories;

        if (strlen($kode_ddc) < 3)
            return 'Tidak Diketahui';

        $prefix = substr($kode_ddc, 0, 1) . '00';
        return isset($ddc_categories[$prefix])
            ? $ddc_categories[$prefix]
            : 'Tidak Diketahui';
    }
}

// ===================================================
// FUNGSI VALIDASI
// ===================================================

/**
 * Validasi nomor WhatsApp Indonesia
 * @param string $number Nomor WhatsApp
 * @return bool True jika valid
 */
function validate_whatsapp($number)
{
    $number = preg_replace('/[^0-9]/', '', $number);
    return preg_match('/^08[1-9][0-9]{7,10}$/', $number) ||
        preg_match('/^628[1-9][0-9]{7,10}$/', $number);
}

// ===================================================
// FUNGSI TAMPILAN
// ===================================================

/**
 * Membuat notifikasi alert Bootstrap
 * @param string $type Tipe alert (success, danger, warning, info)
 * @param string $message Pesan yang ditampilkan
 * @return string HTML alert
 */
function show_alert($type, $message)
{
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show">
        ' . htmlspecialchars($message) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// ===================================================
// FUNGSI DATABASE
// ===================================================

/**
 * Execute query dengan prepared statement
 * @param string $sql Query SQL
 * @param array $params Parameter untuk binding
 * @return mysqli_result|bool Hasil query
 */
function db_query($sql, $params = [])
{
    global $conn;

    if (!$conn) {
        error_log("Database connection error");
        return false;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Query Error: " . $conn->error . " | SQL: $sql");
        return false;
    }

    if (!empty($params)) {
        $types = '';
        $values = [];
        foreach ($params as $param) {
            if (is_int($param))
                $types .= 'i';
            elseif (is_double($param))
                $types .= 'd';
            else
                $types .= 's';
            $values[] = $param;
        }

        $stmt->bind_param($types, ...$values);
    }

    if (!$stmt->execute()) {
        error_log("Execution Error: " . $stmt->error);
        return false;
    }

    return $stmt->get_result();
}

// ===================================================
// FUNGSI LAINNYA
// ===================================================

/**
 * Generate CSRF token jika belum ada
 */
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Tampilkan CSRF token input field
 */
function csrf_field()
{
    generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
}

// Perbaikan: Cek apakah fungsi sudah didefinisikan sebelumnya
if (!function_exists('get_status_label')) {
    /**
     * Tampilkan label status peminjaman
     */
    function get_status_label($row)
    {
        $sisa_hari = $row['sisa_hari'] ?? 0;
        $terlambat_hari = $row['terlambat_hari'] ?? 0;

        if ($terlambat_hari > 0) {
            return '<span class="badge bg-danger">Terlambat ' . $terlambat_hari . ' Hari</span>';
        }
        if ($sisa_hari <= 0) {
            return '<span class="badge bg-warning">Hari Terakhir</span>';
        }
        return '<span class="badge bg-success">' . $sisa_hari . ' Hari Lagi</span>';
    }
}