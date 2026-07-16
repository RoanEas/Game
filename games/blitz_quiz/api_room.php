<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/db.php';
session_start();

// Lock API to admin only, except poll_view which can be accessed by the player view screen
$action = $_GET['action'] ?? '';

if ($action !== 'poll_view') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(["status" => "error", "message" => "Unauthorized: Admins only"]);
        exit();
    }
}

// 1. Sync State (Admin calls this to sync timer, score, question choices, etc.)
if ($action === 'sync') {
    $score = intval($_POST['score'] ?? 0);
    $seconds = intval($_POST['seconds_remaining'] ?? 120);
    $running = intval($_POST['timer_running'] ?? 0);
    $question_id = intval($_POST['current_question_id'] ?? 0);
    $status = $_POST['game_status'] ?? 'setup';
    
    // Upsert into blitz_rooms
    $stmt = $conn->prepare("INSERT INTO blitz_rooms (room_code, host_user_id, current_question_id, game_status, score, seconds_remaining, timer_running) 
                            VALUES ('live', ?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                                current_question_id = ?, 
                                game_status = ?, 
                                score = ?, 
                                seconds_remaining = ?, 
                                timer_running = ?");
    $host_id = $_SESSION['user_id'];
    $stmt->bind_param("iisiiisiii", $host_id, $question_id, $status, $score, $seconds, $running, $question_id, $status, $score, $seconds, $running);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

// 2. Poll State (Player View screen calls this to render choices, scoring ladder, timer)
if ($action === 'poll_view') {
    $stmt = $conn->prepare("SELECT r.*, q.choices FROM blitz_rooms r 
                            LEFT JOIN blitz_questions q ON r.current_question_id = q.id 
                            WHERE r.room_code = 'live'");
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $row = $res->fetch_assoc()) {
        echo json_encode([
            "status" => "success",
            "score" => intval($row['score']),
            "seconds_remaining" => intval($row['seconds_remaining']),
            "timer_running" => intval($row['timer_running']),
            "game_status" => $row['game_status'],
            "choices" => $row['choices'] ? json_decode($row['choices'], true) : []
        ]);
    } else {
        // Return default setup state if no active room is stored
        echo json_encode([
            "status" => "success",
            "score" => 0,
            "seconds_remaining" => 120,
            "timer_running" => 0,
            "game_status" => "setup",
            "choices" => []
        ]);
    }
    exit();
}

// 3. Get random question (Admin calls this to fetch new questions)
if ($action === 'get_question') {
    $exclude_id = intval($_GET['exclude'] ?? 0);
    
    $stmt = $conn->prepare("SELECT id, question_text, choices FROM blitz_questions WHERE id != ? ORDER BY RAND() LIMIT 1");
    $stmt->bind_param("i", $exclude_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) {
        $res = $conn->query("SELECT id, question_text, choices FROM blitz_questions LIMIT 1");
    }
    
    if ($res && $row = $res->fetch_assoc()) {
        echo json_encode([
            "status" => "success",
            "question" => [
                "id" => $row['id'],
                "question_text" => $row['question_text'],
                "choices" => json_decode($row['choices'], true)
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "ไม่มีคำถามในระบบ กรุณาเพิ่มคำถามในระบบหลังบ้าน"]);
    }
    exit();
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
exit();
?>
