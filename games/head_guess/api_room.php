<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/db.php';
session_start();

$action = $_GET['action'] ?? '';

// Auto-create players and rooms tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS `head_guess_rooms` (
    `room_code` VARCHAR(10) PRIMARY KEY,
    `host_user_id` INT NOT NULL,
    `category_id` VARCHAR(50) DEFAULT NULL,
    `current_word` VARCHAR(255) DEFAULT NULL,
    `game_status` VARCHAR(50) DEFAULT 'setup',
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
    `current_word` VARCHAR(255) DEFAULT NULL,
    `is_caught` TINYINT(1) DEFAULT 0,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `room_player` (`room_code`, `player_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");


if ($action === 'create') {
    $player_name = trim($_POST['player_name'] ?? '');
    $avatar = trim($_POST['avatar_icon'] ?? 'person-outline');
    
    if (empty($player_name)) {
        echo json_encode(["status" => "error", "message" => "กรุณากรอกชื่อของคุณ"]);
        exit();
    }
    
    // Generate a unique 4-digit room code
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
    
    $userId = $_SESSION['user_id'] ?? 999;
    
    // Insert room
    $stmt = $conn->prepare("INSERT INTO head_guess_rooms (room_code, host_user_id, game_status) VALUES (?, ?, 'setup') ON DUPLICATE KEY UPDATE host_user_id = ?, game_status = 'setup'");
    $stmt->bind_param("sii", $room_code, $userId, $userId);
    $stmt->execute();
    
    // Clear old players in this room code (if any)
    $stmt = $conn->prepare("DELETE FROM head_guess_players WHERE room_code = ?");
    $stmt->bind_param("s", $room_code);
    $stmt->execute();
    
    // Insert host player
    $stmt = $conn->prepare("INSERT INTO head_guess_players (room_code, player_name, avatar_icon, is_ready, is_host) VALUES (?, ?, ?, 1, 1)");
    $stmt->bind_param("sss", $room_code, $player_name, $avatar);
    $stmt->execute();
    
    echo json_encode(["status" => "success", "room_code" => $room_code]);
    exit();
}

if ($action === 'join') {
    $code = trim($_POST['room_code'] ?? '');
    $player_name = trim($_POST['player_name'] ?? '');
    $avatar = trim($_POST['avatar_icon'] ?? 'person-outline');
    
    if (empty($code) || empty($player_name)) {
        echo json_encode(["status" => "error", "message" => "กรุณากรอกชื่อและรหัสห้อง"]);
        exit();
    }
    
    // Verify room
    $stmt = $conn->prepare("SELECT room_code FROM head_guess_rooms WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "ไม่พบรหัสห้องนี้"]);
        exit();
    }
    
    // Insert player (unique name constraint inside room)
    try {
        $stmt = $conn->prepare("INSERT INTO head_guess_players (room_code, player_name, avatar_icon, is_ready, is_host) VALUES (?, ?, ?, 0, 0)");
        $stmt->bind_param("sss", $code, $player_name, $avatar);
        $stmt->execute();
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "ชื่อผู้เล่นซ้ำในห้องนี้ กรุณาเปลี่ยนชื่ออื่น"]);
        exit();
    }
    
    echo json_encode(["status" => "success", "room_code" => $code]);
    exit();
}

if ($action === 'ready') {
    $code = $_POST['room_code'] ?? '';
    $player_name = $_POST['player_name'] ?? '';
    
    $stmt = $conn->prepare("UPDATE head_guess_players SET is_ready = NOT is_ready WHERE room_code = ? AND player_name = ?");
    $stmt->bind_param("ss", $code, $player_name);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'players') {
    $code = $_GET['room_code'] ?? '';
    $stmt = $conn->prepare("SELECT player_name, avatar_icon, is_ready, is_host, is_caught FROM head_guess_players WHERE room_code = ? ORDER BY id ASC");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $players = [];
    while ($row = $res->fetch_assoc()) {
        $players[] = $row;
    }
    
    echo json_encode(["status" => "success", "players" => $players]);
    exit();
}

if ($action === 'start_taboo') {
    $code = $_POST['room_code'] ?? '';
    $word_assignments = json_decode($_POST['assignments'] ?? '[]', true);
    
    // Update game status
    $stmt = $conn->prepare("UPDATE head_guess_rooms SET game_status = 'playing', category_id = 'forbidden_words' WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    // Update each player word and reset caught status
    $stmt = $conn->prepare("UPDATE head_guess_players SET current_word = ?, is_caught = 0 WHERE room_code = ? AND player_name = ?");
    foreach ($word_assignments as $assign) {
        $w = $assign['word'];
        $n = $assign['player_name'];
        $stmt->bind_param("sss", $w, $code, $n);
        $stmt->execute();
    }
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'catch_player') {
    $code = $_POST['room_code'] ?? '';
    $target_name = $_POST['target_name'] ?? '';
    
    $stmt = $conn->prepare("UPDATE head_guess_players SET is_caught = 1 WHERE room_code = ? AND player_name = ?");
    $stmt->bind_param("ss", $code, $target_name);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'poll_taboo') {
    $code = $_GET['room_code'] ?? '';
    
    // Get room details
    $stmt = $conn->prepare("SELECT game_status FROM head_guess_rooms WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $room_res = $stmt->get_result();
    
    if ($room_res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Room closed"]);
        exit();
    }
    
    $room_row = $room_res->fetch_assoc();
    
    // Get player details
    $stmt = $conn->prepare("SELECT player_name, avatar_icon, is_ready, is_host, current_word, is_caught FROM head_guess_players WHERE room_code = ? ORDER BY id ASC");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $players = [];
    while ($row = $res->fetch_assoc()) {
        $players[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "game_status" => $room_row['game_status'],
        "players" => $players
    ]);
    exit();
}

if ($action === 'exit') {
    $code = $_POST['room_code'] ?? '';
    $player_name = $_POST['player_name'] ?? '';
    
    $stmt = $conn->prepare("DELETE FROM head_guess_players WHERE room_code = ? AND player_name = ?");
    $stmt->bind_param("ss", $code, $player_name);
    $stmt->execute();
    
    // If no players left, close room
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

if ($action === 'end_taboo') {
    $code = $_POST['room_code'] ?? '';
    $stmt = $conn->prepare("UPDATE head_guess_rooms SET game_status = 'ended' WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'restart_taboo') {
    $code = $_POST['room_code'] ?? '';
    
    // Reset room to setup status
    $stmt = $conn->prepare("UPDATE head_guess_rooms SET game_status = 'setup', current_word = NULL WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    // Reset all players: not ready, not caught, clear assigned word
    $stmt = $conn->prepare("UPDATE head_guess_players SET is_ready = 0, is_caught = 0, current_word = NULL WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    // Keep host as ready automatically
    $stmt = $conn->prepare("UPDATE head_guess_players SET is_ready = 1 WHERE room_code = ? AND is_host = 1");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
exit();
