CREATE DATABASE IF NOT EXISTS `club_game_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `club_game_db`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `real_name` VARCHAR(255) DEFAULT NULL,
  `avatar_img` VARCHAR(255) DEFAULT NULL,
  `is_avatar_created` TINYINT(1) DEFAULT 0,
  `score` INT DEFAULT 0,
  `role` VARCHAR(50) DEFAULT 'member',
  `last_checkin` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `games` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `game_name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `game_url` VARCHAR(255) NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blitz_questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question_text` TEXT NOT NULL,
  `choices` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blitz_rooms` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blitz_players` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_code` VARCHAR(10) NOT NULL,
  `user_id` INT NOT NULL,
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `room_user` (`room_code`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password is 'admin')
INSERT INTO `users` (`username`, `email`, `password`, `real_name`, `avatar_img`, `is_avatar_created`, `score`, `role`)
VALUES ('admin', 'admin@clubgame.com', '$2y$10$MzMBrY0RbFILl9HR.9QTYeU9xUHa0xHGT9MVO.QVW8STJTjeOyXDG', 'Administrator', 'dog.png', 1, 0, 'admin')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Insert default games
INSERT INTO `games` (`game_name`, `description`, `game_url`, `image_url`, `status`)
VALUES 
('สุ่มภารกิจจับคู่', 'สับกองไพ่ทายปริศนาใบหน้ารุ่นพี่ ปวส.', 'games/senior_roulette/index.php', 'https://images.unsplash.com/photo-1614728263952-84ea256f9679', 'active'),
('สมรภูมิทายเพลง', 'ฟังเสียงท่อนฮุกออโต้จำกัดเวลาทายชื่อเพลง', 'games/senior_roulette/game_music.php', 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4', 'active'),
('ทายภาพอุปกรณ์', 'วิเคราะห์ภาพฮาร์ดแวร์ ทดสอบความไว', 'games/hardware_quiz/index.php', 'https://images.unsplash.com/photo-1518770660439-4636190af475', 'active'),
('กาชาคัดออก', 'ตู้สไลด์สายพานสุ่มไฟกระพริบ 3 ใบสุดท้าย', 'games/gacha_v2.php', 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f', 'active'),
('ปริศนาสายฟ้า', 'ตอบคำถามเก็บคะแนนสะสมไต่บันไดความสูง 10 ขั้น', 'games/blitz_quiz/index.php', 'https://images.unsplash.com/photo-1551818255-e6e10975bc17', 'active')
ON DUPLICATE KEY UPDATE `game_url`=`game_url`;

-- Insert default sample questions for Blitz Quiz
INSERT INTO `blitz_questions` (`question_text`, `choices`) VALUES
('ถ้าคุณเอาเหรียญ 5 บาท 100 เหรียญ ไปแลก แบงค์ 1,000 คุณจะได้ แบงค์ 1,000 มา กี่ใบ', '[\"10 ใบ\", \"2 ใบ\", \"5 ใบ\", \"1 ใบ\", \"หลายใบ\", \"ไม่ได้สักใบ\"]'),
('เดือนอะไรมี 28 วัน', '[\"มกราคม\", \"กุมภาพันธ์\", \"มีนาคม\", \"ทุกเดือน\", \"ไม่มีเลย\"]'),
('อะไรอยู่บนฟ้า แต่ตกน้ำแล้วไม่เปียก', '[\"เครื่องบิน\", \"นก\", \"ก้อนเมฆ\", \"เงาของก้อนเมฆ\", \"ดวงจันทร์\"]'),
('มีส้ม 10 ลูก กินไป 3 ลูก เหลือส้มกี่ลูก', '[\"3 ลูก\", \"7 ลูก\", \"10 ลูก\", \"0 ลูก\"]');

