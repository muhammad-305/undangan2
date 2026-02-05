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
        redirect('pages/customer/kelola-hadiah');
    }
    
    $action = $_POST['action'];
    
    // Add hadiah
    if ($action === 'add_hadiah') {
        $jenis = sanitizeInput($_POST['jenis']);
        $nama_bank = $jenis === 'bank' ? sanitizeInput($_POST['nama_bank']) : null;
        $nama_ewallet = $jenis === 'ewallet' ? sanitizeInput($_POST['nama_ewallet']) : null;
        $nomor_rekening = sanitizeInput($_POST['nomor_rekening']);
        $atas_nama = sanitizeInput($_POST['atas_nama']);
        
        $qr_code = null;
        if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES['qr_code'], UPLOAD_PATH . 'qr');
            if ($uploadResult['success']) {
                $qr_code = $uploadResult['filename'];
            } else {
                setFlashMessage('error', $uploadResult['message']);
                redirect('pages/customer/kelola-hadiah');
            }
        }
        
        // Get max urutan
        $stmt = $conn->prepare("SELECT MAX(urutan) as max_urutan FROM hadiah WHERE undangan_id = ?");
        $stmt->bind_param("i", $undanganId);
        $stmt->execute();
        $urutan = $stmt->get_result()->fetch_assoc()['max_urutan'] + 1;
        $stmt->close();
        
        $stmt = $conn->prepare("INSERT INTO hadiah (undangan_id, jenis, nama_bank, nama_ewallet, nomor_rekening, atas_nama, qr_code, urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssi", $undanganId, $jenis, $nama_bank, $nama_ewallet, $nomor_rekening, $atas_nama, $qr_code, $urutan);
        
        if ($stmt->execute()) {
            logActivity($userId, 'add_hadiah', "Tambah hadiah $jenis");
            setFlashMessage('success', 'Hadiah berhasil ditambahkan');
        } else {
            setFlashMessage('error', 'Gagal menambahkan hadiah');
        }
        $stmt->close();
        redirect('pages/customer/kelola-hadiah');
    }
    
    // Edit hadiah
    if ($action === 'edit_hadiah') {
        $hadiahId = (int)$_POST['hadiah_id'];
        $jenis = sanitizeInput($_POST['jenis']);
        $nama_bank = $jenis === 'bank' ? sanitizeInput($_POST['nama_bank']) : null;
        $nama_ewallet = $jenis === 'ewallet' ? sanitizeInput($_POST['nama_ewallet']) : null;
        $nomor_rekening = sanitizeInput($_POST['nomor_rekening']);
        $atas_nama = sanitizeInput($_POST['atas_nama']);
        
        // Check ownership
        $stmt = $conn->prepare("SELECT qr_code FROM hadiah WHERE id = ? AND undangan_id = ?");
        $stmt->bind_param("ii", $hadiahId, $undanganId);
        $stmt->execute();
        $hadiah = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$hadiah) {
            setFlashMessage('error', 'Hadiah tidak ditemukan');
            redirect('pages/customer/kelola-hadiah');
        }
        
        $qr_code = $hadiah['qr_code'];
        if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES['qr_code'], UPLOAD_PATH . 'qr');
            if ($uploadResult['success']) {
                // Delete old QR
                if ($qr_code && file_exists(UPLOAD_PATH . 'qr/' . $qr_code)) {
                    unlink(UPLOAD_PATH . 'qr/' . $qr_code);
                }
                $qr_code = $uploadResult['filename'];
            }
        }
        
        $stmt = $conn->prepare("UPDATE hadiah SET jenis=?, nama_bank=?, nama_ewallet=?, nomor_rekening=?, atas_nama=?, qr_code=? WHERE id=? AND undangan_id=?");
        $stmt->bind_param("ssssssi i", $jenis, $nama_bank, $nama_ewallet, $nomor_rekening, $atas_nama, $qr_code, $hadiahId, $undanganId);
        
        if ($stmt->execute()) {
            logActivity($userId, 'edit_hadiah', "Edit hadiah ID: $hadiahId");
            setFlashMessage('success', 'Hadiah berhasil diperbarui');
        } else {
            setFlashMessage('error', 'Gagal memperbarui hadiah');
        }
        $stmt->close();
        redirect('pages/customer/kelola-hadiah');
    }
    
    // Delete hadiah
    if ($action === 'delete_hadiah') {
        $hadiahId = (int)$_POST['hadiah_id'];
        
        // Get QR code
        $stmt = $conn->prepare("SELECT qr_code FROM hadiah WHERE id = ? AND undangan_id = ?");
        $stmt->bind_param("ii", $hadiahId, $undanganId);
        $stmt->execute();
        $hadiah = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($hadiah) {
            // Delete QR file
            if ($hadiah['qr_code'] && file_exists(UPLOAD_PATH . 'qr/' . $hadiah['qr_code'])) {
                unlink(UPLOAD_PATH . 'qr/' . $hadiah['qr_code']);
            }
            
            $stmt = $conn->prepare("DELETE FROM hadiah WHERE id = ? AND undangan_id = ?");
            $stmt->bind_param("ii", $hadiahId, $undanganId);
            
            if ($stmt->execute()) {
                logActivity($userId, 'delete_hadiah', "Hapus hadiah ID: $hadiahId");
                setFlashMessage('success', 'Hadiah berhasil dihapus');
            } else {
                setFlashMessage('error', 'Gagal menghapus hadiah');
            }
            $stmt->close();
        }
        redirect('pages/customer/kelola-hadiah');
    }
    
    // Update urutan
    if ($action === 'update_urutan') {
        $urutan = json_decode($_POST['urutan'], true);
        
        foreach ($urutan as $index => $hadiahId) {
            $stmt = $conn->prepare("UPDATE hadiah SET urutan = ? WHERE id = ? AND undangan_id = ?");
            $stmt->bind_param("iii", $index, $hadiahId, $undanganId);
            $stmt->execute();
            $stmt->close();
        }
        
        logActivity($userId, 'reorder_hadiah', 'Ubah urutan hadiah');
        echo json_encode(['success' => true]);
        exit;
    }
}

