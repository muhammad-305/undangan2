<?php
require_once '../../includes/functions.php';
startSecureSession();
requireAdmin();

$conn = getConnection();

// Handle search
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build query with search
if ($search) {
    $searchLike = "%$search%";
    
    // Count total
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM undangan u LEFT JOIN users us ON u.user_id = us.id WHERE u.nama_pria LIKE ? OR u.nama_wanita LIKE ? OR us.nama_lengkap LIKE ?");
    $countStmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get undangan list
    $stmt = $conn->prepare("
        SELECT u.*, us.nama_lengkap as customer_name, us.email as customer_email, t.nama_template 
        FROM undangan u 
        LEFT JOIN users us ON u.user_id = us.id 
        LEFT JOIN template_undangan t ON u.template_id = t.id 
        WHERE u.nama_pria LIKE ? OR u.nama_wanita LIKE ? OR us.nama_lengkap LIKE ?
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("sssii", $searchLike, $searchLike, $searchLike, $perPage, $offset);
    $stmt->execute();
    $undanganQuery = $stmt->get_result();
} else {
    // Count total
    $countQuery = $conn->query("SELECT COUNT(*) as total FROM undangan u LEFT JOIN users us ON u.user_id = us.id");
    $totalRecords = $countQuery->fetch_assoc()['total'];
    
    // Get undangan list
    $stmt = $conn->prepare("
        SELECT u.*, us.nama_lengkap as customer_name, us.email as customer_email, t.nama_template 
        FROM undangan u 
        LEFT JOIN users us ON u.user_id = us.id 
        LEFT JOIN template_undangan t ON u.template_id = t.id 
        ORDER BY u.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $undanganQuery = $stmt->get_result();
}

$totalPages = ceil($totalRecords / $perPage);

// Handle delete
if (isset($_POST['delete_id']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $deleteId = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM undangan WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        setFlashMessage('success', 'Undangan berhasil dihapus');
        logActivity($_SESSION['user_id'], 'delete_undangan', "Deleted undangan ID: $deleteId");
    } else {
        setFlashMessage('error', 'Gagal menghapus undangan');
    }
    $stmt->close();
    header('Location: ' . BASE_URL . 'pages/admin/undangan-list');
    exit;
}

$pageTitle = "Undangan List";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Undangan Online</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-dark);
        }
        .pagination a:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        .pagination span.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-heart"></i> Undangan Online</h2>
                <p>Admin Panel</p>
            </div>
            <nav class="sidebar-menu">
                <a href="<?php echo BASE_URL; ?>pages/admin/dashboard" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>pages/admin/undangan-list" class="menu-item active">
                    <i class="fas fa-envelope"></i> Undangan List
                </a>
                <a href="<?php echo BASE_URL; ?>pages/admin/kelola-user" class="menu-item">
                    <i class="fas fa-users"></i> Kelola User
                </a>
                <a href="<?php echo BASE_URL; ?>pages/logout" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <h1><?php echo $pageTitle; ?></h1>
                <div class="user-menu">
                    <span><i class="fas fa-user-circle"></i> <?php echo escapeOutput($_SESSION['nama_lengkap'] ?? 'Admin'); ?></span>
                    <a href="<?php echo BASE_URL; ?>pages/logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Content -->
            <div class="content-wrapper">
                <?php 
                $flash = getFlashMessage();
                if ($flash): 
                ?>
                <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 5px; background: <?php echo $flash['type'] === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $flash['type'] === 'success' ? '#155724' : '#721c24'; ?>;">
                    <?php echo escapeOutput($flash['message']); ?>
                </div>
                <?php endif; ?>

                <div class="card" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="font-size: 20px; margin: 0;">Daftar Undangan (<?php echo $totalRecords; ?>)</h2>
                    </div>

                    <!-- Search Form -->
                    <form method="GET" action="" class="search-box">
                        <input type="text" name="search" placeholder="Cari berdasarkan nama mempelai atau customer..." value="<?php echo escapeOutput($search); ?>">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 20px; background: var(--primary-color); color: white; border: none; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <?php if ($search): ?>
                        <a href="<?php echo BASE_URL; ?>pages/admin/undangan-list" class="btn" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;">
                            <i class="fas fa-times"></i> Reset
                        </a>
                        <?php endif; ?>
                    </form>
                    
                    <div style="overflow-x: auto;">
                        <table class="table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 12px; text-align: left;">ID</th>
                                    <th style="padding: 12px; text-align: left;">Nama Mempelai</th>
                                    <th style="padding: 12px; text-align: left;">Customer</th>
                                    <th style="padding: 12px; text-align: left;">Template</th>
                                    <th style="padding: 12px; text-align: center;">Status</th>
                                    <th style="padding: 12px; text-align: center;">Views</th>
                                    <th style="padding: 12px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($undanganQuery->num_rows > 0): ?>
                                    <?php while ($row = $undanganQuery->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 12px;">#<?php echo $row['id']; ?></td>
                                        <td style="padding: 12px;">
                                            <div>
                                                <strong><?php echo escapeOutput($row['nama_wanita']); ?></strong> & 
                                                <strong><?php echo escapeOutput($row['nama_pria']); ?></strong>
                                            </div>
                                            <small style="color: #6c757d;">Slug: <?php echo escapeOutput($row['slug']); ?></small>
                                        </td>
                                        <td style="padding: 12px;">
                                            <div><?php echo escapeOutput($row['customer_name'] ?? 'N/A'); ?></div>
                                            <small style="color: #6c757d;"><?php echo escapeOutput($row['customer_email'] ?? ''); ?></small>
                                        </td>
                                        <td style="padding: 12px;"><?php echo escapeOutput($row['nama_template'] ?? 'N/A'); ?></td>
                                        <td style="padding: 12px; text-align: center;">
                                            <?php if ($row['is_published']): ?>
                                                <span style="display: inline-block; padding: 4px 12px; background: #d4edda; color: #155724; border-radius: 12px; font-size: 12px;">
                                                    <i class="fas fa-check-circle"></i> Published
                                                </span>
                                            <?php else: ?>
                                                <span style="display: inline-block; padding: 4px 12px; background: #fff3cd; color: #856404; border-radius: 12px; font-size: 12px;">
                                                    <i class="fas fa-clock"></i> Draft
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px; text-align: center;"><?php echo number_format($row['views']); ?></td>
                                        <td style="padding: 12px; text-align: center;">
                                            <div class="action-buttons" style="display: flex; gap: 5px; justify-content: center;">
                                                <a href="<?php echo BASE_URL; ?>pages/admin/undangan-preview?id=<?php echo $row['id']; ?>" 
                                                   class="btn btn-sm" 
                                                   style="padding: 6px 12px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;"
                                                   title="Preview">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL . escapeOutput($row['slug']); ?>" 
                                                   class="btn btn-sm" 
                                                   style="padding: 6px 12px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;"
                                                   title="View Public"
                                                   target="_blank">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <button onclick="deleteUndangan(<?php echo $row['id']; ?>, '<?php echo escapeOutput($row['nama_wanita'] . ' & ' . $row['nama_pria']); ?>')" 
                                                        class="btn btn-sm" 
                                                        style="padding: 6px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; font-size: 14px; cursor: pointer;"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="padding: 20px; text-align: center; color: #6c757d;">
                                            <i class="fas fa-inbox"></i> 
                                            <?php echo $search ? 'Tidak ada undangan yang sesuai dengan pencarian' : 'Belum ada undangan'; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="delete_id" id="deleteId">
    </form>

    <script>
    function deleteUndangan(id, name) {
        if (confirm('Apakah Anda yakin ingin menghapus undangan "' + name + '"?\n\nSemua data terkait (ucapan, hadiah, link tamu) akan ikut terhapus.')) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
    </script>
</body>
</html>
<?php
$conn->close();
?>
