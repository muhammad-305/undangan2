<?php
// Password hashing
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Session management
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        if (ENVIRONMENT === 'production') {
            ini_set('session.cookie_secure', 1);
        }
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerate session ID
        if (!isset($_SESSION['created'])) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

// Check login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isCustomer() {
    return isLoggedIn() && $_SESSION['role'] === 'customer';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login-admin');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL);
        exit;
    }
}

function requireCustomer() {
    requireLogin();
    if (!isCustomer()) {
        header('Location: ' . BASE_URL);
        exit;
    }
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// HTML helper
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

// Sanitize input
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate URL
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// Clean filename
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}

// Rate limiting
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    if (!isset($_SESSION['rate_limit'][$identifier])) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    $data = $_SESSION['rate_limit'][$identifier];
    
    // Reset jika sudah lewat time window
    if (time() - $data['first_attempt'] > $timeWindow) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    // Increment attempts
    $_SESSION['rate_limit'][$identifier]['attempts']++;
    
    // Check limit
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    return true;
}

// File upload validation
function validateImageUpload($file, $maxSize = 2097152) { // 2MB
    $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'File tidak valid'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File terlalu besar (max 2MB)'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowed)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    if (!getimagesize($file['tmp_name'])) {
        return ['success' => false, 'message' => 'File bukan gambar yang valid'];
    }
    
    return ['success' => true];
}

function uploadImage($file, $destination, $newFilename = null) {
    $validation = validateImageUpload($file);
    
    if (!$validation['success']) {
        return $validation;
    }
    
    if (!file_exists($destination)) {
        mkdir($destination, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $newFilename ?? uniqid() . '_' . time() . '.' . $extension;
    $filepath = $destination . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Gagal upload file'];
}
?>
