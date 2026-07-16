<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Please login first."]);
    exit();
}

$action = $_GET['action'] ?? '';

// Modifying actions are strictly restricted to admin role
if ($action !== 'get_state') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(["status" => "error", "message" => "Unauthorized access. Admins only."]);
        exit();
    }
}


// Auto-create tables for Lightning Quiz
$conn->query("CREATE TABLE IF NOT EXISTS `lightning_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `question_text` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS `lightning_quiz_state` (
    `id` INT PRIMARY KEY,
    `current_question_id` INT DEFAULT 0,
    `current_level` INT DEFAULT 0,
    `timer_duration` INT DEFAULT 60,
    `timer_seconds` INT DEFAULT 60,
    `timer_running` TINYINT DEFAULT 0,
    `timer_sync_time` BIGINT DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Ensure state row exists
$checkState = $conn->query("SELECT id FROM lightning_quiz_state WHERE id = 1");
if ($checkState->num_rows === 0) {
    $conn->query("INSERT INTO lightning_quiz_state (id, current_question_id, current_level, timer_duration, timer_seconds) VALUES (1, 0, 0, 60, 60)");
}

// Seed default questions if empty
$checkQuestions = $conn->query("SELECT id FROM lightning_questions LIMIT 1");
if ($checkQuestions->num_rows === 0) {
    $conn->query("INSERT INTO lightning_questions (question_text) VALUES 
        ('สายแลน UTP ย่อมาจากอะไร?'),
        ('CPU ย่อมาจากอะไร?'),
        ('RAM คือหน่วยความจำหลักของเครื่องคอมพิวเตอร์ ใช่หรือไม่?'),
        ('1 Byte มีค่าเท่ากับกี่ Bit?'),
        ('โปรโตคอล HTTP และ HTTPS แตกต่างกันที่เรื่องใด?'),
        ('อุปกรณ์ที่ทำหน้าที่แปลงสัญญาณดิจิทัลเป็นอนาล็อกเพื่อรับส่งข้อมูล เรียกว่าอะไร?'),
        ('ที่อยู่อีเมลประกอบด้วยเครื่องหมายใดเป็นหลัก?')
    ");
}

if ($action === 'get_state') {
    // Get current state
    $state_res = $conn->query("SELECT * FROM lightning_quiz_state WHERE id = 1")->fetch_assoc();
    
    // Get all questions
    $questions_res = $conn->query("SELECT * FROM lightning_questions ORDER BY id ASC");
    $questions = [];
    while ($row = $questions_res->fetch_assoc()) {
        $questions[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "state" => [
            "current_question_id" => intval($state_res['current_question_id']),
            "current_level" => intval($state_res['current_level']),
            "timer_duration" => intval($state_res['timer_duration']),
            "timer_seconds" => intval($state_res['timer_seconds']),
            "timer_running" => intval($state_res['timer_running']),
            "timer_sync_time" => floatval($state_res['timer_sync_time'])
        ],
        "questions" => $questions
    ]);
    exit();
}

if ($action === 'update_level') {
    $type = $_POST['type'] ?? ''; // 'correct' or 'wrong' or 'reset'
    
    $state = $conn->query("SELECT current_level FROM lightning_quiz_state WHERE id = 1")->fetch_assoc();
    $curr = intval($state['current_level']);
    
    if ($type === 'correct') {
        $new_level = min(10, $curr + 1);
    } elseif ($type === 'wrong') {
        $new_level = 0; // Streak resets back to 0 on wrong answer
    } else {
        $new_level = 0;
    }
    
    $stmt = $conn->prepare("UPDATE lightning_quiz_state SET current_level = ? WHERE id = 1");
    $stmt->bind_param("i", $new_level);
    $stmt->execute();
    
    echo json_encode(["status" => "success", "new_level" => $new_level]);
    exit();
}

if ($action === 'set_question') {
    $q_id = intval($_POST['question_id'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE lightning_quiz_state SET current_question_id = ? WHERE id = 1");
    $stmt->bind_param("i", $q_id);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'timer_control') {
    $timer_action = $_POST['timer_action'] ?? ''; // 'start', 'pause', 'reset', 'set_duration'
    
    if ($timer_action === 'start') {
        $now_ms = round(microtime(true) * 1000);
        $stmt = $conn->prepare("UPDATE lightning_quiz_state SET timer_running = 1, timer_sync_time = ? WHERE id = 1");
        $stmt->bind_param("d", $now_ms);
        $stmt->execute();
    } elseif ($timer_action === 'pause') {
        $rem_sec = intval($_POST['remaining_seconds'] ?? 60);
        $stmt = $conn->prepare("UPDATE lightning_quiz_state SET timer_running = 0, timer_seconds = ? WHERE id = 1");
        $stmt->bind_param("i", $rem_sec);
        $stmt->execute();
    } elseif ($timer_action === 'reset') {
        $stmt = $conn->prepare("UPDATE lightning_quiz_state SET timer_running = 0, timer_seconds = timer_duration WHERE id = 1");
        $stmt->execute();
    } elseif ($timer_action === 'set_duration') {
        $dur = intval($_POST['duration'] ?? 60);
        $stmt = $conn->prepare("UPDATE lightning_quiz_state SET timer_duration = ?, timer_seconds = ? WHERE id = 1");
        $stmt->bind_param("ii", $dur, $dur);
        $stmt->execute();
    }
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'add_question') {
    $text = trim($_POST['question_text'] ?? '');
    if (empty($text)) {
        echo json_encode(["status" => "error", "message" => "คำถามห้ามว่าง"]);
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO lightning_questions (question_text) VALUES (?)");
    $stmt->bind_param("s", $text);
    $stmt->execute();
    
    echo json_encode(["status" => "success", "new_id" => $stmt->insert_id]);
    exit();
}

if ($action === 'edit_question') {
    $id = intval($_POST['id'] ?? 0);
    $text = trim($_POST['question_text'] ?? '');
    
    if (empty($text)) {
        echo json_encode(["status" => "error", "message" => "คำถามห้ามว่าง"]);
        exit();
    }
    
    $stmt = $conn->prepare("UPDATE lightning_questions SET question_text = ? WHERE id = ?");
    $stmt->bind_param("si", $text, $id);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'delete_question') {
    $id = intval($_POST['id'] ?? 0);
    
    $stmt = $conn->prepare("DELETE FROM lightning_questions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
    exit();
}

if ($action === 'reset_game') {
    $conn->query("UPDATE lightning_quiz_state SET current_level = 0, current_question_id = 0, timer_running = 0, timer_seconds = timer_duration WHERE id = 1");
    echo json_encode(["status" => "success"]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Invalid API Action"]);
exit();
