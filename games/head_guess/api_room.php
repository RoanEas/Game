<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// ═══════════════════════════════════════════════════════════════
//  🛠️ AUTO-CREATE & AUTO-UPDATE DATABASE TABLES
// ═══════════════════════════════════════════════════════════════
$conn->query("CREATE TABLE IF NOT EXISTS `head_guess_rooms` (
    `room_code` VARCHAR(10) PRIMARY KEY,
    `host_user_id` INT NOT NULL,
    `category_id` VARCHAR(50) DEFAULT NULL,
    `current_word` VARCHAR(255) DEFAULT NULL,
    `game_status` VARCHAR(50) DEFAULT 'setup',
    `game_mode` VARCHAR(20) DEFAULT 'single',
    `score` INT DEFAULT 0,
    `seconds_remaining` INT DEFAULT 60,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS `head_guess_players` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_code` VARCHAR(10) NOT NULL,
    `player_name` VARCHAR(100) NOT NULL,
    `avatar_icon` VARCHAR(50) DEFAULT 'person-outline',
    `is_ready` TINYINT(1) DEFAULT 0,
    `is_host` TINYINT(1) DEFAULT 0,
    `team_side` VARCHAR(10) DEFAULT 'A',
    `is_spectator` TINYINT(1) DEFAULT 0,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `room_player` (`room_code`, `player_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ═══════════════════════════════════════════════════════════════
//  🎮 ACTIONS
// ═══════════════════════════════════════════════════════════════

if ($action === 'create') {
    $player_name = trim($_POST['player_name'] ?? '');
    $avatar = trim($_POST['avatar_icon'] ?? 'dog.png');
    
    if (empty($player_name)) {
        echo json_encode(["status" => "error", "message" => "กรุณากรอกชื่อของคุณ"]);
        exit();
    }
    
    $room_code = '';
    for ($i = 0; $i < 10; $i++) {
        $temp_code = strval(rand(1000, 9999));
        $stmt = $conn->prepare("SELECT room_code FROM head_guess_rooms WHERE room_code = ?");
        $stmt->bind_param("s", $temp_code);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $room_code = $temp_code;
            break;
        }
    }
    
    if (empty($room_code)) {
        echo json_encode(["status" => "error", "message" => "Could not generate room code"]);
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO head_guess_rooms (room_code, host_user_id, game_status, game_mode, seconds_remaining, score) VALUES (?, ?, 'setup', 'single', 60, 0) ON DUPLICATE KEY UPDATE host_user_id = ?, game_status = 'setup', game_mode = 'single', seconds_remaining = 60, score = 0");
    $stmt->bind_param("siii", $room_code, $userId, $userId, $userId);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM head_guess_players WHERE room_code = ?");
    $stmt->bind_param("s", $room_code);
    $stmt->execute();
    
    $stmt = $conn->prepare("INSERT INTO head_guess_players (room_code, player_name, avatar_icon, is_ready, is_host, team_side, is_spectator) VALUES (?, ?, ?, 1, 1, 'A', 0)");
    $stmt->bind_param("sss", $room_code, $player_name, $avatar);
    $stmt->execute();
    
    echo json_encode(["status" => "success", "room_code" => $room_code]);
    exit();
}

if ($action === 'join') {
    $code = trim($_POST['room_code'] ?? '');
    $player_name = trim($_POST['player_name'] ?? '');
    $avatar = trim($_POST['avatar_icon'] ?? 'dog.png');
    
    if (empty($code) || empty($player_name)) {
        echo json_encode(["status" => "error", "message" => "กรุณากรอกชื่อและรหัสห้อง"]);
        exit();
    }
    
    $stmt = $conn->prepare("SELECT room_code, game_status FROM head_guess_rooms WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "ไม่พบรหัสห้องนี้"]);
        exit();
    }

    $room = $res->fetch_assoc();
    if ($room['game_status'] !== 'setup') {
        echo json_encode(["status" => "error", "message" => "ห้องนี้เริ่มเกมไปแล้ว หรือเกมเพิ่งจบลง"]);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO head_guess_players (room_code, player_name, avatar_icon, is_ready, is_host, team_side, is_spectator) VALUES (?, ?, ?, 0, 0, 'A', 0)");
        $stmt->bind_param("sss", $code, $player_name, $avatar);
        $stmt->execute();
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "ชื่อผู้เล่นซ้ำในห้องนี้ กรุณาเปลี่ยนชื่ออื่น"]);
        exit();
    }
    
    echo json_encode(["status" => "success", "room_code" => $code]);
    exit();
}

