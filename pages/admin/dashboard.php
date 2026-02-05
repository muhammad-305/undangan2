<?php
require_once 'includes/functions.php';
startSecureSession();
requireAdmin();

$conn = getConnection();

// Get statistics
$totalUndanganQuery = $conn->query("SELECT COUNT(*) as total FROM undangan");
$totalUndangan = $totalUndanganQuery->fetch_assoc()['total'];

$totalCustomersQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$totalCustomers = $totalCustomersQuery->fetch_assoc()['total'];

$totalViewsQuery = $conn->query("SELECT SUM(views) as total FROM undangan");
$totalViews = $totalViewsQuery->fetch_assoc()['total'] ?? 0;

$totalUcapanQuery = $conn->query("SELECT COUNT(*) as total FROM ucapan");
$totalUcapan = $totalUcapanQuery->fetch_assoc()['total'];

// Get recent undangan
$recentUndanganQuery = $conn->query("
    SELECT u.*, us.nama_lengkap as customer_name, t.nama_template 
    FROM undangan u 
    LEFT JOIN users us ON u.user_id = us.id 
    LEFT JOIN template_undangan t ON u.template_id = t.id 
    ORDER BY u.created_at DESC 
    LIMIT 10
");

$pageTitle = "Dashboard Admin";
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
                <a href="<?php echo BASE_URL; ?>admin/dashboard" class="menu-item active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>admin/undangan-list" class="menu-item">
                    <i class="fas fa-envelope"></i> Undangan List
                </a>
                <a href="<?php echo BASE_URL; ?>admin/kelola-user" class="menu-item">
                    <i class="fas fa-users"></i> Kelola User
                </a>
                <a href="<?php echo BASE_URL; ?>logout" class="menu-item">
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
                    <a href="<?php echo BASE_URL; ?>logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
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

                <!-- Dashboard Cards -->
                <div class="dashboard-cards">
                    <div class="dashboard-card primary">
                        <div class="icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Total Undangan</h3>
                        <div class="value"><?php echo $totalUndangan; ?></div>
                    </div>

                    <div class="dashboard-card success">
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Total Customers</h3>
                        <div class="value"><?php echo $totalCustomers; ?></div>
                    </div>

                    <div class="dashboard-card warning">
                        <div class="icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3>Total Views</h3>
                        <div class="value"><?php echo number_format($totalViews); ?></div>
                    </div>

                    <div class="dashboard-card danger">
                        <div class="icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Total Ucapan</h3>
                        <div class="value"><?php echo number_format($totalUcapan); ?></div>
                    </div>
                </div>

                <!-- Recent Undangan -->
                <div class="card" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="font-size: 20px; margin: 0;">Undangan Terbaru</h2>
                        <a href="<?php echo BASE_URL; ?>admin/undangan-list" class="btn btn-primary" style="padding: 10px 20px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 5px;">
                            <i class="fas fa-plus"></i> Lihat Semua
                        </a>
                    </div>
                    
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
                                <?php if ($recentUndanganQuery->num_rows > 0): ?>
                                    <?php while ($row = $recentUndanganQuery->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 12px;">#<?php echo $row['id']; ?></td>
                                        <td style="padding: 12px;">
                                            <strong><?php echo escapeOutput($row['nama_wanita']); ?></strong> & 
                                            <strong><?php echo escapeOutput($row['nama_pria']); ?></strong>
                                        </td>
                                        <td style="padding: 12px;"><?php echo escapeOutput($row['customer_name'] ?? 'N/A'); ?></td>
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
                                                <a href="<?php echo BASE_URL; ?>admin/undangan-preview?id=<?php echo $row['id']; ?>" 
                                                   class="btn btn-sm" 
                                                   style="padding: 6px 12px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;"
                                                   title="Preview">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="padding: 20px; text-align: center; color: #6c757d;">
                                            <i class="fas fa-inbox"></i> Belum ada undangan
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<?php
$conn->close();
?>
