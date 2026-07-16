<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
session_start();

$uid = $_SESSION['user_id'] ?? null;
if (!$uid) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$action = $_GET['action'] ?? '';

// ═══════════════════════════════════════════════════════════════
//  🛠️ AUTO-CREATE DATABASE TABLES
// ═══════════════════════════════════════════════════════════════
try {
    $conn->query("ALTER TABLE `users` ADD COLUMN `last_active` INT DEFAULT 0");
} catch (Exception $e) {}

$conn->query("CREATE TABLE IF NOT EXISTS `friends` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `friend_id` INT NOT NULL,
    `status` VARCHAR(20) DEFAULT 'pending', -- 'pending', 'accepted'
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_friend` (`user_id`, `friend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS `room_invitations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `room_code` VARCHAR(10) NOT NULL,
    `game_type` VARCHAR(50) DEFAULT 'taboo',
    `status` VARCHAR(20) DEFAULT 'pending', -- 'pending', 'accepted', 'declined'
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ═══════════════════════════════════════════════════════════════
//  🎮 SOCIAL ACTIONS
// ═══════════════════════════════════════════════════════════════

// Update user active status
if ($action === 'ping_active') {
    $now = time();
    $stmt = $conn->prepare("UPDATE users SET last_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $now, $uid);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

// Search users to add as friends
if ($action === 'search_users') {
    $query = '%' . trim($_GET['query'] ?? '') . '%';
    
    // Find users who are not me, and not already friends (accepted or pending)
    $stmt = $conn->prepare("SELECT id, username, real_name, avatar_img FROM users 
        WHERE id != ? 
        AND (username LIKE ? OR real_name LIKE ?)
        AND id NOT IN (
            SELECT friend_id FROM friends WHERE user_id = ?
            UNION
            SELECT user_id FROM friends WHERE friend_id = ?
        )
        LIMIT 10");
    $stmt->bind_param("issii", $uid, $query, $query, $uid, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $users = [];
    while ($row = $res->fetch_assoc()) {
        $users[] = [
            "id" => $row['id'],
            "username" => $row['username'],
            "real_name" => $row['real_name'],
            "avatar" => 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($row['real_name'] ?? $row['username']) . '&backgroundColor=b6e3f4,c0aede,d1c4e9'
        ];
    }
    
    echo json_encode(["status" => "success", "users" => $users]);
    exit();
}

// Send friend request
if ($action === 'add_friend') {
    $friend_id = intval($_POST['friend_id'] ?? 0);
    if ($friend_id === 0 || $friend_id === $uid) {
        echo json_encode(["status" => "error", "message" => "Invalid target"]);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("ii", $uid, $friend_id);
        $stmt->execute();
        echo json_encode(["status" => "success"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "คำขอถูกส่งไปแล้ว หรือเป็นเพื่อนกันอยู่แล้ว"]);
    }
    exit();
}

// Accept friend request
if ($action === 'accept_friend') {
    $request_id = intval($_POST['request_id'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE friends SET status = 'accepted' WHERE id = ? AND friend_id = ?");
    $stmt->bind_param("ii", $request_id, $uid);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

// Decline friend request
if ($action === 'decline_friend') {
    $request_id = intval($_POST['request_id'] ?? 0);
    
    $stmt = $conn->prepare("DELETE FROM friends WHERE id = ? AND (friend_id = ? OR user_id = ?)");
    $stmt->bind_param("iii", $request_id, $uid, $uid);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

// List friends (accepted friends, incoming requests, sent pending)
if ($action === 'list_friends') {
    // 1. Accepted friends
    $stmt = $conn->prepare("SELECT f.id as friendship_id, u.id as user_id, u.username, u.real_name, u.last_active
        FROM friends f
        JOIN users u ON (f.user_id = u.id OR f.friend_id = u.id)
        WHERE (f.user_id = ? OR f.friend_id = ?) AND u.id != ? AND f.status = 'accepted'");
    $stmt->bind_param("iii", $uid, $uid, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $friends = [];
    $now = time();
    while ($row = $res->fetch_assoc()) {
        $is_online = ($now - intval($row['last_active'] ?? 0)) < 30; // active within 30 seconds
        $friends[] = [
            "friendship_id" => $row['friendship_id'],
            "user_id" => $row['user_id'],
            "username" => $row['username'],
            "real_name" => $row['real_name'],
            "is_online" => $is_online,
            "avatar" => 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($row['real_name'] ?? $row['username']) . '&backgroundColor=b6e3f4,c0aede,d1c4e9'
        ];
    }
    
    // 2. Incoming requests
    $stmt = $conn->prepare("SELECT f.id as friendship_id, u.id as user_id, u.username, u.real_name 
        FROM friends f
        JOIN users u ON f.user_id = u.id
        WHERE f.friend_id = ? AND f.status = 'pending'");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $requests = [];
    while ($row = $res->fetch_assoc()) {
        $requests[] = [
            "friendship_id" => $row['friendship_id'],
            "user_id" => $row['user_id'],
            "username" => $row['username'],
            "real_name" => $row['real_name'],
            "avatar" => 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($row['real_name'] ?? $row['username']) . '&backgroundColor=b6e3f4,c0aede,d1c4e9'
        ];
    }
    
    echo json_encode(["status" => "success", "friends" => $friends, "requests" => $requests]);
    exit();
}

// Send room invitation to friend
if ($action === 'send_invite') {
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $room_code = trim($_POST['room_code'] ?? '');
    $game_type = trim($_POST['game_type'] ?? 'taboo');
    
    if ($receiver_id === 0 || empty($room_code)) {
        echo json_encode(["status" => "error", "message" => "Invalid parameters"]);
        exit();
    }
    
    // Clean old invitations to the same receiver to avoid clutter
    $stmt = $conn->prepare("DELETE FROM room_invitations WHERE receiver_id = ? AND room_code = ? AND game_type = ?");
    $stmt->bind_param("iss", $receiver_id, $room_code, $game_type);
    $stmt->execute();
    
    $stmt = $conn->prepare("INSERT INTO room_invitations (sender_id, receiver_id, room_code, game_type, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiss", $uid, $receiver_id, $room_code, $game_type);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

// Check for pending invitations (incoming popups)
if ($action === 'check_invites') {
    $stmt = $conn->prepare("SELECT ri.id, ri.room_code, ri.game_type, u.real_name as sender_name 
        FROM room_invitations ri
        JOIN users u ON ri.sender_id = u.id
        WHERE ri.receiver_id = ? AND ri.status = 'pending'
        ORDER BY ri.id DESC LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        echo json_encode(["status" => "success", "invitation" => $res->fetch_assoc()]);
    } else {
        echo json_encode(["status" => "success", "invitation" => null]);
    }
    exit();
}

// Accept or decline invitation
if ($action === 'update_invite') {
    $invite_id = intval($_POST['invite_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'declined'); // 'accepted', 'declined'
    
    $stmt = $conn->prepare("UPDATE room_invitations SET status = ? WHERE id = ? AND receiver_id = ?");
    $stmt->bind_param("sii", $status, $invite_id, $uid);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
exit();