if ($action === 'set_config') {
    $code = $_POST['room_code'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $seconds = intval($_POST['seconds_remaining'] ?? 60);
    $mode = $_POST['game_mode'] ?? 'single';
    
    $stmt = $conn->prepare("UPDATE head_guess_rooms SET category_id = ?, seconds_remaining = ?, game_mode = ? WHERE room_code = ?");
    $stmt->bind_param("siss", $category_id, $seconds, $mode, $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'switch_role') {
    $code = $_POST['room_code'] ?? '';
    $player_name = $_POST['player_name'] ?? '';
    $role = $_POST['role'] ?? 'A'; // 'A', 'B', 'spectator'
    
    if ($role === 'spectator') {
        $stmt = $conn->prepare("UPDATE head_guess_players SET is_spectator = 1, is_ready = 1 WHERE room_code = ? AND player_name = ?");
        $stmt->bind_param("ss", $code, $player_name);
    } else {
        $stmt = $conn->prepare("UPDATE head_guess_players SET is_spectator = 0, team_side = ?, is_ready = (CASE WHEN is_host=1 THEN 1 ELSE 0 END) WHERE room_code = ? AND player_name = ?");
        $stmt->bind_param("sss", $role, $code, $player_name);
    }
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'ready') {
    $code = $_POST['room_code'] ?? '';
    $player_name = $_POST['player_name'] ?? '';
    
    $stmt = $conn->prepare("UPDATE head_guess_players SET is_ready = NOT is_ready WHERE room_code = ? AND player_name = ? AND is_spectator = 0 AND is_host = 0");
    $stmt->bind_param("ss", $code, $player_name);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'start') {
    $code = $_POST['room_code'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $first_word = $_POST['first_word'] ?? '';
    
    $stmt = $conn->prepare("UPDATE head_guess_rooms SET game_status = 'playing', category_id = ?, current_word = ?, score = 0 WHERE room_code = ?");
    $stmt->bind_param("sss", $category_id, $first_word, $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'update_word') {
    $code = $_POST['room_code'] ?? '';
    $word = $_POST['word'] ?? '';
    $score = intval($_POST['score'] ?? 0);
    $seconds = intval($_POST['seconds_remaining'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE head_guess_rooms SET current_word = ?, score = ?, seconds_remaining = ? WHERE room_code = ?");
    $stmt->bind_param("siis", $word, $score, $seconds, $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'poll') {
    $code = $_GET['room_code'] ?? '';
    
    $stmt = $conn->prepare("SELECT host_user_id, category_id, current_word, game_status, game_mode, score, seconds_remaining FROM head_guess_rooms WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $room_res = $stmt->get_result();
    
    if ($room_res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Room closed"]);
        exit();
    }
    
    $room_row = $room_res->fetch_assoc();
    
    // Fetch players with friendship status
    $stmt = $conn->prepare("
        SELECT 
            u.id AS user_id,
            u.username,
            u.real_name,
            u.avatar_img,
            p.is_ready,
            p.is_host,
            p.team_side,
            p.is_spectator,
            (SELECT status FROM friends WHERE (user_id = ? AND friend_id = u.id) OR (user_id = u.id AND friend_id = ?)) AS friendship_status,
            (SELECT id FROM friends WHERE (user_id = ? AND friend_id = u.id) OR (user_id = u.id AND friend_id = ?)) AS friendship_id
        FROM head_guess_players p
        LEFT JOIN users u ON p.player_name = u.username OR p.player_name = u.real_name
        WHERE p.room_code = ?
        ORDER BY p.id ASC
    ");
    $stmt->bind_param("iiiiis", $userId, $userId, $userId, $userId, $code);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $players = [];
    while ($row = $res->fetch_assoc()) {
        $players[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "host_user_id" => intval($room_row['host_user_id']),
        "category_id" => $room_row['category_id'],
        "current_word" => $room_row['current_word'],
        "game_status" => $room_row['game_status'],
        "game_mode" => $room_row['game_mode'],
        "score" => intval($room_row['score']),
        "seconds_remaining" => intval($room_row['seconds_remaining']),
        "players" => $players
    ]);
    exit();
}

if ($action === 'leave') {
    $code = $_POST['room_code'] ?? '';
    $player_name = $_POST['player_name'] ?? '';
    
    $stmt = $conn->prepare("DELETE FROM head_guess_players WHERE room_code = ? AND player_name = ?");
    $stmt->bind_param("ss", $code, $player_name);
    $stmt->execute();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM head_guess_players WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $cnt = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($cnt == 0) {
        $stmt = $conn->prepare("DELETE FROM head_guess_rooms WHERE room_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
    }
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'end') {
    $code = $_POST['room_code'] ?? '';
    $stmt = $conn->prepare("UPDATE head_guess_rooms SET game_status = 'ended' WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'reset') {
    $code = $_POST['room_code'] ?? '';
    $stmt = $conn->prepare("UPDATE head_guess_rooms SET game_status = 'setup', current_word = NULL, score = 0 WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    $stmt = $conn->prepare("UPDATE head_guess_players SET is_ready = (CASE WHEN is_host=1 OR is_spectator=1 THEN 1 ELSE 0 END) WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
exit();