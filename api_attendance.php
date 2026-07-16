<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
session_start();

$action = $_GET['action'] ?? '';

// Check-in action
if ($action === 'checkin') {
    $uid = $_SESSION['user_id'] ?? null;
    if (!$uid) {
        echo json_encode(["status" => "error", "message" => "Unauthorized"]);
        exit();
    }
    $now = time();
    $stmt = $conn->prepare("UPDATE users SET last_checkin = ? WHERE id = ?");
    $stmt->bind_param("ii", $now, $uid);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Check-in successful"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Check-in failed"]);
    }
    exit();
}

// Reset action (Admins only)
if ($action === 'reset') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(["status" => "error", "message" => "Unauthorized. Admins only."]);
        exit();
    }
    $stmt = $conn->query("UPDATE users SET last_checkin = 0");
    if ($stmt) {
        echo json_encode(["status" => "success", "message" => "Attendance list reset successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to reset attendance"]);
    }
    exit();
}


// Fetch users checked in within the last 12 hours
$time_limit = time() - (12 * 3600);

$stmt = $conn->prepare("SELECT id, username, real_name, avatar_img, score, last_checkin, last_active 
    FROM users 
    WHERE last_checkin > ? AND role = 'member' 
    ORDER BY last_checkin DESC");
$stmt->bind_param("i", $time_limit);
$stmt->execute();
$res = $stmt->get_result();

$players = [];
$now = time();
while ($row = $res->fetch_assoc()) {
    $is_online = ($now - intval($row['last_active'] ?? 0)) < 30; // online if active within last 30s
    $players[] = [
        "id" => $row['id'],
        "username" => $row['username'],
        "real_name" => $row['real_name'] ?? $row['username'],
        "avatar_file" => $row['avatar_img'] ?? 'dog.png',
        "score" => intval($row['score']),
        "checkin_time" => date("H:i น.", $row['last_checkin']),
        "is_online" => $is_online
    ];
}

echo json_encode(["status" => "success", "players" => $players]);
exit();
?>
