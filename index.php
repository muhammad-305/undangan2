<?php
// Entry point for the application
define('ROOT_PATH', __DIR__ . '/');
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'includes/functions.php';

startSecureSession();

// Simple routing
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$requestUri = str_replace($scriptName, '', $requestUri);
$requestUri = trim($requestUri, '/');

// Remove query string
if (strpos($requestUri, '?') !== false) {
    $requestUri = substr($requestUri, 0, strpos($requestUri, '?'));
}

// Default to home page
if (empty($requestUri)) {
    require_once ROOT_PATH . 'pages/home.php';
    exit;
}

// Check if it's a page request
$pagePath = ROOT_PATH . 'pages/' . $requestUri . '.php';
if (file_exists($pagePath)) {
    require_once $pagePath;
    exit;
}

// 404 Not Found
http_response_code(404);
echo '<!DOCTYPE html>
<html>
<head>
    <title>404 - Halaman Tidak Ditemukan</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #D4AF37; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>Halaman yang Anda cari tidak ditemukan.</p>
    <a href="' . BASE_URL . '">Kembali ke Beranda</a>
</body>
</html>';
?>
