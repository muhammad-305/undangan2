<?php
// Include configuration
require_once ROOT_PATH . 'config/config.php';
require_once ROOT_PATH . 'config/database.php';
require_once ROOT_PATH . 'config/security.php';

// Helper functions
function escapeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . BASE_URL . $url);
    exit;
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return $difference . ' detik yang lalu';
    } elseif ($difference < 3600) {
        return floor($difference / 60) . ' menit yang lalu';
    } elseif ($difference < 86400) {
        return floor($difference / 3600) . ' jam yang lalu';
    } elseif ($difference < 604800) {
        return floor($difference / 86400) . ' hari yang lalu';
    } else {
        return date('d M Y', $timestamp);
    }
}

function formatDate($date, $format = 'd F Y') {
    $months = [
        'January' => 'Januari',
        'February' => 'Februari',
        'March' => 'Maret',
        'April' => 'April',
        'May' => 'Mei',
        'June' => 'Juni',
        'July' => 'Juli',
        'August' => 'Agustus',
        'September' => 'September',
        'October' => 'Oktober',
        'November' => 'November',
        'December' => 'Desember'
    ];
    
    $days = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $formatted = date($format, strtotime($date));
    
    foreach ($months as $eng => $indo) {
        $formatted = str_replace($eng, $indo, $formatted);
    }
    
    foreach ($days as $eng => $indo) {
        $formatted = str_replace($eng, $indo, $formatted);
    }
    
    return $formatted;
}

function getDayName($date) {
    return formatDate($date, 'l');
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

function getBackgrounds() {
    $backgroundPath = ROOT_PATH . 'images/background/';
    $backgrounds = [];
    
    if (is_dir($backgroundPath)) {
        $files = scandir($backgroundPath);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'])) {
                $backgrounds[] = $file;
            }
        }
    }
    
    return $backgrounds;
}

function logActivity($userId, $action, $description = '', $ipAddress = null, $userAgent = null) {
    $conn = getConnection();
    
    $ipAddress = $ipAddress ?? $_SERVER['REMOTE_ADDR'];
    $userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $action, $description, $ipAddress, $userAgent);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>
