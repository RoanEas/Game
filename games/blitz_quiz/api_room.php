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

// Action: Create Room
if ($action === 'create') {
    // Generate a unique 4-digit code
    $room_code = '';
    for ($i = 0; $i < 10; $i++) {
        $temp_code = strval(rand(1000, 9999));
        $stmt = $conn->prepare("SELECT room_code FROM blitz_rooms WHERE room_code = ?");
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
    
    // Clear old players and insert new room
    $stmt = $conn->prepare("DELETE FROM blitz_players WHERE room_code = ?");
    $stmt->bind_param("s", $room_code);
    $stmt->execute();
    
    $stmt = $conn->prepare("INSERT INTO blitz_rooms (room_code, host_user_id, game_status, score, seconds_remaining, timer_running) VALUES (?, ?, 'setup', 0, 120, 0) ON DUPLICATE KEY UPDATE host_user_id = ?, game_status = 'setup', score = 0, seconds_remaining = 120, timer_running = 0");
    $stmt->bind_param("sii", $room_code, $userId, $userId);
    $stmt->execute();
    
    echo json_encode(["status" => "success", "room_code" => $room_code]);
    exit();
}

// Action: Join Room
if ($action === 'join') {
    $code = trim($_POST['room_code'] ?? '');
    
    if (empty($code)) {
        echo json_encode(["status" => "error", "message" => "กรุณากรอกรหัสห้อง"]);
        exit();
    }
    
    $stmt = $conn->prepare("SELECT room_code, game_status FROM blitz_rooms WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "ไม่พบห้องนี้ในระบบ"]);
        exit();
    }
    
    $room = $res->fetch_assoc();
    
    try {
        $stmt = $conn->prepare("INSERT INTO blitz_players (room_code, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_id = user_id");
        $stmt->bind_param("si", $code, $userId);
        $stmt->execute();
    } catch (Exception $e) {
        // Already joined
    }
    
    echo json_encode(["status" => "success", "room_code" => $code]);
    exit();
}

// Action: Select Player to play
if ($action === 'select_player') {
    $code = $_POST['room_code'] ?? '';
    $player_id = intval($_POST['player_id'] ?? 0);
    
    // Fetch first question randomly
    $q_res = $conn->query("SELECT id FROM blitz_questions ORDER BY RAND() LIMIT 1");
    $first_q_id = ($q_res && $q_res->num_rows > 0) ? intval($q_res->fetch_assoc()['id']) : NULL;
    
    $stmt = $conn->prepare("UPDATE blitz_rooms SET current_player_id = ?, current_question_id = ?, game_status = 'playing', score = 0, seconds_remaining = 120, selected_choice = NULL, timer_running = 0 WHERE room_code = ?");
    $stmt->bind_param("iiis", $player_id, $first_q_id, $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

// Action: Sync/Poll Room State
if ($action === 'poll') {
    $code = $_GET['room_code'] ?? '';
    
    $stmt = $conn->prepare("SELECT r.*, u.real_name as player_name, u.avatar_img as player_avatar FROM blitz_rooms r LEFT JOIN users u ON r.current_player_id = u.id WHERE r.room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $room_res = $stmt->get_result();
    
    if ($room_res->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Room closed"]);
        exit();
    }
    
    $room = $room_res->fetch_assoc();
    
    // Fetch current question details if available
    $question = null;
    if ($room['current_question_id']) {
        $q_stmt = $conn->prepare("SELECT id, question_text, choices FROM blitz_questions WHERE id = ?");
        $q_stmt->bind_param("i", $room['current_question_id']);
        $q_stmt->execute();
        $q_row = $q_stmt->get_result()->fetch_assoc();
        if ($q_row) {
            $question = [
                "id" => $q_row['id'],
                "question_text" => $q_row['question_text'],
                "choices" => json_decode($q_row['choices'], true)
            ];
        }
    }
    
    // Fetch active player list
    $p_stmt = $conn->prepare("SELECT p.user_id, u.username, u.real_name, u.avatar_img, u.score FROM blitz_players p JOIN users u ON p.user_id = u.id WHERE p.room_code = ? ORDER BY p.id ASC");
    $p_stmt->bind_param("s", $code);
    $p_stmt->execute();
    $p_res = $p_stmt->get_result();
    
    $players = [];
    while ($row = $p_res->fetch_assoc()) {
        $players[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "room" => [
            "room_code" => $room['room_code'],
            "host_user_id" => intval($room['host_user_id']),
            "current_player_id" => $room['current_player_id'] ? intval($room['current_player_id']) : null,
            "player_name" => $room['player_name'],
            "player_avatar" => $room['player_avatar'],
            "current_question_id" => $room['current_question_id'] ? intval($room['current_question_id']) : null,
            "game_status" => $room['game_status'],
            "score" => intval($room['score']),
            "seconds_remaining" => intval($room['seconds_remaining']),
            "selected_choice" => $room['selected_choice'],
            "timer_running" => intval($room['timer_running'])
        ],
        "question" => $question,
        "players" => $players
    ]);
    exit();
}

// Action: Submit Answer Choice (Mobile player)
if ($action === 'select_choice') {
    $code = $_POST['room_code'] ?? '';
    $choice = $_POST['choice'] ?? '';
    
    $stmt = $conn->prepare("UPDATE blitz_rooms SET selected_choice = ? WHERE room_code = ? AND current_player_id = ? AND game_status = 'playing'");
    $stmt->bind_param("ssi", $choice, $code, $userId);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

// Action: Update Timer Sync (PC host sends every second)
if ($action === 'update_timer') {
    $code = $_POST['room_code'] ?? '';
    $seconds = intval($_POST['seconds_remaining'] ?? 0);
    $running = intval($_POST['timer_running'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE blitz_rooms SET seconds_remaining = ?, timer_running = ? WHERE room_code = ?");
    $stmt->bind_param("iis", $seconds, $running, $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

// Action: Grade current answer (PC Host judges)
if ($action === 'grade_answer') {
    $code = $_POST['room_code'] ?? '';
    $result = $_POST['result'] ?? ''; // 'correct' or 'incorrect'
    
    // Get current room state
    $stmt = $conn->prepare("SELECT score, current_question_id, current_player_id FROM blitz_rooms WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    
    if (!$room) {
        echo json_encode(["status" => "error", "message" => "Room not found"]);
        exit();
    }
    
    $current_score = intval($room['score']);
    $player_id = intval($room['current_player_id']);
    
    if ($result === 'correct') {
        $new_score = $current_score + 1;
        
        // If they reach 10, game ends!
        if ($new_score >= 10) {
            // Update user global score with a bonus of 100 points
            $points_earned = 100;
            $u_stmt = $conn->prepare("UPDATE users SET score = score + ? WHERE id = ?");
            $u_stmt->bind_param("ii", $points_earned, $player_id);
            $u_stmt->execute();
            
            // Set room status to ended
            $stmt = $conn->prepare("UPDATE blitz_rooms SET score = ?, game_status = 'ended', timer_running = 0, selected_choice = NULL WHERE room_code = ?");
            $stmt->bind_param("is", $new_score, $code);
            $stmt->execute();
            
            echo json_encode(["status" => "success", "reached_max" => true]);
            exit();
        } else {
            // Select next question randomly (different from current if possible)
            $q_stmt = $conn->prepare("SELECT id FROM blitz_questions WHERE id != ? ORDER BY RAND() LIMIT 1");
            $q_stmt->bind_param("i", $room['current_question_id']);
            $q_stmt->execute();
            $q_res = $q_stmt->get_result();
            if ($q_res->num_rows === 0) {
                // fallback if only 1 question
                $q_res = $conn->query("SELECT id FROM blitz_questions LIMIT 1");
            }
            $next_q_id = intval($q_res->fetch_assoc()['id']);
            
            $stmt = $conn->prepare("UPDATE blitz_rooms SET score = ?, current_question_id = ?, selected_choice = NULL WHERE room_code = ?");
            $stmt->bind_param("iis", $new_score, $next_q_id, $code);
            $stmt->execute();
        }
    } else {
        // Incorrect: Score falls back to 0
        $new_score = 0;
        
        $q_stmt = $conn->prepare("SELECT id FROM blitz_questions WHERE id != ? ORDER BY RAND() LIMIT 1");
        $q_stmt->bind_param("i", $room['current_question_id']);
        $q_stmt->execute();
        $q_res = $q_stmt->get_result();
        if ($q_res->num_rows === 0) {
            $q_res = $conn->query("SELECT id FROM blitz_questions LIMIT 1");
        }
        $next_q_id = intval($q_res->fetch_assoc()['id']);
        
        $stmt = $conn->prepare("UPDATE blitz_rooms SET score = ?, current_question_id = ?, selected_choice = NULL WHERE room_code = ?");
        $stmt->bind_param("iis", $new_score, $next_q_id, $code);
        $stmt->execute();
    }
    
    echo json_encode(["status" => "success", "reached_max" => false]);
    exit();
}

// Action: Skip Question manually (PC Host)
if ($action === 'skip_question') {
    $code = $_POST['room_code'] ?? '';
    
    $stmt = $conn->prepare("SELECT current_question_id FROM blitz_rooms WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    
    $q_stmt = $conn->prepare("SELECT id FROM blitz_questions WHERE id != ? ORDER BY RAND() LIMIT 1");
    $q_stmt->bind_param("i", $room['current_question_id']);
    $q_stmt->execute();
    $q_res = $q_stmt->get_result();
    if ($q_res->num_rows === 0) {
        $q_res = $conn->query("SELECT id FROM blitz_questions LIMIT 1");
    }
    $next_q_id = intval($q_res->fetch_assoc()['id']);
    
    $stmt = $conn->prepare("UPDATE blitz_rooms SET current_question_id = ?, selected_choice = NULL WHERE room_code = ?");
    $stmt->bind_param("is", $next_q_id, $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

// Action: End Game/Turn manually
if ($action === 'end_game') {
    $code = $_POST['room_code'] ?? '';
    
    // Fetch room final score and player ID to add global points
    $stmt = $conn->prepare("SELECT score, current_player_id FROM blitz_rooms WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
    
    if ($room && $room['current_player_id']) {
        $points = intval($room['score']) * 5; // e.g. 5 points per climb level
        if ($points > 0) {
            $u_stmt = $conn->prepare("UPDATE users SET score = score + ? WHERE id = ?");
            $u_stmt->bind_param("ii", $points, $room['current_player_id']);
            $u_stmt->execute();
        }
    }
    
    $stmt = $conn->prepare("UPDATE blitz_rooms SET game_status = 'ended', timer_running = 0, selected_choice = NULL WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

// Action: Reset room back to setup
if ($action === 'reset') {
    $code = $_POST['room_code'] ?? '';
    
    $stmt = $conn->prepare("UPDATE blitz_rooms SET game_status = 'setup', current_player_id = NULL, current_question_id = NULL, score = 0, seconds_remaining = 120, selected_choice = NULL, timer_running = 0 WHERE room_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

// Action: Leave room (Mobile player)
if ($action === 'leave') {
    $code = $_POST['room_code'] ?? '';
    
    $stmt = $conn->prepare("DELETE FROM blitz_players WHERE room_code = ? AND user_id = ?");
    $stmt->bind_param("si", $code, $userId);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
exit();
?>
