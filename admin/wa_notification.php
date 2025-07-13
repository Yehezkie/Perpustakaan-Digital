<?php
ob_start();
session_start();
require_once '../includes/config.php';
require_once '../includes/utilities.php';

// Cek apakah staff sudah login
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: ../index.php');
    exit();
}

// Fungsi untuk mengirim notifikasi WhatsApp
function send_wa_notification($number, $message)
{
    if (!defined('WA_API_ENABLED') || !WA_API_ENABLED) {
        return ['status' => false, 'message' => 'WhatsApp API tidak aktif'];
    }

    if (!defined('WA_API_URL') || !defined('WA_DEVICE_ID') || !defined('WA_API_KEY')) {
        return ['status' => false, 'message' => 'Konfigurasi WhatsApp tidak lengkap'];
    }

    // Bersihkan nomor (pastikan format 62)
    $number = preg_replace('/[^0-9]/', '', $number);
    if (substr($number, 0, 1) === '0') {
        $number = '62' . substr($number, 1);
    }

    $data = [
        'device_id' => WA_DEVICE_ID,
        'number' => $number,
        'message' => $message,
        'api_key' => WA_API_KEY
    ];

    $ch = curl_init(WA_API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ['status' => false, 'message' => "CURL Error: " . $error_msg];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['status' => false, 'message' => "HTTP Error: $httpCode", 'response' => $response];
    }

    $responseData = json_decode($response, true);
    if ($responseData && $responseData['status']) {
        return ['status' => true, 'message' => 'Notifikasi terkirim', 'response' => $responseData];
    }

    return ['status' => false, 'message' => $responseData['message'] ?? 'Gagal mengirim notifikasi'];
}

// Query untuk mengambil data peminjaman yang terlambat dan belum dikirim notifikasi
$notif_hari = NOTIF_TERLAMBAT_HARI;
$query = "
    SELECT 
        p.id_peminjaman AS id,
        s.nama, 
        s.kelas,
        s.no_telepon AS no_wa,
        b.judul, 
        b.kode_ddc,
        p.tgl_batas_kembali,
        DATEDIFF(CURDATE(), p.tgl_batas_kembali) AS hari_telat
    FROM peminjaman p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN copy_buku cb ON p.id_copy = cb.id_copy
    JOIN buku b ON cb.id_buku = b.id_buku
    LEFT JOIN log_whatsapp lw ON p.id_peminjaman = lw.id_peminjaman
    WHERE 
        p.tgl_kembali IS NULL
        AND DATEDIFF(CURDATE(), p.tgl_batas_kembali) > ?
        AND lw.id_log IS NULL
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $notif_hari);
$stmt->execute();
$result = $stmt->get_result();

