<?php
require_once '../../includes/functions.php';
startSecureSession();
requireAdmin();

$conn = getConnection();

// Handle add/edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'];
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $namaLengkap = sanitizeInput($_POST['nama_lengkap']);
    $telepon = sanitizeInput($_POST['telepon']);
    $status = sanitizeInput($_POST['status']);
    
    if ($action === 'add') {
        $password = sanitizeInput($_POST['password']);
        
        if (empty($username) || empty($email) || empty($password)) {
            setFlashMessage('error', 'Username, email, dan password wajib diisi');
        } elseif (!validateEmail($email)) {
            setFlashMessage('error', 'Format email tidak valid');
        } else {
            // Check duplicate
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $checkStmt->bind_param("ss", $username, $email);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                setFlashMessage('error', 'Username atau email sudah digunakan');
            } else {
                $hashedPassword = hashPassword($password);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, nama_lengkap, telepon, role, status) VALUES (?, ?, ?, ?, ?, 'customer', ?)");
                $stmt->bind_param("ssssss", $username, $email, $hashedPassword, $namaLengkap, $telepon, $status);
                if ($stmt->execute()) {
                    setFlashMessage('success', 'Customer berhasil ditambahkan');
                    logActivity($_SESSION['user_id'], 'add_user', "Added customer: $username");
                } else {
                    setFlashMessage('error', 'Gagal menambahkan customer');
                }
                $stmt->close();
            }
            $checkStmt->close();
        }
    } elseif ($action === 'edit' && $userId > 0) {
        if (empty($username) || empty($email)) {
            setFlashMessage('error', 'Username dan email wajib diisi');
        } elseif (!validateEmail($email)) {
            setFlashMessage('error', 'Format email tidak valid');
        } else {
            // Check duplicate (excluding current user)
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $checkStmt->bind_param("ssi", $username, $email, $userId);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                setFlashMessage('error', 'Username atau email sudah digunakan');
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, nama_lengkap = ?, telepon = ?, status = ? WHERE id = ? AND role = 'customer'");
                $stmt->bind_param("sssssi", $username, $email, $namaLengkap, $telepon, $status, $userId);
                if ($stmt->execute()) {
                    setFlashMessage('success', 'Customer berhasil diupdate');
                    logActivity($_SESSION['user_id'], 'edit_user', "Updated customer ID: $userId");
                } else {
                    setFlashMessage('error', 'Gagal mengupdate customer');
                }
                $stmt->close();
            }
            $checkStmt->close();
        }
    } elseif ($action === 'delete' && $userId > 0) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            setFlashMessage('success', 'Customer berhasil dihapus');
            logActivity($_SESSION['user_id'], 'delete_user', "Deleted customer ID: $userId");
        } else {
            setFlashMessage('error', 'Gagal menghapus customer');
        }
        $stmt->close();
    } elseif ($action === 'reset_password' && $userId > 0) {
        $newPassword = sanitizeInput($_POST['new_password']);
        if (empty($newPassword)) {
            setFlashMessage('error', 'Password baru wajib diisi');
        } else {
            $hashedPassword = hashPassword($newPassword);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'customer'");
            $stmt->bind_param("si", $hashedPassword, $userId);
            if ($stmt->execute()) {
                setFlashMessage('success', 'Password berhasil direset');
                logActivity($_SESSION['user_id'], 'reset_password', "Reset password for customer ID: $userId");
            } else {
                setFlashMessage('error', 'Gagal mereset password');
            }
            $stmt->close();
        }
    }
    
    header('Location: ' . BASE_URL . 'pages/admin/kelola-user');
    exit;
}

// Get customers list
$customersQuery = $conn->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM undangan WHERE user_id = u.id) as total_undangan 
    FROM users u 
    WHERE role = 'customer' 
    ORDER BY u.created_at DESC
");

