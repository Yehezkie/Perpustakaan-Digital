<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/utilities.php';

// Hanya boleh diakses di lingkungan development
if ($_SERVER['SERVER_NAME'] != 'localhost') {
    die('Akses ditolak! Hanya untuk lingkungan development');
}

// ===================================================
// FUNGSI UTAMA YANG DIPERBAIKI
// ===================================================
function add_business_days($start_date, $days)
{
    $current_date = new DateTime($start_date);
    $added_days = 0;

    while ($added_days < $days) {
        $current_date->modify('+1 day');
        $day_of_week = $current_date->format('w');
        if ($day_of_week != 0 && $day_of_week != 6) { // Skip weekend
            $added_days++;
        }
    }

    // Pastikan tidak jatuh di weekend
    while ($current_date->format('w') == 0 || $current_date->format('w') == 6) {
        $current_date->modify('+1 day');
    }

    return $current_date->format('Y-m-d');
}

function get_copy_info($kode_buku)
{
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM copy_buku WHERE kode_unik = ? AND status = 'Tersedia'");
    $stmt->bind_param("s", $kode_buku);
    $stmt->execute();
    $copy = $stmt->get_result()->fetch_assoc();

    if (!$copy)
        throw new Exception("Buku tidak tersedia atau kode salah");
    return $copy;
}

function update_copy_status($id_copy, $status)
{
    global $conn;

    $stmt = $conn->prepare("UPDATE copy_buku SET status = ? WHERE id_copy = ?");
    $stmt->bind_param("si", $status, $id_copy);
    $stmt->execute();
}

function record_peminjaman($id_siswa, $id_copy)
{
    global $conn;

    $tgl_pinjam = get_current_date()->format('Y-m-d');
    $tgl_batas = add_business_days($tgl_pinjam, MAX_PINJAM_HARI);

    $stmt = $conn->prepare("INSERT INTO peminjaman 
        (id_siswa, id_copy, tgl_pinjam, tgl_batas_kembali)
        VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $id_siswa, $id_copy, $tgl_pinjam, $tgl_batas);
    $stmt->execute();
}

