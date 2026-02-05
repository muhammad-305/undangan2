<?php
require_once 'includes/functions.php';
startSecureSession();
requireAdmin();

$conn = getConnection();

// Get undangan ID from GET parameter
$undanganId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($undanganId === 0) {
    setFlashMessage('error', 'ID undangan tidak valid');
    header('Location: ' . BASE_URL . 'admin/undangan-list');
    exit;
}

// Get undangan details
$query = $conn->prepare("
    SELECT u.*, 
           us.nama_lengkap as customer_name, 
           us.email as customer_email,
           us.telepon as customer_telepon,
           t.nama_template,
           t.primary_color as template_primary_color,
           t.secondary_color as template_secondary_color,
           (SELECT COUNT(*) FROM ucapan WHERE undangan_id = u.id) as total_ucapan,
           (SELECT COUNT(*) FROM link_tamu WHERE undangan_id = u.id) as total_link
    FROM undangan u 
    LEFT JOIN users us ON u.user_id = us.id 
    LEFT JOIN template_undangan t ON u.template_id = t.id 
    WHERE u.id = ?
");
$query->bind_param("i", $undanganId);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Undangan tidak ditemukan');
    header('Location: ' . BASE_URL . 'admin/undangan-list');
    exit;
}

$undangan = $result->fetch_assoc();

// Parse JSON fields
$cerita = !empty($undangan['cerita_json']) ? json_decode($undangan['cerita_json'], true) : [];
$galeri = !empty($undangan['galeri_json']) ? json_decode($undangan['galeri_json'], true) : [];

// Get hadiah
$hadiahQuery = $conn->prepare("SELECT * FROM hadiah WHERE undangan_id = ? ORDER BY urutan ASC");
$hadiahQuery->bind_param("i", $undanganId);
$hadiahQuery->execute();
$hadiahResult = $hadiahQuery->get_result();

