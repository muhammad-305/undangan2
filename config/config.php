<?php
// Environment
define('ENVIRONMENT', 'development'); // 'development' atau 'production'

// Base URL
if (ENVIRONMENT === 'development') {
    define('BASE_URL', 'http://localhost/undangan-online/');
} else {
    define('BASE_URL', 'https://undangan.muza-project.com/');
}

// Path
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
}

// Security
define('SECRET_KEY', 'muza-undangan-online-2026-secret-key-change-in-production');
define('SESSION_LIFETIME', 3600); // 1 jam

// Timezone
date_default_timezone_set('Asia/Jakarta');
?>
