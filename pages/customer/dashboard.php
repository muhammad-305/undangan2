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

// Initialize stats
$totalViews = 0;
$totalUcapan = 0;
$hadirCount = 0;
$tidakHadirCount = 0;
$masihRaguCount = 0;

if ($undangan) {
    $undanganId = $undangan['id'];
    $totalViews = $undangan['views'];
    
    // Get ucapan stats
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
    
    $totalUcapan = $stats['total'];
    $hadirCount = $stats['hadir'];
    $tidakHadirCount = $stats['tidak_hadir'];
    $masihRaguCount = $stats['masih_ragu'];
}

$conn->close();

$pageTitle = 'Dashboard Customer';
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
                <a href="<?php echo BASE_URL; ?>customer/dashboard" class="menu-item active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <?php if ($undangan): ?>
                <a href="<?php echo BASE_URL; ?>customer/edit-undangan" class="menu-item">
                    <i class="fas fa-edit"></i> Edit Undangan
                </a>
                <a href="<?php echo BASE_URL; ?>customer/kelola-ucapan" class="menu-item">
                    <i class="fas fa-comments"></i> Kelola Ucapan
                </a>
                <a href="<?php echo BASE_URL; ?>customer/kelola-hadiah" class="menu-item">
                    <i class="fas fa-gift"></i> Kelola Hadiah
                </a>
                <a href="<?php echo BASE_URL; ?>customer/kelola-link" class="menu-item">
                    <i class="fas fa-link"></i> Kelola Link
                </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>logout" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-wrapper" style="padding: 30px;">
                <div class="page-header">
                    <h1>Dashboard</h1>
                    <p style="color: var(--text-light);">Selamat datang kembali, <?php echo escapeOutput($userName); ?>!</p>
                </div>

                <?php
                $flash = getFlashMessage();
                if ($flash):
                ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 5px; background: <?php echo $flash['type'] === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $flash['type'] === 'success' ? '#155724' : '#721c24'; ?>;">
                        <?php echo escapeOutput($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$undangan): ?>
                    <div class="alert alert-info" style="padding: 20px; background: #d1ecf1; color: #0c5460; border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-info-circle"></i> Belum ada undangan yang diberikan kepada Anda. Silakan hubungi admin.
                    </div>
                <?php else: ?>
                    <!-- Statistics Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="number"><?php echo number_format($totalViews); ?></div>
                            <div class="label"><i class="fas fa-eye"></i> Total Views</div>
                        </div>
                        <div class="stat-card">
                            <div class="number"><?php echo number_format($totalUcapan); ?></div>
                            <div class="label"><i class="fas fa-comments"></i> Total Ucapan</div>
                        </div>
                        <div class="stat-card">
                            <div class="number" style="color: #28a745;"><?php echo number_format($hadirCount); ?></div>
                            <div class="label"><i class="fas fa-check-circle"></i> Hadir</div>
                        </div>
                        <div class="stat-card">
                            <div class="number" style="color: #dc3545;"><?php echo number_format($tidakHadirCount); ?></div>
                            <div class="label"><i class="fas fa-times-circle"></i> Tidak Hadir</div>
                        </div>
                    </div>

                    <!-- Undangan Info -->
                    <div class="card" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
                        <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-heart"></i> Informasi Undangan
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div>
                                <p style="color: var(--text-light); margin-bottom: 5px;">Nama Mempelai</p>
                                <p style="font-weight: 600;"><?php echo escapeOutput($undangan['nama_wanita'] . ' & ' . $undangan['nama_pria']); ?></p>
                            </div>
                            <div>
                                <p style="color: var(--text-light); margin-bottom: 5px;">Tanggal Acara</p>
                                <p style="font-weight: 600;"><?php echo formatDate($undangan['tanggal_resepsi'], 'd F Y'); ?></p>
                            </div>
                            <div>
                                <p style="color: var(--text-light); margin-bottom: 5px;">Status</p>
                                <p>
                                    <?php if ($undangan['is_published']): ?>
                                        <span style="background: #d4edda; color: #155724; padding: 5px 15px; border-radius: 15px; font-size: 14px;">
                                            <i class="fas fa-check-circle"></i> Published
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #fff3cd; color: #856404; padding: 5px 15px; border-radius: 15px; font-size: 14px;">
                                            <i class="fas fa-clock"></i> Draft
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <p style="color: var(--text-light); margin-bottom: 5px;">Link Undangan</p>
                                <a href="<?php echo BASE_URL . $undangan['slug']; ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;">
                                    <i class="fas fa-external-link-alt"></i> Preview Undangan
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="card" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                        <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <a href="<?php echo BASE_URL; ?>customer/edit-undangan" class="btn" style="background: var(--primary-color); color: white; padding: 15px; text-align: center; border-radius: 5px; text-decoration: none; display: block;">
                                <i class="fas fa-edit"></i> Edit Undangan
                            </a>
                            <a href="<?php echo BASE_URL; ?>customer/kelola-ucapan" class="btn" style="background: #17a2b8; color: white; padding: 15px; text-align: center; border-radius: 5px; text-decoration: none; display: block;">
                                <i class="fas fa-comments"></i> Kelola Ucapan
                            </a>
                            <a href="<?php echo BASE_URL; ?>customer/kelola-hadiah" class="btn" style="background: #ffc107; color: white; padding: 15px; text-align: center; border-radius: 5px; text-decoration: none; display: block;">
                                <i class="fas fa-gift"></i> Kelola Hadiah
                            </a>
                            <a href="<?php echo BASE_URL; ?>customer/kelola-link" class="btn" style="background: #28a745; color: white; padding: 15px; text-align: center; border-radius: 5px; text-decoration: none; display: block;">
                                <i class="fas fa-link"></i> Kelola Link
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
