<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: ../index.php');
    exit();
}

include '../includes/config.php';

// Jika ada parameter tanggal, proses ekspor
if (isset($_GET['start']) && isset($_GET['end'])) {
    $start = $_GET['start'];
    $end = $_GET['end'];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_peminjaman_' . $start . '_' . $end . '.csv"');

    $output = fopen('php://output', 'w');

    // Header CSV
    fputcsv($output, [
        'Tanggal Pinjam',
        'Tanggal Kembali',
        'Nama Siswa',
        'Kelas',
        'No WhatsApp',
        'Judul Buku',
        'Kode DDC',
        'Status',
        'Notifikasi Terkirim'
    ]);

    // Query data peminjaman
    $query = mysqli_query(
        $conn,
        "SELECT 
            p.tgl_pinjam AS tanggal_pinjam,
            p.tgl_kembali AS tanggal_kembali,
            s.nama,
            s.kelas,
            s.no_telepon AS no_wa,
            b.judul,
            b.kode_ddc,
            CASE 
                WHEN p.tgl_kembali IS NULL AND DATEDIFF(CURDATE(), p.tgl_batas_kembali) > 3 THEN 'Terlambat'
                WHEN p.tgl_kembali IS NULL THEN 'Dipinjam'
                ELSE 'Dikembalikan'
            END AS status,
            IF(lw.id_log IS NULL, 'Tidak', 'Ya') AS notifikasi_terkirim
         FROM peminjaman p
         JOIN siswa s ON p.id_siswa = s.id_siswa
         JOIN copy_buku cb ON p.id_copy = cb.id_copy
         JOIN buku b ON cb.id_buku = b.id_buku
         LEFT JOIN log_whatsapp lw ON p.id_peminjaman = lw.id_peminjaman
         WHERE p.tgl_pinjam BETWEEN '$start' AND '$end'
         ORDER BY p.tgl_pinjam DESC"
    );

    while ($row = mysqli_fetch_assoc($query)) {
        // Format tanggal
        $row['tanggal_pinjam'] = date('d/m/Y', strtotime($row['tanggal_pinjam']));
        $row['tanggal_kembali'] = $row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : 'Belum Kembali';

        // Format nomor WhatsApp
        $row['no_wa'] = $row['no_wa'] ? "'" . $row['no_wa'] : '';

        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

include '../includes/header.php';
?>

<main class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 py-3">
        <div>
            <h1 class="h2 mb-0">Ekspor Laporan Peminjaman</h1>
            <p class="mb-0 text-muted">Pilih rentang tanggal untuk mengekspor data</p>
        </div>
    </div>

    <?php include '../includes/alert.php'; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form id="exportForm" method="GET">
                <div class="form-group mb-4">
                    <label class="form-label">Rentang Tanggal</label>
                    <div class="date-range">
                        <div class="mb-3">
                            <label for="start">Dari Tanggal</label>
                            <input type="date" class="form-control" id="start" name="start" required>
                        </div>
                        <div class="mb-3">
                            <label for="end">Sampai Tanggal</label>
                            <input type="date" class="form-control" id="end" name="end" required>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-file-earmark-arrow-down"></i> Ekspor Data
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title mb-4">
                <i class="bi bi-info-circle"></i> Informasi Ekspor
            </h5>
            <div class="alert alert-info">
                <ul class="mb-0">
                    <li>Data akan diekspor dalam format CSV yang kompatibel dengan Excel</li>
                    <li>File akan berisi semua peminjaman dalam rentang tanggal yang dipilih</li>
                    <li>Kolom yang diekspor: Tanggal Pinjam, Tanggal Kembali, Nama Siswa, Kelas, No WhatsApp, Judul
                        Buku, Kode DDC, Status, Notifikasi Terkirim</li>
                    <li>Format nomor WhatsApp sudah diatur untuk kompatibilitas dengan Excel</li>
                    <li>File dapat dibuka dengan Microsoft Excel, Google Sheets, atau aplikasi spreadsheet lainnya</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<style>
    .date-range {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .btn-group {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn-primary {
        background: #2e7d32;
        color: white;
    }

    .btn-primary:hover {
        background: #1b5e20;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(46, 125, 50, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        font-size: 1rem;
    }

    .form-control:focus {
        border-color: #2e7d32;
        outline: none;
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
    }

    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        display: block;
    }

    .alert-info {
        background-color: #e8f5e9;
        border-left: 4px solid #2e7d32;
        color: #1b5e20;
    }

    @media (max-width: 768px) {
        .date-range {
            grid-template-columns: 1fr;
        }

        .btn-group {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Set tanggal default: 1 bulan terakhir
        const today = new Date();
        const lastMonth = new Date(today);
        lastMonth.setMonth(today.getMonth() - 1);

        // Format tanggal ke YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Set nilai default
        document.getElementById('start').value = formatDate(lastMonth);
        document.getElementById('end').value = formatDate(today);

        // Validasi form
        document.getElementById('exportForm').addEventListener('submit', function (e) {
            const start = document.getElementById('start').value;
            const end = document.getElementById('end').value;

            if (!start || !end) {
                e.preventDefault();
                alert('Silakan pilih rentang tanggal yang lengkap');
                return;
            }

            if (new Date(start) > new Date(end)) {
                e.preventDefault();
                alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir');
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>