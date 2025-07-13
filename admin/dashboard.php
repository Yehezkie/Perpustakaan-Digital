<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: ../index.php');
    exit();
}

include '../includes/config.php';

// Ambil data statistik
$stats = [
    'total_buku' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id_buku) AS total FROM buku"))['total'],
    'total_siswa' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id_siswa) AS total FROM siswa"))['total'],
    'pinjaman_aktif' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id_peminjaman) AS total FROM peminjaman WHERE tgl_kembali IS NULL"))['total'],
    'terlambat' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id_peminjaman) AS total FROM peminjaman 
                                                          WHERE tgl_kembali IS NULL 
                                                          AND DATEDIFF(CURDATE(), tgl_batas_kembali) > 3"))['total']
];

// Ambil riwayat terakhir
$query_riwayat = "SELECT p.tgl_pinjam, s.nama AS siswa, b.judul, b.kode_ddc, b.kategori,
                 CASE 
                    WHEN p.tgl_kembali IS NULL AND DATEDIFF(CURDATE(), p.tgl_batas_kembali) > 3 THEN 'Terlambat'
                    WHEN p.tgl_kembali IS NULL THEN 'Dipinjam'
                    ELSE 'Dikembalikan'
                 END AS status
                 FROM peminjaman p
                 JOIN siswa s ON p.id_siswa = s.id_siswa
                 JOIN copy_buku cb ON p.id_copy = cb.id_copy
                 JOIN buku b ON cb.id_buku = b.id_buku
                 ORDER BY p.tgl_pinjam DESC LIMIT 5";
$riwayat = mysqli_query($conn, $query_riwayat);

// Ambil buku populer berdasarkan DDC
$query_populer = "SELECT b.kode_ddc, b.judul, b.penulis, COUNT(p.id_peminjaman) AS total
                 FROM peminjaman p
                 JOIN copy_buku cb ON p.id_copy = cb.id_copy
                 JOIN buku b ON cb.id_buku = b.id_buku
                 GROUP BY b.id_buku
                 ORDER BY total DESC LIMIT 5";
$buku_populer = mysqli_query($conn, $query_populer);

// Ambil siswa yang paling sering meminjam
$query_siswa_aktif = "SELECT s.nama, s.kelas, s.no_telepon, COUNT(p.id_peminjaman) AS total
                      FROM peminjaman p
                      JOIN siswa s ON p.id_siswa = s.id_siswa
                      GROUP BY s.id_siswa
                      ORDER BY total DESC LIMIT 5";
$siswa_aktif = mysqli_query($conn, $query_siswa_aktif);

// Ambil data untuk grafik (peminjaman per kategori DDC)
$query_grafik_ddc = "SELECT 
    CASE 
        WHEN b.kode_ddc BETWEEN 0 AND 99 THEN '000-099'
        WHEN b.kode_ddc BETWEEN 100 AND 199 THEN '100-199'
        WHEN b.kode_ddc BETWEEN 200 AND 299 THEN '200-299'
        WHEN b.kode_ddc BETWEEN 300 AND 399 THEN '300-399'
        WHEN b.kode_ddc BETWEEN 400 AND 499 THEN '400-499'
        WHEN b.kode_ddc BETWEEN 500 AND 599 THEN '500-599'
        WHEN b.kode_ddc BETWEEN 600 AND 699 THEN '600-699'
        WHEN b.kode_ddc BETWEEN 700 AND 799 THEN '700-799'
        WHEN b.kode_ddc BETWEEN 800 AND 899 THEN '800-899'
        WHEN b.kode_ddc BETWEEN 900 AND 999 THEN '900-999'
    END AS kategori_ddc,
    COUNT(p.id_peminjaman) AS jumlah
FROM peminjaman p
JOIN copy_buku cb ON p.id_copy = cb.id_copy
JOIN buku b ON cb.id_buku = b.id_buku
WHERE p.tgl_pinjam >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
GROUP BY kategori_ddc
ORDER BY kategori_ddc";

$result_grafik_ddc = mysqli_query($conn, $query_grafik_ddc);
$labels_ddc = [];
$data_ddc = [];

