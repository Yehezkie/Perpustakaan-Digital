<?php
session_start();

if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/config.php';
require_once '../includes/utilities.php';

// Handle resend notification
if (isset($_GET['resend'])) {
    if (!isset($_GET['csrf_token']) || !validate_csrf_token($_GET['csrf_token'])) {
        $_SESSION['error'] = "Token keamanan tidak valid";
        header('Location: log_whatsapp.php');
        exit();
    }

    $id_log = (int) $_GET['resend'];

    $log_query = "SELECT lw.*, s.no_telepon, s.nama, b.judul, b.kode_ddc, p.tgl_batas_kembali, p.id_peminjaman
                  FROM log_whatsapp lw
                  JOIN peminjaman p ON lw.id_peminjaman = p.id_peminjaman
                  JOIN siswa s ON p.id_siswa = s.id_siswa
                  JOIN copy_buku cb ON p.id_copy = cb.id_copy
                  JOIN buku b ON cb.id_buku = b.id_buku
                  WHERE lw.id_log = ?";

    $stmt = $conn->prepare($log_query);
    $stmt->bind_param("i", $id_log);
    $stmt->execute();
    $log_result = $stmt->get_result();

    if ($log_result && $log_result->num_rows > 0) {
        $log_data = $log_result->fetch_assoc();

        $tgl_batas = new DateTime($log_data['tgl_batas_kembali']);
        $tgl_sekarang = new DateTime();
        $telat = $tgl_batas->diff($tgl_sekarang)->days;

        $pesan = "Hai {$log_data['nama']}! 🙏\n"
            . "Kami ingatkan buku: *{$log_data['judul']}* (DDC: {$log_data['kode_ddc']})\n"
            . "📅 Batas kembali: " . date('d M Y', strtotime($log_data['tgl_batas_kembali'])) . "\n"
            . "⏱️ Telat: $telat hari\n\n"
            . "Harap segera dikembalikan ke perpustakaan.\n"
            . "(Ini adalah pengingat ulang)";

        // Fungsi untuk mengirim notifikasi WhatsApp
        function send_wa_notification($number, $message)
        {
            // Contoh implementasi dengan WhatsApp Business API menggunakan cURL
            $api_url = "YOUR_WHATSAPP_API_ENDPOINT"; // Ganti dengan endpoint API Anda
            $api_token = "YOUR_API_TOKEN"; // Ganti dengan token API Anda
            $data = [
                'phone_number' => $number,
                'message' => $message
            ];

            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $api_token,
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return [
                'status' => $http_code == 200,
                'message' => $http_code == 200 ? 'Notifikasi terkirim' : 'Gagal mengirim notifikasi'
            ];
        }

        $result = send_wa_notification($log_data['no_telepon'], $pesan);

        $insert_query = "INSERT INTO log_whatsapp (id_peminjaman, tgl_kirim, status, pesan)
                         VALUES (?, NOW(), ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $status = $result['status'] ? 'terkirim' : 'gagal';
        $stmt->bind_param("iss", $log_data['id_peminjaman'], $status, $result['message']);
        $stmt->execute();

        $_SESSION['success'] = "Notifikasi berhasil dikirim ulang! Status: " . ($result['status'] ? 'Terkirim' : 'Gagal');
        header("Location: log_whatsapp.php");
        exit;
    } else {
        $_SESSION['error'] = "Data log tidak ditemukan!";
        header("Location: log_whatsapp.php");
        exit;
    }
}

// Paginasi dan filter
$limit = 10; // Jumlah log per halaman
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'all';
$where_clause = $filter_status !== 'all' ? "WHERE lw.status = '$filter_status'" : '';

$query = "SELECT lw.id_log, lw.tgl_kirim, lw.status, lw.pesan,
                 s.nama AS nama_siswa, s.kelas, s.no_telepon AS no_wa,
                 b.judul, b.kode_ddc,
                 p.tgl_pinjam, p.tgl_batas_kembali
          FROM log_whatsapp lw
          JOIN peminjaman p ON lw.id_peminjaman = p.id_peminjaman
          JOIN siswa s ON p.id_siswa = s.id_siswa
          JOIN copy_buku cb ON p.id_copy = cb.id_copy
          JOIN buku b ON cb.id_buku = b.id_buku
          $where_clause
          ORDER BY lw.tgl_kirim DESC
          LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Error in main query: " . mysqli_error($conn));
}

// Hitung total log untuk paginasi
$total_query = "SELECT COUNT(*) AS total FROM log_whatsapp $where_clause";
$total_result = mysqli_query($conn, $total_query);
$total_logs = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_logs / $limit);

// Hitung statistik notifikasi
$statistik_query = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'terkirim' THEN 1 ELSE 0 END) AS sukses,
                    SUM(CASE WHEN status = 'gagal' THEN 1 ELSE 0 END) AS gagal
                    FROM log_whatsapp";
$statistik_result = mysqli_query($conn, $statistik_query);
$statistik = mysqli_fetch_assoc($statistik_result) ?: ['total' => 0, 'sukses' => 0, 'gagal' => 0];

