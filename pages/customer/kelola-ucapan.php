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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Invalid CSRF token');
        redirect('pages/customer/kelola-ucapan');
    }
    
    if ($_POST['action'] === 'delete_ucapan') {
        $ucapanId = (int)$_POST['ucapan_id'];
        
        $stmt = $conn->prepare("DELETE FROM ucapan WHERE id=? AND undangan_id=?");
        $stmt->bind_param("ii", $ucapanId, $undanganId);
        
        if ($stmt->execute()) {
            logActivity($userId, 'delete_ucapan', "Hapus ucapan ID: $ucapanId");
            setFlashMessage('success', 'Ucapan berhasil dihapus');
        } else {
            setFlashMessage('error', 'Gagal menghapus ucapan');
        }
        $stmt->close();
        redirect('pages/customer/kelola-ucapan');
    }
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $conn->prepare("SELECT nama, ucapan, kehadiran, jumlah_tamu, created_at 
        FROM ucapan WHERE undangan_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $undanganId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ucapan_' . $undangan['slug'] . '_' . date('YmdHis') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Nama', 'Ucapan', 'Kehadiran', 'Jumlah Tamu', 'Tanggal']);
    
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['nama'],
            $row['ucapan'],
            ucfirst(str_replace('_', ' ', $row['kehadiran'])),
            $row['jumlah_tamu'],
            date('d/m/Y H:i', strtotime($row['created_at']))
        ]);
    }
    
    fclose($output);
    $stmt->close();
    $conn->close();
    exit;
}

// Get filter
$filterKehadiran = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get stats
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN kehadiran = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN kehadiran = 'tidak_hadir' THEN 1 ELSE 0 END) as tidak_hadir,
    SUM(CASE WHEN kehadiran = 'masih_ragu' THEN 1 ELSE 0 END) as masih_ragu
    FROM ucapan WHERE undangan_id = ?");
