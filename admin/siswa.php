<?php
session_start();
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: ../index.php');
    exit();
}

include '../includes/config.php';

// Generate CSRF token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CRUD Logic
$error = $success = '';
$current_siswa = null;

// Create/Update Siswa
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'])) {
        $error = "Token keamanan tidak valid!";
    } else {
        $id = $_POST['id'] ?? 0;
        $nama = clean_input($_POST['nama']);
        $kelas = clean_input($_POST['kelas']);
        $no_telp = clean_input($_POST['no_telepon']);

        // Validasi nomor WhatsApp
        $no_telp = preg_replace('/[^0-9]/', '', $no_telp);
        if (substr($no_telp, 0, 1) === '0') {
            $no_telp = '62' . substr($no_telp, 1);
        }

        if (empty($nama) || empty($kelas) || empty($no_telp)) {
            $error = "Nama, Kelas, dan Nomor WhatsApp wajib diisi!";
        } elseif (strlen($no_telp) < 10 || !is_numeric($no_telp)) {
            $error = "Nomor WhatsApp harus minimal 10 digit angka!";
        } else {
            if ($id > 0) {
                // Update
                $sql = "UPDATE siswa SET nama = ?, kelas = ?, no_telepon = ? WHERE id_siswa = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $nama, $kelas, $no_telp, $id);
                $action = "diupdate";
            } else {
                // Create
                $sql = "INSERT INTO siswa (nama, kelas, no_telepon) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $nama, $kelas, $no_telp);
                $action = "ditambahkan";
            }

            if ($stmt->execute()) {
                $success = "Siswa berhasil $action!";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Delete Siswa
if (isset($_GET['delete'])) {
    if (!validate_csrf_token($_GET['csrf_token'])) {
        $error = "Token keamanan tidak valid!";
    } else {
        $id = (int) $_GET['delete'];
        $sql = "DELETE FROM siswa WHERE id_siswa = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $success = "Siswa berhasil dihapus!";
        } else {
            $error = "Gagal menghapus: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get All Students
$siswa = mysqli_query($conn, "SELECT * FROM siswa ORDER BY nama ASC");

// Set page title
$page_title = "Manajemen Siswa";

// Include header
include '../includes/header.php';
?>

<style>
    /* === VARIABLES === */
    :root {
        --primary-50: #e8f5e9;
        --primary-100: #c8e6c9;
        --primary-200: #a5d6a7;
        --primary-300: #81c784;
        --primary-400: #66bb6a;
        --primary-500: #4caf50;
        --primary-600: #43a047;
        --primary-700: #388e3c;
        --primary-800: #2e7d32;
        --primary-900: #1b5e20;
        --primary-dark: #1b5e20;
        --primary-light: #4caf50;
        --secondary: #2196f3;
        --danger: #f44336;
        --warning: #ff9800;
        --success: #4caf50;
        --info: #17a2b8;
        --dark: #333;
        --light: #f5f5f5;
        --gray: #e0e0e0;
        --card-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        --transition: all 0.3s ease;
        --border-radius: 12px;
        --gradient: linear-gradient(135deg, var(--primary-800) 0%, var(--primary-600) 100%);
    }

    /* === BASE STYLES === */
    body {
        background-color: #f5f9fc;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--dark);
    }

    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* === HEADER === */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--primary-100);
    }

    .page-header h3 {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-800);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .page-header h3 i {
        background: var(--gradient);
        color: white;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* === CARDS === */
    .card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        border: none;
        margin-bottom: 30px;
        transition: var(--transition);
        overflow: hidden;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    }

    .card-header {
        background: var(--gradient);
        color: white;
        padding: 18px 25px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .card-title {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-body {
        padding: 25px;
    }

    /* === FORM STYLES === */
    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: var(--primary-800);
        font-size: 15px;
    }

    .form-control {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e0e0e0;
        border-radius: var(--border-radius);
        font-size: 16px;
        transition: var(--transition);
        background-color: white;
        color: var(--dark);
    }

    .form-control:focus {
        border-color: var(--primary-500);
        outline: none;
        box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.2);
    }

    .input-group {
        display: flex;
        margin-bottom: 10px;
        position: relative;
    }

    .input-group-text {
        padding: 14px 15px;
        background-color: var(--primary-50);
        border: 2px solid #e0e0e0;
        border-right: none;
        border-radius: var(--border-radius) 0 0 var(--border-radius);
        color: var(--primary-800);
        font-weight: 500;
        display: flex;
        align-items: center;
    }

    .input-group input {
        border-radius: 0 var(--border-radius) var(--border-radius) 0;
        flex: 1;
    }

    .text-muted {
        color: #6c757d;
        font-size: 14px;
        display: block;
        margin-top: 8px;
    }

    /* === BUTTONS === */
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: var(--border-radius);
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-primary {
        background: var(--gradient);
        color: white;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-900) 0%, var(--primary-700) 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(27, 94, 32, 0.3);
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
    }

    .btn-action {
        padding: 8px 14px;
        font-size: 14px;
        border-radius: 8px;
    }

    .btn-edit {
        background-color: var(--warning);
        color: white;
    }

    .btn-edit:hover {
        background-color: #e68a00;
    }

    .btn-delete {
        background-color: var(--danger);
        color: white;
    }

    .btn-delete:hover {
        background-color: #e53935;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    /* === STUDENT LIST === */
    .student-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 25px;
    }

    .student-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        padding: 20px;
        transition: var(--transition);
        border-left: 4px solid var(--primary-500);
        position: relative;
        overflow: hidden;
    }

    .student-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .student-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient);
        opacity: 0;
        transition: var(--transition);
    }

    .student-card:hover::before {
        opacity: 1;
    }

    .student-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .student-avatar {
        width: 60px;
        height: 60px;
        background: var(--gradient);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: bold;
        flex-shrink: 0;
        margin-right: 15px;
    }

    .student-info {
        flex: 1;
    }

    .student-name {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 5px 0;
        color: var(--primary-800);
    }

    .student-class {
        background-color: var(--primary-100);
        color: var(--primary-800);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        display: inline-block;
    }

    .student-details {
        margin-bottom: 15px;
    }

    .detail-item {
        display: flex;
        margin-bottom: 10px;
        align-items: center;
    }

    .detail-label {
        width: 120px;
        font-weight: 600;
        color: var(--primary-700);
        font-size: 14px;
    }

    .detail-value {
        flex: 1;
        font-size: 15px;
    }

    .wa-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #25D366;
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
        padding: 6px 12px;
        border-radius: 6px;
        background: rgba(37, 211, 102, 0.1);
    }

    .wa-link:hover {
        background: rgba(37, 211, 102, 0.2);
        color: #128C7E;
    }

    .student-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        font-size: 14px;
        color: #6c757d;
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        grid-column: 1 / -1;
    }

    .empty-icon {
        font-size: 64px;
        color: var(--primary-200);
        margin-bottom: 20px;
    }

    .empty-text {
        font-size: 18px;
        color: #6c757d;
        margin-bottom: 30px;
    }

    /* === ANIMATIONS === */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .student-card {
        animation: fadeIn 0.5s ease-out;
        animation-fill-mode: both;
    }

    .student-card:nth-child(1) { animation-delay: 0.1s; }
    .student-card:nth-child(2) { animation-delay: 0.2s; }
    .student-card:nth-child(3) { animation-delay: 0.3s; }
    .student-card:nth-child(4) { animation-delay: 0.4s; }
    .student-card:nth-child(5) { animation-delay: 0.5s; }
    .student-card:nth-child(6) { animation-delay: 0.6s; }
    .student-card:nth-child(7) { animation-delay: 0.7s; }
    .student-card:nth-child(8) { animation-delay: 0.8s; }

    /* === ALERTS === */
    .alert {
        padding: 16px 20px;
        border-radius: var(--border-radius);
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        animation: fadeIn 0.5s;
    }

    .alert-danger {
        background-color: #ffebee;
        color: #b71c1c;
        border-left: 4px solid var(--danger);
    }

    .alert-success {
        background-color: #e8f5e9;
        color: #1b5e20;
        border-left: 4px solid var(--success);
    }

    .alert-icon {
        font-size: 24px;
    }

    .alert-close {
        margin-left: auto;
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: inherit;
    }

    /* === RESPONSIVE === */
    @media (max-width: 992px) {
        .student-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .card-body {
            padding: 20px;
        }
        
        .student-grid {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .input-group {
            flex-direction: column;
        }
        
        .input-group-text {
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            justify-content: center;
        }
        
        .form-control {
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
    }

    @media (max-width: 576px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .card-header {
            padding: 15px 20px;
        }
    }
</style>

<div class="container-fluid px-4">
    <div class="page-header">
        <h3>
            <i class="bi bi-people"></i>
            Manajemen Siswa
        </h3>
    </div>

    <!-- Notifikasi -->
    <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <div><?= htmlspecialchars($error) ?></div>
                <button class="alert-close">&times;</button>
            </div>
    <?php endif; ?>

    <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill alert-icon"></i>
                <div><?= htmlspecialchars($success) ?></div>
                <button class="alert-close">&times;</button>
            </div>
    <?php endif; ?>

    <!-- Form Tambah/Edit Siswa -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="bi <?= isset($_GET['edit']) ? 'bi-pencil' : 'bi-person-add' ?>"></i>
                <?= isset($_GET['edit']) ? 'Edit Data Siswa' : 'Tambah Siswa Baru' ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <?php if (isset($_GET['edit'])):
                    $edit_id = (int) $_GET['edit'];
                    $current_siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa=$edit_id"));
                    ?>
                        <input type="hidden" name="id" value="<?= $edit_id ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nama"
                        value="<?= htmlspecialchars($current_siswa['nama'] ?? '') ?>" required
                        placeholder="Masukkan nama lengkap">
                </div>

                <div class="form-group">
                    <label class="form-label">Kelas <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="kelas" 
                        value="<?= htmlspecialchars($current_siswa['kelas'] ?? '') ?>" required
                        placeholder="Contoh: 12-A">
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">+62</span>
                        <input type="tel" class="form-control" name="no_telepon" pattern="[0-9]{9,15}"
                            title="Minimal 10 digit angka" value="<?= isset($current_siswa['no_telepon']) ?
                                (substr($current_siswa['no_telepon'], 0, 2) === '62' ?
                                    substr($current_siswa['no_telepon'], 2) :
                                    $current_siswa['no_telepon'])
                                : '' ?>" required
                            placeholder="Masukkan nomor WhatsApp">
                    </div>
                    <small class="text-muted">Contoh: 81234567890 (tanpa +62)</small>
                </div>

                <div class="form-group" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Data
                    </button>

                    <?php if (isset($_GET['edit'])): ?>
                            <a href="siswa.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Siswa -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="bi bi-people"></i>
                Daftar Siswa Terdaftar
            </h5>
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($siswa) === 0): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <h4>Belum Ada Data Siswa</h4>
                        <p class="empty-text">Silakan tambahkan siswa baru menggunakan form di atas</p>
                    </div>
            <?php else: ?>
                    <div class="student-grid">
                        <?php while ($row = mysqli_fetch_assoc($siswa)): ?>
                                <?php
                                $wa_number = $row['no_telepon'];
                                // Pastikan format 62xxxx
                                if (substr($wa_number, 0, 2) !== '62') {
                                    $wa_number = '62' . ltrim($wa_number, '0');
                                }

                                $initial = substr($row['nama'], 0, 1);
                                $registered_date = date('d M Y', strtotime($row['created_at']));
                                ?>
                                <div class="student-card">
                                    <div class="student-header">
                                        <div class="student-avatar">
                                            <?= $initial ?>
                                        </div>
                                        <div class="student-info">
                                            <h4 class="student-name"><?= htmlspecialchars($row['nama']) ?></h4>
                                            <span class="student-class"><?= htmlspecialchars($row['kelas']) ?></span>
                                        </div>
                                    </div>
                            
                                    <div class="student-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Nomor WA:</span>
                                            <span class="detail-value">
                                                <a href="https://wa.me/<?= $wa_number ?>" class="wa-link" target="_blank">
                                                    <i class="bi bi-whatsapp"></i>
                                                    <?= htmlspecialchars($row['no_telepon']) ?>
                                                </a>
                                            </span>
                                        </div>
                                
                                        <div class="detail-item">
                                            <span class="detail-label">Terdaftar:</span>
                                            <span class="detail-value"><?= $registered_date ?></span>
                                        </div>
                                    </div>
                            
                                    <div class="student-footer">
                                        <span>ID: <?= $row['id_siswa'] ?></span>
                                        <div class="action-buttons">
                                            <a href="siswa.php?edit=<?= $row['id_siswa'] ?>" class="btn btn-action btn-edit">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="siswa.php?delete=<?= $row['id_siswa'] ?>&csrf_token=<?= htmlspecialchars($_SESSION['csrf_token']) ?>" 
                                               class="btn btn-action btn-delete" 
                                               onclick="return confirm('Hapus data siswa <?= htmlspecialchars(addslashes($row['nama'])) ?>?')">
                                                <i class="bi bi-trash"></i> Hapus
                                            </a>
                                        </div>
                                    </div>
                                </div>
                        <?php endwhile; ?>
                    </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Close alert buttons
    document.querySelectorAll('.alert-close').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.alert').style.display = 'none';
        });
    });
    
    // Confirm delete function
    function confirmDelete(name) {
        return confirm(`Apakah Anda yakin ingin menghapus data siswa ${name}?`);
    }
</script>

<?php
include '../includes/footer.php';
?>