// Include header
require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Notifikasi WhatsApp - Perpustakaan Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Custom styles untuk meningkatkan Tailwind */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 50;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        .btn {
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .table-container {
            overflow-x: auto;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Log Notifikasi WhatsApp</h1>
                <p class="text-gray-600">Riwayat notifikasi yang dikirimkan kepada siswa</p>
            </div>
            <button onclick="window.history.back()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                ← Kembali
            </button>
        </header>

        <?php include '../includes/alert.php'; ?>

        <div class="bg-blue-100 text-blue-800 p-4 rounded-lg mb-6 flex items-center">
            <span class="text-2xl mr-2">ⓘ</span>
            <p>Berikut adalah riwayat notifikasi WhatsApp yang telah dikirimkan kepada siswa</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-700">Total Notifikasi</h3>
                <p class="text-2xl font-bold text-gray-800"><?= $statistik['total'] ?></p>
            </div>
            <div class="bg-green-100 p-4 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-700">Berhasil Dikirim</h3>
                <p class="text-2xl font-bold text-green-800"><?= $statistik['sukses'] ?></p>
            </div>
            <div class="bg-red-100 p-4 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-700">Gagal Dikirim</h3>
                <p class="text-2xl font-bold text-red-800"><?= $statistik['gagal'] ?></p>
            </div>
        </div>

        <div class="mb-6 flex items-center">
            <label for="filter-status" class="mr-2 font-medium text-gray-700">Filter Status:</label>
            <select id="filter-status" onchange="applyFilter()" class="border rounded-lg px-4 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Semua</option>
                <option value="terkirim" <?= $filter_status === 'terkirim' ? 'selected' : '' ?>>Terkirim</option>
                <option value="gagal" <?= $filter_status === 'gagal' ? 'selected' : '' ?>>Gagal</option>
            </select>
        </div>

        <div class="bg-white rounded-lg shadow-md">
            <div class="p-4">
                <h2 class="text-xl font-semibold text-gray-800">Riwayat Pengiriman</h2>
            </div>
            <div class="table-container p-4">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-3">Tanggal</th>
                            <th class="p-3">Siswa</th>
                            <th class="p-3">Buku</th>
                            <th class="p-3">Keterlambatan</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while ($log = mysqli_fetch_assoc($result)): ?>
                                <?php
                                $tgl_batas = new DateTime($log['tgl_batas_kembali']);
                                $tgl_kirim = new DateTime($log['tgl_kirim']);
                                $telat = 0;

                                if ($tgl_kirim > $tgl_batas) {
                                    $interval = $tgl_batas->diff($tgl_kirim);
                                    $telat = $interval->days;
                                }
                                ?>
                                <tr class="border-b">
                                    <td class="p-3">
                                        <strong><?= date('d M Y', strtotime($log['tgl_kirim'])) ?></strong><br>
                                        <small class="text-gray-500"><?= date('H:i', strtotime($log['tgl_kirim'])) ?></small>
                                    </td>
                                    <td class="p-3">
                                        <strong><?= htmlspecialchars($log['nama_siswa']) ?></strong><br>
                                        <small class="text-gray-500">Kelas: <?= htmlspecialchars($log['kelas']) ?></small><br>
                                        <small class="text-gray-500">WA: <?= htmlspecialchars($log['no_wa']) ?></small>
                                    </td>
                                    <td class="p-3">
                                        <strong><?= htmlspecialchars($log['judul']) ?></strong><br>
                                        <small class="text-gray-500">DDC: <?= htmlspecialchars($log['kode_ddc']) ?></small>
                                    </td>
                                    <td class="p-3">
                                        <span class="status-badge <?= $telat > 3 ? 'status-failed' : 'status-success' ?>">
                                            <?= $telat ?> hari
                                        </span><br>
                                        <small class="text-gray-500">
                                            Pinjam: <?= date('d M Y', strtotime($log['tgl_pinjam'])) ?><br>
                                            Batas: <?= date('d M Y', strtotime($log['tgl_batas_kembali'])) ?>
                                        </small>
                                    </td>
                                    <td class="p-3">
                                        <span class="status-badge <?= $log['status'] === 'terkirim' ? 'status-success' : 'status-failed' ?>">
                                            <?= $log['status'] === 'terkirim' ? 'Terkirim' : 'Gagal' ?>
                                        </span>
                                    </td>
                                    <td class="p-3 flex space-x-2">
                                        <button class="btn btn-info bg-blue-500 text-white px-3 py-1 rounded-lg hover:bg-blue-600"
                                            data-id="<?= $log['id_log'] ?>"
                                            data-nama="<?= htmlspecialchars($log['nama_siswa']) ?>"
                                            data-kelas="<?= htmlspecialchars($log['kelas']) ?>"
                                            data-nomor="<?= htmlspecialchars($log['no_wa']) ?>"
                                            data-buku="<?= htmlspecialchars($log['judul']) ?>"
                                            data-ddc="<?= htmlspecialchars($log['kode_ddc']) ?>"
                                            data-pinjam="<?= date('d M Y', strtotime($log['tgl_pinjam'])) ?>"
                                            data-harus="<?= date('d M Y', strtotime($log['tgl_batas_kembali'])) ?>"
                                            data-telat="<?= $telat ?>"
                                            data-status="<?= $log['status'] ?>"
                                            data-pesan="<?= htmlspecialchars($log['pesan'] ?? '') ?>">
                                            👁️ Detail
                                        </button>
                                        <?php if ($log['status'] === 'gagal'): ?>
                                            <button class="btn btn-warning bg-yellow-500 text-white px-3 py-1 rounded-lg hover:bg-yellow-600"
                                                data-id="<?= $log['id_log'] ?>"
                                                data-csrf="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                ↻ Kirim Ulang
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="p-4 text-center">
                                    <div class="text-gray-500">
                                        <div class="text-4xl">📭</div>
                                        <h3 class="text-lg font-semibold">Belum Ada Notifikasi WhatsApp</h3>
                                        <p>Riwayat notifikasi akan muncul di sini setelah ada pengiriman</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination flex justify-center space-x-2 mt-4">
                    <?php
                    $prev_page = $page - 1;
                    $next_page = $page + 1;
                    ?>
                    <a href="?page=<?= $prev_page ?>&status=<?= $filter_status ?>" class="px-4 py-2 bg-gray-200 rounded-lg <?= $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-300' ?>">← Sebelumnya</a>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&status=<?= $filter_status ?>" class="px-4 py-2 rounded-lg <?= $i === $page ? 'bg-green-500 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?page=<?= $next_page ?>&status=<?= $filter_status ?>" class="px-4 py-2 bg-gray-200 rounded-lg <?= $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-300' ?>">Selanjutnya →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header p-4 border-b">
                <h3 class="text-xl font-semibold text-gray-800">💬 Detail Notifikasi</h3>
                <button class="modal-close text-gray-500 hover:text-gray-700">×</button>
            </div>
            <div class="modal-body p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-medium text-gray-700">Siswa</h4>
                        <p id="detail-nama" class="text-gray-800"></p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700">Kelas</h4>
                        <p id="detail-kelas" class="text-gray-800"></p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700">Nomor WhatsApp</h4>
                        <p id="detail-nomor" class="text-gray-800"></p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700">Buku</h4>
                        <p id="detail-buku" class="text-gray-800"></p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700">Kode DDC</h4>
                        <p id="detail-ddc" class="text-gray-800"></p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700">Status</h4>
                        <p id="detail-status" class="text-gray-800"></p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700">Tanggal Pinjam</h4>
                        <p id="detail-pinjam" class="text-gray-800"></p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700">Harus Kembali</h4>
                        <p id="detail-harus" class="text-gray-800"></p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700">Keterlambatan</h4>
                        <p id="detail-telat" class="text-gray-800"></p>
                    </div>
                </div>
                <div class="mt-4">
                    <h4 class="font-medium text-gray-700">Pesan yang Dikirim</h4>
                    <div id="detail-pesan" class="bg-gray-100 p-4 rounded-lg text-gray-800 whitespace-pre-wrap"></div>
                </div>
            </div>
            <div class="modal-footer p-4 border-t">
                <button id="closeModal" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('detailModal');
        const closeModalBtn = document.getElementById('closeModal');
        const modalCloseBtn = document.querySelector('.modal-close');

        document.querySelectorAll('.btn-info').forEach(button => {
            button.addEventListener('click', function () {
                const data = this.dataset;
                document.getElementById('detail-nama').textContent = data.nama;
                document.getElementById('detail-kelas').textContent = data.kelas;
                document.getElementById('detail-nomor').textContent = data.nomor;
                document.getElementById('detail-buku').textContent = data.buku;
                document.getElementById('detail-ddc').textContent = data.ddc;
                document.getElementById('detail-pinjam').textContent = data.pinjam;
                document.getElementById('detail-harus').textContent = data.harus;
                document.getElementById('detail-telat').textContent = data.telat + " hari";
                document.getElementById('detail-pesan').textContent = data.pesan;

                const statusElement = document.getElementById('detail-status');
                statusElement.innerHTML = data.status === 'terkirim'
                    ? '<span class="status-badge status-success">Terkirim</span>'
                    : '<span class="status-badge status-failed">Gagal</span>';

                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        });

        function closeModal() {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        closeModalBtn.addEventListener('click', closeModal);
        modalCloseBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Resend notification
        document.querySelectorAll('.btn-warning').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.id;
                const csrfToken = this.dataset.csrf;

                if (confirm('Kirim ulang notifikasi ini?')) {
                    window.location.href = `log_whatsapp.php?resend=${id}&csrf_token=${csrfToken}&status=<?= $filter_status ?>&page=<?= $page ?>`;
                }
            });
        });

        // Filter status
        function applyFilter() {
            const status = document.getElementById('filter-status').value;
            window.location.href = `log_whatsapp.php?status=${status}&page=1`;
        }

        // Auto-close alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    </script>

    <?php require_once '../includes/footer.php'; ?>
</body>
</html>