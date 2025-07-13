<?php
ob_start();
session_start();

if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(dirname(__FILE__) . '/../..'));
}
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/utilities.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: ../index.php');
    exit();
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');

    // Handle book copies request
    if (isset($_GET['id'])) {
        $bookId = (int) $_GET['id'];

        $sql = "SELECT id_copy, kode_unik, status 
                FROM copy_buku 
                WHERE id_buku = ? 
                ORDER BY kode_unik";
        $result = db_query($sql, [$bookId]);

        $copies = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $copies[] = [
                    'id_copy' => $row['id_copy'],
                    'kode_unik' => $row['kode_unik'],
                    'status' => $row['status']
                ];
            }
            mysqli_free_result($result);
            echo json_encode($copies);
        } else {
            echo json_encode(['error' => 'Gagal memuat data copy buku']);
        }
        ob_end_flush();
        exit;
    }

    // Handle search request
    if (isset($_GET['query'])) {
        // Validate CSRF token
        if (!isset($_GET['csrf_token']) || !validate_csrf_token($_GET['csrf_token'])) {
            echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid']);
            ob_end_flush();
            exit;
        }

        $query = clean_input($_GET['query']);
        if (strlen($query) < 1) {
            echo json_encode(['success' => false, 'message' => 'Query terlalu pendek']);
            ob_end_flush();
            exit;
        }

        // Query to fetch available books
        $sql = "SELECT b.id_buku, b.judul, b.kode_ddc,
                       cb.id_copy, cb.kode_unik
                FROM buku b
                JOIN copy_buku cb ON b.id_buku = cb.id_buku
                LEFT JOIN peminjaman p ON cb.id_copy = p.id_copy AND p.tgl_kembali IS NULL
                WHERE (b.kode_ddc LIKE ? OR b.judul LIKE ?)
                AND p.id_peminjaman IS NULL
                GROUP BY cb.id_copy
                LIMIT 10";

        $param = "%$query%";
        $result = db_query($sql, [$param, $param]);

        $books = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $books[] = [
                    'id_buku' => (int) $row['id_buku'],
                    'id_copy' => (int) $row['id_copy'],
                    'kode_ddc' => $row['kode_ddc'],
                    'judul' => $row['judul'],
                    'kode_unik' => $row['kode_unik']
                ];
            }
            mysqli_free_result($result);
        }

        echo json_encode([
            'success' => true,
            'data' => $books,
            'message' => empty($books) ? 'Tidak ada buku tersedia ditemukan' : 'Buku ditemukan'
        ]);
        ob_end_flush();
        exit;
    }
}

// Include header
require_once BASE_PATH . '/includes/header.php';
?>

<div class="container">
    <h3 class="mt-4 mb-4">Manajemen Copy Buku</h3>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span>Daftar Copy Buku</span>
                <button class="btn btn-primary" id="refreshBtn">
                    <i class="refresh-icon">↻</i> Refresh Data
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="alert alert-info">
                <i class="info-icon">ℹ</i>
                <div>
                    Gunakan halaman ini untuk melihat detail copy buku atau mencari buku yang tersedia.
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Cari Buku (DDC atau Judul):</label>
                <div class="d-flex">
                    <input type="text" class="form-control" id="searchInput"
                        placeholder="Masukkan kode DDC atau judul buku">
                    <button class="btn btn-primary ml-2" id="searchBtn">
                        <i class="search-icon">🔍</i> Cari
                    </button>
                </div>
            </div>

            <div id="searchResults" class="mt-4">
                <div class="text-center p-3" id="searchPlaceholder">
                    <div class="info-icon">📚</div>
                    <p class="mt-2">Gunakan form pencarian di atas untuk menemukan buku</p>
                </div>
            </div>

            <div class="table-responsive mt-4" id="copyTableContainer" style="display: none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode Unik</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="copyTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Copy Details -->
<div class="modal" id="copyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Detail Copy Buku</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Kode Unik:</label>
                <input type="text" class="form-control" id="modalKode" readonly>
            </div>

            <div class="form-group">
                <label class="form-label">Status:</label>
                <input type="text" class="form-control" id="modalStatus" readonly>
            </div>

            <div class="form-group">
                <label class="form-label">ID Buku:</label>
                <input type="text" class="form-control" id="modalBookId" readonly>
            </div>

            <div class="form-group">
                <label class="form-label">ID Copy:</label>
                <input type="text" class="form-control" id="modalCopyId" readonly>
            </div>
        </div>
    </div>
</div>

