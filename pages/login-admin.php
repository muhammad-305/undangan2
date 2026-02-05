<?php
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard');
    } else {
        redirect('customer/dashboard');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid CSRF token';
    } else if (!checkRateLimit('login_admin_' . $_SERVER['REMOTE_ADDR'])) {
        $error = 'Terlalu banyak percobaan login. Coba lagi nanti.';
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, username, email, password, role, nama_lengkap FROM users WHERE (username = ? OR email = ?) AND role = 'admin' AND status = 'active'");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (verifyPassword($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                
                logActivity($user['id'], 'login', 'Admin login successful');
                redirect('admin/dashboard');
            } else {
                $error = 'Username atau password salah';
            }
        } else {
            $error = 'Username atau password salah';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Undangan Online</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="<?php echo BASE_URL; ?>logo.png" alt="Logo" height="60">
                <h2>Login Admin</h2>
                <p>Masuk ke Dashboard Admin</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo escapeOutput($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <?php echo csrfField(); ?>
                
                <div class="form-group">
                    <label for="username">Username atau Email</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="login-footer">
                <a href="<?php echo BASE_URL; ?>">← Kembali ke Beranda</a>
                <a href="<?php echo BASE_URL; ?>login-customer">Login Customer</a>
            </div>
        </div>
    </div>
</body>
</html>
