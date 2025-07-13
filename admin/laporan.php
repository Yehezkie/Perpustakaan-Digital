<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: ../index.php');
    exit();
}

include '../includes/config.php';
include '../includes/header.php';

// Filter tanggal
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Query data untuk chart bulanan
$bulanan = mysqli_query(
    $conn,
    "SELECT MONTH(tgl_pinjam) AS bulan, 
            COUNT(id_peminjaman) AS total
     FROM peminjaman
     GROUP BY MONTH(tgl_pinjam)
     ORDER BY bulan"
);

// Query data untuk chart distribusi buku berdasarkan nama buku dan kode DDC
$distribusi_buku = mysqli_query(
    $conn,
    "SELECT b.judul, b.kode_ddc, COUNT(p.id_peminjaman) AS total
     FROM peminjaman p
     JOIN copy_buku cb ON p.id_copy = cb.id_copy
     JOIN buku b ON cb.id_buku = b.id_buku
     WHERE p.tgl_pinjam BETWEEN '$start_date' AND '$end_date'
     GROUP BY b.id_buku
     ORDER BY total DESC LIMIT 8"
);

// Query data peminjaman terlambat
$terlambat = mysqli_query(
    $conn,
    "SELECT p.*, s.nama AS siswa, b.judul, b.kode_ddc,
            DATEDIFF(p.tgl_kembali, p.tgl_batas_kembali) AS terlambat
     FROM peminjaman p
     JOIN siswa s ON p.id_siswa = s.id_siswa
     JOIN copy_buku cb ON p.id_copy = cb.id_copy
     JOIN buku b ON cb.id_buku = b.id_buku
     WHERE p.tgl_kembali IS NOT NULL
        AND DATEDIFF(p.tgl_kembali, p.tgl_batas_kembali) > 3
        AND p.tgl_kembali BETWEEN '$start_date' AND '$end_date'"
);

// Query riwayat peminjaman
$riwayat = mysqli_query(
    $conn,
    "SELECT p.*, s.nama AS siswa, b.judul, b.kode_ddc,
            DATEDIFF(p.tgl_kembali, p.tgl_batas_kembali) AS terlambat
     FROM peminjaman p
     JOIN siswa s ON p.id_siswa = s.id_siswa
     JOIN copy_buku cb ON p.id_copy = cb.id_copy
     JOIN buku b ON cb.id_buku = b.id_buku
     WHERE p.tgl_kembali IS NOT NULL
     ORDER BY p.tgl_kembali DESC"
);

// Query buku populer
$buku_populer = mysqli_query(
    $conn,
    "SELECT b.judul, b.kode_ddc, COUNT(p.id_peminjaman) AS total_pinjam
     FROM peminjaman p
     JOIN copy_buku cb ON p.id_copy = cb.id_copy
     JOIN buku b ON cb.id_buku = b.id_buku
     GROUP BY b.id_buku
     ORDER BY total_pinjam DESC LIMIT 5"
);

// Query siswa aktif
$siswa_aktif = mysqli_query(
    $conn,
    "SELECT s.nama, s.kelas, COUNT(p.id_peminjaman) AS total_pinjam
     FROM peminjaman p
     JOIN siswa s ON p.id_siswa = s.id_siswa
     GROUP BY s.id_siswa
     ORDER BY total_pinjam DESC LIMIT 5"
);

// Query DDC populer
$ddc_populer = mysqli_query(
    $conn,
    "SELECT b.kode_ddc, COUNT(p.id_peminjaman) AS total_pinjam
     FROM peminjaman p
     JOIN copy_buku cb ON p.id_copy = cb.id_copy
     JOIN buku b ON cb.id_buku = b.id_buku
     GROUP BY b.kode_ddc
     ORDER BY total_pinjam DESC LIMIT 5"
);