$stmt->bind_param("i", $undanganId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Build query
$query = "SELECT * FROM ucapan WHERE undangan_id = ?";
$params = [$undanganId];
$types = "i";

if ($filterKehadiran) {
    $query .= " AND kehadiran = ?";
    $params[] = $filterKehadiran;
    $types .= "s";
}

if ($search) {
    $query .= " AND (nama LIKE ? OR ucapan LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM ucapan WHERE undangan_id = ?";
$countParams = [$undanganId];
$countTypes = "i";

if ($filterKehadiran) {
    $countQuery .= " AND kehadiran = ?";
    $countParams[] = $filterKehadiran;
    $countTypes .= "s";
}

if ($search) {
    $countQuery .= " AND (nama LIKE ? OR ucapan LIKE ?)";
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
    $countTypes .= "ss";
}

$stmt = $conn->prepare($countQuery);
$stmt->bind_param($countTypes, ...$countParams);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalRows / $perPage);

// Get ucapan
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$ucapanList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

$pageTitle = 'Kelola Ucapan';
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
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar select, .filter-bar input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .filter-bar button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
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
                <a href="<?php echo BASE_URL; ?>pages/customer/kelola-ucapan" class="menu-item active">
                    <i class="fas fa-comments"></i> Kelola Ucapan
                </a>
                <a href="<?php echo BASE_URL; ?>pages/customer/kelola-hadiah" class="menu-item">
                    <i class="fas fa-gift"></i> Kelola Hadiah
                </a>
                <a href="<?php echo BASE_URL; ?>pages/customer/kelola-link" class="menu-item">
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
                <div class="page-header" style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1><i class="fas fa-comments"></i> Kelola Ucapan</h1>
                        <p style="color: var(--text-light);">Kelola ucapan dan konfirmasi kehadiran</p>
                    </div>
                    <a href="?export=csv" class="btn" style="background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                </div>

                <?php
                $flash = getFlashMessage();
                if ($flash):
                ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 5px; background: <?php echo $flash['type'] === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $flash['type'] === 'success' ? '#155724' : '#721c24'; ?>;">
                        <?php echo escapeOutput($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="number"><?php echo number_format($stats['total']); ?></div>
                        <div class="label"><i class="fas fa-comments"></i> Total Ucapan</div>
                    </div>
                    <div class="stat-card">
                        <div class="number" style="color: #28a745;"><?php echo number_format($stats['hadir']); ?></div>
                        <div class="label"><i class="fas fa-check-circle"></i> Hadir</div>
                    </div>
                    <div class="stat-card">
                        <div class="number" style="color: #dc3545;"><?php echo number_format($stats['tidak_hadir']); ?></div>
                        <div class="label"><i class="fas fa-times-circle"></i> Tidak Hadir</div>
                    </div>
                    <div class="stat-card">
                        <div class="number" style="color: #ffc107;"><?php echo number_format($stats['masih_ragu']); ?></div>
                        <div class="label"><i class="fas fa-question-circle"></i> Masih Ragu</div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <form class="filter-bar" method="GET">
                    <select name="filter" onchange="this.form.submit()">
                        <option value="">Semua Kehadiran</option>
                        <option value="hadir" <?php echo $filterKehadiran === 'hadir' ? 'selected' : ''; ?>>Hadir</option>
                        <option value="tidak_hadir" <?php echo $filterKehadiran === 'tidak_hadir' ? 'selected' : ''; ?>>Tidak Hadir</option>
                        <option value="masih_ragu" <?php echo $filterKehadiran === 'masih_ragu' ? 'selected' : ''; ?>>Masih Ragu</option>
                    </select>
                    
                    <input type="text" name="search" placeholder="Cari nama atau ucapan..." value="<?php echo escapeOutput($search); ?>" style="flex: 1; min-width: 200px;">
                    
                    <button type="submit" style="background: var(--primary-color); color: white;">
                        <i class="fas fa-search"></i> Cari
                    </button>
                    
                    <?php if ($filterKehadiran || $search): ?>
                        <a href="<?php echo BASE_URL; ?>pages/customer/kelola-ucapan" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>

                <!-- Ucapan List -->
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <?php if (empty($ucapanList)): ?>
                        <p style="text-align: center; color: var(--text-light); padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                            Belum ada ucapan
                        </p>
                    <?php else: ?>
                        <?php foreach ($ucapanList as $ucapan): ?>
                            <div class="ucapan-item">
                                <div class="ucapan-header">
                                    <div>
                                        <div class="ucapan-name"><?php echo escapeOutput($ucapan['nama']); ?></div>
                                        <div class="ucapan-date"><?php echo timeAgo($ucapan['created_at']); ?></div>
                                    </div>
                                    <form method="POST" style="display: inline;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete_ucapan">
                                        <input type="hidden" name="ucapan_id" value="<?php echo $ucapan['id']; ?>">
                                        <button type="submit" onclick="return confirm('Hapus ucapan ini?')" style="background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 12px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="ucapan-text"><?php echo nl2br(escapeOutput($ucapan['ucapan'])); ?></div>
                                <div class="ucapan-footer">
                                    <span class="kehadiran-badge <?php echo $ucapan['kehadiran']; ?>">
                                        <?php 
                                        if ($ucapan['kehadiran'] === 'hadir') {
                                            echo '<i class="fas fa-check-circle"></i> Hadir (' . $ucapan['jumlah_tamu'] . ' orang)';
                                        } elseif ($ucapan['kehadiran'] === 'tidak_hadir') {
                                            echo '<i class="fas fa-times-circle"></i> Tidak Hadir';
                                        } else {
                                            echo '<i class="fas fa-question-circle"></i> Masih Ragu';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $filterKehadiran ? '&filter=' . $filterKehadiran : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i> Prev
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <span class="active"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?><?php echo $filterKehadiran ? '&filter=' . $filterKehadiran : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $filterKehadiran ? '&filter=' . $filterKehadiran : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
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
</body>
</html>
