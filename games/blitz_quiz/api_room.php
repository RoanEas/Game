<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/db.php';
session_start();

// Lock API to admin only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized: Admins only"]);
    exit();
}

$action = $_GET['action'] ?? '';

// 1. List registered players
if ($action === 'list_players') {
    $stmt = $conn->prepare("SELECT id, username, real_name, avatar_img, score FROM users WHERE role = 'member' ORDER BY real_name ASC");
    $stmt->execute();
    $res = $stmt->get_result();
    
    $players = [];
    while ($row = $res->fetch_assoc()) {
        $players[] = [
            "id" => $row['id'],
            "username" => $row['username'],
            "real_name" => $row['real_name'] ?? $row['username'],
            "avatar" => $row['avatar_img'] ?? 'dog.png',
            "score" => intval($row['score'])
        ];
    }
    echo json_encode(["status" => "success", "players" => $players]);
    exit();
}

// 2. Get a random question
if ($action === 'get_question') {
    $exclude_id = intval($_GET['exclude'] ?? 0);
    
    $stmt = $conn->prepare("SELECT id, question_text, choices FROM blitz_questions WHERE id != ? ORDER BY RAND() LIMIT 1");
    $stmt->bind_param("i", $exclude_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) {
        // fallback if only 1 question exists in database
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

// 3. Save player's score
if ($action === 'save_score') {
    $player_id = intval($_POST['player_id'] ?? 0);
    $climb_score = intval($_POST['score'] ?? 0); // how many correct answers (0-10)
    
    if ($player_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid player ID"]);
        exit();
    }
    
    // Define score multiplier: e.g. 5 points per step, or 100 points bonus if they reach 10
    $points_earned = $climb_score * 5;
    if ($climb_score >= 10) {
        $points_earned = 100; // Bonus for reaching level 10
    }
    
    if ($points_earned > 0) {
        $stmt = $conn->prepare("UPDATE users SET score = score + ? WHERE id = ?");
        $stmt->bind_param("ii", $points_earned, $player_id);
        $stmt->execute();
    }
    
    echo json_encode(["status" => "success", "points_earned" => $points_earned]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);
exit();
?>