function get_peminjaman_info($kode_buku)
{
    global $conn;

    $stmt = $conn->prepare("SELECT p.* FROM peminjaman p
        JOIN copy_buku cb ON p.id_copy = cb.id_copy
        WHERE cb.kode_unik = ? AND p.tgl_kembali IS NULL");
    $stmt->bind_param("s", $kode_buku);
    $stmt->execute();
    $peminjaman = $stmt->get_result()->fetch_assoc();

    if (!$peminjaman)
        throw new Exception("Tidak ada data peminjaman aktif");
    return $peminjaman;
}

function calculate_denda($tgl_batas)
{
    $tgl_batas = new DateTime($tgl_batas);
    $tgl_kembali = get_current_date();

    if ($tgl_kembali <= $tgl_batas)
        return 0;

    $terlambat = 0;
    $current = clone $tgl_batas;
    $current->modify('+1 day');

    while ($current <= $tgl_kembali) {
        $day_of_week = $current->format('w');
        if ($day_of_week != 0 && $day_of_week != 6) {
            $terlambat++;
        }
        $current->modify('+1 day');
    }

    return $terlambat * DENDA_PER_HARI;
}

function update_pengembalian($id_peminjaman, $denda)
{
    global $conn;

    $tgl_kembali = get_current_date()->format('Y-m-d');
    $stmt = $conn->prepare("UPDATE peminjaman SET 
        tgl_kembali = ?, denda = ? WHERE id_peminjaman = ?");
    $stmt->bind_param("sdi", $tgl_kembali, $denda, $id_peminjaman);
    $stmt->execute();
}

// ===================================================
// SIMULASI TANGGAL
// ===================================================
$simulated_date = $_SESSION['simulated_date'] ?? date('Y-m-d');

if (isset($_POST['set_simulation'])) {
    $_SESSION['simulated_date'] = $_POST['simulation_date'];
    header('Location: transaksi_test.php');
    exit();
}

if (isset($_GET['reset_date'])) {
    unset($_SESSION['simulated_date']);
    header('Location: transaksi_test.php');
    exit();
}

function get_current_date()
{
    global $simulated_date;
    return new DateTime($simulated_date);
}

// ===================================================
// TESTING TRANSAKSI
// ===================================================
$test_result = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_action'])) {
    try {
        $conn->begin_transaction();

        if ($_POST['test_action'] === 'pinjam') {
            // Simulasi peminjaman
            $id_siswa = 1;
            $kode_buku = 'BUKU-TEST-001';

            $copy = get_copy_info($kode_buku);
            update_copy_status($copy['id_copy'], 'Dipinjam');

            // Insert peminjaman dengan tanggal simulasi
            $tgl_pinjam = get_current_date()->format('Y-m-d');
            $tgl_batas = add_business_days($tgl_pinjam, MAX_PINJAM_HARI);

            $stmt = $conn->prepare("INSERT INTO peminjaman 
                (id_siswa, id_copy, tgl_pinjam, tgl_batas_kembali)
                VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_siswa, $copy['id_copy'], $tgl_pinjam, $tgl_batas);
            $stmt->execute();

            $test_result['pinjam'] = [
                'tgl_pinjam' => $tgl_pinjam,
                'tgl_batas' => $tgl_batas,
                'copy_id' => $copy['id_copy']
            ];

        } elseif ($_POST['test_action'] === 'kembali') {
            // Simulasi pengembalian
            $kode_buku = 'BUKU-TEST-001';
            $peminjaman = get_peminjaman_info($kode_buku);

            // Hitung denda dengan tanggal simulasi
            $denda = calculate_denda($peminjaman['tgl_batas_kembali']);

            update_pengembalian($peminjaman['id_peminjaman'], $denda);
            update_copy_status($peminjaman['id_copy'], 'Tersedia');

            $test_result['kembali'] = [
                'tgl_kembali' => get_current_date()->format('Y-m-d'),
                'denda' => $denda,
                'hari_terlambat' => $denda / DENDA_PER_HARI
            ];
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $test_result['error'] = "ERROR: " . $e->getMessage() . " Pada baris: " . $e->getLine();
    }
}

// ===================================================
// TAMPILAN TESTING
// ===================================================
require_once '../includes/header.php';
?>

<div class="container-fluid px-4">
    <h3 class="mt-4">Testing Transaksi</h3>

    <!-- Form Simulasi Tanggal -->
    <div class="card mb-4">
        <div class="card-body">
            <h5>📅 Simulasi Tanggal Saat Ini</h5>
            <form method="POST">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <input type="date" name="simulation_date" value="<?= htmlspecialchars($simulated_date) ?>"
                            class="form-control">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="set_simulation" class="btn btn-primary">
                            Set Tanggal
                        </button>
                        <a href="?reset_date" class="btn btn-secondary">
                            Reset
                        </a>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted">
                            Tanggal Simulasi: <?= htmlspecialchars($simulated_date) ?>
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Hasil Testing -->
    <?php if (!empty($test_result)): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5>📊 Hasil Testing</h5>
                <pre><?= print_r($test_result, true) ?></pre>

                <?php if (isset($test_result['pinjam'])): ?>
                    <div class="alert alert-success">
                        <strong>✅ Berhasil meminjam!</strong><br>
                        📅 Tgl Pinjam: <?= $test_result['pinjam']['tgl_pinjam'] ?><br>
                        ⏳ Batas Kembali: <?= $test_result['pinjam']['tgl_batas'] ?><br>
                        🆔 ID Copy: <?= $test_result['pinjam']['copy_id'] ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($test_result['kembali'])): ?>
                    <div class="alert alert-info">
                        <strong>🔄 Berhasil mengembalikan!</strong><br>
                        📅 Tgl Kembali: <?= $test_result['kembali']['tgl_kembali'] ?><br>
                        ⏰ Hari Terlambat: <?= $test_result['kembali']['hari_terlambat'] ?> hari<br>
                        💰 Denda: Rp<?= number_format($test_result['kembali']['denda'], 0, ',', '.') ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($test_result['error'])): ?>
                    <div class="alert alert-danger">
                        <?= $test_result['error'] ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Form Testing -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="text-success">🧪 Test Peminjaman</h5>
                    <form method="POST">
                        <input type="hidden" name="test_action" value="pinjam">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-book"></i> Test Pinjam
                        </button>
                        <small class="text-muted d-block mt-2">
                            Data dummy akan dibuat dengan:<br>
                            - 🧑 Siswa ID: 1<br>
                            - 📚 Kode Buku: BUKU-TEST-001
                        </small>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="text-danger">🧪 Test Pengembalian</h5>
                    <form method="POST">
                        <input type="hidden" name="test_action" value="kembali">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-arrow-return-left"></i> Test Kembali
                        </button>
                        <small class="text-muted d-block mt-2">
                            Akan mengembalikan buku dengan:<br>
                            - 📚 Kode Buku: BUKU-TEST-001
                        </small>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>