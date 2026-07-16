<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Allow overriding via query parameters for testing/debugging
$device = $_GET['device'] ?? '';

if (empty($device)) {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $isMobile = preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $ua);
    $device = $isMobile ? 'mobile' : 'pc';
}

if ($device === 'mobile') {
    include __DIR__ . '/mobile.php';
} else {
    include __DIR__ . '/pc.php';
}
?>