// Fungsi untuk format kategori DDC
function format_ddc_category($kode_ddc)
{
    $categories = [
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

    $prefix = substr($kode_ddc, 0, 1) . '00';
    return $categories[$prefix] ?? 'Tidak Diketahui';
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perpustakaan Digital</title>
    <script src="../assets/js/chart.umd.js"></script>
    <style>
        /* === VARIABLES === */
        :root {
            --primary-100: #e8f5e9;
            --primary-200: #c8e6c9;
            --primary-300: #a5d6a7;
            --primary-400: #81c784;
            --primary-500: #66bb6a;
            --primary-600: #4caf50;
            --primary-700: #388e3c;
            --primary-800: #2e7d32;
            --primary-900: #1b5e20;

            --accent-100: #fff9c4;
            --accent-200: #fff59d;
            --accent-300: #fff176;
            --accent-400: #ffee58;
            --accent-500: #ffeb3b;
            --accent-600: #fdd835;
            --accent-700: #fbc02d;
            --accent-800: #f9a825;
            --accent-900: #f57f17;

            --neutral-50: #fafafa;
            --neutral-100: #f5f5f5;
            --neutral-200: #eeeeee;
            --neutral-300: #e0e0e0;
            --neutral-400: #bdbdbd;
            --neutral-500: #9e9e9e;
            --neutral-600: #757575;
            --neutral-700: #616161;
            --neutral-800: #424242;
            --neutral-900: #212121;

            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;

            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        /* === BASE STYLES === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, var(--primary-100) 0%, var(--neutral-50) 100%);
            color: var(--neutral-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* === HEADER STYLES === */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-800) 0%, var(--primary-600) 100%);
            border-radius: 16px;
            color: white;
            box-shadow: var(--shadow-md);
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title-icon {
            background: rgba(255, 255, 255, 0.15);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .page-actions {
            display: flex;
            gap: 10px;
        }

        /* === CARD STYLES === */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            padding: 25px;
            margin-bottom: 30px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-600), var(--primary-800));
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--neutral-200);
        }

        .card-title {
            font-size: 1.5rem;
            color: var(--primary-800);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .card-title-icon {
            background: var(--primary-200);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-800);
        }

        /* === FILTER SECTION === */
        .filter-card {
            background: linear-gradient(135deg, var(--primary-100), var(--neutral-50));
            border: 1px solid var(--neutral-200);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--neutral-700);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            padding: 14px 16px;
            border: 1px solid var(--neutral-300);
            border-radius: 12px;
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-600);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        .btn {
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-700), var(--primary-600));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-800), var(--primary-700));
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-600);
            color: var(--primary-700);
        }

        .btn-outline:hover {
            background: var(--primary-600);
            color: white;
        }

        /* === BADGE STYLES === */
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-primary {
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
            color: white;
        }

        .badge-success {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
        }

        .badge-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: var(--neutral-800);
        }

        .badge-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }

        .ddc-badge {
            background: var(--primary-800);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* === TABLE STYLES === */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .table th {
            background: linear-gradient(to bottom, var(--primary-600), var(--primary-700));
            color: white;
            text-align: left;
            padding: 16px;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--neutral-200);
        }

        .table tr {
            transition: var(--transition);
        }

        .table tr:nth-child(even) {
            background-color: rgba(200, 230, 201, 0.1);
        }

        .table tr:hover {
            background-color: rgba(200, 230, 201, 0.3);
        }

        /* === CHART SECTION === */
        .chart-container {
            position: relative;
            height: 350px;
            margin-top: 20px;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }

        /* === STATS SECTION === */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            padding: 25px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-400), var(--primary-600));
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card-icon {
            background: var(--primary-100);
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary-800);
        }

        .stat-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--neutral-800);
        }

        .stat-card-body {
            padding: 10px 0;
        }

        .list-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.02);
            transition: var(--transition);
            border-left: 4px solid var(--primary-600);
        }

        .list-group-item:hover {
            background: rgba(0, 0, 0, 0.05);
            transform: translateX(5px);
        }

        .list-group-content {
            flex: 1;
        }

        .list-group-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--neutral-800);
        }

        .list-group-meta {
            font-size: 0.9rem;
            color: var(--neutral-600);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .list-group-badge {
            background: var(--primary-600);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* === EMPTY STATE === */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--neutral-500);
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--neutral-300);
        }

        /* === UTILITY CLASSES === */
        .text-center {
            text-align: center;
        }

        .text-muted {
            color: var(--neutral-500);
        }

        .fw-bold {
            font-weight: 700;
        }

        .mb-4 {
            margin-bottom: 25px;
        }

        .mt-4 {
            margin-top: 25px;
        }

        .py-4 {
            padding-top: 25px;
            padding-bottom: 25px;
        }

        /* === RESPONSIVE ADJUSTMENTS === */
        @media (max-width: 992px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .card {
                padding: 20px;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .card-title {
                font-size: 1.3rem;
            }

            .table th,
            .table td {
                padding: 12px 14px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-title">
                <div class="page-title-icon">📊</div>
                <div>Laporan Perpustakaan Digital</div>
            </div>
            <div class="page-actions">
                <a href="../admin/dashboard.php" class="btn btn-outline">
                    <span>←</span> Kembali ke Dashboard
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card filter-card">
            <form class="filter-form" method="GET">
                <div class="form-group">
                    <label class="form-label">📅 Tanggal Mulai</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">📅 Tanggal Selesai</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-control">
                </div>

                <div class="form-group d-flex align-center" style="align-self: flex-end; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <span>🔍</span> Terapkan Filter
                    </button>

                    <a href="ekspor.php?type=laporan&start=<?= urlencode($start_date) ?>&end=<?= urlencode($end_date) ?>"
                        class="btn btn-outline">
                        <span>📊</span> Ekspor Excel
                    </a>
                </div>
            </form>
        </div>

        <!-- Charts Section -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <div class="card-title-icon">📈</div>
                    Statistik Utama
                </h2>
            </div>
            <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="chart-container">
                    <canvas id="monthlyActivityChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="bookDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Keterlambatan Section -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <div class="card-title-icon">⏱️</div>
                    Peminjaman Terlambat
                </h2>
                <span class="badge badge-danger">
                    <span>📝</span> <?= mysqli_num_rows($terlambat) ?> Data
                </span>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Buku (DDC)</th>
                            <th>Tgl Pinjam</th>
                            <th>Batas Kembali</th>
                            <th>Tgl Kembali</th>
                            <th>Terlambat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($terlambat && mysqli_num_rows($terlambat) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($terlambat)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['siswa']) ?></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['judul']) ?></div>
                                                <div class="ddc-badge">DDC: <?= htmlspecialchars($row['kode_ddc']) ?></div>
                                            </td>
                                            <td><?= date('d M Y', strtotime($row['tgl_pinjam'])) ?></td>
                                            <td><?= date('d M Y', strtotime($row['tgl_batas_kembali'])) ?></td>
                                            <td><?= date('d M Y', strtotime($row['tgl_kembali'])) ?></td>
                                            <td>
                                                <span class="badge badge-danger">
                                                    <span>⏳</span> <?= $row['terlambat'] ?> Hari
                                                </span>
                                            </td>
                                        </tr>
                                <?php endwhile; ?>
                        <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">✅</div>
                                            <h3>Tidak Ada Data Keterlambatan</h3>
                                            <p class="text-muted">Semua peminjaman telah dikembalikan tepat waktu</p>
                                        </div>
                                    </td>
                                </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Riwayat Section -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <div class="card-title-icon">🕒</div>
                    Riwayat Peminjaman Terakhir
                </h2>
                <span class="badge badge-success">
                    <span>📚</span> <?= mysqli_num_rows($riwayat) ?> Data
                </span>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Siswa</th>
                            <th>Buku (DDC)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($riwayat && mysqli_num_rows($riwayat) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($riwayat)): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($row['tgl_pinjam'])) ?></td>
                                            <td><?= date('d M Y', strtotime($row['tgl_kembali'])) ?></td>
                                            <td><?= htmlspecialchars($row['siswa']) ?></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($row['judul']) ?></div>
                                                <div class="ddc-badge">DDC: <?= htmlspecialchars($row['kode_ddc']) ?></div>
                                            </td>
                                            <td>
                                                <?php if ($row['terlambat'] > 3): ?>
                                                        <span class="badge badge-danger">
                                                            <span>❗</span> Terlambat <?= $row['terlambat'] ?> Hari
                                                        </span>
                                                <?php elseif ($row['terlambat'] > 0): ?>
                                                        <span class="badge badge-warning">
                                                            <span>⚠️</span> Terlambat <?= $row['terlambat'] ?> Hari
                                                        </span>
                                                <?php else: ?>
                                                        <span class="badge badge-success">
                                                            <span>✅</span> Tepat Waktu
                                                        </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                <?php endwhile; ?>
                        <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">📭</div>
                                            <h3>Tidak Ada Riwayat Peminjaman</h3>
                                            <p class="text-muted">Belum ada riwayat peminjaman dalam periode ini</p>
                                        </div>
                                    </td>
                                </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Statistik Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon">⭐</div>
                    <div class="stat-card-title">Buku Terpopuler</div>
                </div>
                <div class="stat-card-body">
                    <div class="list-group">
                        <?php if ($buku_populer && mysqli_num_rows($buku_populer) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($buku_populer)): ?>
                                        <div class="list-group-item">
                                            <div class="list-group-content">
                                                <div class="list-group-title"><?= htmlspecialchars($row['judul']) ?></div>
                                                <div class="list-group-meta">
                                                    <span class="ddc-badge"><?= htmlspecialchars($row['kode_ddc']) ?></span>
                                                    <?= format_ddc_category($row['kode_ddc']) ?>
                                                </div>
                                            </div>
                                            <span class="list-group-badge">
                                                <?= $row['total_pinjam'] ?>x
                                            </span>
                                        </div>
                                <?php endwhile; ?>
                        <?php else: ?>
                                <div class="empty-state py-4">
                                    <div class="empty-state-icon">📚</div>
                                    <h3>Tidak Ada Data</h3>
                                    <p class="text-muted">Belum ada data buku populer</p>
                                </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon">🏆</div>
                    <div class="stat-card-title">Siswa Paling Aktif</div>
                </div>
                <div class="stat-card-body">
                    <div class="list-group">
                        <?php if ($siswa_aktif && mysqli_num_rows($siswa_aktif) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($siswa_aktif)): ?>
                                        <div class="list-group-item">
                                            <div class="list-group-content">
                                                <div class="list-group-title"><?= htmlspecialchars($row['nama']) ?></div>
                                                <div class="list-group-meta">
                                                    <span>👨‍🎓</span> Kelas: <?= htmlspecialchars($row['kelas']) ?>
                                                </div>
                                            </div>
                                            <span class="list-group-badge">
                                                <?= $row['total_pinjam'] ?>x
                                            </span>
                                        </div>
                                <?php endwhile; ?>
                        <?php else: ?>
                                <div class="empty-state py-4">
                                    <div class="empty-state-icon">👤</div>
                                    <h3>Tidak Ada Data</h3>
                                    <p class="text-muted">Belum ada data siswa aktif</p>
                                </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon">🔖</div>
                    <div class="stat-card-title">DDC Paling Sering Dipinjam</div>
                </div>
                <div class="stat-card-body">
                    <div class="list-group">
                        <?php if ($ddc_populer && mysqli_num_rows($ddc_populer) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($ddc_populer)): ?>
                                        <div class="list-group-item">
                                            <div class="list-group-content">
                                                <div class="list-group-title">Kode <?= htmlspecialchars($row['kode_ddc']) ?></div>
                                                <div class="list-group-meta">
                                                    <?= format_ddc_category($row['kode_ddc']) ?>
                                                </div>
                                            </div>
                                            <span class="list-group-badge">
                                                <?= $row['total_pinjam'] ?>x
                                            </span>
                                        </div>
                                <?php endwhile; ?>
                        <?php else: ?>
                                <div class="empty-state py-4">
                                    <div class="empty-state-icon">📊</div>
                                    <h3>Tidak Ada Data</h3>
                                    <p class="text-muted">Belum ada data DDC</p>
                                </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart data
        const monthlyData = {
            labels: [<?php
            $months = [];
            if ($bulanan && mysqli_num_rows($bulanan) > 0) {
                while ($row = mysqli_fetch_assoc($bulanan)) {
                    $monthName = new DateTime('2023-' . $row['bulan'] . '-01');
                    $months[] = $monthName->format('M');
                }
                echo "'" . implode("','", $months) . "'";
            }
            ?>],
            datasets: [{
                label: 'Jumlah Peminjaman',
                data: [<?php
                $data = [];
                if ($bulanan) {
                    mysqli_data_seek($bulanan, 0);
                    while ($row = mysqli_fetch_assoc($bulanan)) {
                        $data[] = $row['total'];
                    }
                    echo implode(",", $data);
                }
                ?>],
                borderColor: '#388e3c',
                backgroundColor: 'rgba(56, 142, 60, 0.1)',
                borderWidth: 3,
                tension: 0.3,
                fill: true
            }]
        };

        const bookData = {
            labels: [<?php
            $books = [];
            if ($distribusi_buku && mysqli_num_rows($distribusi_buku) > 0) {
                while ($row = mysqli_fetch_assoc($distribusi_buku)) {
                    $books[] = $row['judul'] . ' (' . $row['kode_ddc'] . ')';
                }
                echo "'" . implode("','", array_map('addslashes', $books)) . "'";
            }
            ?>],
            datasets: [{
                data: [<?php
                $values = [];
                if ($distribusi_buku) {
                    mysqli_data_seek($distribusi_buku, 0);
                    while ($row = mysqli_fetch_assoc($distribusi_buku)) {
                        $values[] = $row['total'];
                    }
                    echo implode(",", $values);
                }
                ?>],
                backgroundColor: [
                    '#2e7d32',
                    '#388e3c',
                    '#43a047',
                    '#4caf50',
                    '#66bb6a',
                    '#81c784',
                    '#a5d6a7',
                    '#c8e6c9'
                ],
                borderWidth: 1
            }]
        };

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function () {
            // Monthly Activity Chart
            const monthlyCtx = document.getElementById('monthlyActivityChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'line',
                data: monthlyData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#333',
                            bodyColor: '#333',
                            borderColor: '#ddd',
                            borderWidth: 1,
                            padding: 12,
                            callbacks: {
                                label: function (context) {
                                    return `Peminjaman: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#666',
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#666'
                            }
                        }
                    }
                }
            });

            // Book Distribution Chart
            const bookCtx = document.getElementById('bookDistributionChart').getContext('2d');
            new Chart(bookCtx, {
                type: 'doughnut',
                data: bookData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: '#333',
                                font: {
                                    size: 13
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#333',
                            bodyColor: '#333',
                            borderColor: '#ddd',
                            borderWidth: 1,
                            padding: 12,
                            callbacks: {
                                label: function (context) {
                                    return `${context.label}: ${context.parsed} kali`;
                                }
                            }
                        }
                    },
                    cutout: '60%',
                    animation: {
                        animateRotate: true,
                        animateScale: true
                    }
                }
            });
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>

</html>