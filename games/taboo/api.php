<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/db.php';
session_start();

$action = $_GET['action'] ?? '';

// ═══════════════════════════════════════════════════════════════
//  🛠️ AUTO-CREATE DATABASE TABLES
// ═══════════════════════════════════════════════════════════════
$conn->query("CREATE TABLE IF NOT EXISTS `taboo_rooms` (
    `room_code` VARCHAR(10) PRIMARY KEY,
    `host_user_id` INT NOT NULL,
    `game_status` VARCHAR(50) DEFAULT 'setup',
    `game_mode` VARCHAR(20) DEFAULT 'single',
    `seconds_remaining` INT DEFAULT 120,
    `expires_at` INT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

try {
    $conn->query("ALTER TABLE `taboo_rooms` ADD COLUMN `expires_at` INT DEFAULT NULL");
} catch (Exception $e) {}

$conn->query("CREATE TABLE IF NOT EXISTS `taboo_players` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_code` VARCHAR(10) NOT NULL,
    `player_name` VARCHAR(100) NOT NULL,
    `avatar_icon` VARCHAR(150) DEFAULT 'person-outline',
    `is_ready` TINYINT(1) DEFAULT 0,
    `is_host` TINYINT(1) DEFAULT 0,
    `team_side` VARCHAR(10) DEFAULT 'A',
    `is_spectator` TINYINT(1) DEFAULT 0,
    `is_controller` TINYINT(1) DEFAULT 0,
    `current_word` VARCHAR(255) DEFAULT NULL,
    `is_caught` TINYINT(1) DEFAULT 0,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `room_player` (`room_code`, `player_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ═══════════════════════════════════════════════════════════════
//  🎮 ACTIONS
// ═══════════════════════════════════════════════════════════════

if ($action === 'create') {
    $player_name = trim($_POST['player_name'] ?? '');
    $avatar = trim($_POST['avatar_icon'] ?? 'person-outline');
    
    if (empty($player_name)) {
        echo json_encode(["status" => "error", "message" => "กรุณากรอกชื่อของคุณ"]);
        exit();
    }
    
    $room_code = '';
    for ($i = 0; $i < 10; $i++) {
        $temp_code = strval(rand(1000, 9999));
        $stmt = $conn->prepare("SELECT room_code FROM taboo_rooms WHERE room_code = ?");
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
    
    $stmt = $conn->prepare("INSERT INTO taboo_rooms (room_code, host_user_id, game_status, game_mode, seconds_remaining) VALUES (?, ?, 'setup', 'single', 120) ON DUPLICATE KEY UPDATE host_user_id = ?, game_status = 'setup', game_mode = 'single', seconds_remaining = 120");
    $stmt->bind_param("sii", $room_code, $userId, $userId);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM taboo_players WHERE room_code = ?");
    $stmt->bind_param("s", $room_code);
    $stmt->execute();
    
    // โฮสต์เริ่มต้น: พร้อมเสมอ, ไม่ใช่ spectator, ไม่ใช่ controller
    $stmt = $conn->prepare("INSERT INTO taboo_players (room_code, player_name, avatar_icon, is_ready, is_host, team_side, is_spectator, is_controller) VALUES (?, ?, ?, 1, 1, 'A', 0, 0)");
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
    
    $stmt = $conn->prepare("SELECT room_code, game_status, game_mode FROM taboo_rooms WHERE room_code = ?");
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
    
    $target_team = 'A';
    $is_spectator = 0;
    $is_controller = 0;
    
    if ($room['game_mode'] === 'team_3v3' || $room['game_mode'] === 'team_6v6') {
        $limit = ($room['game_mode'] === 'team_3v3') ? 3 : 6;
        
        $stmt_cnt = $conn->prepare("SELECT COUNT(*) as count FROM taboo_players WHERE room_code = ? AND team_side = 'A' AND is_spectator = 0 AND is_controller = 0");
        $stmt_cnt->bind_param("s", $code);
        $stmt_cnt->execute();
        $cntA = $stmt_cnt->get_result()->fetch_assoc()['count'];
        
        if ($cntA >= $limit) {
            $stmt_cnt = $conn->prepare("SELECT COUNT(*) as count FROM taboo_players WHERE room_code = ? AND team_side = 'B' AND is_spectator = 0 AND is_controller = 0");
            $stmt_cnt->bind_param("s", $code);
            $stmt_cnt->execute();
            $cntB = $stmt_cnt->get_result()->fetch_assoc()['count'];
            
            if ($cntB >= $limit) {
                $target_team = 'spectator';
                $is_spectator = 1;
            } else {
                $target_team = 'B';
            }
        }
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO taboo_players (room_code, player_name, avatar_icon, is_ready, is_host, team_side, is_spectator, is_controller) VALUES (?, ?, ?, 0, 0, ?, ?, ?)");
        $stmt->bind_param("ssssii", $code, $player_name, $avatar, $target_team, $is_spectator, $is_controller);
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
    $mode = $_POST['game_mode'] ?? 'single';
    $time = intval($_POST['seconds_remaining'] ?? 120);
    
    $stmt = $conn->prepare("UPDATE taboo_rooms SET game_mode = ?, seconds_remaining = ? WHERE room_code = ?");
    $stmt->bind_param("sis", $mode, $time, $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'switch_role') {
    $code = $_POST['room_code'] ?? '';
    $player_name = $_POST['player_name'] ?? '';
    $role = $_POST['role'] ?? 'A';
    
    $stmt = $conn->prepare("SELECT game_mode FROM taboo_rooms WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $room_res = $stmt->get_result();
    if ($room_res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Room closed"]);
        exit();
    }
    $room = $room_res->fetch_assoc();
    $mode = $room['game_mode'];
    
    if ($role === 'A' || $role === 'B') {
        if ($mode === 'team_3v3' || $mode === 'team_6v6') {
            $limit = ($mode === 'team_3v3') ? 3 : 6;
            
            $stmt_cnt = $conn->prepare("SELECT COUNT(*) as count FROM taboo_players WHERE room_code = ? AND team_side = ? AND player_name != ? AND is_spectator = 0 AND is_controller = 0");
            $stmt_cnt->bind_param("sss", $code, $role, $player_name);
            $stmt_cnt->execute();
            $cnt = $stmt_cnt->get_result()->fetch_assoc()['count'];
            
            if ($cnt >= $limit) {
                echo json_encode(["status" => "error", "message" => "ทีม " . $role . " เต็มแล้ว! (จำกัด $limit คน)"]);
                exit();
            }
        }
        
        $stmt = $conn->prepare("UPDATE taboo_players SET is_spectator = 0, is_controller = 0, team_side = ?, is_ready = (CASE WHEN is_host=1 THEN 1 ELSE 0 END) WHERE room_code = ? AND player_name = ?");
        $stmt->bind_param("sss", $role, $code, $player_name);
    } else if ($role === 'spectator') {
        $stmt = $conn->prepare("UPDATE taboo_players SET is_spectator = 1, is_controller = 0, team_side = 'spectator', is_ready = 1 WHERE room_code = ? AND player_name = ?");
        $stmt->bind_param("ss", $code, $player_name);
    } else if ($role === 'controller') {
        $stmt = $conn->prepare("UPDATE taboo_players SET is_spectator = 0, is_controller = 1, team_side = 'controller', is_ready = 1 WHERE room_code = ? AND player_name = ?");
        $stmt->bind_param("ss", $code, $player_name);
    }
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'ready') {
    $code = $_POST['room_code'] ?? '';
    $player_name = $_POST['player_name'] ?? '';
    
    $stmt = $conn->prepare("UPDATE taboo_players SET is_ready = NOT is_ready WHERE room_code = ? AND player_name = ? AND is_spectator = 0 AND is_controller = 0");
    $stmt->bind_param("ss", $code, $player_name);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'players') {
    $code = $_GET['room_code'] ?? '';
    $current_uid = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("SELECT p.player_name, p.avatar_icon, p.is_ready, p.is_host, p.team_side, p.is_spectator, p.is_controller, p.is_caught, u.id AS user_id,
        (SELECT status FROM friends WHERE (user_id = ? AND friend_id = u.id) OR (user_id = u.id AND friend_id = ?)) AS friendship_status,
        (SELECT id FROM friends WHERE (user_id = ? AND friend_id = u.id) OR (user_id = u.id AND friend_id = ?)) AS friendship_id
        FROM taboo_players p 
        LEFT JOIN users u ON (p.player_name = u.real_name OR p.player_name = u.username) 
        WHERE p.room_code = ? 
        ORDER BY p.id ASC");
    $stmt->bind_param("iiiis", $current_uid, $current_uid, $current_uid, $current_uid, $code);
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
    
    // Select seconds_remaining to calculate expires_at
    $stmt = $conn->prepare("SELECT seconds_remaining FROM taboo_rooms WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $room_sec = $stmt->get_result()->fetch_assoc();
    $sec = intval($room_sec['seconds_remaining'] ?? 120);
    $expires_at = time() + $sec;
    
    $stmt = $conn->prepare("UPDATE taboo_rooms SET game_status = 'playing', expires_at = ? WHERE room_code = ?");
    $stmt->bind_param("is", $expires_at, $code);
    $stmt->execute();
    
    $stmt = $conn->prepare("UPDATE taboo_players SET current_word = ?, is_caught = 0 WHERE room_code = ? AND player_name = ?");
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
    
    $stmt = $conn->prepare("UPDATE taboo_players SET is_caught = 1 WHERE room_code = ? AND player_name = ?");
    $stmt->bind_param("ss", $code, $target_name);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'poll_taboo') {
    $code = $_GET['room_code'] ?? '';
    
    $stmt = $conn->prepare("SELECT game_status, game_mode, seconds_remaining, expires_at FROM taboo_rooms WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $room_res = $stmt->get_result();
    
    if ($room_res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Room closed"]);
        exit();
    }
    
    $room_row = $room_res->fetch_assoc();
    
    // Server-side countdown logic
    if ($room_row['game_status'] === 'playing') {
        $expires = intval($room_row['expires_at'] ?? 0);
        $seconds_remaining = $expires - time();
        if ($seconds_remaining <= 0) {
            $seconds_remaining = 0;
            // End the game automatically
            $stmt = $conn->prepare("UPDATE taboo_rooms SET game_status = 'ended', seconds_remaining = 0 WHERE room_code = ?");
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $room_row['game_status'] = 'ended';
        } else {
            // Persist remaining seconds
            $stmt = $conn->prepare("UPDATE taboo_rooms SET seconds_remaining = ? WHERE room_code = ?");
            $stmt->bind_param("is", $seconds_remaining, $code);
            $stmt->execute();
        }
        $room_row['seconds_remaining'] = $seconds_remaining;
    }
    
    $stmt = $conn->prepare("SELECT player_name, avatar_icon, is_ready, is_host, team_side, is_spectator, is_controller, current_word, is_caught FROM taboo_players WHERE room_code = ? ORDER BY id ASC");
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
        "game_mode" => $room_row['game_mode'],
        "seconds_remaining" => intval($room_row['seconds_remaining']),
        "players" => $players
    ]);
    exit();
}

if ($action === 'exit') {
    $player_name = trim($_POST['player_name'] ?? '');
    $code = trim($_POST['room_code'] ?? '');
    
    if (empty($player_name) || empty($code)) {
        echo json_encode(["status" => "success"]);
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM taboo_players WHERE room_code = ? AND player_name = ?");
    $stmt->bind_param("ss", $code, $player_name);
    $stmt->execute();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM taboo_players WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $cnt = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($cnt == 0) {
        $stmt = $conn->prepare("DELETE FROM taboo_rooms WHERE room_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
    } else {
        $stmt_host_cnt = $conn->prepare("SELECT COUNT(*) as count FROM taboo_players WHERE room_code = ? AND is_host = 1");
        $stmt_host_cnt->bind_param("s", $code);
        $stmt_host_cnt->execute();
        if ($stmt_host_cnt->get_result()->fetch_assoc()['count'] == 0) {
            $stmt_new_host = $conn->prepare("UPDATE taboo_players SET is_host = 1, is_ready = 1 WHERE room_code = ? ORDER BY id ASC LIMIT 1");
            $stmt_new_host->bind_param("s", $code);
            $stmt_new_host->execute();
        }
    }
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'end_taboo') {
    $code = $_POST['room_code'] ?? '';
    $stmt = $conn->prepare("UPDATE taboo_rooms SET game_status = 'ended' WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'restart_taboo') {
    $code = $_POST['room_code'] ?? '';
    
    $stmt = $conn->prepare("UPDATE taboo_rooms SET game_status = 'setup' WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    $stmt = $conn->prepare("UPDATE taboo_players SET is_ready = 0, is_caught = 0, current_word = NULL WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    $stmt = $conn->prepare("UPDATE taboo_players SET is_ready = 1 WHERE room_code = ? AND (is_host = 1 OR is_spectator = 1 OR is_controller = 1)");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
exit();
