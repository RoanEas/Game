<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$username = isset($_SESSION['username']) ? $_SESSION['username'] : "admin1";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>คลังข้อมูลกลาง - Cyber Dashboard</title>
    <link href="dashboard-style.css" rel="stylesheet" type="text/css">
</head>
<body>

<div class="dashboard-wrapper">

    <header class="dash-header">
        <div class="welcome-zone">
            <span class="badge">ระบบจัดการข้อมูลกลาง</span>
            <h1>ยินดีต้อนรับ, <?php echo htmlspecialchars($username); ?></h1>
            <p>ภาพรวมสถานะการทดสอบและสถาปัตยกรรมคลังอุปกรณ์ของคุณในปัจจุบัน</p>
        </div>
    </header>

    <main class="bento-grid">
        
        <div class="bento-item" style="background: linear-gradient(180deg, #0b0b12 0%, #1a0610 100%); border-color: rgba(255, 0, 85, 0.15);">
            <div>
                <h3>🎮 ปฏิบัติการจำลองระบบ</h3>
                <h2 style="font-size: 1.4rem; font-weight: 600; margin-top: 12px; line-height: 1.4; letter-spacing: -0.3px;">
                    แบบทดสอบ Hardware Identification Module
                </h2>
                <p style="color: var(--text-dim); font-weight: 300; margin-top: 8px; font-size: 0.88rem; line-height: 1.5;">
                    เริ่มต้นการจับคู่ วิเคราะห์ และคัดกรองชุดอุปกรณ์คอมพิวเตอร์ผ่าน Bento สปีดจำลอง 10 วินาทีบีบหัวใจ
                </p>
            </div>
            
            <div class="btn-glow-container">
                <a href="games/hardware_quiz/index.php" class="btn-launch">เข้าสู่โมดูลการทดสอบ ➔</a>
            </div>
        </div>

        <div class="bento-item">
            <h3>🏆 คะแนนสูงสุดของคุณ</h3>
            <div>
                <div class="huge-number" style="color: var(--neon-cyan); text-shadow: 0 0 25px rgba(0, 240, 255, 0.4);">100</div>
                <div class="sub-info">อัปเดตล่าสุด: ประเมินผลผ่านเกณฑ์คัดกรองสมบูรณ์แบบ</div>
            </div>
        </div>

        <div class="bento-item">
            <h3>⚡ สถานะเซิร์ฟเวอร์</h3>
            <div>
                <div class="huge-number" style="font-size: 2.3rem; font-weight: 700; color: #ffffff; letter-spacing: 1px;">ONLINE</div>
                <div class="sub-info" style="color: var(--neon-green);">
                    <span style="width: 6px; height: 6px; background: var(--neon-green); border-radius: 50%; display: inline-block; box-shadow: 0 0 8px var(--neon-green);"></span>
                    เชื่อมต่อฐานข้อมูล MySQL สำเร็จ
                </div>
            </div>
        </div>

        <div class="bento-item">
            <h3>📦 อุปกรณ์ในระบบ</h3>
            <div>
                <div class="huge-number">10</div>
                <div class="sub-info">ไอเทมหลักระบบโครงสร้างคอมพิวเตอร์</div>
            </div>
        </div>

        <div class="bento-item">
            <h3>⏱️ เวลาจำกัดต่อข้อ</h3>
            <div>
                <div class="huge-number" style="color: var(--neon-pink); text-shadow: 0 0 25px rgba(255, 0, 85, 0.4);">10s</div>
                <div class="sub-info">ระบบนับถอยหลังตรวจวินาทีวิกฤตกลางหน้าจอ</div>
            </div>
        </div>

    </main>

    <nav class="floating-tabbar">
        <a href="#" class="tab-item active">หน้าหลักแผงควบคุม</a>
        <a href="games/hardware_quiz/index.php" class="tab-item">เข้าเล่นเกมคำถาม</a>
        <a href="logout.php" class="tab-item" style="color: var(--neon-pink);">ออกจากระบบ</a>
    </nav>

</div>

</body>
</html>