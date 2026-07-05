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
  `role` VARCHAR(50) DEFAULT 'member'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `games` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `game_name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `game_url` VARCHAR(255) NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'active'
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
('กาชาคัดออก', 'ตู้สไลด์สายพานสุ่มไฟกระพริบ 3 ใบสุดท้าย', 'games/gacha_v2.php', 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f', 'active');