// Kategori lengkap DDC
$ddc_categories = [
    '000-099' => 'Umum',
    '100-199' => 'Filsafat',
    '200-299' => 'Agama',
    '300-399' => 'Sosial',
    '400-499' => 'Bahasa',
    '500-599' => 'Sains',
    '600-699' => 'Teknologi',
    '700-799' => 'Kesenian',
    '800-899' => 'Sastra',
    '900-999' => 'Sejarah'
];

// Inisialisasi semua kategori dengan 0
foreach ($ddc_categories as $range => $label) {
    $labels_ddc[] = $label;
    $data_ddc[$range] = 0;
}

// Isi data dari query
if ($result_grafik_ddc) {
    while ($row = mysqli_fetch_assoc($result_grafik_ddc)) {
        if (isset($data_ddc[$row['kategori_ddc']])) {
            $data_ddc[$row['kategori_ddc']] = (int) $row['jumlah'];
        }
    }
}

// Konversi ke array untuk chart
$chart_data_ddc = array_values($data_ddc);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Perpustakaan Digital</title>
    <script src="../assets/js/chart.min.js"></script>
    <style>
        :root {
            --primary-dark: #0d4b26;
            --primary-medium: #1e8449;
            --primary-light: #2ecc71;
            --accent-color: #f1c40f;
            --light-green: #d5f5e3;
            --light-gray: #f5f7fa;
            --dark-gray: #34495e;
            --border-radius: 10px;
            --box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #3498db;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-medium));
            color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .dashboard-header::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .dashboard-title {
            font-size: 2.2rem;
            margin-bottom: 5px;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }

        .dashboard-subtitle {
            opacity: 0.85;
            font-weight: 400;
            font-size: 1.1rem;
            position: relative;
            z-index: 2;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            position: absolute;
            top: 30px;
            right: 30px;
            z-index: 2;
        }

        .date-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .wa-button {
            background: var(--accent-color);
            color: #333;
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 30px;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(241, 196, 15, 0.3);
        }

        .wa-button:hover {
            background: #f39c12;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(241, 196, 15, 0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card.primary {
            background: linear-gradient(135deg, var(--primary-medium), var(--primary-light));
            color: white;
        }

        .stat-card.warning {
            background: linear-gradient(135deg, #e67e22, #f39c12);
            color: white;
        }

        .stat-icon {
            font-size: 2.8rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .stat-label {
            font-size: 1rem;
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .stat-value {
            font-size: 2.4rem;
            font-weight: 700;
        }

        .stat-trend {
            margin-top: 10px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .trend-up {
            color: #2ecc71;
        }

        .trend-down {
            color: #e74c3c;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        @media (max-width: 992px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 25px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .chart-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .chart-controls {
            display: flex;
            gap: 12px;
        }

        .chart-btn {
            padding: 8px 16px;
            background: var(--light-green);
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--primary-dark);
        }

        .chart-btn.active {
            background: var(--primary-medium);
            color: white;
        }

        .chart-btn:hover {
            background: var(--primary-dark);
            color: white;
        }

        .chart-canvas-container {
            width: 100%;
            height: 300px;
            position: relative;
        }

        .activity-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .activity-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .view-all-link {
            color: var(--primary-medium);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .view-all-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            padding: 18px;
            background: var(--light-green);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-medium);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .activity-item.warning {
            border-left: 4px solid var(--warning);
            background: #fef5e7;
        }

        .activity-item.danger {
            border-left: 4px solid var(--danger);
            background: #fdedec;
        }

        .activity-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .activity-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .activity-info {
            flex: 1;
        }

        .activity-book {
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 1.1rem;
            color: var(--dark-gray);
        }

        .activity-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 0.95rem;
            color: #555;
        }

        .ddc-badge {
            background: var(--primary-medium);
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .category-badge {
            background: var(--info);
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .activity-status {
            min-width: 120px;
            text-align: right;
        }

        .activity-time {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 8px;
            font-style: italic;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-success {
            background: var(--success);
            color: white;
        }

        .status-warning {
            background: var(--warning);
            color: white;
        }

        .status-danger {
            background: var(--danger);
            color: white;
        }

        .popular-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }

        .popular-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            transition: var(--transition);
        }

        .popular-container:hover {
            transform: translateY(-5px);
        }

        .popular-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .popular-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .view-link {
            color: var(--primary-medium);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 6px 14px;
            border: 2px solid var(--primary-medium);
            border-radius: 30px;
            transition: var(--transition);
        }

        .view-link:hover {
            background: var(--primary-medium);
            color: white;
        }

        .book-list,
        .student-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .book-item,
        .student-item {
            padding: 15px;
            border-radius: 8px;
            background: #f9f9f9;
            transition: var(--transition);
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .book-item:hover,
        .student-item:hover {
            background: var(--light-green);
            transform: translateX(5px);
        }

        .book-icon,
        .student-avatar {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: var(--primary-medium);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .student-avatar {
            background: var(--info);
            border-radius: 50%;
            font-weight: 700;
            font-size: 1.4rem;
        }

        .book-info,
        .student-info {
            flex: 1;
        }

        .book-title {
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark-gray);
        }

        .book-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 0.9rem;
            color: #555;
        }

        .book-count {
            background: var(--primary-medium);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .student-name {
            font-weight: 700;
            margin-bottom: 3px;
            color: var(--dark-gray);
        }

        .student-class {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .student-phone {
            font-size: 0.85rem;
            color: #777;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #777;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                text-align: center;
            }

            .header-actions {
                position: relative;
                top: auto;
                right: auto;
                justify-content: center;
                margin-top: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .popular-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Dashboard Perpustakaan Digital</h1>
            <p class="dashboard-subtitle">Statistik dan Aktivitas Terkini</p>
            <div class="header-actions">
                <div class="date-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <?= date('d F Y') ?>
                </div>
                <a href="wa_notification.php" class="wa-button">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z">
                        </path>
                    </svg>
                    Kirim Peringatan
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">📚</div>
                <div class="stat-label">Total Buku</div>
                <div class="stat-value"><?= number_format($stats['total_buku']) ?></div>
                <div class="stat-trend trend-up">+12% dari bulan lalu</div>
            </div>

            <div class="stat-card primary">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Total Siswa</div>
                <div class="stat-value"><?= number_format($stats['total_siswa']) ?></div>
                <div class="stat-trend trend-up">+5% dari bulan lalu</div>
            </div>

            <div class="stat-card primary">
                <div class="stat-icon">⏱️</div>
                <div class="stat-label">Pinjaman Aktif</div>
                <div class="stat-value"><?= number_format($stats['pinjaman_aktif']) ?></div>
                <div class="stat-trend trend-down">-3% dari minggu lalu</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">⚠️</div>
                <div class="stat-label">Keterlambatan</div>
                <div class="stat-value"><?= number_format($stats['terlambat']) ?></div>
                <div class="stat-trend trend-up">+8% dari minggu lalu</div>
            </div>
        </div>

        <div class="main-grid">
            <div>
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">Peminjaman Berdasarkan Kategori DDC</div>
                        <div class="chart-controls">
                            <button class="chart-btn active">Bulanan</button>
                            <button class="chart-btn">Tahunan</button>
                        </div>
                    </div>
                    <div class="chart-canvas-container">
                        <canvas id="chartCanvas"></canvas>
                    </div>
                </div>

                <div class="popular-section">
                    <div class="popular-container">
                        <div class="popular-header">
                            <div class="popular-title">Buku Paling Sering Dipinjam</div>
                            <a href="buku.php" class="view-link">Lihat Semua</a>
                        </div>
                        <div class="book-list">
                            <?php if ($buku_populer && mysqli_num_rows($buku_populer) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($buku_populer)): ?>
                                    <div class="book-item">
                                        <div class="book-icon">📖</div>
                                        <div class="book-info">
                                            <div class="book-title"><?= htmlspecialchars($row['judul']) ?></div>
                                            <div class="book-meta">
                                                <span class="ddc-badge">DDC: <?= htmlspecialchars($row['kode_ddc']) ?></span>
                                                <span>Penulis: <?= htmlspecialchars($row['penulis']) ?></span>
                                            </div>
                                        </div>
                                        <div class="book-count"><?= $row['total'] ?>x</div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-data">Tidak ada data buku populer</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="popular-container">
                        <div class="popular-header">
                            <div class="popular-title">Siswa Paling Aktif</div>
                            <a href="siswa.php" class="view-link">Lihat Semua</a>
                        </div>
                        <div class="student-list">
                            <?php if ($siswa_aktif && mysqli_num_rows($siswa_aktif) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($siswa_aktif)): ?>
                                    <?php $inisial = substr($row['nama'], 0, 1); ?>
                                    <div class="student-item">
                                        <div class="student-avatar"><?= $inisial ?></div>
                                        <div class="student-info">
                                            <div class="student-name"><?= htmlspecialchars($row['nama']) ?></div>
                                            <div class="student-class">Kelas: <?= htmlspecialchars($row['kelas']) ?></div>
                                            <div class="student-phone">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path
                                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                                    </path>
                                                </svg>
                                                <?= htmlspecialchars($row['no_telepon']) ?>
                                            </div>
                                        </div>
                                        <div class="book-count"><?= $row['total'] ?>x</div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-data">Tidak ada data siswa aktif</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="activity-container">
                    <div class="activity-header">
                        <div class="activity-title">Aktivitas Terakhir</div>
                        <a href="laporan.php" class="view-all-link">Lihat Semua</a>
                    </div>
                    <div class="activity-list">
                        <?php if ($riwayat && mysqli_num_rows($riwayat) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($riwayat)): ?>
                                <?php
                                $statusClass = '';
                                $badgeClass = '';
                                if ($row['status'] == 'Terlambat') {
                                    $statusClass = 'danger';
                                    $badgeClass = 'status-danger';
                                } elseif ($row['status'] == 'Dipinjam') {
                                    $badgeClass = 'status-warning';
                                } else {
                                    $badgeClass = 'status-success';
                                }
                                ?>
                                <div class="activity-item <?= $statusClass ?>">
                                    <div class="activity-content">
                                        <div class="activity-info">
                                            <div class="activity-book"><?= htmlspecialchars($row['judul']) ?></div>
                                            <div class="activity-meta">
                                                <span class="ddc-badge">DDC: <?= htmlspecialchars($row['kode_ddc']) ?></span>
                                                <span class="category-badge"><?= htmlspecialchars($row['kategori']) ?></span>
                                                <span><?= htmlspecialchars($row['siswa']) ?></span>
                                            </div>
                                        </div>
                                        <div class="activity-status">
                                            <div class="activity-time"><?= date('d M H:i', strtotime($row['tgl_pinjam'])) ?>
                                            </div>
                                            <span class="status-badge <?= $badgeClass ?>"><?= $row['status'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-data">Tidak ada aktivitas terakhir</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Grafik menggunakan Chart.js
        document.addEventListener('DOMContentLoaded', function () {
            // Data untuk grafik
            const chartData = {
                labels: <?= json_encode($labels_ddc) ?>,
                datasets: [{
                    label: 'Jumlah Peminjaman',
                    data: <?= json_encode($chart_data_ddc) ?>,
                    backgroundColor: [
                        '#1a5276', '#2874a6', '#2e86c1', '#3498db',
                        '#5dade2', '#85c1e9', '#aed6f1', '#d6eaf8',
                        '#ebf5fb', '#f8f9fa'
                    ],
                    borderWidth: 1,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            };

            // Konfigurasi grafik
            const chartConfig = {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            },
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
                                color: 'rgba(200, 200, 200, 0.2)'
                            },
                            ticks: {
                                color: '#555',
                                stepSize: 1
                            },
                            title: {
                                display: true,
                                text: 'Jumlah Peminjaman',
                                color: '#555'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#555'
                            }
                        }
                    }
                }
            };

            // Inisialisasi grafik
            const ctx = document.getElementById('chartCanvas').getContext('2d');
            new Chart(ctx, chartConfig);

            // Fungsi untuk tombol-tombol grafik
            const chartButtons = document.querySelectorAll('.chart-btn');
            chartButtons.forEach(button => {
                button.addEventListener('click', function () {
                    chartButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    // Simulasi perubahan data grafik
                    if (this.textContent === 'Tahunan') {
                        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                        const yearlyChart = new Chart(ctx, {
                            ...chartConfig,
                            data: {
                                ...chartData,
                                datasets: [{
                                    ...chartData.datasets[0],
                                    data: [45, 32, 28, 19, 15, 22, 38, 27, 18, 9]
                                }]
                            }
                        });
                    } else {
                        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                        new Chart(ctx, chartConfig);
                    }
                });
            });
        });
    </script>
</body>

</html>