$pageTitle = "Preview Undangan";
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
        .preview-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .preview-header {
            background: linear-gradient(135deg, var(--primary-color), #8B7355);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .preview-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .preview-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        .preview-body {
            padding: 30px;
        }
        .section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section-title {
            font-size: 20px;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-item label {
            display: block;
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .info-item .value {
            font-size: 16px;
            color: var(--text-dark);
        }
        .mempelai-card {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        .mempelai-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .mempelai-item img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 5px solid var(--primary-color);
        }
        .mempelai-item h3 {
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        .mempelai-item .parents {
            font-size: 14px;
            color: #6c757d;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--primary-color);
        }
        .timeline-item {
            position: relative;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 3px solid white;
            box-shadow: 0 0 0 3px var(--primary-color);
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
        .gallery-item {
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .hadiah-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .hadiah-card {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .hadiah-card .icon {
            font-size: 32px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .hadiah-card h4 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .hadiah-card .rekening {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 10px 0;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
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
                <a href="<?php echo BASE_URL; ?>admin/dashboard" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>admin/undangan-list" class="menu-item active">
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
                    <a href="<?php echo BASE_URL; ?>admin/undangan-list" class="btn" style="padding: 8px 16px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-right: 15px;">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <span><i class="fas fa-user-circle"></i> <?php echo escapeOutput($_SESSION['nama_lengkap'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <!-- Content -->
            <div class="content-wrapper">
                <div class="preview-container">
                    <!-- Header -->
                    <div class="preview-header">
                        <h1><?php echo escapeOutput($undangan['nama_wanita'] . ' & ' . $undangan['nama_pria']); ?></h1>
                        <p>
                            <?php if ($undangan['is_published']): ?>
                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Published</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Draft</span>
                            <?php endif; ?>
                            | Views: <?php echo number_format($undangan['views']); ?>
                            | Ucapan: <?php echo number_format($undangan['total_ucapan']); ?>
                            | Link Tamu: <?php echo number_format($undangan['total_link']); ?>
                        </p>
                        <p style="margin-top: 10px;">
                            <a href="<?php echo BASE_URL . escapeOutput($undangan['slug']); ?>" target="_blank" style="color: white; text-decoration: underline;">
                                <i class="fas fa-external-link-alt"></i> Lihat Halaman Publik
                            </a>
                        </p>
                    </div>

                    <!-- Body -->
                    <div class="preview-body">
                        <!-- Basic Info -->
                        <div class="section">
                            <h2 class="section-title"><i class="fas fa-info-circle"></i> Informasi Dasar</h2>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Slug URL</label>
                                    <div class="value"><?php echo escapeOutput($undangan['slug']); ?></div>
                                </div>
                                <div class="info-item">
                                    <label>Customer</label>
                                    <div class="value"><?php echo escapeOutput($undangan['customer_name'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="info-item">
                                    <label>Template</label>
                                    <div class="value"><?php echo escapeOutput($undangan['nama_template'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="info-item">
                                    <label>Tanggal Dibuat</label>
                                    <div class="value"><?php echo formatDate($undangan['created_at'], 'd F Y H:i'); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Mempelai -->
                        <div class="section">
                            <h2 class="section-title"><i class="fas fa-heart"></i> Data Mempelai</h2>
                            <div class="mempelai-card">
                                <!-- Wanita -->
                                <div class="mempelai-item">
                                    <?php if (!empty($undangan['foto_wanita'])): ?>
                                        <img src="<?php echo BASE_URL . 'uploads/undangan/' . escapeOutput($undangan['foto_wanita']); ?>" alt="Mempelai Wanita">
                                    <?php else: ?>
                                        <div style="width: 150px; height: 150px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                                            <i class="fas fa-user" style="font-size: 60px; color: #999;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h3><?php echo escapeOutput($undangan['nama_wanita']); ?></h3>
                                    <div style="font-size: 14px; color: #6c757d; margin-bottom: 10px;">
                                        <?php echo escapeOutput($undangan['nama_lengkap_wanita'] ?? ''); ?>
                                    </div>
                                    <div class="parents">
                                        Putri dari:<br>
                                        <?php echo escapeOutput($undangan['nama_ayah_wanita'] ?? '-'); ?> & 
                                        <?php echo escapeOutput($undangan['nama_ibu_wanita'] ?? '-'); ?>
                                    </div>
                                    <?php if (!empty($undangan['instagram_wanita'])): ?>
                                        <div style="margin-top: 10px;">
                                            <a href="https://instagram.com/<?php echo escapeOutput($undangan['instagram_wanita']); ?>" target="_blank" style="color: var(--primary-color);">
                                                <i class="fab fa-instagram"></i> @<?php echo escapeOutput($undangan['instagram_wanita']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Pria -->
                                <div class="mempelai-item">
                                    <?php if (!empty($undangan['foto_pria'])): ?>
                                        <img src="<?php echo BASE_URL . 'uploads/undangan/' . escapeOutput($undangan['foto_pria']); ?>" alt="Mempelai Pria">
                                    <?php else: ?>
                                        <div style="width: 150px; height: 150px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                                            <i class="fas fa-user" style="font-size: 60px; color: #999;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h3><?php echo escapeOutput($undangan['nama_pria']); ?></h3>
                                    <div style="font-size: 14px; color: #6c757d; margin-bottom: 10px;">
                                        <?php echo escapeOutput($undangan['nama_lengkap_pria'] ?? ''); ?>
                                    </div>
                                    <div class="parents">
                                        Putra dari:<br>
                                        <?php echo escapeOutput($undangan['nama_ayah_pria'] ?? '-'); ?> & 
                                        <?php echo escapeOutput($undangan['nama_ibu_pria'] ?? '-'); ?>
                                    </div>
                                    <?php if (!empty($undangan['instagram_pria'])): ?>
                                        <div style="margin-top: 10px;">
                                            <a href="https://instagram.com/<?php echo escapeOutput($undangan['instagram_pria']); ?>" target="_blank" style="color: var(--primary-color);">
                                                <i class="fab fa-instagram"></i> @<?php echo escapeOutput($undangan['instagram_pria']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Acara -->
                        <div class="section">
                            <h2 class="section-title"><i class="fas fa-calendar"></i> Acara Pernikahan</h2>
                            <div class="info-grid">
                                <!-- Akad -->
                                <div class="info-item">
                                    <label><i class="fas fa-ring"></i> Akad Nikah</label>
                                    <div class="value">
                                        <div style="margin-bottom: 10px;">
                                            <strong><?php echo formatDate($undangan['tanggal_akad'], 'l, d F Y'); ?></strong>
                                        </div>
                                        <div>
                                            <i class="fas fa-clock"></i> 
                                            <?php echo date('H:i', strtotime($undangan['waktu_akad_mulai'])); ?> - 
                                            <?php echo date('H:i', strtotime($undangan['waktu_akad_selesai'])); ?> WIB
                                        </div>
                                        <div style="margin-top: 10px;">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo escapeOutput($undangan['tempat_akad']); ?>
                                        </div>
                                        <div style="font-size: 14px; color: #6c757d; margin-top: 5px;">
                                            <?php echo escapeOutput($undangan['alamat_akad']); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Resepsi -->
                                <div class="info-item">
                                    <label><i class="fas fa-glass-cheers"></i> Resepsi</label>
                                    <div class="value">
                                        <div style="margin-bottom: 10px;">
                                            <strong><?php echo formatDate($undangan['tanggal_resepsi'], 'l, d F Y'); ?></strong>
                                        </div>
                                        <div>
                                            <i class="fas fa-clock"></i> 
                                            <?php echo date('H:i', strtotime($undangan['waktu_resepsi_mulai'])); ?> - 
                                            <?php echo date('H:i', strtotime($undangan['waktu_resepsi_selesai'])); ?> WIB
                                        </div>
                                        <div style="margin-top: 10px;">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo escapeOutput($undangan['tempat_resepsi']); ?>
                                        </div>
                                        <div style="font-size: 14px; color: #6c757d; margin-top: 5px;">
                                            <?php echo escapeOutput($undangan['alamat_resepsi']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cerita -->
                        <?php if (!empty($cerita)): ?>
                        <div class="section">
                            <h2 class="section-title"><i class="fas fa-book-open"></i> Cerita Kami (<?php echo count($cerita); ?>)</h2>
                            <div class="timeline">
                                <?php foreach ($cerita as $item): ?>
                                <div class="timeline-item">
                                    <div style="font-weight: 700; color: var(--primary-color); margin-bottom: 5px;">
                                        <?php echo escapeOutput($item['tahun'] ?? ''); ?> - <?php echo escapeOutput($item['judul'] ?? ''); ?>
                                    </div>
                                    <div style="color: #6c757d;">
                                        <?php echo escapeOutput($item['deskripsi'] ?? ''); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Galeri -->
                        <?php if (!empty($galeri)): ?>
                        <div class="section">
                            <h2 class="section-title"><i class="fas fa-images"></i> Galeri Foto (<?php echo count($galeri); ?>)</h2>
                            <div class="gallery-grid">
                                <?php foreach ($galeri as $foto): ?>
                                <div class="gallery-item">
                                    <img src="<?php echo BASE_URL . 'uploads/gallery/' . escapeOutput($foto); ?>" alt="Gallery">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Hadiah -->
                        <?php if ($hadiahResult->num_rows > 0): ?>
                        <div class="section">
                            <h2 class="section-title"><i class="fas fa-gift"></i> Hadiah Pernikahan (<?php echo $hadiahResult->num_rows; ?>)</h2>
                            <div class="hadiah-list">
                                <?php while ($hadiah = $hadiahResult->fetch_assoc()): ?>
                                <div class="hadiah-card">
                                    <div class="icon">
                                        <?php if ($hadiah['jenis'] === 'bank'): ?>
                                            <i class="fas fa-university"></i>
                                        <?php else: ?>
                                            <i class="fas fa-mobile-alt"></i>
                                        <?php endif; ?>
                                    </div>
                                    <h4><?php echo escapeOutput($hadiah['jenis'] === 'bank' ? $hadiah['nama_bank'] : $hadiah['nama_ewallet']); ?></h4>
                                    <div class="rekening"><?php echo escapeOutput($hadiah['nomor_rekening']); ?></div>
                                    <div>a/n <strong><?php echo escapeOutput($hadiah['atas_nama']); ?></strong></div>
                                    <?php if (!empty($hadiah['qr_code'])): ?>
                                        <div style="margin-top: 15px;">
                                            <img src="<?php echo BASE_URL . 'uploads/qr/' . escapeOutput($hadiah['qr_code']); ?>" alt="QR Code" style="max-width: 150px;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<?php
$query->close();
$hadiahQuery->close();
$conn->close();
?>