<script>
    // CSRF Token
    const csrfToken = "<?= $_SESSION['csrf_token'] ?>";

    // DOM Elements
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const searchResults = document.getElementById('searchResults');
    const searchPlaceholder = document.getElementById('searchPlaceholder');
    const copyTableContainer = document.getElementById('copyTableContainer');
    const copyTableBody = document.getElementById('copyTableBody');
    const refreshBtn = document.getElementById('refreshBtn');
    const copyModal = document.getElementById('copyModal');
    const modalClose = document.querySelector('.modal-close');

    // Event Listeners
    searchBtn.addEventListener('click', searchBooks);
    refreshBtn.addEventListener('click', clearSearch);
    modalClose.addEventListener('click', () => copyModal.style.display = 'none');

    // Search on Enter key
    searchInput.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') searchBooks();
    });

    // Search books function
    function searchBooks() {
        const query = searchInput.value.trim();
        if (!query) return;

        searchPlaceholder.innerHTML = '<div class="spinner"></div><p>Mencari buku...</p>';
        copyTableContainer.style.display = 'none';

        fetch(`get_kode_buku.php?query=${encodeURIComponent(query)}&csrf_token=${csrfToken}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    displaySearchResults(data.data);
                } else {
                    searchPlaceholder.innerHTML = `
                        <div class="info-icon">🔍</div>
                        <p class="mt-2">${data.message || 'Tidak ada buku yang ditemukan'}</p>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                searchPlaceholder.innerHTML = `
                    <div class="info-icon">⚠️</div>
                    <p class="mt-2">Terjadi kesalahan saat mencari buku</p>
                `;
            });
    }

    // Display search results
    function displaySearchResults(books) {
        searchPlaceholder.style.display = 'none';
        searchResults.innerHTML = '';

        books.forEach(book => {
            const bookElement = document.createElement('div');
            bookElement.className = 'card mb-3';
            bookElement.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4>${book.judul}</h4>
                            <p class="mb-1">DDC: ${book.kode_ddc}</p>
                            <p>Kode Unik: ${book.kode_unik}</p>
                        </div>
                        <button class="btn btn-primary" onclick="showBookCopies(${book.id_buku})">
                            Lihat Copy
                        </button>
                    </div>
                </div>
            `;
            searchResults.appendChild(bookElement);
        });
    }

    // Show book copies
    function showBookCopies(bookId) {
        copyTableContainer.style.display = 'block';
        copyTableBody.innerHTML = '<tr><td colspan="3" class="text-center"><div class="spinner"></div></td></tr>';

        fetch(`get_kode_buku.php?id=${bookId}`)
            .then(response => response.json())
            .then(copies => {
                copyTableBody.innerHTML = '';

                if (copies.length > 0) {
                    copies.forEach(copy => {
                        const statusClass = copy.status === 'Tersedia' ?
                            'badge-success' : copy.status === 'Dipinjam' ?
                                'badge-warning' : 'badge-danger';

                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${copy.kode_unik}</td>
                            <td><span class="badge ${statusClass}">${copy.status}</span></td>
                            <td>
                                <button class="btn btn-primary" 
                                    onclick="showCopyDetails(${copy.id_copy}, '${copy.kode_unik}', '${copy.status}', ${bookId})">
                                    Detail
                                </button>
                            </td>
                        `;
                        copyTableBody.appendChild(row);
                    });
                } else {
                    copyTableBody.innerHTML = `
                        <tr>
                            <td colspan="3" class="text-center">
                                Tidak ada copy buku untuk buku ini
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                copyTableBody.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center text-danger">
                            Gagal memuat data copy buku
                        </td>
                    </tr>
                `;
            });
    }

    // Show copy details in modal
    function showCopyDetails(copyId, kodeUnik, status, bookId) {
        document.getElementById('modalKode').value = kodeUnik;
        document.getElementById('modalStatus').value = status;
        document.getElementById('modalCopyId').value = copyId;
        document.getElementById('modalBookId').value = bookId;
        copyModal.style.display = 'flex';
    }

    // Clear search
    function clearSearch() {
        searchInput.value = '';
        searchPlaceholder.style.display = 'block';
        searchPlaceholder.innerHTML = `
            <div class="info-icon">📚</div>
            <p class="mt-2">Gunakan form pencarian di atas untuk menemukan buku</p>
        `;
        searchResults.innerHTML = '';
        copyTableContainer.style.display = 'none';
    }

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === copyModal) {
            copyModal.style.display = 'none';
        }
    });
</script>

<?php
// Include footer
require_once BASE_PATH . '/includes/footer.php';
ob_end_flush();
?>