$total_dikirim = 0;
$total_gagal = 0;
$logs = [];
$message = '';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format pesan
        $message = "Hai {$row['nama']} ({$row['kelas']})! 🙏\n"
            . "Buku *{$row['judul']}* (DDC: {$row['kode_ddc']}) "
            . "sudah terlambat {$row['hari_telat']} hari dari batas pengembalian.\n"
            . "Batas kembali: " . date('d M Y', strtotime($row['tgl_batas_kembali'])) . "\n"
            . "Mohon segera kembalikan buku ke perpustakaan untuk menghindari keterlambatan lebih lanjut.\n\n"
            . "Terima kasih 🙏";

        // Kirim notifikasi
        $wa_result = send_wa_notification($row['no_wa'], $message);
        $status = $wa_result['status'] ? 'terkirim' : 'gagal';
        $log_message = $wa_result['message'];

        // Catat di log database
        $log_query = "INSERT INTO log_whatsapp (id_peminjaman, tgl_kirim, status, pesan) VALUES (?, NOW(), ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("iss", $row['id'], $status, $log_message);
        $log_stmt->execute();
        $log_stmt->close();

        // Simpan untuk ditampilkan
        $logs[] = [
            'siswa' => $row['nama'],
            'buku' => $row['judul'],
            'ddc' => $row['kode_ddc'],
            'telat' => $row['hari_telat'],
            'status' => $status,
            'message' => $log_message,
            'no_wa' => $row['no_wa'],
            'tgl_batas' => $row['tgl_batas_kembali']
        ];

        if ($wa_result['status']) {
            $total_dikirim++;
        } else {
            $total_gagal++;
        }

        // Jeda 1 detik antar pengiriman
        sleep(1);
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi WhatsApp</title>
    <style>
        :root {
            --primary: #2e7d32;
            --primary-dark: #1b5e20;
            --primary-light: #4caf50;
            --secondary: #1565c0;
            --success: #388e3c;
            --danger: #d32f2f;
            --warning: #f57c00;
            --info: #0288d1;
            --light: #f8f9fa;
            --dark: #212121;
            --gray: #757575;
            --light-gray: #e0e0e0;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: var(--dark);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 30px;
            border-radius: var(--radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .header::after {
            content: "";
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--white);
            color: var(--primary);
            border: none;
            padding: 10px 15px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card::before {
            content: "";
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 50%;
            z-index: 0;
        }

        .stat-card.success {
            background: linear-gradient(135deg, var(--success) 0%, var(--primary) 100%);
            color: var(--white);
        }

        .stat-card.danger {
            background: linear-gradient(135deg, var(--danger) 0%, #b71c1c 100%);
            color: var(--white);
        }

        .stat-card.info {
            background: linear-gradient(135deg, var(--info) 0%, #01579b 100%);
            color: var(--white);
        }

        .stat-card h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .content-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--secondary) 0%, #0d47a1 100%);
            color: var(--white);
            padding: 20px;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        .alert {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-success {
            background: #e8f5e9;
            border-left: 5px solid var(--success);
            color: var(--dark);
        }

        .alert-info {
            background: #e3f2fd;
            border-left: 5px solid var(--info);
            color: var(--dark);
        }

        .alert-icon {
            font-size: 2rem;
        }

        .notification-preview {
            background: #dcf8c6;
            border-radius: 10px;
            padding: 20px;
            margin: 25px 0;
            position: relative;
            border: 1px solid #b3e0a3;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .notification-preview:before {
            content: "";
            position: absolute;
            top: 20px;
            left: -10px;
            width: 0;
            height: 0;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
            border-right: 10px solid #dcf8c6;
        }

        .notification-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .notification-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 12px;
        }

        .notification-header-text {
            flex-grow: 1;
        }

        .notification-header-text strong {
            font-size: 1.1rem;
        }

        .notification-time {
            font-size: 0.85rem;
            color: #666;
        }

        .notification-content {
            line-height: 1.5;
            font-size: 1rem;
            white-space: pre-wrap;
        }

        .notification-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
            font-size: 0.85rem;
            color: #666;
        }

        .log-list {
            list-style: none;
        }

        .log-item {
            padding: 20px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: background-color 0.2s;
        }

        .log-item:last-child {
            border-bottom: none;
        }

        .log-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .status-icon {
            font-size: 1.8rem;
            min-width: 40px;
            text-align: center;
        }

        .success-icon {
            color: var(--success);
        }

        .fail-icon {
            color: var(--danger);
        }

        .log-details {
            flex-grow: 1;
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .log-header .name {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .telat-badge {
            background: #ffcdd2;
            color: #c62828;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .book-info {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .book-info .ddc {
            background: #5c6bc0;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            display: inline-block;
            margin-right: 8px;
        }

        .contact-info {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 8px;
            display: flex;
            gap: 15px;
        }

        .log-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-text {
            font-weight: 500;
        }

        .status-success {
            color: var(--success);
        }

        .status-fail {
            color: var(--danger);
        }

        .error-message {
            background: #ffebee;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-card {
            background: #e3f2fd;
            border-left: 4px solid var(--info);
            border-radius: var(--radius);
            padding: 20px;
            margin-top: 30px;
        }

        .info-card h5 {
            margin-bottom: 15px;
            color: var(--info);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card ul {
            margin-left: 20px;
            margin-bottom: 20px;
        }

        .info-card li {
            margin-bottom: 8px;
        }

        .btn-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            border: none;
            text-decoration: none;
        }

        .btn-info {
            background: var(--info);
            color: var(--white);
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
            z-index: 100;
        }

        .floating-btn:hover {
            transform: translateY(-5px) rotate(15deg);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 2rem;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            .log-header {
                flex-direction: column;
                gap: 8px;
            }

            .log-status {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <button class="back-btn" onclick="window.location.href='dashboard.php'">
                ← Kembali
            </button>
            <h1>Notifikasi WhatsApp</h1>
            <p>Sistem pengiriman pemberitahuan keterlambatan pengembalian buku</p>
            <p><small><?= date('d F Y, H:i') ?></small></p>
        </div>

        <div class="stats-container">
            <div class="stat-card success">
                <h3>Notifikasi Terkirim</h3>
                <div class="stat-number"><?= $total_dikirim ?></div>
            </div>

            <div class="stat-card danger">
                <h3>Notifikasi Gagal</h3>
                <div class="stat-number"><?= $total_gagal ?></div>
            </div>

            <div class="stat-card info">
                <h3>Batas Keterlambatan</h3>
                <div class="stat-number"><?= NOTIF_TERLAMBAT_HARI ?>+ hari</div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">Hasil Pengiriman Notifikasi</div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <div class="alert alert-success">
                        <div class="alert-icon">✓</div>
                        <div>
                            <h3>Tidak ada notifikasi yang perlu dikirim</h3>
                            <p>Semua peminjaman masih dalam batas waktu atau sudah menerima notifikasi</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <div class="alert-icon">ⓘ</div>
                        <div>
                            <?php if ($total_dikirim > 0): ?>
                                <p>Berhasil mengirim <strong><?= $total_dikirim ?></strong> notifikasi WhatsApp.</p>
                            <?php endif; ?>
                            <?php if ($total_gagal > 0): ?>
                                <p>Terdapat <strong><?= $total_gagal ?></strong> notifikasi yang gagal dikirim.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="notification-preview">
                        <div class="notification-header">
                            <div class="notification-avatar">ⓦ</div>
                            <div class="notification-header-text">
                                <strong>Perpustakaan Digital</strong>
                                <div class="notification-time"><?= date('H:i') ?></div>
                            </div>
                        </div>
                        <div class="notification-content"><?= nl2br(htmlspecialchars($message)) ?></div>
                        <div class="notification-footer">Dikirim via WA API</div>
                    </div>

                    <h3 style="margin: 25px 0 15px;">Log Pengiriman</h3>
                    <ul class="log-list">
                        <?php foreach ($logs as $log): ?>
                            <li class="log-item">
                                <div class="status-icon">
                                    <?= $log['status'] === 'terkirim' ? '✓' : '✗' ?>
                                </div>
                                <div class="log-details">
                                    <div class="log-header">
                                        <div class="name"><?= htmlspecialchars($log['siswa']) ?></div>
                                        <div class="telat-badge">Telat <?= $log['telat'] ?> hari</div>
                                    </div>

                                    <div class="book-info">
                                        <span class="ddc">DDC: <?= htmlspecialchars($log['ddc']) ?></span>
                                        <?= htmlspecialchars($log['buku']) ?>
                                    </div>

                                    <div class="contact-info">
                                        <div>📱 <?= htmlspecialchars($log['no_wa']) ?></div>
                                        <div>📅 Batas kembali: <?= date('d M Y', strtotime($log['tgl_batas'])) ?></div>
                                    </div>

                                    <div class="log-status">
                                        <div class="status-text">
                                            Status: <span
                                                class="<?= $log['status'] === 'terkirim' ? 'status-success' : 'status-fail' ?>">
                                                <?= $log['status'] === 'terkirim' ? 'Berhasil' : 'Gagal' ?>
                                            </span>
                                        </div>
                                    </div>

                                    <?php if ($log['status'] === 'gagal'): ?>
                                        <div class="error-message">
                                            <span>⚠️</span> <?= htmlspecialchars($log['message']) ?>
                                        </div>
                                    <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="info-card">
                    <h5>ⓘ Informasi Sistem</h5>
                    <ul>
                        <li>Sistem ini berjalan otomatis setiap hari untuk mengirim notifikasi</li>
                        <li>Notifikasi hanya dikirim untuk keterlambatan lebih dari <?= NOTIF_TERLAMBAT_HARI ?> hari
                        </li>
                        <li>Setiap peminjaman hanya akan menerima satu kali notifikasi</li>
                        <li>Status notifikasi dapat dilihat di menu Log WhatsApp</li>
                        <li>Fitur ini tidak dikenakan denda, hanya pemberitahuan</li>
                    </ul>

                    <div class="btn-container">
                        <a href="log_whatsapp.php" class="btn btn-info">Lihat Log WhatsApp</a>
                        <a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="floating-btn" onclick="location.reload()">↻</div>

    <script>
        // Scroll to logs section if there are logs
        document.addEventListener('DOMContentLoaded', function () {
            <?php if (!empty($logs)): ?>
                setTimeout(() => {
                    const firstLog = document.querySelector('.log-item');
                    if (firstLog) {
                        firstLog.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }, 500);
            <?php endif; ?>

            // Auto-close alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>

</html>
<?php ob_end_flush(); ?>