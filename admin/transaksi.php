<?php
session_start();

// Redirect jika belum login
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/config.php';
require_once '../includes/utilities.php';

// ===================================================
// FUNGSI UTAMA TRANSAKSI
// ===================================================

function add_business_days($start_date, $days)
{
    $current_date = new DateTime($start_date);
    $added_days = 0;

    while ($added_days < $days) {
        $current_date->modify('+1 day');
        $day_of_week = $current_date->format('w');
        if ($day_of_week != 0 && $day_of_week != 6) { // Lewati weekend
            $added_days++;
        }
    }

    // Pastikan tidak jatuh di weekend
    while (in_array($current_date->format('w'), [0, 6])) {
        $current_date->modify('+1 day');
    }

    return $current_date->format('Y-m-d');
}

function handle_peminjaman($id_siswa, $id_buku)
{
    global $conn;

    $conn->begin_transaction();
    try {
        // Cek apakah siswa sudah memiliki peminjaman aktif
        $stmt = $conn->prepare("SELECT COUNT(*) as active_loans FROM peminjaman WHERE id_siswa = ? AND tgl_kembali IS NULL");
        $stmt->bind_param("i", $id_siswa);
        $stmt->execute();
        $result = $stmt->get_result();
        $active_loans = $result->fetch_assoc()['active_loans'];

        if ($active_loans > 0) {
            throw new Exception("Siswa ini sudah meminjam buku dan belum mengembalikannya.");
        }

        // Dapatkan info copy buku yang tersedia
        $stmt = $conn->prepare("SELECT id_copy FROM copy_buku 
                               WHERE id_buku = ? AND status = 'Tersedia' 
                               LIMIT 1");
        $stmt->bind_param("i", $id_buku);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Tidak ada copy tersedia untuk buku ini");
        }

        $copy = $result->fetch_assoc();
        $id_copy = $copy['id_copy'];

        // Update status copy
        $stmt = $conn->prepare("UPDATE copy_buku SET status = 'Dipinjam' WHERE id_copy = ?");
        $stmt->bind_param("i", $id_copy);
        $stmt->execute();

        // Catat peminjaman
        $tgl_pinjam = date('Y-m-d');
        $tgl_batas = add_business_days($tgl_pinjam, MAX_PINJAM_HARI);

        $stmt = $conn->prepare("INSERT INTO peminjaman 
            (id_siswa, id_copy, tgl_pinjam, tgl_batas_kembali)
            VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $id_siswa, $id_copy, $tgl_pinjam, $tgl_batas);
        $stmt->execute();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function handle_pengembalian($kode_unik)
{
    global $conn;

    $conn->begin_transaction();
    try {
        // Dapatkan data peminjaman berdasarkan kode unik
        $stmt = $conn->prepare("SELECT p.* FROM peminjaman p
            JOIN copy_buku cb ON p.id_copy = cb.id_copy
            WHERE cb.kode_unik = ? AND p.tgl_kembali IS NULL");
        $stmt->bind_param("s", $kode_unik);
        $stmt->execute();
        $peminjaman = $stmt->get_result()->fetch_assoc();

        if (!$peminjaman) {
            throw new Exception("Tidak ada peminjaman aktif untuk kode: $kode_unik");
        }

        // Update pengembalian
        $tgl_kembali = date('Y-m-d');
        $stmt = $conn->prepare("UPDATE peminjaman SET 
            tgl_kembali = ?
            WHERE id_peminjaman = ?");
        $stmt->bind_param("si", $tgl_kembali, $peminjaman['id_peminjaman']);
        $stmt->execute();

        // Update status copy
        $stmt = $conn->prepare("UPDATE copy_buku SET status = 'Tersedia' WHERE id_copy = ?");
        $stmt->bind_param("i", $peminjaman['id_copy']);
        $stmt->execute();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// ===================================================
// HANDLE FORM SUBMIT
// ===================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi CSRF
    if (!validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = "Token keamanan tidak valid";
        header('Location: transaksi.php');
        exit();
    }

    try {
        if ($_POST['action'] == 'pinjam') {
            $id_siswa = (int) $_POST['id_siswa'];
            $id_buku = (int) $_POST['id_buku'];

            handle_peminjaman($id_siswa, $id_buku);
            $_SESSION['success'] = "Peminjaman berhasil diproses!";

        } elseif ($_POST['action'] == 'kembali') {
            if (isset($_POST['kode_unik'])) {
                // Jika kode_unik dikirim dari dropdown
                $kode_unik = clean_input($_POST['kode_unik']);
                handle_pengembalian($kode_unik);
                $_SESSION['success'] = "Pengembalian berhasil diproses!";
            } else {
                // Proses pencarian berdasarkan nama buku dan kode DDC
                $nama_buku = clean_input($_POST['nama_buku']);
                $kode_ddc = clean_input($_POST['kode_ddc']);

                $stmt = $conn->prepare("
                    SELECT p.*, cb.kode_unik
                    FROM peminjaman p
                    JOIN copy_buku cb ON p.id_copy = cb.id_copy
                    JOIN buku b ON cb.id_buku = b.id_buku
                    WHERE b.judul LIKE ? AND b.kode_ddc = ? AND p.tgl_kembali IS NULL
                ");
                $nama_buku_like = "%$nama_buku%";
                $stmt->bind_param("ss", $nama_buku_like, $kode_ddc);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 0) {
                    throw new Exception("Tidak ada peminjaman aktif untuk buku dengan nama '$nama_buku' dan kode DDC '$kode_ddc'");
                } elseif ($result->num_rows == 1) {
                    $peminjaman = $result->fetch_assoc();
                    handle_pengembalian($peminjaman['kode_unik']);
                    $_SESSION['success'] = "Pengembalian berhasil diproses!";
                } else {
                    // Simpan daftar peminjaman untuk ditampilkan
                    $_SESSION['peminjaman_list'] = $result->fetch_all(MYSQLI_ASSOC);
                    $_SESSION['error'] = "Terdapat beberapa copy buku yang sedang dipinjam. Silakan pilih copy yang akan dikembalikan.";
                    header('Location: transaksi.php');
                    exit();
                }
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }

    header('Location: transaksi.php');
    exit();
}

// ===================================================
// AMBIL DATA
// ===================================================

// Ambil data siswa untuk dropdown
$siswa_list = $conn->query("SELECT id_siswa, nama, kelas FROM siswa ORDER BY nama")
    ->fetch_all(MYSQLI_ASSOC);

// Ambil data semua buku dengan jumlah copy tersedia
$available_books = $conn->query("
    SELECT b.id_buku, b.judul, b.kode_ddc, 
           COALESCE(COUNT(CASE WHEN cb.status = 'Tersedia' THEN cb.id_copy END), 0) AS copy_tersedia
    FROM buku b
    LEFT JOIN copy_buku cb ON b.id_buku = cb.id_buku
    GROUP BY b.id_buku
    ORDER BY b.judul
");

// Ambil data pinjaman aktif
$active_loans = $conn->query("
    SELECT p.*, s.nama AS siswa, b.judul, b.kode_ddc, cb.kode_unik,
        DATEDIFF(p.tgl_batas_kembali, CURDATE()) AS sisa_hari,
        DATEDIFF(CURDATE(), p.tgl_batas_kembali) AS terlambat_hari
    FROM peminjaman p
    JOIN siswa s ON p.id_siswa = s.id_siswa
    JOIN copy_buku cb ON p.id_copy = cb.id_copy
    JOIN buku b ON cb.id_buku = b.id_buku
    WHERE p.tgl_kembali IS NULL
    ORDER BY p.tgl_batas_kembali ASC
");

require_once '../includes/header.php';
?>

<!-- CSS dan HTML -->
<style>
    :root {
        --primary: #2e7d32;
        --primary-light: #4caf50;
        --primary-dark: #1b5e20;
        --secondary: #ff9800;
        --danger: #f44336;
        --warning: #ffc107;
        --success: #4caf50;
        --light: #f5f5f5;
        --dark: #333;
        --gray: #e0e0e0;
        --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --radius: 8px;
        --transition: all 0.3s ease;
    }

    .container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 0 15px;
    }

    .header {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        color: white;
        padding: 1.5rem;
        border-radius: var(--radius);
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
    }

    .header h1 {
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
    }

    .card {
        background: white;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
        overflow: hidden;
        transition: var(--transition);
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .card-header {
        padding: 1rem 1.5rem;
        background: var(--primary-light);
        color: white;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-body {
        padding: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.2rem;
    }

    label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    select,
    input {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: var(--radius);
        font-size: 1rem;
        transition: var(--transition);
    }

    select:focus,
    input:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
    }

    select option[disabled] {
        color: #999;
        background: #f5f5f5;
    }

    .btn {
        display: inline-block;
        padding: 0.8rem 1.5rem;
        border: none;
        border-radius: var(--radius);
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
    }

    .btn-success {
        background: var(--success);
        color: white;
    }

    .btn-success:hover {
        background: var(--primary-dark);
    }

    .btn-danger {
        background: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background: #d32f2f;
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .table-container {
        overflow-x: auto;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        background: white;
    }

    th,
    td {
        padding: 0.8rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    thead tr {
        background: var(--primary-light);
        color: white;
    }

    tbody tr {
        transition: background-color 0.2s;
    }

    tbody tr:hover {
        background-color: rgba(76, 175, 80, 0.05);
    }

    .badge {
        display: inline-block;
        padding: 0.3rem 0.7rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .badge.success {
        background: rgba(76, 175, 80, 0.2);
        color: var(--success);
    }

    .badge.warning {
        background: rgba(255, 193, 7, 0.2);
        color: #e6a800;
    }

    .badge.danger {
        background: rgba(244, 67, 54, 0.2);
        color: var(--danger);
    }

    .alert {
        padding: 1rem;
        border-radius: var(--radius);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background: rgba(76, 175, 80, 0.15);
        border: 1px solid var(--success);
        color: var(--success);
    }

    .alert-error {
        background: rgba(244, 67, 54, 0.15);
        border: 1px solid var(--danger);
        color: var(--danger);
    }

    .close-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        cursor: pointer;
        font-size: 1.2rem;
        opacity: 0.7;
        transition: opacity 0.2s;
    }

    .close-btn:hover {
        opacity: 1;
    }

    .icon {
        font-size: 1.2rem;
    }

    .count-badge {
        background: var(--primary);
        color: white;
        border-radius: 20px;
        padding: 0.2rem 0.6rem;
        font-size: 0.85rem;
        margin-left: 8px;
    }

    .book-info {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        border: 1px solid #eee;
        border-radius: var(--radius);
        margin-bottom: 15px;
        background: #f9f9f9;
    }

    .book-details {
        flex-grow: 1;
    }

    .book-title {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .book-meta {
        font-size: 0.85rem;
        color: #666;
    }

    .copy-badge {
        background: #e3f2fd;
        color: #1976d2;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
    }

    /* Responsif */
    @media (max-width: 768px) {
        .grid {
            grid-template-columns: 1fr;
        }

        .header h1 {
            font-size: 1.5rem;
        }

        .card-body {
            padding: 1rem;
        }

        table {
            font-size: 0.9rem;
        }
    }

    @media (max-width: 480px) {
        .header {
            padding: 1rem;
        }

        .card-header {
            padding: 0.8rem;
            font-size: 0.9rem;
        }

        th,
        td {
            padding: 0.6rem;
        }
    }
</style>

<div class="container">
    <div class="header">
        <h1>Transaksi Perpustakaan</h1>
        <p>Manajemen peminjaman dan pengembalian buku</p>
    </div>

    <?php include '../includes/alert.php'; ?>

    <div class="grid">
        <!-- Form Peminjaman -->
        <div class="card">
            <div class="card-header">
                <span class="icon">↑</span>
                <h2>Peminjaman Buku</h2>
            </div>
            <div class="card-body">
                <form method="POST" id="pinjamForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="pinjam">

                    <div class="form-group">
                        <label for="id_siswa">Siswa</label>
                        <select id="id_siswa" name="id_siswa" required>
                            <option value="">Pilih Siswa</option>
                            <?php foreach ($siswa_list as $siswa): ?>
                                <option value="<?= $siswa['id_siswa'] ?>">
                                    <?= htmlspecialchars($siswa['nama']) ?> (<?= $siswa['kelas'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_buku">Buku</label>
                        <select id="id_buku" name="id_buku" required>
                            <option value="">Pilih Buku</option>
                            <?php while ($book = $available_books->fetch_assoc()): ?>
                                <option value="<?= $book['id_buku'] ?>" <?php echo $book['copy_tersedia'] == 0 ? 'disabled' : ''; ?>>
                                    [<?= htmlspecialchars($book['kode_ddc']) ?>] <?= htmlspecialchars($book['judul']) ?>
                                    (Tersedia: <?= $book['copy_tersedia'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <span class="icon">✓</span> Proses Peminjaman
                    </button>
                </form>
            </div>
        </div>

        <!-- Form Pengembalian -->
        <div class="card">
            <div class="card-header">
                <span class="icon">↓</span>
                <h2>Pengembalian Buku</h2>
            </div>
            <div class="card-body">
                <form method="POST" id="kembaliForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="kembali">

                    <div class="form-group">
                        <label for="nama_buku">Nama Buku</label>
                        <input type="text" id="nama_buku" name="nama_buku" placeholder="Masukkan nama buku" required>
                    </div>

                    <div class="form-group">
                        <label for="kode_ddc">Kode DDC</label>
                        <input type="text" id="kode_ddc" name="kode_ddc" placeholder="Masukkan kode DDC" required>
                    </div>

                    <button type="submit" class="btn btn-danger">
                        <span class="icon">↩</span> Proses Pengembalian
                    </button>
                </form>

                <?php if (isset($_SESSION['peminjaman_list'])): ?>
                    <div class="mt-4">
                        <h5>Pilih Copy Buku yang Akan Dikembalikan:</h5>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="kembali">
                            <select name="kode_unik" required>
                                <option value="">Pilih Copy</option>
                                <?php foreach ($_SESSION['peminjaman_list'] as $peminjaman): ?>
                                    <option value="<?= $peminjaman['kode_unik'] ?>">
                                        <?= $peminjaman['kode_unik'] ?> - Dipinjam oleh <?= $peminjaman['id_siswa'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-danger mt-2">Kembalikan Copy Ini</button>
                        </form>
                        <?php unset($_SESSION['peminjaman_list']); ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <h5>Cara Pengembalian:</h5>
                    <ol>
                        <li>Masukkan nama buku yang akan dikembalikan</li>
                        <li>Masukkan kode DDC buku tersebut</li>
                        <li>Klik tombol "Proses Pengembalian"</li>
                        <li>Jika ada lebih dari satu copy yang dipinjam, pilih copy dari daftar</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Pinjaman Aktif -->
    <div class="card">
        <div class="card-header">
            <span class="icon">⏱</span>
            <h2>Pinjaman Aktif</h2>
            <span class="count-badge"><?= $active_loans->num_rows ?></span>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Buku</th>
                            <th>Kode Unik</th>
                            <th>DDC</th>
                            <th>Tgl Pinjam</th>
                            <th>Batas Kembali</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($active_loans->num_rows > 0): ?>
                            <?php while ($row = $active_loans->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['siswa']) ?></td>
                                    <td><?= htmlspecialchars($row['judul']) ?></td>
                                    <td><?= htmlspecialchars($row['kode_unik']) ?></td>
                                    <td><?= htmlspecialchars($row['kode_ddc']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tgl_pinjam'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['tgl_batas_kembali'])) ?></td>
                                    <td>
                                        <?php
                                        $sisa_hari = $row['sisa_hari'];
                                        $terlambat_hari = $row['terlambat_hari'];

                                        if ($terlambat_hari > 0) {
                                            echo '<span class="badge danger">Terlambat ' . $terlambat_hari . ' Hari</span>';
                                        } elseif ($sisa_hari <= 0) {
                                            echo '<span class="badge warning">Hari Terakhir</span>';
                                        } else {
                                            echo '<span class="badge success">' . $sisa_hari . ' Hari Lagi</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">
                                    Tidak ada pinjaman aktif saat ini
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-close alerts setelah 5 detik
    document.addEventListener('DOMContentLoaded', function () {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            }, 5000);
        });

        // Fokus ke input siswa saat halaman dimuat
        document.getElementById('id_siswa').focus();

        // Handle form submission feedback
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function (e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="icon">⏳</span> Memproses...';

                // Validasi sederhana
                const inputs = this.querySelectorAll('input, select');
                let isValid = true;

                inputs.forEach(input => {
                    if (input.required && !input.value.trim()) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = this.id === 'pinjamForm'
                        ? '<span class="icon">✓</span> Proses Peminjaman'
                        : '<span class="icon">↩</span> Proses Pengembalian';
                }
            });
        });

        // Animasi tabel saat di-scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = 1;
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.opacity = 0;
            row.style.transform = 'translateY(20px)';
            row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(row);
        });
    });
</script>

<?php include '../includes/footer.php'; ?>