// Get hadiah list
$stmt = $conn->prepare("SELECT * FROM hadiah WHERE undangan_id = ? ORDER BY urutan ASC");
$stmt->bind_param("i", $undanganId);
$stmt->execute();
$hadiahList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

$pageTitle = 'Kelola Hadiah';
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
        .form-group input, .form-group select {
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
        .hadiah-actions {
            display: flex;
            gap: 10px;
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
                <a href="<?php echo BASE_URL; ?>pages/customer/kelola-hadiah" class="menu-item active">
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
                        <h1><i class="fas fa-gift"></i> Kelola Hadiah</h1>
                        <p style="color: var(--text-light);">Kelola informasi hadiah pernikahan</p>
                    </div>
                    <button onclick="openAddModal()" class="btn btn-success">
                        <i class="fas fa-plus"></i> Tambah Hadiah
                    </button>
                </div>

                <?php
                $flash = getFlashMessage();
                if ($flash):
                ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 5px; background: <?php echo $flash['type'] === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $flash['type'] === 'success' ? '#155724' : '#721c24'; ?>;">
                        <?php echo escapeOutput($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Hadiah List -->
                <div class="hadiah-list">
                    <?php if (empty($hadiahList)): ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; background: white; border-radius: 10px;">
                            <i class="fas fa-gift" style="font-size: 64px; color: var(--text-light); margin-bottom: 20px;"></i>
                            <p style="color: var(--text-light); font-size: 18px;">Belum ada hadiah ditambahkan</p>
                            <button onclick="openAddModal()" class="btn btn-success" style="margin-top: 15px;">
                                <i class="fas fa-plus"></i> Tambah Hadiah Pertama
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($hadiahList as $hadiah): ?>
                            <div class="hadiah-card">
                                <div class="hadiah-header">
                                    <div class="hadiah-type">
                                        <i class="fas fa-<?php echo $hadiah['jenis'] === 'bank' ? 'university' : 'wallet'; ?>"></i>
                                        <?php echo $hadiah['jenis'] === 'bank' ? $hadiah['nama_bank'] : $hadiah['nama_ewallet']; ?>
                                    </div>
                                </div>
                                <div class="hadiah-info">
                                    <p><strong>Nomor:</strong> <?php echo escapeOutput($hadiah['nomor_rekening']); ?></p>
                                    <p><strong>Atas Nama:</strong> <?php echo escapeOutput($hadiah['atas_nama']); ?></p>
                                </div>
                                <?php if ($hadiah['qr_code']): ?>
                                    <div class="hadiah-qr">
                                        <img src="<?php echo BASE_URL . 'uploads/qr/' . escapeOutput($hadiah['qr_code']); ?>" alt="QR Code">
                                    </div>
                                <?php endif; ?>
                                <div class="hadiah-actions">
                                    <button onclick='openEditModal(<?php echo json_encode($hadiah); ?>)' class="btn btn-warning" style="flex: 1;">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="flex: 1; display: inline;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete_hadiah">
                                        <input type="hidden" name="hadiah_id" value="<?php echo $hadiah['id']; ?>">
                                        <button type="submit" onclick="return confirm('Hapus hadiah ini?')" class="btn btn-danger" style="width: 100%;">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Hadiah</h3>
                <button class="modal-close" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add_hadiah">
                
                <div class="form-group">
                    <label>Jenis *</label>
                    <select name="jenis" id="add_jenis" required onchange="toggleJenis('add')">
                        <option value="">Pilih Jenis</option>
                        <option value="bank">Bank</option>
                        <option value="ewallet">E-Wallet</option>
                    </select>
                </div>
                
                <div class="form-group" id="add_bank_group" style="display: none;">
                    <label>Nama Bank *</label>
                    <select name="nama_bank" id="add_nama_bank">
                        <option value="">Pilih Bank</option>
                        <option value="BCA">BCA</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="BRI">BRI</option>
                        <option value="BNI">BNI</option>
                        <option value="BSI">BSI</option>
                        <option value="CIMB Niaga">CIMB Niaga</option>
                        <option value="Danamon">Danamon</option>
                        <option value="Permata">Permata</option>
                    </select>
                </div>
                
                <div class="form-group" id="add_ewallet_group" style="display: none;">
                    <label>Nama E-Wallet *</label>
                    <select name="nama_ewallet" id="add_nama_ewallet">
                        <option value="">Pilih E-Wallet</option>
                        <option value="GoPay">GoPay</option>
                        <option value="OVO">OVO</option>
                        <option value="Dana">Dana</option>
                        <option value="ShopeePay">ShopeePay</option>
                        <option value="LinkAja">LinkAja</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Nomor Rekening / HP *</label>
                    <input type="text" name="nomor_rekening" required>
                </div>
                
                <div class="form-group">
                    <label>Atas Nama *</label>
                    <input type="text" name="atas_nama" required>
                </div>
                
                <div class="form-group">
                    <label>QR Code (Opsional)</label>
                    <input type="file" name="qr_code" accept="image/*">
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
                <h3><i class="fas fa-edit"></i> Edit Hadiah</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="edit_hadiah">
                <input type="hidden" name="hadiah_id" id="edit_hadiah_id">
                
                <div class="form-group">
                    <label>Jenis *</label>
                    <select name="jenis" id="edit_jenis" required onchange="toggleJenis('edit')">
                        <option value="bank">Bank</option>
                        <option value="ewallet">E-Wallet</option>
                    </select>
                </div>
                
                <div class="form-group" id="edit_bank_group">
                    <label>Nama Bank *</label>
                    <select name="nama_bank" id="edit_nama_bank">
                        <option value="BCA">BCA</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="BRI">BRI</option>
                        <option value="BNI">BNI</option>
                        <option value="BSI">BSI</option>
                        <option value="CIMB Niaga">CIMB Niaga</option>
                        <option value="Danamon">Danamon</option>
                        <option value="Permata">Permata</option>
                    </select>
                </div>
                
                <div class="form-group" id="edit_ewallet_group">
                    <label>Nama E-Wallet *</label>
                    <select name="nama_ewallet" id="edit_nama_ewallet">
                        <option value="GoPay">GoPay</option>
                        <option value="OVO">OVO</option>
                        <option value="Dana">Dana</option>
                        <option value="ShopeePay">ShopeePay</option>
                        <option value="LinkAja">LinkAja</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Nomor Rekening / HP *</label>
                    <input type="text" name="nomor_rekening" id="edit_nomor_rekening" required>
                </div>
                
                <div class="form-group">
                    <label>Atas Nama *</label>
                    <input type="text" name="atas_nama" id="edit_atas_nama" required>
                </div>
                
                <div class="form-group">
                    <label>QR Code (Kosongkan jika tidak ingin mengubah)</label>
                    <input type="file" name="qr_code" accept="image/*">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
            </form>
        </div>
    </div>

    <script>
    function openAddModal() {
        document.getElementById('addModal').classList.add('active');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.remove('active');
    }

    function openEditModal(hadiah) {
        document.getElementById('edit_hadiah_id').value = hadiah.id;
        document.getElementById('edit_jenis').value = hadiah.jenis;
        document.getElementById('edit_nomor_rekening').value = hadiah.nomor_rekening;
        document.getElementById('edit_atas_nama').value = hadiah.atas_nama;
        
        if (hadiah.jenis === 'bank') {
            document.getElementById('edit_nama_bank').value = hadiah.nama_bank;
        } else {
            document.getElementById('edit_nama_ewallet').value = hadiah.nama_ewallet;
        }
        
        toggleJenis('edit');
        document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }

    function toggleJenis(prefix) {
        const jenis = document.getElementById(prefix + '_jenis').value;
        const bankGroup = document.getElementById(prefix + '_bank_group');
        const ewalletGroup = document.getElementById(prefix + '_ewallet_group');
        
        if (jenis === 'bank') {
            bankGroup.style.display = 'block';
            ewalletGroup.style.display = 'none';
            document.getElementById(prefix + '_nama_bank').required = true;
            document.getElementById(prefix + '_nama_ewallet').required = false;
        } else if (jenis === 'ewallet') {
            bankGroup.style.display = 'none';
            ewalletGroup.style.display = 'block';
            document.getElementById(prefix + '_nama_bank').required = false;
            document.getElementById(prefix + '_nama_ewallet').required = true;
        } else {
            bankGroup.style.display = 'none';
            ewalletGroup.style.display = 'none';
        }
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
