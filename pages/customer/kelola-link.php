<?php
define('ROOT_PATH', dirname(__DIR__, 2) . '/');
require_once ROOT_PATH . 'includes/functions.php';

startSecureSession();
requireCustomer();

$conn = getConnection();
$userId = $_SESSION['user_id'];
$userName = $_SESSION['nama_lengkap'] ?? $_SESSION['username'];

// Get customer's undangan
$stmt = $conn->prepare("SELECT * FROM undangan WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$undangan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$undangan) {
    setFlashMessage('error', 'Anda belum memiliki undangan.');
    redirect('pages/customer/dashboard');
}

$undanganId = $undangan['id'];
$undanganSlug = $undangan['slug'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Invalid CSRF token');
        redirect('pages/customer/kelola-link');
    }
    
    $action = $_POST['action'];
    
    // Add link
    if ($action === 'add_link') {
        $nama_tamu = sanitizeInput($_POST['nama_tamu']);
        $keterangan = sanitizeInput($_POST['keterangan']);
        $slug_tamu = generateSlug($nama_tamu);
        
        // Check duplicate
        $stmt = $conn->prepare("SELECT id FROM link_tamu WHERE undangan_id = ? AND slug_tamu = ?");
        $stmt->bind_param("is", $undanganId, $slug_tamu);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($exists) {
            $slug_tamu = $slug_tamu . '-' . time();
        }
        
        $stmt = $conn->prepare("INSERT INTO link_tamu (undangan_id, nama_tamu, slug_tamu, keterangan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $undanganId, $nama_tamu, $slug_tamu, $keterangan);
        
        if ($stmt->execute()) {
            logActivity($userId, 'add_link', "Tambah link tamu: $nama_tamu");
            setFlashMessage('success', 'Link tamu berhasil ditambahkan');
        } else {
            setFlashMessage('error', 'Gagal menambahkan link tamu');
        }
        $stmt->close();
        redirect('pages/customer/kelola-link');
    }
    
    // Edit link
    if ($action === 'edit_link') {
        $linkId = (int)$_POST['link_id'];
        $nama_tamu = sanitizeInput($_POST['nama_tamu']);
        $keterangan = sanitizeInput($_POST['keterangan']);
        
        $stmt = $conn->prepare("UPDATE link_tamu SET nama_tamu=?, keterangan=? WHERE id=? AND undangan_id=?");
        $stmt->bind_param("ssii", $nama_tamu, $keterangan, $linkId, $undanganId);
        
        if ($stmt->execute()) {
            logActivity($userId, 'edit_link', "Edit link tamu ID: $linkId");
            setFlashMessage('success', 'Link tamu berhasil diperbarui');
        } else {
            setFlashMessage('error', 'Gagal memperbarui link tamu');
        }
        $stmt->close();
        redirect('pages/customer/kelola-link');
    }
    
    // Delete link
    if ($action === 'delete_link') {
        $linkId = (int)$_POST['link_id'];
        
        $stmt = $conn->prepare("DELETE FROM link_tamu WHERE id=? AND undangan_id=?");
        $stmt->bind_param("ii", $linkId, $undanganId);
        
        if ($stmt->execute()) {
            logActivity($userId, 'delete_link', "Hapus link tamu ID: $linkId");
            setFlashMessage('success', 'Link tamu berhasil dihapus');
        } else {
            setFlashMessage('error', 'Gagal menghapus link tamu');
        }
        $stmt->close();
        redirect('pages/customer/kelola-link');
    }
    
    // Bulk add from CSV
    if ($action === 'bulk_add') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $header = fgetcsv($file); // Skip header
            $count = 0;
            
            while (($row = fgetcsv($file)) !== false) {
                if (!empty($row[0])) {
                    $nama_tamu = sanitizeInput($row[0]);
                    $keterangan = isset($row[1]) ? sanitizeInput($row[1]) : '';
                    $slug_tamu = generateSlug($nama_tamu);
                    
                    // Check duplicate and add timestamp if needed
                    $stmt = $conn->prepare("SELECT id FROM link_tamu WHERE undangan_id = ? AND slug_tamu = ?");
                    $stmt->bind_param("is", $undanganId, $slug_tamu);
                    $stmt->execute();
                    if ($stmt->get_result()->fetch_assoc()) {
                        $slug_tamu = $slug_tamu . '-' . time() . '-' . $count;
                    }
                    $stmt->close();
                    
                    $stmt = $conn->prepare("INSERT INTO link_tamu (undangan_id, nama_tamu, slug_tamu, keterangan) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $undanganId, $nama_tamu, $slug_tamu, $keterangan);
                    if ($stmt->execute()) {
                        $count++;
                    }
                    $stmt->close();
                }
            }
            
            fclose($file);
            logActivity($userId, 'bulk_add_link', "Bulk add $count link tamu");
            setFlashMessage('success', "$count link tamu berhasil ditambahkan");
        } else {
            setFlashMessage('error', 'File CSV tidak valid');
        }
        redirect('pages/customer/kelola-link');
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total links
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM link_tamu WHERE undangan_id = ?");
$stmt->bind_param("i", $undanganId);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalRows / $perPage);

