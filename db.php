<?php
$host = "127.0.0.1";
$username = "root"; // ปรับตามของวิทยาลัย (ถ้ามี)
$password = "";     // ปรับตามของวิทยาลัย (ถ้ามี)
$dbname = "club_game_db";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ================= AUTO MIGRATION FOR BLITZ QUIZ & ATTENDANCE =================
try {
    $conn->query("ALTER TABLE `users` ADD COLUMN `last_checkin` INT DEFAULT 0");
} catch (Exception $e) {}

$conn->query("CREATE TABLE IF NOT EXISTS `blitz_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `question_text` TEXT NOT NULL,
    `choices` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS `blitz_rooms` (
    `room_code` VARCHAR(10) PRIMARY KEY,
    `host_user_id` INT NOT NULL,
    `current_player_id` INT DEFAULT NULL,
    `current_question_id` INT DEFAULT NULL,
    `game_status` VARCHAR(50) DEFAULT 'setup',
    `score` INT DEFAULT 0,
    `seconds_remaining` INT DEFAULT 120,
    `selected_choice` VARCHAR(255) DEFAULT NULL,
    `timer_running` TINYINT(1) DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$conn->query("CREATE TABLE IF NOT EXISTS `blitz_players` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_code` VARCHAR(10) NOT NULL,
    `user_id` INT NOT NULL,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `room_user` (`room_code`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
?>