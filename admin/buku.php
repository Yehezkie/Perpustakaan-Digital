<?php
session_start();

// Redirect jika belum login
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/config.php';
require_once '../includes/utilities.php';

// Inisialisasi variabel
$current_book = null;
$conn->set_charset("utf8mb4");

// HANDLE TAMBAH COPY BUKU
if (isset($_POST['add_copies'])) {
    // Validasi CSRF
    if (!validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid CSRF token";
        header('Location: buku.php');
        exit();
    }

    $id_buku = (int) $_POST['id_buku'];
    $jumlah = (int) $_POST['jumlah_copy'];

    try {
        $conn->begin_transaction();

        // Generate kode
        $stmt = $conn->prepare("SELECT MAX(kode_unik) AS last_code FROM copy_buku WHERE id_buku = ?");
        $stmt->bind_param("i", $id_buku);
        $stmt->execute();
        $result = $stmt->get_result();
        $last_code = $result->fetch_assoc()['last_code'];

        $base_code = $last_code ? preg_replace('/-\d+$/', '', $last_code) : 'BUKU-' . str_pad($id_buku, 3, '0', STR_PAD_LEFT);
        $last_num = $last_code ? (int) substr($last_code, strrpos($last_code, '-') + 1) : 0;

        $stmt = $conn->prepare("INSERT INTO copy_buku (id_buku, kode_unik) VALUES (?, ?)");
        for ($i = 1; $i <= $jumlah; $i++) {
            $kode = $base_code . '-' . ($last_num + $i);
            $stmt->bind_param("is", $id_buku, $kode);
            $stmt->execute();
        }

        $conn->commit();
        $_SESSION['success'] = "$jumlah copy berhasil ditambahkan!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: buku.php');
    exit();
}

// HANDLE OPERASI CRUD UTAMA DENGAN DDC
if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['delete'])) {
    // Validasi CSRF
    if (!isset($_REQUEST['csrf_token']) || $_REQUEST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token";
        header('Location: buku.php');
        exit();
    }

    if (isset($_GET['delete'])) {
        // Handle delete
        $id = (int) $_GET['delete'];
        try {
            $conn->begin_transaction();
            $stmt = $conn->prepare("DELETE FROM copy_buku WHERE id_buku = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM buku WHERE id_buku = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $conn->commit();
            $_SESSION['success'] = "Buku dan semua copy berhasil dihapus!";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Gagal menghapus: " . $e->getMessage();
        }
        header('Location: buku.php');
        exit();
    }

    // Handle form submit
    $id = (int) ($_POST['id'] ?? 0);

    // Bersihkan input
    $data = [
        'judul' => clean_input($_POST['judul']),
        'kode_ddc' => clean_input($_POST['kode_ddc']),
        'penulis' => clean_input($_POST['penulis']),
        'penerbit' => clean_input($_POST['penerbit']),
        'tahun_terbit' => (int) $_POST['tahun_terbit'],
        'deskripsi' => clean_input($_POST['deskripsi'])
    ];

    // Validasi DDC
    if (!array_key_exists($data['kode_ddc'], $ddc_categories)) {
        $_SESSION['error'] = "Kategori DDC tidak valid!";
        header('Location: buku.php');
        exit();
    }

    // Validasi lainnya
    if (empty($data['judul']) || empty($data['penulis'])) {
        $_SESSION['error'] = "Judul dan Penulis wajib diisi!";
        header('Location: buku.php');
        exit();
    }

    try {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE buku SET 
                judul = ?, kode_ddc = ?, penulis = ?, 
                penerbit = ?, tahun_terbit = ?, deskripsi = ?
                WHERE id_buku = ?");
            $stmt->bind_param(
                "ssssisi",
                $data['judul'],
                $data['kode_ddc'],
                $data['penulis'],
                $data['penerbit'],
                $data['tahun_terbit'],
                $data['deskripsi'],
                $id
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO buku 
                (judul, kode_ddc, penulis, penerbit, tahun_terbit, deskripsi)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "ssssis",
                $data['judul'],
                $data['kode_ddc'],
                $data['penulis'],
                $data['penerbit'],
                $data['tahun_terbit'],
                $data['deskripsi']
            );
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "Buku berhasil " . ($id > 0 ? "diupdate" : "ditambahkan");
        } else {
            throw new Exception("Gagal menyimpan data: " . $stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: buku.php');
    exit();
}

// Pengaturan Pagination dan Pencarian
$limit = 10; // Jumlah buku per halaman
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Query untuk menghitung total buku
$total_query = "SELECT COUNT(*) as total FROM buku";
if ($search) {
    $total_query .= " WHERE judul LIKE ? OR penulis LIKE ? OR penerbit LIKE ?";
}
$stmt = $conn->prepare($total_query);
if ($search) {
    $search_param = "%$search%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt->execute();
$total_books = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_books / $limit);

// Ambil data buku dengan pagination dan pencarian
$query = "
    SELECT b.*, 
    COUNT(cb.id_copy) AS total_copy,
    SUM(CASE WHEN cb.status = 'Tersedia' THEN 1 ELSE 0 END) AS tersedia
    FROM buku b
    LEFT JOIN copy_buku cb ON b.id_buku = cb.id_buku";
if ($search) {
    $query .= " WHERE b.judul LIKE ? OR b.penulis LIKE ? OR b.penerbit LIKE ?";
}
$query .= " GROUP BY b.id_buku ORDER BY b.kode_ddc, b.judul LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
if ($search) {
    $search_param = "%$search%";
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$books = $stmt->get_result();

// Jika mode edit
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM buku WHERE id_buku = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $current_book = $stmt->get_result()->fetch_assoc();
}

require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Buku - Perpustakaan Digital</title>
    <style>
        :root {
            --primary: #2e7d32;
            --primary-dark: #1b5e20;
            --primary-light: #c8e6c9;
            --secondary: #4caf50;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --success: #28a745;
            --border: #dee2e6;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e9 100%);
            color: #212529;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-light);
            position: relative;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 150px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }

        .alert {
            padding: 16px 20px;
            margin-bottom: 24px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            position: relative;
            box-shadow: var(--card-shadow);
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid var(--danger);
        }

        .btn-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: none;
            border: none;
            font-size: 1.4rem;
            cursor: pointer;
            opacity: 0.7;
            color: inherit;
        }

        .btn-close:hover {
            opacity: 1;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            overflow: hidden;
            transition: var(--transition);
            border: none;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .required::after {
            content: '*';
            color: var(--danger);
            margin-left: 4px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background: #fff;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
        }

        .form-select {
            display: block;
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            background: #fff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px 12px;
            appearance: none;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            gap: 8px;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 800px;
        }

        .table th {
            background: linear-gradient(135deg, var(--primary-light), #d1e7dd);
            color: var(--primary-dark);
            font-weight: 600;
            padding: 16px;
            text-align: left;
        }

        .table td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover td {
            background-color: rgba(200, 230, 201, 0.2);
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-info {
            background: linear-gradient(135deg, #0dcaf0, #0aa2c0);
            color: white;
        }

        .badge-success {
            background: linear-gradient(135deg, var(--success), #218838);
            color: white;
        }

        .badge-primary {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            padding: 8px;
            border-radius: 6px;
            min-width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-edit {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--danger), #bd2130);
            color: white;
        }

        .btn-copy {
            background: linear-gradient(135deg, var(--info), #138496);
            color: white;
        }

        .btn-add {
            background: linear-gradient(135deg, var(--success), #218838);
            color: white;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transform: translateY(20px);
            transition: var(--transition);
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-lg .modal-content {
            max-width: 800px;
        }

        .modal-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-body {
            padding: 25px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 16px 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: var(--primary-light);
            margin-bottom: 20px;
        }

        .empty-state-text {
            font-size: 1.2rem;
            color: var(--gray);
            margin-bottom: 30px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 10px 15px;
            border-radius: 8px;
            background: var(--light);
            color: var(--dark);
            text-decoration: none;
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination a.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination a.disabled {
            background: var(--light-gray);
            color: var(--gray);
            pointer-events: none;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            max-width: 400px;
        }

        .search-container input {
            flex: 1;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .card-body {
                padding: 20px;
            }

            .table th,
            .table td {
                padding: 12px;
            }

            .action-buttons {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 15px;
            }

            .page-header {
                font-size: 1.8rem;
            }

            .modal-content {
                width: 95%;
            }

            .search-container {
                flex-direction: column;
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="page-header">Manajemen Buku</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close">×</button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close">×</button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Form Input -->
        <div class="card">
            <div class="card-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                </svg>
                <h2 class="card-title">
                    <?= isset($_GET['edit']) ? '✏️ Edit Buku' : '➕ Tambah Buku Baru' ?>
                </h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <?php if (isset($current_book)): ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($current_book['id_buku']) ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label required">Judul Buku</label>
                            <input type="text" class="form-control" name="judul"
                                value="<?= htmlspecialchars($current_book['judul'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Kategori DDC</label>
                            <select class="form-select" name="kode_ddc" required>
                                <option value="">Pilih Kategori DDC</option>
                                <?php foreach ($ddc_categories as $code => $label): ?>
                                    <?php $selected = ($current_book['kode_ddc'] ?? '') == $code ? 'selected' : ''; ?>
                                    <option value="<?= $code ?>" <?= $selected ?>>
                                        <?= $code ?> - <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">Penulis</label>
                            <input type="text" class="form-control" name="penulis"
                                value="<?= htmlspecialchars($current_book['penulis'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Penerbit</label>
                            <input type="text" class="form-control" name="penerbit"
                                value="<?= htmlspecialchars($current_book['penerbit'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tahun Terbit</label>
                            <input type="number" class="form-control" name="tahun_terbit"
                                value="<?= htmlspecialchars($current_book['tahun_terbit'] ?? date('Y')) ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi"
                                rows="4"><?= htmlspecialchars($current_book['deskripsi'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Simpan
                        </button>
                        <?php if (isset($_GET['edit'])): ?>
                            <a href="buku.php" class="btn btn-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daftar Buku -->
        <div class="card">
            <div class="card-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                <h2 class="card-title">Daftar Buku</h2>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <div class="search-container">
                    <form method="GET">
                        <input type="text" name="search" class="form-control"
                            placeholder="Cari judul, penulis, atau penerbit..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            Cari
                        </button>
                    </form>
                </div>

                <?php if ($books->num_rows === 0): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📚</div>
                        <h3 class="empty-state-text">Belum ada data buku</h3>
                        <p>Silakan tambahkan buku baru untuk memulai</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>DDC</th>
                                    <th>Judul</th>
                                    <th>Penulis</th>
                                    <th>Copy</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($book = $books->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= htmlspecialchars($book['kode_ddc']) ?>
                                            </span>
                                            <div class="text-muted">
                                                <?= $ddc_categories[substr($book['kode_ddc'], 0, 1) . '00'] ?? 'Umum' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold"><?= htmlspecialchars($book['judul']) ?></div>
                                            <div class="text-muted"><?= htmlspecialchars($book['penerbit']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($book['penulis']) ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <span class="badge badge-primary">Total: <?= $book['total_copy'] ?></span>
                                                <span class="badge badge-success">Tersedia: <?= $book['tersedia'] ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-icon btn-copy btn-show-codes"
                                                    data-id="<?= $book['id_buku'] ?>" title="Lihat Kode">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1">
                                                        </path>
                                                    </svg>
                                                </button>
                                                <button class="btn btn-icon btn-add btn-add-copies"
                                                    data-id="<?= $book['id_buku'] ?>" title="Tambah Copy">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                                    </svg>
                                                </button>
                                                <a href="?edit=<?= $book['id_buku'] ?>" class="btn btn-icon btn-edit"
                                                    title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7">
                                                        </path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z">
                                                        </path>
                                                    </svg>
                                                </a>
                                                <a href="?delete=<?= $book['id_buku'] ?>&csrf_token=<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                                    class="btn btn-icon btn-delete" title="Hapus"
                                                    onclick="return confirm('Hapus buku ini beserta semua copy?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path
                                                            d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                        </path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="pagination">
                        <?php
                        $range = 2;
                        $start = max(1, $page - $range);
                        $end = min($total_pages, $page + $range);

                        if ($page > 1) {
                            $prev_page = $page - 1;
                            echo "<a href='?page=$prev_page&search=" . htmlspecialchars($search) . "'>&laquo; Prev</a>";
                        } else {
                            echo "<a class='disabled'>&laquo; Prev</a>";
                        }

                        if ($start > 1) {
                            echo "<a href='?page=1&search=" . htmlspecialchars($search) . "'>1</a>";
                            if ($start > 2)
                                echo "<span>...</span>";
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            $active = ($i == $page) ? 'active' : '';
                            echo "<a href='?page=$i&search=" . htmlspecialchars($search) . "' class='$active'>$i</a>";
                        }

                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1)
                                echo "<span>...</span>";
                            echo "<a href='?page=$total_pages&search=" . htmlspecialchars($search) . "'>$total_pages</a>";
                        }

                        if ($page < $total_pages) {
                            $next_page = $page + 1;
                            echo "<a href='?page=$next_page& chapeau=" . htmlspecialchars($search) . "'>Next &raquo;</a>";
                        } else {
                            echo "<a class='disabled'>Next &raquo;</a>";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Copy -->
    <div class="modal" id="copyModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Tambah Copy Buku</h3>
                    <button type="button" class="close">×</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="id_buku" id="modalBookId">
                        <div class="form-group">
                            <label class="form-label">Jumlah Copy</label>
                            <input type="number" class="form-control" name="jumlah_copy" min="1" max="20" value="1"
                                required>
                            <small class="text-muted">Maksimal 20 copy per buku</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="add_copies" class="btn btn-success">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Lihat Kode -->
    <div class="modal" id="kodeModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Daftar Kode Buku</h3>
                    <button type="button" class="close">×</button>
                </div>
                <div class="modal-body">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kode Unik</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="kodeList">
                                <!-- Data diisi via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        const modals = document.querySelectorAll('.modal');
        const openModal = (modalId) => {
            const modal = document.getElementById(modalId);
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        };

        const closeModal = (modalId) => {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        };

        document.querySelectorAll('.close').forEach(button => {
            button.addEventListener('click', () => {
                const modal = button.closest('.modal');
                closeModal(modal.id);
            });
        });

        modals.forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal(modal.id);
                }
            });
        });

        document.querySelectorAll('.btn-add-copies').forEach(button => {
            button.addEventListener('click', () => {
                document.getElementById('modalBookId').value = button.dataset.id;
                openModal('copyModal');
            });
        });

        document.querySelectorAll('.btn-show-codes').forEach(button => {
            button.addEventListener('click', () => {
                const bookId = button.dataset.id;

                fetch(`get_kode_buku.php?id=${bookId}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        const tbody = document.getElementById('kodeList');
                        if (Array.isArray(data) && data.length > 0) {
                            tbody.innerHTML = data.map(item => `
                                <tr>
                                    <td>${item.kode_unik || ''}</td>
                                    <td>
                                        <span class="badge ${item.status === 'Tersedia' ? 'badge-success' : 'badge-warning'}">
                                            ${item.status || ''}
                                        </
                                    </td>
                                </tr>
                            `).join('');
                        } else {
                            tbody.innerHTML = `<tr><td colspan="2" class="text-center">Tidak ada data copy buku</td></tr>`;
                        }
                        openModal('kodeModal');
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        const tbody = document.getElementById('kodeList');
                        tbody.innerHTML = `<tr><td colspan="2" class="text-center">Error memuat data</td></tr>`;
                        openModal('kodeModal');
                    });
            });
        });

        document.querySelectorAll('.btn-close').forEach(button => {
            button.addEventListener('click', () => {
                button.closest('.alert').style.display = 'none';
            });
        });

        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        });
    </script>
</body>

</html>
<?php
require_once '../includes/footer.php';
?>