// Get links
$stmt = $conn->prepare("SELECT * FROM link_tamu WHERE undangan_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $undanganId, $perPage, $offset);
$stmt->execute();
$linkList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

$pageTitle = 'Kelola Link Tamu';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/customer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: white;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: var(--bg-light);
            font-weight: 600;
            color: var(--text-dark);
        }
        .link-url {
            color: var(--primary-color);
            font-size: 13px;
            word-break: break-all;
        }
        .actions {
            display: flex;
            gap: 5px;
        }
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 2000;
            display: none;
        }
        .toast.show {
            display: block;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from {
                transform: translateX(400px);
            }
            to {
                transform: translateX(0);
            }
        }
        .pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 15px;
            background: white;
            border-radius: 5px;
            text-decoration: none;
            color: var(--text-dark);
        }
        .pagination .active {
            background: var(--primary-color);
            color: white;
        }
        .info-box {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="customer-dashboard">
        <!-- Sidebar -->
        <aside class="customer-sidebar">
            <div class="sidebar-header">
                <h2>Customer Panel</h2>
                <p style="opacity: 0.8; font-size: 14px;"><?php echo escapeOutput($userName); ?></p>
            </div>
            <nav class="sidebar-menu">
                <a href="<?php echo BASE_URL; ?>pages/customer/dashboard" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>pages/customer/edit-undangan" class="menu-item">
                    <i class="fas fa-edit"></i> Edit Undangan
                </a>
                <a href="<?php echo BASE_URL; ?>pages/customer/kelola-ucapan" class="menu-item">
                    <i class="fas fa-comments"></i> Kelola Ucapan
                </a>
                <a href="<?php echo BASE_URL; ?>pages/customer/kelola-hadiah" class="menu-item">
                    <i class="fas fa-gift"></i> Kelola Hadiah
                </a>
                <a href="<?php echo BASE_URL; ?>pages/customer/kelola-link" class="menu-item active">
                    <i class="fas fa-link"></i> Kelola Link
                </a>
                <a href="<?php echo BASE_URL; ?>pages/logout" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-wrapper" style="padding: 30px;">
                <div class="page-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h1><i class="fas fa-link"></i> Kelola Link Tamu</h1>
                        <p style="color: var(--text-light);">Kelola link undangan untuk tamu</p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="openBulkModal()" class="btn btn-info">
                            <i class="fas fa-file-csv"></i> Import CSV
                        </button>
                        <button onclick="openAddModal()" class="btn btn-success">
                            <i class="fas fa-plus"></i> Tambah Link
                        </button>
                    </div>
                </div>

                <?php
                $flash = getFlashMessage();
                if ($flash):
                ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 5px; background: <?php echo $flash['type'] === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $flash['type'] === 'success' ? '#155724' : '#721c24'; ?>;">
                        <?php echo escapeOutput($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <i class="fas fa-info-circle"></i> <strong>Format Link:</strong> 
                    <?php echo BASE_URL . escapeOutput($undanganSlug); ?>/<strong>nama-tamu</strong>
                </div>

                <!-- Link Table -->
                <div style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
                    <?php if (empty($linkList)): ?>
                        <div style="text-align: center; padding: 60px 20px;">
                            <i class="fas fa-link" style="font-size: 64px; color: var(--text-light); margin-bottom: 20px;"></i>
                            <p style="color: var(--text-light); font-size: 18px;">Belum ada link tamu</p>
                            <button onclick="openAddModal()" class="btn btn-success" style="margin-top: 15px;">
                                <i class="fas fa-plus"></i> Tambah Link Pertama
                            </button>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Nama Tamu</th>
                                        <th>Link</th>
                                        <th>Keterangan</th>
                                        <th style="width: 200px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($linkList as $index => $link): ?>
                                        <tr>
                                            <td><?php echo $offset + $index + 1; ?></td>
                                            <td><strong><?php echo escapeOutput($link['nama_tamu']); ?></strong></td>
                                            <td>
                                                <div class="link-url">
                                                    <?php echo BASE_URL . escapeOutput($undanganSlug) . '/' . escapeOutput($link['slug_tamu']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo escapeOutput($link['keterangan']); ?></td>
                                            <td>
                                                <div class="actions">
                                                    <button onclick='copyLink("<?php echo BASE_URL . escapeOutput($undanganSlug) . '/' . escapeOutput($link['slug_tamu']); ?>")' class="btn btn-primary" style="padding: 8px 12px; font-size: 12px;">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                    <button onclick='openEditModal(<?php echo json_encode($link); ?>)' class="btn btn-warning" style="padding: 8px 12px; font-size: 12px;">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="action" value="delete_link">
                                                        <input type="hidden" name="link_id" value="<?php echo $link['id']; ?>">
                                                        <button type="submit" onclick="return confirm('Hapus link ini?')" class="btn btn-danger" style="padding: 8px 12px; font-size: 12px;">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination" style="padding: 20px;">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Prev
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <span class="active"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Link Tamu</h3>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add_link">
                
                <div class="form-group">
                    <label>Nama Tamu *</label>
                    <input type="text" name="nama_tamu" required placeholder="Contoh: Budi dan Keluarga">
                    <small style="color: var(--text-light);">Slug akan dibuat otomatis dari nama tamu</small>
                </div>
                
                <div class="form-group">
                    <label>Keterangan (Opsional)</label>
                    <textarea name="keterangan" rows="3" placeholder="Catatan tambahan..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Link Tamu</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="edit_link">
                <input type="hidden" name="link_id" id="edit_link_id">
                
                <div class="form-group">
                    <label>Nama Tamu *</label>
                    <input type="text" name="nama_tamu" id="edit_nama_tamu" required>
                </div>
                
                <div class="form-group">
                    <label>Keterangan (Opsional)</label>
                    <textarea name="keterangan" id="edit_keterangan" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
            </form>
        </div>
    </div>

    <!-- Bulk Import Modal -->
    <div id="bulkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-csv"></i> Import Link dari CSV</h3>
                <button class="modal-close" onclick="closeBulkModal()">&times;</button>
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <strong>Format CSV:</strong><br>
                Baris pertama adalah header: <code>Nama Tamu,Keterangan</code><br>
                Contoh:<br>
                <code>
                Nama Tamu,Keterangan<br>
                Budi dan Keluarga,Tetangga<br>
                Ani Susanti,Teman Kantor
                </code>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="bulk_add">
                
                <div class="form-group">
                    <label>Upload File CSV *</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Import
                </button>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i> Link berhasil disalin!
    </div>

    <script>
    function openAddModal() {
        document.getElementById('addModal').classList.add('active');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.remove('active');
    }

    function openEditModal(link) {
        document.getElementById('edit_link_id').value = link.id;
        document.getElementById('edit_nama_tamu').value = link.nama_tamu;
        document.getElementById('edit_keterangan').value = link.keterangan || '';
        document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    function openBulkModal() {
        document.getElementById('bulkModal').classList.add('active');
    }

    function closeBulkModal() {
        document.getElementById('bulkModal').classList.remove('active');
    }

    function copyLink(link) {
        navigator.clipboard.writeText(link).then(function() {
            const toast = document.getElementById('toast');
            toast.classList.add('show');
            setTimeout(function() {
                toast.classList.remove('show');
            }, 3000);
        });
    }

    // Close modal on outside click
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('active');
        }
    }
    </script>
</body>
</html>
