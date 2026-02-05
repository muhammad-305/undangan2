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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Invalid CSRF token');
        redirect('pages/customer/edit-undangan');
    }
    
    $action = $_POST['action'];
    
    // Update mempelai data
    if ($action === 'update_mempelai') {
        $nama_pria = sanitizeInput($_POST['nama_pria']);
        $nama_lengkap_pria = sanitizeInput($_POST['nama_lengkap_pria']);
        $nama_ayah_pria = sanitizeInput($_POST['nama_ayah_pria']);
        $nama_ibu_pria = sanitizeInput($_POST['nama_ibu_pria']);
        $instagram_pria = sanitizeInput($_POST['instagram_pria']);
        
        $nama_wanita = sanitizeInput($_POST['nama_wanita']);
        $nama_lengkap_wanita = sanitizeInput($_POST['nama_lengkap_wanita']);
        $nama_ayah_wanita = sanitizeInput($_POST['nama_ayah_wanita']);
        $nama_ibu_wanita = sanitizeInput($_POST['nama_ibu_wanita']);
        $instagram_wanita = sanitizeInput($_POST['instagram_wanita']);
        
        $stmt = $conn->prepare("UPDATE undangan SET 
            nama_pria=?, nama_lengkap_pria=?, nama_ayah_pria=?, nama_ibu_pria=?, instagram_pria=?,
            nama_wanita=?, nama_lengkap_wanita=?, nama_ayah_wanita=?, nama_ibu_wanita=?, instagram_wanita=?
            WHERE id=? AND user_id=?");
        $stmt->bind_param("ssssssssssii", 
            $nama_pria, $nama_lengkap_pria, $nama_ayah_pria, $nama_ibu_pria, $instagram_pria,
            $nama_wanita, $nama_lengkap_wanita, $nama_ayah_wanita, $nama_ibu_wanita, $instagram_wanita,
            $undanganId, $userId);
        
        if ($stmt->execute()) {
            logActivity($userId, 'update_undangan', 'Update data mempelai');
            setFlashMessage('success', 'Data mempelai berhasil diperbarui');
        } else {
            setFlashMessage('error', 'Gagal memperbarui data mempelai');
        }
        $stmt->close();
        redirect('pages/customer/edit-undangan');
    }
    
    // Upload foto mempelai
    if ($action === 'upload_foto') {
        $jenis = $_POST['jenis_foto']; // 'pria' atau 'wanita'
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES['foto'], UPLOAD_PATH . 'undangan');
            
            if ($uploadResult['success']) {
                $field = ($jenis === 'pria') ? 'foto_pria' : 'foto_wanita';
                
                // Delete old photo
                $oldPhoto = $undangan[$field];
                if ($oldPhoto && file_exists(UPLOAD_PATH . 'undangan/' . $oldPhoto)) {
                    unlink(UPLOAD_PATH . 'undangan/' . $oldPhoto);
                }
                
                $stmt = $conn->prepare("UPDATE undangan SET $field=? WHERE id=? AND user_id=?");
                $stmt->bind_param("sii", $uploadResult['filename'], $undanganId, $userId);
                $stmt->execute();
                $stmt->close();
                
                logActivity($userId, 'upload_foto', "Upload foto $jenis");
                setFlashMessage('success', 'Foto berhasil diupload');
            } else {
                setFlashMessage('error', $uploadResult['message']);
            }
        }
        redirect('pages/customer/edit-undangan');
    }
    
    // Update pembuka text
    if ($action === 'update_pembuka') {
        $pembuka_text = sanitizeInput($_POST['pembuka_text']);
        
        $stmt = $conn->prepare("UPDATE undangan SET pembuka_text=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sii", $pembuka_text, $undanganId, $userId);
        
        if ($stmt->execute()) {
            logActivity($userId, 'update_undangan', 'Update text pembuka');
            setFlashMessage('success', 'Text pembuka berhasil diperbarui');
        } else {
            setFlashMessage('error', 'Gagal memperbarui text pembuka');
        }
        $stmt->close();
        redirect('pages/customer/edit-undangan');
    }
    
    // Update acara
    if ($action === 'update_acara') {
        $tanggal_akad = $_POST['tanggal_akad'];
        $waktu_akad_mulai = $_POST['waktu_akad_mulai'];
        $waktu_akad_selesai = $_POST['waktu_akad_selesai'];
        $tempat_akad = sanitizeInput($_POST['tempat_akad']);
        $alamat_akad = sanitizeInput($_POST['alamat_akad']);
        $maps_akad = $_POST['maps_akad'];
        
        $tanggal_resepsi = $_POST['tanggal_resepsi'];
        $waktu_resepsi_mulai = $_POST['waktu_resepsi_mulai'];
        $waktu_resepsi_selesai = $_POST['waktu_resepsi_selesai'];
        $tempat_resepsi = sanitizeInput($_POST['tempat_resepsi']);
        $alamat_resepsi = sanitizeInput($_POST['alamat_resepsi']);
        $maps_resepsi = $_POST['maps_resepsi'];
        
        $stmt = $conn->prepare("UPDATE undangan SET 
            tanggal_akad=?, waktu_akad_mulai=?, waktu_akad_selesai=?, tempat_akad=?, alamat_akad=?, maps_akad=?,
            tanggal_resepsi=?, waktu_resepsi_mulai=?, waktu_resepsi_selesai=?, tempat_resepsi=?, alamat_resepsi=?, maps_resepsi=?
            WHERE id=? AND user_id=?");
        $stmt->bind_param("ssssssssssssii", 
            $tanggal_akad, $waktu_akad_mulai, $waktu_akad_selesai, $tempat_akad, $alamat_akad, $maps_akad,
            $tanggal_resepsi, $waktu_resepsi_mulai, $waktu_resepsi_selesai, $tempat_resepsi, $alamat_resepsi, $maps_resepsi,
            $undanganId, $userId);
        
        if ($stmt->execute()) {
            logActivity($userId, 'update_undangan', 'Update data acara');
            setFlashMessage('success', 'Data acara berhasil diperbarui');
        } else {
            setFlashMessage('error', 'Gagal memperbarui data acara');
        }
        $stmt->close();
        redirect('pages/customer/edit-undangan');
    }
    
    // Update cerita
    if ($action === 'update_cerita') {
        $cerita_json = $_POST['cerita_json'];
        
        $stmt = $conn->prepare("UPDATE undangan SET cerita_json=? WHERE id=? AND user_id=?");
        $stmt->bind_param("sii", $cerita_json, $undanganId, $userId);
        
        if ($stmt->execute()) {
            logActivity($userId, 'update_undangan', 'Update cerita timeline');
            setFlashMessage('success', 'Cerita berhasil diperbarui');
        } else {
            setFlashMessage('error', 'Gagal memperbarui cerita');
        }
        $stmt->close();
        redirect('pages/customer/edit-undangan');
    }
    
    // Upload galeri foto
    if ($action === 'upload_galeri') {
        if (isset($_FILES['foto_galeri']) && $_FILES['foto_galeri']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES['foto_galeri'], UPLOAD_PATH . 'gallery');
            
            if ($uploadResult['success']) {
                $galeri = $undangan['galeri_json'] ? json_decode($undangan['galeri_json'], true) : [];
                $galeri[] = $uploadResult['filename'];
                $galeri_json = json_encode($galeri);
                
                $stmt = $conn->prepare("UPDATE undangan SET galeri_json=? WHERE id=? AND user_id=?");
                $stmt->bind_param("sii", $galeri_json, $undanganId, $userId);
                $stmt->execute();
                $stmt->close();
                
                logActivity($userId, 'upload_galeri', 'Upload foto galeri');
                setFlashMessage('success', 'Foto galeri berhasil ditambahkan');
            } else {
                setFlashMessage('error', $uploadResult['message']);
            }
        }
        redirect('pages/customer/edit-undangan');
    }
    
    // Delete galeri foto
    if ($action === 'delete_galeri') {
        $filename = $_POST['filename'];
        $galeri = $undangan['galeri_json'] ? json_decode($undangan['galeri_json'], true) : [];
        
        if (($key = array_search($filename, $galeri)) !== false) {
            unset($galeri[$key]);
            $galeri = array_values($galeri);
            $galeri_json = json_encode($galeri);
            
            // Delete file
            if (file_exists(UPLOAD_PATH . 'gallery/' . $filename)) {
                unlink(UPLOAD_PATH . 'gallery/' . $filename);
            }
            
            $stmt = $conn->prepare("UPDATE undangan SET galeri_json=? WHERE id=? AND user_id=?");
            $stmt->bind_param("sii", $galeri_json, $undanganId, $userId);
            $stmt->execute();
            $stmt->close();
            
            logActivity($userId, 'delete_galeri', 'Hapus foto galeri');
            setFlashMessage('success', 'Foto galeri berhasil dihapus');
        }
        redirect('pages/customer/edit-undangan');
    }
}