$pageTitle = "Kelola User";
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
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body {
            padding: 30px;
        }
        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
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
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-size: 12px;
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
                <a href="<?php echo BASE_URL; ?>pages/admin/undangan-list" class="menu-item">
                    <i class="fas fa-envelope"></i> Undangan List
                </a>
                <a href="<?php echo BASE_URL; ?>pages/admin/kelola-user" class="menu-item active">
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
                        <h2 style="font-size: 20px; margin: 0;">Daftar Customer (<?php echo $customersQuery->num_rows; ?>)</h2>
                        <button onclick="openAddModal()" class="btn btn-primary" style="padding: 10px 20px; background: var(--primary-color); color: white; border: none; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-plus"></i> Tambah Customer
                        </button>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 12px; text-align: left;">ID</th>
                                    <th style="padding: 12px; text-align: left;">Username</th>
                                    <th style="padding: 12px; text-align: left;">Email</th>
                                    <th style="padding: 12px; text-align: left;">Nama</th>
                                    <th style="padding: 12px; text-align: left;">Telepon</th>
                                    <th style="padding: 12px; text-align: center;">Undangan</th>
                                    <th style="padding: 12px; text-align: center;">Status</th>
                                    <th style="padding: 12px; text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($customersQuery->num_rows > 0): ?>
                                    <?php while ($row = $customersQuery->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 12px;">#<?php echo $row['id']; ?></td>
                                        <td style="padding: 12px;"><strong><?php echo escapeOutput($row['username']); ?></strong></td>
                                        <td style="padding: 12px;"><?php echo escapeOutput($row['email']); ?></td>
                                        <td style="padding: 12px;"><?php echo escapeOutput($row['nama_lengkap'] ?? '-'); ?></td>
                                        <td style="padding: 12px;"><?php echo escapeOutput($row['telepon'] ?? '-'); ?></td>
                                        <td style="padding: 12px; text-align: center;">
                                            <span style="display: inline-block; padding: 4px 12px; background: #e3f2fd; color: #1976d2; border-radius: 12px; font-size: 12px;">
                                                <?php echo $row['total_undangan']; ?> undangan
                                            </span>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <?php if ($row['status'] === 'active'): ?>
                                                <span style="display: inline-block; padding: 4px 12px; background: #d4edda; color: #155724; border-radius: 12px; font-size: 12px;">
                                                    <i class="fas fa-check-circle"></i> Active
                                                </span>
                                            <?php else: ?>
                                                <span style="display: inline-block; padding: 4px 12px; background: #f8d7da; color: #721c24; border-radius: 12px; font-size: 12px;">
                                                    <i class="fas fa-times-circle"></i> Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <div class="action-buttons" style="display: flex; gap: 5px; justify-content: center;">
                                                <button onclick='openEditModal(<?php echo json_encode($row); ?>)' 
                                                        class="btn btn-sm" 
                                                        style="padding: 6px 12px; background: #ffc107; color: white; border: none; border-radius: 4px; font-size: 14px; cursor: pointer;"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="openResetPasswordModal(<?php echo $row['id']; ?>, '<?php echo escapeOutput($row['username']); ?>')" 
                                                        class="btn btn-sm" 
                                                        style="padding: 6px 12px; background: #17a2b8; color: white; border: none; border-radius: 4px; font-size: 14px; cursor: pointer;"
                                                        title="Reset Password">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <button onclick="deleteCustomer(<?php echo $row['id']; ?>, '<?php echo escapeOutput($row['username']); ?>')" 
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
                                        <td colspan="8" style="padding: 20px; text-align: center; color: #6c757d;">
                                            <i class="fas fa-users-slash"></i> Belum ada customer
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

    <!-- Add/Edit Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Customer</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form id="userForm" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="user_id" id="userId" value="">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username <span style="color: red;">*</span></label>
                        <input type="text" name="username" id="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email <span style="color: red;">*</span></label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    
                    <div class="form-group" id="passwordGroup">
                        <label>Password <span style="color: red;">*</span></label>
                        <input type="password" name="password" id="password">
                        <small>Minimal 6 karakter</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="namaLengkap">
                    </div>
                    
                    <div class="form-group">
                        <label>Telepon</label>
                        <input type="text" name="telepon" id="telepon">
                    </div>
                    
                    <div class="form-group">
                        <label>Status <span style="color: red;">*</span></label>
                        <select name="status" id="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" class="btn" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px; background: var(--primary-color); color: white; border: none; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reset Password</h3>
                <button class="close-modal" onclick="closeResetPasswordModal()">&times;</button>
            </div>
            <form id="resetPasswordForm" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="resetUserId">
                
                <div class="modal-body">
                    <p>Reset password untuk user: <strong id="resetUsername"></strong></p>
                    
                    <div class="form-group">
                        <label>Password Baru <span style="color: red;">*</span></label>
                        <input type="password" name="new_password" id="newPassword" required>
                        <small>Minimal 6 karakter</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeResetPasswordModal()" class="btn" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px; background: #17a2b8; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="deleteUserId">
    </form>

    <script>
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Tambah Customer';
        document.getElementById('formAction').value = 'add';
        document.getElementById('userId').value = '';
        document.getElementById('userForm').reset();
        document.getElementById('passwordGroup').style.display = 'block';
        document.getElementById('password').required = true;
        document.getElementById('userModal').classList.add('active');
    }

    function openEditModal(user) {
        document.getElementById('modalTitle').textContent = 'Edit Customer';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('userId').value = user.id;
        document.getElementById('username').value = user.username;
        document.getElementById('email').value = user.email;
        document.getElementById('namaLengkap').value = user.nama_lengkap || '';
        document.getElementById('telepon').value = user.telepon || '';
        document.getElementById('status').value = user.status;
        document.getElementById('passwordGroup').style.display = 'none';
        document.getElementById('password').required = false;
        document.getElementById('userModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('userModal').classList.remove('active');
    }

    function openResetPasswordModal(userId, username) {
        document.getElementById('resetUserId').value = userId;
        document.getElementById('resetUsername').textContent = username;
        document.getElementById('newPassword').value = '';
        document.getElementById('resetPasswordModal').classList.add('active');
    }

    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').classList.remove('active');
    }

    function deleteCustomer(id, username) {
        if (confirm('Apakah Anda yakin ingin menghapus customer "' + username + '"?\n\nSemua undangan milik customer ini akan ikut terhapus.')) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteForm').submit();
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('active');
        }
    }
    </script>
</body>
</html>
<?php
$conn->close();
?>