// Refresh undangan data
$stmt = $conn->prepare("SELECT * FROM undangan WHERE id = ?");
$stmt->bind_param("i", $undanganId);
$stmt->execute();
$undangan = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->close();

$pageTitle = 'Edit Undangan';
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
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        .form-section h3 {
            margin-bottom: 20px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
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
        .foto-preview {
            max-width: 200px;
            margin: 10px 0;
            border-radius: 8px;
        }
        .galeri-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .galeri-item {
            position: relative;
        }
        .galeri-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
        .galeri-item .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .readonly-info {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .cerita-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
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
                <a href="<?php echo BASE_URL; ?>pages/customer/edit-undangan" class="menu-item active">
                    <i class="fas fa-edit"></i> Edit Undangan
                </a>
                <a href="<?php echo BASE_URL; ?>pages/customer/kelola-ucapan" class="menu-item">
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
                <div class="page-header" style="margin-bottom: 30px;">
                    <h1><i class="fas fa-edit"></i> Edit Undangan</h1>
                    <p style="color: var(--text-light);">Kelola dan perbarui detail undangan Anda</p>
                </div>

                <?php
                $flash = getFlashMessage();
                if ($flash):
                ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 5px; background: <?php echo $flash['type'] === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $flash['type'] === 'success' ? '#155724' : '#721c24'; ?>;">
                        <?php echo escapeOutput($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <div class="readonly-info">
                    <i class="fas fa-info-circle"></i> <strong>Catatan:</strong> Template dan background undangan hanya bisa diubah oleh admin.
                </div>

                <!-- Data Mempelai -->
                <div class="form-section">
                    <h3><i class="fas fa-user-friends"></i> Data Mempelai</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="update_mempelai">
                        
                        <h4 style="color: var(--primary-color); margin-bottom: 15px;">Mempelai Pria</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Panggilan *</label>
                                <input type="text" name="nama_pria" value="<?php echo escapeOutput($undangan['nama_pria']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_lengkap_pria" value="<?php echo escapeOutput($undangan['nama_lengkap_pria']); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Ayah</label>
                                <input type="text" name="nama_ayah_pria" value="<?php echo escapeOutput($undangan['nama_ayah_pria']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Nama Ibu</label>
                                <input type="text" name="nama_ibu_pria" value="<?php echo escapeOutput($undangan['nama_ibu_pria']); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Instagram (tanpa @)</label>
                            <input type="text" name="instagram_pria" value="<?php echo escapeOutput($undangan['instagram_pria']); ?>">
                        </div>
                        
                        <h4 style="color: var(--primary-color); margin: 30px 0 15px;">Mempelai Wanita</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Panggilan *</label>
                                <input type="text" name="nama_wanita" value="<?php echo escapeOutput($undangan['nama_wanita']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_lengkap_wanita" value="<?php echo escapeOutput($undangan['nama_lengkap_wanita']); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Ayah</label>
                                <input type="text" name="nama_ayah_wanita" value="<?php echo escapeOutput($undangan['nama_ayah_wanita']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Nama Ibu</label>
                                <input type="text" name="nama_ibu_wanita" value="<?php echo escapeOutput($undangan['nama_ibu_wanita']); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Instagram (tanpa @)</label>
                            <input type="text" name="instagram_wanita" value="<?php echo escapeOutput($undangan['instagram_wanita']); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Data Mempelai
                        </button>
                    </form>
                </div>

                <!-- Upload Foto Mempelai -->
                <div class="form-section">
                    <h3><i class="fas fa-camera"></i> Foto Mempelai</h3>
                    <div class="form-row">
                        <div>
                            <h4>Foto Pria</h4>
                            <?php if ($undangan['foto_pria']): ?>
                                <img src="<?php echo BASE_URL . 'uploads/undangan/' . escapeOutput($undangan['foto_pria']); ?>" class="foto-preview" alt="Foto Pria">
                            <?php endif; ?>
                            <form method="POST" enctype="multipart/form-data">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="upload_foto">
                                <input type="hidden" name="jenis_foto" value="pria">
                                <div class="form-group">
                                    <input type="file" name="foto" accept="image/*" required>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-upload"></i> Upload Foto Pria
                                </button>
                            </form>
                        </div>
                        <div>
                            <h4>Foto Wanita</h4>
                            <?php if ($undangan['foto_wanita']): ?>
                                <img src="<?php echo BASE_URL . 'uploads/undangan/' . escapeOutput($undangan['foto_wanita']); ?>" class="foto-preview" alt="Foto Wanita">
                            <?php endif; ?>
                            <form method="POST" enctype="multipart/form-data">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="upload_foto">
                                <input type="hidden" name="jenis_foto" value="wanita">
                                <div class="form-group">
                                    <input type="file" name="foto" accept="image/*" required>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-upload"></i> Upload Foto Wanita
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Text Pembuka -->
                <div class="form-section">
                    <h3><i class="fas fa-align-left"></i> Text Pembuka</h3>
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="update_pembuka">
                        <div class="form-group">
                            <label>Text Pembuka</label>
                            <textarea name="pembuka_text"><?php echo escapeOutput($undangan['pembuka_text']); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Text Pembuka
                        </button>
                    </form>
                </div>

                <!-- Data Acara -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar-alt"></i> Data Acara</h3>
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="update_acara">
                        
                        <h4 style="color: var(--primary-color); margin-bottom: 15px;">Akad Nikah</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal_akad" value="<?php echo escapeOutput($undangan['tanggal_akad']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Waktu Mulai</label>
                                <input type="time" name="waktu_akad_mulai" value="<?php echo escapeOutput($undangan['waktu_akad_mulai']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Waktu Selesai</label>
                                <input type="time" name="waktu_akad_selesai" value="<?php echo escapeOutput($undangan['waktu_akad_selesai']); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tempat</label>
                                <input type="text" name="tempat_akad" value="<?php echo escapeOutput($undangan['tempat_akad']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Alamat</label>
                                <input type="text" name="alamat_akad" value="<?php echo escapeOutput($undangan['alamat_akad']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Link Google Maps</label>
                            <input type="url" name="maps_akad" value="<?php echo escapeOutput($undangan['maps_akad']); ?>">
                        </div>
                        
                        <h4 style="color: var(--primary-color); margin: 30px 0 15px;">Resepsi</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal_resepsi" value="<?php echo escapeOutput($undangan['tanggal_resepsi']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Waktu Mulai</label>
                                <input type="time" name="waktu_resepsi_mulai" value="<?php echo escapeOutput($undangan['waktu_resepsi_mulai']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Waktu Selesai</label>
                                <input type="time" name="waktu_resepsi_selesai" value="<?php echo escapeOutput($undangan['waktu_resepsi_selesai']); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tempat</label>
                                <input type="text" name="tempat_resepsi" value="<?php echo escapeOutput($undangan['tempat_resepsi']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Alamat</label>
                                <input type="text" name="alamat_resepsi" value="<?php echo escapeOutput($undangan['alamat_resepsi']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Link Google Maps</label>
                            <input type="url" name="maps_resepsi" value="<?php echo escapeOutput($undangan['maps_resepsi']); ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Data Acara
                        </button>
                    </form>
                </div>

                <!-- Timeline Cerita -->
                <div class="form-section">
                    <h3><i class="fas fa-heart"></i> Timeline Cerita</h3>
                    <form method="POST" id="ceritaForm">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="update_cerita">
                        <input type="hidden" name="cerita_json" id="cerita_json">
                        
                        <div id="cerita-container">
                            <?php
                            $cerita = $undangan['cerita_json'] ? json_decode($undangan['cerita_json'], true) : [];
                            if (empty($cerita)) {
                                $cerita = [['tahun' => '', 'judul' => '', 'deskripsi' => '']];
                            }
                            foreach ($cerita as $i => $item):
                            ?>
                            <div class="cerita-item">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Tahun</label>
                                        <input type="text" class="cerita-tahun" value="<?php echo escapeOutput($item['tahun']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Judul</label>
                                        <input type="text" class="cerita-judul" value="<?php echo escapeOutput($item['judul']); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Deskripsi</label>
                                    <textarea class="cerita-deskripsi"><?php echo escapeOutput($item['deskripsi']); ?></textarea>
                                </div>
                                <button type="button" class="btn btn-danger" onclick="removeCerita(this)">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" class="btn btn-success" onclick="addCerita()">
                            <i class="fas fa-plus"></i> Tambah Cerita
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Timeline
                        </button>
                    </form>
                </div>

                <!-- Galeri Foto -->
                <div class="form-section">
                    <h3><i class="fas fa-images"></i> Galeri Foto</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="upload_galeri">
                        <div class="form-group">
                            <label>Upload Foto Galeri</label>
                            <input type="file" name="foto_galeri" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload"></i> Upload Foto
                        </button>
                    </form>
                    
                    <div class="galeri-grid">
                        <?php
                        $galeri = $undangan['galeri_json'] ? json_decode($undangan['galeri_json'], true) : [];
                        foreach ($galeri as $foto):
                        ?>
                        <div class="galeri-item">
                            <img src="<?php echo BASE_URL . 'uploads/gallery/' . escapeOutput($foto); ?>" alt="Galeri">
                            <form method="POST" style="display: inline;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete_galeri">
                                <input type="hidden" name="filename" value="<?php echo escapeOutput($foto); ?>">
                                <button type="submit" class="delete-btn" onclick="return confirm('Hapus foto ini?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    function addCerita() {
        const container = document.getElementById('cerita-container');
        const div = document.createElement('div');
        div.className = 'cerita-item';
        div.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <label>Tahun</label>
                    <input type="text" class="cerita-tahun">
                </div>
                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" class="cerita-judul">
                </div>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea class="cerita-deskripsi"></textarea>
            </div>
            <button type="button" class="btn btn-danger" onclick="removeCerita(this)">
                <i class="fas fa-trash"></i> Hapus
            </button>
        `;
        container.appendChild(div);
    }

    function removeCerita(btn) {
        btn.parentElement.remove();
    }

    document.getElementById('ceritaForm').addEventListener('submit', function(e) {
        const items = document.querySelectorAll('.cerita-item');
        const cerita = [];
        
        items.forEach(item => {
            cerita.push({
                tahun: item.querySelector('.cerita-tahun').value,
                judul: item.querySelector('.cerita-judul').value,
                deskripsi: item.querySelector('.cerita-deskripsi').value
            });
        });
        
        document.getElementById('cerita_json').value = JSON.stringify(cerita);
    });
    </script>
</body>
</html>
