<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}
require_once dirname(__DIR__, 2) . '/db.php';
// Auto-create database table if not exists
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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>📱 เกมทายคำบนหัว — Heads Up Cyber</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;700&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="style.css?v=<?=time();?>">
</head>
<body>

<div class="game-container">

    <!-- HEADER HUD -->
    <header class="game-header">
        <a href="../../index.php" class="back-btn">
            <ion-icon name="chevron-back-outline"></ion-icon>
            กลับหน้าแรก
        </a>
        <h1 class="hud-title">HEADS UP CYBER</h1>
        <div style="display: flex; align-items: center; gap: 8px;">
            <button class="back-btn" onclick="toggleTheme()" style="border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; padding: 0;" title="สลับธีม (iPhone / Cyberpunk)">
                <ion-icon name="color-palette-outline" id="theme-icon" style="font-size: 1.2rem;"></ion-icon>
            </button>
            <button class="back-btn" onclick="toggleFullscreen()" style="border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; padding: 0;" title="ขยายจอเต็ม (F11)">
                <ion-icon name="expand-outline" id="fs-icon" style="font-size: 1.2rem;"></ion-icon>
            </button>
        </div>
    </header>

    <!-- 1. CATEGORY SCREEN -->
    <div id="screen-category" class="category-screen">
        <div class="screen-title">เลือกหมวดหมู่คำใบ้</div>
        <div class="screen-desc">เลือกหมวดหมู่ที่ต้องการทายคำ แล้วเตรียมตัวถือโทรศัพท์ขึ้นแนบหน้าผาก</div>
        <div class="categories-grid" id="categories-list"></div>
    </div>

    <!-- 2. SETUP / CALIBRATION SCREEN -->
    <div id="screen-setup" class="setup-screen" style="display: none;">
        <div class="screen-title" id="selected-category-title">หมวดหมู่</div>

        <!-- Taboo Multiplayer Lobby Block -->
        <div id="taboo-lobby-container" style="width: 100%; display: none; flex-direction: column; gap: 16px; margin-bottom: 12px; align-items: center;">
            <!-- Step A: Profile preview + Create/Join Room -->
            <div id="taboo-setup-entry" style="width: 100%; display: flex; flex-direction: column; gap: 14px; padding: 18px; border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border);">
                <div style="font-size: 0.9rem; font-weight: 800; color: var(--text-main); text-align: center;">🎮 ปาร์ตี้คำห้ามพูด 4 เครื่อง (Taboo Multiplayer)</div>

                <!-- Profile Card from session -->
                <div style="display:flex;align-items:center;gap:14px;padding:12px 16px;border-radius:16px;background:rgba(255,255,255,0.04);border:1px solid var(--card-border);">
                    <?php
                        $avatarSrc = '../../assets/avatar/' . htmlspecialchars($_SESSION['avatar_img'] ?? 'dog.png');
                        $displayName = htmlspecialchars($_SESSION['real_name'] ?? $_SESSION['username'] ?? 'ผู้เล่น');
                    ?>
                    <img src="<?= $avatarSrc ?>" alt="avatar" style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--accent-cyan);flex-shrink:0;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:800;font-size:1rem;color:var(--text-main);"><?= $displayName ?></div>
                        <div style="font-size:0.75rem;color:var(--accent-cyan);margin-top:2px;">@<?= htmlspecialchars($_SESSION['username'] ?? '') ?></div>
                        <div style="font-size:0.7rem;color:var(--text-muted);margin-top:2px;">✅ เข้าสู่ระบบแล้ว</div>
                    </div>
                    <ion-icon name="checkmark-circle" style="color:#30d158;font-size:1.4rem;flex-shrink:0;"></ion-icon>
                </div>

                <!-- Hidden name input (populated from session via JS) -->
                <input type="hidden" id="taboo-player-name" value="<?= $displayName ?>">

                <div style="display: flex; gap: 8px; margin-top: 2px;">
                    <button class="btn-primary" onclick="createTabooRoom()" style="flex: 1; padding: 12px; font-size: 0.8rem; border-radius: 12px; box-shadow: none;">สร้างห้องใหม่</button>
                    <div style="flex: 1.2; display: flex; gap: 4px;">
                        <input type="tel" id="taboo-room-code" maxlength="4" placeholder="รหัสห้อง 4 หลัก" style="width: 100%; padding: 0 8px; border-radius: 12px; background: rgba(255,255,255,0.04); border: 1px solid var(--card-border); color: #fff; text-align: center; font-weight: bold; font-size: 0.8rem;">
                        <button class="btn-primary" onclick="joinTabooRoom()" style="padding: 0 12px; font-size: 0.8rem; border-radius: 12px; box-shadow: none;">เข้าร่วม</button>
                    </div>
                </div>
            </div>
            
            <!-- Step B: Lobby / Waiting Room -->
            <div id="taboo-lobby-waiting" style="width: 100%; display: none; flex-direction: column; gap: 14px; padding: 18px; border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border);">
                <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--card-border); padding-bottom: 10px;">
                    <div style="font-size: 0.95rem; font-weight: 800; color: var(--text-main);" id="taboo-room-title">ห้อง: 0000</div>
                    <button class="time-opt-btn" onclick="exitTabooRoom()" style="padding: 6px 12px; font-size: 0.75rem; max-width: 90px; border-radius: 8px;">ออกห้อง ❌</button>
                </div>
                
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 4px;">
                    <ion-icon name="people-outline" style="color: var(--accent-cyan);"></ion-icon>
                    <span>รายชื่อคนที่เข้ามารออยู่ด้านล่าง:</span>
                </div>
                
                <!-- Players waiting list -->
                <div id="taboo-waiting-players-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 180px; overflow-y: auto; width: 100%;">
                    <!-- Dynamically populated -->
                </div>
                
                <!-- Actions -->
                <div style="margin-top: 6px; width: 100%;">
                    <!-- Toggle Ready for clients -->
                    <button class="btn-primary" id="taboo-ready-btn" onclick="toggleTabooReady()" style="width: 100%; border-radius: 12px; padding: 14px; font-size: 0.9rem; display: none; box-shadow: none;">
                        <ion-icon name="checkmark-done-circle-outline" style="font-size: 1.15rem; vertical-align: middle; margin-right: 4px;"></ion-icon>
                        กดปุ่มพร้อม 👍
                    </button>
                    <!-- Start game for hosts -->
                    <button class="btn-primary" id="taboo-start-btn" onclick="startTabooGame()" style="width: 100%; border-radius: 12px; padding: 14px; font-size: 0.9rem; display: none; background: #30d158; color: #000; border-color: #30d158; box-shadow: none;">
                        <ion-icon name="play-circle-outline" style="font-size: 1.15rem; vertical-align: middle; margin-right: 4px;"></ion-icon>
                        เริ่มเล่นเกม 🚀
                    </button>
                </div>
            </div>
        </div>

        <div class="instruction-card" id="standard-instruction-card">
            <div class="instruction-step">
                <span class="step-num">1</span>
                <span>หันหน้าจอโทรศัพท์ออกหาเพื่อน (เพื่อนจะมองเห็นคำศัพท์)</span>
            </div>
            <div class="instruction-step">
                <span class="step-num">2</span>
                <span>ทาบโทรศัพท์ไว้ที่**หน้าผาก** โดยห้ามแอบมองหน้าจอเด็ดขาด!</span>
            </div>
            <div class="instruction-step">
                <span class="step-num">3</span>
                <span>เงยหน้าขึ้น (หงายจอขึ้น) = **ถูกต้อง** ✅ | ก้มหัวลง (คว่ำจอลง) = **ข้าม** ❌</span>
            </div>
            <div class="instruction-step" style="border-top: 1px solid var(--card-border); padding-top: 12px; font-size: 0.8rem; color: var(--accent-cyan); text-align: center; justify-content: center;">
                <ion-icon name="shield-checkmark-outline"></ion-icon>
                <span>ระบบจะขอสิทธิ์เซนเซอร์เอียงหน้าจอ (ถ้ามี)</span>
            </div>
        </div>
        <!-- Setup Options Container -->
        <div id="setup-options-container" style="width: 100%;">
            <!-- Time Config Option -->
            <div id="time-selector-block" style="width: 100%; margin-top: 14px; margin-bottom: 12px; display: flex; flex-direction: column; gap: 8px;">
                <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 6px;">
                    <ion-icon name="time-outline" style="color: var(--text-muted); font-size: 1.05rem;"></ion-icon>
                    <span>เลือกเวลาในการเล่น:</span>
                </div>
                <div style="display: flex; gap: 8px; width: 100%;">
                    <button class="time-opt-btn" onclick="setRoundTime(30)" id="time-opt-30">30 วิ</button>
                    <button class="time-opt-btn active" onclick="setRoundTime(60)" id="time-opt-60">1 นาที</button>
                    <button class="time-opt-btn" onclick="setRoundTime(90)" id="time-opt-90">1.5 นาที</button>
                    <button class="time-opt-btn" onclick="setRoundTime(120)" id="time-opt-120">2 นาที</button>
                </div>
            </div>

            <!-- Player Names Input (for taboo/forbidden words) -->
            <div id="player-names-block" style="width: 100%; margin-top: 14px; margin-bottom: 12px; display: none; flex-direction: column; gap: 8px;">
                <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 6px;">
                    <ion-icon name="people-outline" style="color: var(--text-muted); font-size: 1.05rem;"></ion-icon>
                    <span>รายชื่อผู้เล่น (คั่นด้วยเครื่องหมายจุลภาค , ):</span>
                </div>
                <input type="text" id="player-names-input" value="ผู้เล่น 1, ผู้เล่น 2, ผู้เล่น 3, ผู้เล่น 4" style="width: 100%; padding: 12px 16px; border-radius: 12px; background: var(--card-bg); border: 1px solid var(--card-border); color: #fff; font-size: 0.9rem;" placeholder="สมชาย, สมหญิง, สมศักดิ์">
            </div>
        </div>

        <button class="btn-primary" onclick="requestPermissionAndStart()" id="standard-start-btn" style="width: 100%; margin-top: 10px;">
            <ion-icon name="play-outline" style="font-size: 1.2rem; vertical-align: middle; margin-right: 6px;"></ion-icon>
            เริ่มเล่นเกมคำนี้
        </button>
    </div>

    <!-- 3. COUNTDOWN SCREEN OVERLAY -->
    <div id="screen-countdown" class="countdown-overlay" style="display: none;">
        <div class="countdown-num" id="countdown-timer">3</div>
    </div>

    <!-- 4. PLAY SCREEN -->
    <div id="screen-play" class="play-screen" style="display: none;">
        <div class="game-hud">
            <!-- Quit/Exit Button -->
            <button onclick="exitGameToCategories()" style="background: rgba(255, 255, 255, 0.08); border: 1px solid var(--card-border); color: var(--text-main); padding: 8px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; z-index: 9999; position: relative; pointer-events: auto;" title="กลับหน้าเลือกหมวดหมู่">
                <ion-icon name="arrow-back-outline" style="font-size: 1.05rem;"></ion-icon>
                <span>ออก</span>
            </button>
            
            <div class="hud-score">
                <ion-icon name="trophy-outline" style="color: var(--accent-cyan);"></ion-icon>
                คะแนน: <span id="current-score">0</span>
            </div>
            <div class="hud-timer" id="timer-hud">
                <ion-icon name="time-outline"></ion-icon>
                เวลา: <span id="game-time">60</span> วินาที
            </div>
            <!-- Manual exit button for untimed modes (Forbidden Words) -->
            <button id="end-game-btn" onclick="endGame()" style="display: none; padding: 8px 16px; border-radius: 12px; background: rgba(255, 69, 58, 0.15); border: 1px solid rgba(255, 69, 58, 0.3); color: #ff453a; font-size: 0.8rem; font-weight: 700; cursor: pointer; align-items: center; justify-content: center; gap: 4px;">
                <ion-icon name="stop-circle-outline" style="font-size: 1.15rem; vertical-align: middle;"></ion-icon>
                <span>จบเกม</span>
            </button>
        </div>

        <div class="word-card-container">
            <div class="word-card" id="card-box">
                <span class="word-sub" id="card-category-label">CATEGORY</span>
                <div class="word-text" id="card-word">LOADING...</div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 15px; display: flex; flex-direction: column; align-items: center; gap: 4px;" id="tilt-indicator">
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <ion-icon name="phone-portrait-outline"></ion-icon>
                        <span>พร้อมเอียงหน้าจอเพื่อเริ่มควบคุมคำศัพท์</span>
                    </div>
                    <div id="tilt-debug" style="font-size: 0.7rem; color: var(--accent-cyan); font-family: monospace;">B: 0 (0) | G: 0 (0)</div>
                    <span onclick="toggleManualButtons()" style="font-size: 0.75rem; color: var(--accent-purple); cursor: pointer; text-decoration: underline; margin-top: 8px; z-index: 100; pointer-events: auto;">เปิด/ปิดปุ่มกดสัมผัสสำรอง</span>
                </div>
            </div>
        </div>

        <!-- Touch Controls Fallback -->
        <div class="fallback-actions" id="fallback-buttons" style="display: none; padding: 0 24px; margin-top: -20px; margin-bottom: 20px; z-index: 12; width: 100%;">
            <button class="action-btn btn-pass" onclick="triggerPass()">
                <ion-icon name="close-circle-outline"></ion-icon>
                ข้าม (Pass)
            </button>
            <button class="action-btn btn-correct" onclick="triggerCorrect()">
                <ion-icon name="checkmark-circle-outline"></ion-icon>
                ถูก (Correct)
            </button>
        </div>
    </div>
    </div>

    <!-- 5. RESULTS SCREEN -->
    <div id="screen-results" class="result-screen" style="display: none;">
        <div class="screen-title" style="margin-bottom: 5px;">สิ้นสุดการทายคำ!</div>
        
        <!-- Compact Score & Rank Row -->
        <div class="summary-stats-row">
            <div class="stat-card">
                <span class="stat-label">ทายถูกต้อง</span>
                <span class="stat-value" id="final-score">0</span>
                <span class="stat-sub">คะแนน</span>
            </div>
            <div class="stat-card">
                <span class="stat-label">ระดับฝีมือ</span>
                <span class="stat-value-rank" id="rank-name">ROOKIE</span>
                <span class="stat-sub">Rank</span>
            </div>
        </div>

        <div class="summary-title-bar">
            <div class="summary-title">
                <ion-icon name="list-outline" style="color: var(--accent-cyan);"></ion-icon>
                <span>สรุปประวัติการทายคำศัพท์</span>
            </div>
            <span class="summary-count" id="summary-count">ทายทั้งหมด 0 คำ</span>
        </div>
        
        <div class="words-summary-list" id="summary-items"></div>

        <div class="result-actions">
            <button class="btn-secondary" onclick="backToCategories()">
                <ion-icon name="home-outline" style="font-size: 1.15rem; vertical-align: middle; margin-right: 4px;"></ion-icon>
                หมวดหมู่
            </button>
            <button class="btn-primary" onclick="startGameplayWithSameCat()">
                <ion-icon name="refresh-outline" style="font-size: 1.25rem; vertical-align: middle; margin-right: 6px;"></ion-icon>
                เล่นอีกรอบ
            </button>
        </div>
    </div>

</div>

<script>
// ═══════════════════════════════════════════════════════════════
//  👤 SESSION DATA (from PHP login)
// ═══════════════════════════════════════════════════════════════
const SESSION_NAME = <?= json_encode($_SESSION['real_name'] ?? $_SESSION['username'] ?? 'ผู้เล่น') ?>;
const SESSION_AVATAR = <?= json_encode('../../assets/avatar/' . ($_SESSION['avatar_img'] ?? 'dog.png')) ?>;
const SESSION_USERNAME = <?= json_encode($_SESSION['username'] ?? '') ?>;

// ═══════════════════════════════════════════════════════════════
//  🎮 WORD DATABASE & CATEGORIES
// ═══════════════════════════════════════════════════════════════
const categories = [
    {
        id: "hardware",
        title: "อุปกรณ์คอมพิวเตอร์",
        desc: "CPU, RAM, การ์ดจอ และอุปกรณ์ฮาร์ดแวร์ไอทีต่างๆ",
        icon: "hardware-chip-outline",
        words: ["CPU (ซีพียู)", "RAM (แรม)", "การ์ดจอ (GPU)", "ฮาร์ดดิสก์ (HDD)", "SSD (เอสเอสดี)", "เมนบอร์ด (Motherboard)", "พาวเวอร์ซัพพลาย (Power Supply)", "เมาส์ (Mouse)", "คีย์บอร์ด (Keyboard)", "จอภาพ (Monitor)", "เครื่องพิมพ์ (Printer)", "เราเตอร์ (Router)", "แฟลชไดรฟ์ (USB Drive)", "การ์ดเสียง (Sound Card)", "พัดลมระบายอากาศ (Case Fan)", "เครื่องสำรองไฟ (UPS)", "หูฟังไอที (Headphones)", "สายแลน (LAN Cable)", "ซิลิโคนระบายความร้อน (Thermal Paste)", "ฮีทซิงค์ (Heatsink)", "เครื่องสแกนลายนิ้วมือ", "เว็บแคม (Webcam)", "ลำโพงคอมพิวเตอร์", "การ์ดแลน (LAN Card)", "เมโมรี่การ์ด (SD Card)", "แผ่นรองเมาส์ (Mousepad)", "เคสคอมพิวเตอร์ (Case)", "ไดรฟ์ดีวีดี (DVD Drive)", "สาย HDMI", "แว่น VR (Virtual Reality)", "พล็อตเตอร์ (Plotter)", "จอยสติ๊ก (Joystick)", "ไมโครโฟนคอมพิวเตอร์", "สาย DisplayPort", "บลูทูธดองเกิล (Dongle)", "สวิตช์เครือข่าย (Network Switch)", "ออปติคอลไดรฟ์ (Optical Drive)", "เครื่องสแกนบาร์โค้ด", "การ์ดจับภาพ (Capture Card)", "ฮาร์ดดิสก์พกพา (External HDD)"]
    },
    {
        id: "it_terms",
        title: "คำศัพท์ไอที",
        desc: "ไวไฟ, บั๊ก, ไวรัส, แฮกเกอร์ และศัพท์เทคโนโลยีคอมพิวเตอร์",
        icon: "globe-outline",
        words: ["อินเทอร์เน็ต (Internet)", "ไวไฟ (Wi-Fi)", "คลาวด์ (Cloud)", "แฮกเกอร์ (Hacker)", "ไวรัสคอมพิวเตอร์ (Virus)", "บั๊ก (Bug)", "ฐานข้อมูล (Database)", "ปัญญาประดิษฐ์ (AI)", "แอปพลิเคชัน (Application)", "เว็บไซต์ (Website)", "เบราว์เซอร์ (Browser)", "ไฟร์วอลล์ (Firewall)", "บล็อกเชน (Blockchain)", "อีเมล (Email)", "แชทบอท (Chatbot)", "ซอฟต์แวร์ (Software)", "แอนดรอยด์ (Android)", "รหัสผ่าน (Password)", "ระบบปฏิบัติการ (OS)", "ยูทูป (YouTube)", "เซิร์ฟเวอร์ (Server)", "บิตคอยน์ (Bitcoin)", "เมตาเวิร์ส (Metaverse)", "ไซเบอร์ซีคิวริตี้ (Cyber Security)", "บิ๊กดาต้า (Big Data)", "ดาวน์โหลด (Download)", "อัปโหลด (Upload)", "เครือข่ายสังคมออนไลน์ (Social Media)", "อัลกอริทึม (Algorithm)", "ไอพีแอดเดรส (IP Address)", "โดเมนเนม (Domain Name)", "แอปเปิ้ล (Apple iOS)", "ไมโครซอฟต์วินโดวส์", "อีคอมเมิร์ซ (E-commerce)", "สมาร์ทโฟน (Smartphone)", "ไฟล์แนบ (Attachment)", "สมาร์ทโฮม (Smart Home)", "ดิจิทัลฟุตพริ้นท์ (Digital Footprint)", "การเข้ารหัสข้อมูล (Encryption)", "ข้อมูลส่วนตัว (Privacy)"]
    },
    {
        id: "coding",
        title: "ภาษาเขียนโปรแกรม",
        desc: "Python, PHP, HTML, JavaScript และภาษาโค้ดดิ้ง",
        icon: "code-slash-outline",
        words: ["Python (ไพธอน)", "PHP (พีเอชพี)", "HTML (เอชทีเอ็มแอล)", "CSS (ซีเอสเอส)", "JavaScript (จาวาสคริปต์)", "Java (จาวา)", "C++ (ซีพลัสพลัส)", "Swift (สวิฟต์)", "SQL (เอสคิวแอล)", "Kotlin (คอตลิน)", "Ruby (รูบี้)", "TypeScript (ไทป์สคริปต์)", "C# (ซีชาร์ป)", "Dart (ดาร์ท)", "Golang (โกแลง)", "Rust (รัสต์)", "Scala (สกาลา)", "Perl (เพิร์ล)", "R Language (ภาษาอาร์)", "Objective-C", "MATLAB (แมทแล็บ)", "Fortran (ฟอร์แทรน)", "Assembly (แอสเซมบลี)", "COBOL (โคบอล)", "Visual Basic", "PowerShell (พาวเวอร์เชลล์)", "Bash Script", "Sass (แซส)", "LaTeX (ลาเท็กซ์)", "JSON (เจสัน)", "XML (เอ็กซ์เอ็มแอล)", "Markdown (มาร์กดาวน์)", "Git (กิต)", "Docker (ด็อกเกอร์)", "Node.js (โหนดเจเอส)", "React (รีแอกต์)", "Vue.js (วิวเจเอส)", "Angular (แองกูลาร์)", "Laravel (ลาลาเวล)", "Flutter (ฟลัตเตอร์)"]
    },
    {
        id: "foods",
        title: "ของกิน / อาหาร",
        desc: "หมูกระทะ, ชาบู, พิซซ่า, ชานม และเมนูยอดฮิต",
        icon: "pizza-outline",
        words: ["หมูกระทะ", "ชาบู", "ส้มตำ", "ต้มยำกุ้ง", "กะเพราไข่ดาว", "พิซซ่า", "ซูชิ", "แฮมเบอร์เกอร์", "ไก่ทอด", "ชานมไข่มุก", "กาแฟร้อน", "ข้าวเหนียวมะม่วง", "ทุเรียน", "ต้มข่าไก่", "ผัดไทย", "ข้าวมันไก่", "น้ำตกหมู", "ลาบหมู", "ต้มจืดเต้าหู้หมูสับ", "แกงเขียวหวานไก่", "ก๋วยเตี๋ยวเรือ", "บะหมี่กึ่งสำเร็จรูป", "สเต๊กเนื้อ", "สลัดผัก", "เฟรนช์ฟรายส์", "โดนัท", "ไอศกรีม", "เค้กช็อกโกแลต", "ขนมปังปิ้ง", "น้ำมะพร้าว", "โค้กใส่น้ำแข็ง", "ชาเขียวมัทฉะ", "น้ำส้มคั้น", "ไข่เจียวหมูสับ", "แกงส้มชะอมกุ้ง", "หอยทอด", "ผัดซีอิ๊ว", "ข้าวยำปักษ์ใต้", "น้ำพริกกะปิ", "ข้าวผัดปู"]
    },
    {
        id: "animals",
        title: "สัตว์โลกน่ารัก",
        desc: "สุนัข, แมว, สิงโต, ช้าง, ยีราฟ และสัตว์นานาชนิด",
        icon: "paw-outline",
        words: ["สุนัข (หมา)", "แมว", "สิงโต", "ช้าง", "ยีราฟ", "ลิง", "เสือ", "ม้าลาย", "เพนกวิน", "แพนด้า", "จระเข้", "นกอินทรี", "ปลาหมึก", "เต่า", "ฉลาม", "โลมา", "กระต่าย", "จิงโจ้", "หมูแคระ", "นกแก้ว", "โคอาล่า", "สลอธ", "นกฟลามิงโก", "แรคคูน", "จิ้งจอก", "เป็ด", "ไก่", "หมีขั้วโลก", "วาฬเพชฌฆาต", "แมวน้ำ", "ชะนี", "เม่น", "กบ", "แมงกะพรุน", "กระรอก", "หนูแฮมสเตอร์", "ม้า", "ควาย", "นกยูง", "ผีเสื้อ"]
    },
    {
        id: "forbidden_words",
        title: "คำห้ามพูด (Taboo)",
        desc: "ห้ามพูดคำเหล่านี้เด็ดขาด! ห้ามเผลอพูดเป็นคำพูดติดปาก",
        icon: "ban-outline",
        words: ["ใช่", "ไม่ใช่", "เอ่อ...", "ครับ / ค่ะ", "จริงดิ", "ร้อน", "หิว", "นอน", "ไป", "กิน", "ทำไม", "อะไร", "เมื่อไหร่", "บ้า", "ชอบ", "รัก", "โอเค (OK)", "คิด", "พูด", "ดู", "เจ็บ", "เหนื่อย", "สวย", "หล่อ", "แพง", "ง่าย", "ยาก", "สนุก", "ซื้อ", "ขาย", "หยุด", "เดิน", "วิ่ง", "หัวเราะ", "ร้องไห้", "กลัว", "เบื่อ", "ชื่อ", "บ้าน", "โทรศัพท์", "พี่ / น้อง", "เพื่อน", "เงิน", "ข้าว"]
    }
];

// ═══════════════════════════════════════════════════════════════
//  🔊 WEB AUDIO SYNTHESIZER ENGINE
// ═══════════════════════════════════════════════════════════════
let audioCtx = null;

function initAudio() {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === 'suspended') audioCtx.resume();
}

function playCorrectTone() {
    initAudio();
    if (!audioCtx) return;
    const now = audioCtx.currentTime;
    
    // Low to high double beep
    const osc1 = audioCtx.createOscillator();
    const gain1 = audioCtx.createGain();
    osc1.type = 'sine';
    osc1.frequency.setValueAtTime(523.25, now); // C5
    osc1.frequency.setValueAtTime(659.25, now + 0.08); // E5
    
    gain1.gain.setValueAtTime(0.08, now);
    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
    
    osc1.connect(gain1);
    gain1.connect(audioCtx.destination);
    osc1.start(now);
    osc1.stop(now + 0.3);
}

function playPassTone() {
    initAudio();
    if (!audioCtx) return;
    const now = audioCtx.currentTime;
    
    // Downwards buzz tone
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = 'sawtooth';
    osc.frequency.setValueAtTime(220, now); // A3
    osc.frequency.exponentialRampToValueAtTime(110, now + 0.25);
    
    gain.gain.setValueAtTime(0.06, now);
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
    
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.start(now);
    osc.stop(now + 0.25);
}

function playTickTone() {
    initAudio();
    if (!audioCtx) return;
    const now = audioCtx.currentTime;
    
    // Short high woodblock sound
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = 'triangle';
    osc.frequency.setValueAtTime(1000, now);
    
    gain.gain.setValueAtTime(0.05, now);
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.05);
    
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.start(now);
    osc.stop(now + 0.05);
}

function playUrgentBeepTone() {
    initAudio();
    if (!audioCtx) return;
    const now = audioCtx.currentTime;
    
    // Urgent double high-frequency beep
    const osc1 = audioCtx.createOscillator();
    const gain1 = audioCtx.createGain();
    osc1.type = 'sine';
    osc1.frequency.setValueAtTime(1500, now);
    
    gain1.gain.setValueAtTime(0.12, now);
    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
    
    osc1.connect(gain1);
    gain1.connect(audioCtx.destination);
    osc1.start(now);
    osc1.stop(now + 0.08);
    
    setTimeout(() => {
        if (!audioCtx) return;
        const now2 = audioCtx.currentTime;
        const osc2 = audioCtx.createOscillator();
        const gain2 = audioCtx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(1500, now2);
        
        gain2.gain.setValueAtTime(0.12, now2);
        gain2.gain.exponentialRampToValueAtTime(0.001, now2 + 0.08);
        
        osc2.connect(gain2);
        gain2.connect(audioCtx.destination);
        osc2.start(now2);
        osc2.stop(now2 + 0.08);
    }, 120);
}

function playGameOverFanfare() {
    initAudio();
    if (!audioCtx) return;
    const now = audioCtx.currentTime;
    
    const chords = [523.25, 659.25, 783.99, 1046.50]; // C Major chord arpeggio
    chords.forEach((freq, index) => {
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, now + index * 0.1);
        
        gain.gain.setValueAtTime(0, now);
        gain.gain.linearRampToValueAtTime(0.05, now + index * 0.1 + 0.05);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 1.2);
        
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start(now + index * 0.1);
        osc.stop(now + 1.5);
    });
}

// ═══════════════════════════════════════════════════════════════
//  🕹️ GAME LOGIC & STATE
// ═══════════════════════════════════════════════════════════════
let activeCategory = null;
let wordPool = [];
let currentIndex = 0;
let score = 0;
let secondsRemaining = 60;
let customRoundDuration = 60;
let gameInterval = null;
let isSensorActive = false;
let tiltCooldown = false;
let roundRecords = []; // tracks {word, status: 'yes'|'no'}
let baseGz = null;
let smoothGz = null;          // EMA-smoothed Gz value
let warmupFrames = 0;         // frames to wait before enabling triggers
let sensorLocked = false;
let sensorReceived = false;
let sensorCheckTimeout = null;
let isSensorListenerAdded = false;

// Multiplayer Sync State
let isHost = false;
let roomCode = null;
let pollInterval = null;

// EMA smoothing factor: lower = smoother but slower response
// 0.15 gives a ~7-frame rolling average (good for head motion)
const EMA_ALPHA = 0.15;
const TRIGGER_THRESHOLD = 0.5;    // must exceed this to fire
const NEUTRAL_THRESHOLD = 0.15;   // must drop below this to unlock
const WARMUP_FRAMES   = 12;       // ignore triggers for first N frames after calibration

// Render Categories
function renderCategories() {
    const listContainer = document.getElementById('categories-list');
    listContainer.innerHTML = '';
    
    categories.forEach(cat => {
        const card = document.createElement('div');
        card.className = 'category-card';
        card.onclick = () => selectCategory(cat);
        card.innerHTML = `
            <div class="category-info">
                <span class="category-title">${cat.title}</span>
                <span class="category-desc">${cat.desc}</span>
            </div>
            <ion-icon name="${cat.icon}" class="category-icon"></ion-icon>
        `;
        listContainer.appendChild(card);
    });
}

let playerList = [];
let activePlayerName = "";

function selectCategory(cat) {
    activeCategory = cat;
    document.getElementById('selected-category-title').textContent = cat.title;
    
    const timeBlock = document.getElementById('time-selector-block');
    const namesBlock = document.getElementById('player-names-block');
    
    const tabooLobby = document.getElementById('taboo-lobby-container');
    const stdInstruction = document.getElementById('standard-instruction-card');
    const stdOptions = document.getElementById('setup-options-container');
    const stdStartBtn = document.getElementById('standard-start-btn');
    
    if (cat.id === 'forbidden_words') {
        // Show Taboo Multiplayer Lobby
        if (tabooLobby) tabooLobby.style.display = 'flex';
        if (stdInstruction) stdInstruction.style.display = 'none';
        if (stdOptions) stdOptions.style.display = 'none';
        if (stdStartBtn) stdStartBtn.style.display = 'none';
        
        // Default to not inside a room
        roomCode = null;
        isHost = false;
        document.getElementById('taboo-setup-entry').style.display = 'flex';
        document.getElementById('taboo-lobby-waiting').style.display = 'none';
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    } else {
        // Show standard setup
        if (tabooLobby) tabooLobby.style.display = 'none';
        if (stdInstruction) stdInstruction.style.display = 'flex';
        if (stdOptions) stdOptions.style.display = 'block';
        if (stdStartBtn) stdStartBtn.style.display = 'block';
        
        if (timeBlock) timeBlock.style.display = 'flex';
        if (namesBlock) namesBlock.style.display = 'none';
        
        // Reset room stuff
        roomCode = null;
        isHost = false;
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }
    
    switchScreen('screen-setup');
}

function switchScreen(screenId) {
    const screens = ['screen-category', 'screen-setup', 'screen-play', 'screen-results', 'screen-countdown'];
    screens.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = (id === screenId) ? 'flex' : 'none';
    });
    
    // Hide header HUD during active gameplay or countdown to maximize screen real estate
    const header = document.querySelector('.game-header');
    if (header) {
        if (screenId === 'screen-play' || screenId === 'screen-countdown') {
            header.style.display = 'none';
        } else {
            header.style.display = 'flex';
        }
    }
}

// ═══════════════════════════════════════════════════════════════
//  📐 ACCELEROMETER / GYROSCOPE TILT MECHANICS
// ═══════════════════════════════════════════════════════════════
function requestPermissionAndStart() {
    initAudio();
    
    // Request HTML5 Fullscreen mode for immersive mobile view
    const elem = document.documentElement;
    if (elem.requestFullscreen) {
        elem.requestFullscreen().catch(() => {});
    } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen();
    }
    
    // Bypass sensor permission and connection for Forbidden Words
    if (activeCategory.id === 'forbidden_words') {
        isSensorActive = false;
        startCountdown();
        return;
    }
    
    // Check if DeviceOrientation requires permission (iOS 13+)
    if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
        DeviceOrientationEvent.requestPermission()
            .then(permissionState => {
                if (permissionState === 'granted') {
                    if (!isSensorListenerAdded) {
                        window.addEventListener('deviceorientation', handleOrientation);
                        isSensorListenerAdded = true;
                    }
                    isSensorActive = true;
                } else {
                    console.log("DeviceOrientation permission denied.");
                }
                startCountdown();
            })
            .catch(err => {
                console.error(err);
                startCountdown(); // fallback to manual click if permission fails
            });
    } else {
        // Android or older iOS (no request needed)
        if (window.DeviceOrientationEvent) {
            if (!isSensorListenerAdded) {
                window.addEventListener('deviceorientation', handleOrientation);
                isSensorListenerAdded = true;
            }
            isSensorActive = true;
        }
        startCountdown();
    }
}

function handleOrientation(event) {
    let beta = event.beta;
    let gamma = event.gamma;
    
    if (beta !== null && gamma !== null) {
        sensorReceived = true;
    }
    
    if (beta === null || gamma === null) return;
    
    // Calculate gravity vector component on phone's Z-axis (screen normal)
    const rad = Math.PI / 180;
    const rawGz = Math.cos(beta * rad) * Math.cos(gamma * rad);
    
    // EMA low-pass filter — smooths out sensor noise and hand tremors
    if (smoothGz === null) {
        smoothGz = rawGz;
    } else {
        smoothGz = EMA_ALPHA * rawGz + (1 - EMA_ALPHA) * smoothGz;
    }
    
    // Capture base Gz once enough data has been smoothed
    if (baseGz === null) {
        baseGz = smoothGz;
        warmupFrames = 0;
        return;
    }
    
    // Warmup guard: skip triggers for first N frames so smoothed signal can settle
    warmupFrames++;
    
    const diffGz = smoothGz - baseGz;
    
    // Update live debug HUD
    const debugEl = document.getElementById('tilt-debug');
    if (debugEl) {
        const bar = diffGz > 0 ? '▲'.repeat(Math.min(5, Math.round(diffGz * 10))) : '▼'.repeat(Math.min(5, Math.round(-diffGz * 10)));
        debugEl.textContent = `Gz: ${smoothGz.toFixed(2)} Δ${diffGz.toFixed(2)} ${bar} ${sensorLocked ? '🔒' : (warmupFrames < WARMUP_FRAMES ? '⏳' : '✅')}`;
    }
    
    // Return-to-neutral hysteresis unlock
    if (sensorLocked) {
        if (Math.abs(diffGz) < NEUTRAL_THRESHOLD) {
            sensorLocked = false;
            const cardBox = document.getElementById('card-box');
            if (cardBox) cardBox.className = "word-card";
        }
        return;
    }
    
    if (tiltCooldown) return;
    
    // Skip triggers during warmup period
    if (warmupFrames < WARMUP_FRAMES) return;
    
    // Hysteresis triggers with TRIGGER_THRESHOLD (0.5 — stronger than unlock threshold)
    // diffGz < -0.5: screen tilting toward ceiling → Tilt Up = Correct ✅
    // diffGz > +0.5: screen tilting toward floor    → Tilt Down = Pass  ❌
    if (diffGz < -TRIGGER_THRESHOLD) {
        sensorLocked = true;
        triggerCorrect();
    } else if (diffGz > TRIGGER_THRESHOLD) {
        sensorLocked = true;
        triggerPass();
    }
}

// ═══════════════════════════════════════════════════════════════
//  🎬 GAMEPLAY LOOPS
// ═══════════════════════════════════════════════════════════════
function startCountdown() {
    switchScreen('screen-countdown');
    let count = 3;
    const countdownEl = document.getElementById('countdown-timer');
    countdownEl.textContent = count;
    playTickTone();
    
    const countInterval = setInterval(() => {
        count--;
        if (count > 0) {
            countdownEl.textContent = count;
            playTickTone();
        } else if (count === 0) {
            countdownEl.textContent = "เริ่ม!";
            playTickTone();
        } else {
            clearInterval(countInterval);
            startGameplay();
        }
    }, 1000);
}

function startGameplay() {
    switchScreen('screen-play');
    
    // Restore standard card box layout styling and contents
    const cardBox = document.getElementById('card-box');
    if (cardBox) {
        cardBox.style.padding = '';
        cardBox.style.gap = '';
        cardBox.style.flexDirection = '';
        cardBox.style.justifyContent = '';
        cardBox.innerHTML = `
            <span class="word-sub" id="card-category-label">CATEGORY</span>
            <div class="word-text" id="card-word">LOADING...</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 15px; display: flex; flex-direction: column; align-items: center; gap: 4px;" id="tilt-indicator">
                <div style="display: flex; align-items: center; gap: 4px;">
                    <ion-icon name="phone-portrait-outline"></ion-icon>
                    <span>พร้อมเอียงหน้าจอเพื่อเริ่มควบคุมคำศัพท์</span>
                </div>
                <div id="tilt-debug" style="font-size: 0.7rem; color: var(--accent-cyan); font-family: monospace;">B: 0 (0) | G: 0 (0)</div>
                <span onclick="toggleManualButtons()" style="font-size: 0.75rem; color: var(--accent-purple); cursor: pointer; text-decoration: underline; margin-top: 8px; z-index: 100; pointer-events: auto;">เปิด/ปิดปุ่มกดสัมผัสสำรอง</span>
            </div>
        `;
    }
    
    // Prepare words pool (shuffled)
    wordPool = [...activeCategory.words];
    for (let i = wordPool.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [wordPool[i], wordPool[j]] = [wordPool[j], wordPool[i]];
    }
    
    currentIndex = 0;
    score = 0;
    secondsRemaining = customRoundDuration;
    roundRecords = [];
    tiltCooldown = false;
    baseGz = null;
    smoothGz = null;
    warmupFrames = 0;
    sensorLocked = false;
    
    const btnContainer = document.getElementById('fallback-buttons');
    const passBtn = btnContainer ? btnContainer.querySelector('.btn-pass') : null;
    const correctBtn = btnContainer ? btnContainer.querySelector('.btn-correct') : null;
    const tiltIndicator = document.getElementById('tilt-indicator');
    const endBtn = document.getElementById('end-game-btn');
    const timerHud = document.getElementById('timer-hud');
    
    if (activeCategory.id === 'forbidden_words') {
        // Build player list
        const namesVal = document.getElementById('player-names-input').value;
        playerList = namesVal.split(',').map(n => n.trim()).filter(n => n.length > 0);
        if (playerList.length === 0) {
            playerList = ["ผู้เล่น 1", "ผู้เล่น 2", "ผู้เล่น 3", "ผู้เล่น 4"];
        }
        
        // Show buttons permanently for Forbidden Words (no sensor tilt)
        if (btnContainer) btnContainer.style.display = 'flex';
        if (passBtn) {
            passBtn.innerHTML = '<ion-icon name="alert-circle-outline"></ion-icon> พูดคำห้ามพูด 🔴';
            passBtn.style.background = 'rgba(255, 69, 58, 0.15)';
            passBtn.style.color = '#ff453a';
            passBtn.style.borderColor = '#ff453a';
        }
        if (correctBtn) {
            correctBtn.innerHTML = '<ion-icon name="arrow-forward-circle-outline"></ion-icon> รอดตัว / คนถัดไป 🟢';
            correctBtn.style.background = 'rgba(48, 209, 88, 0.15)';
            correctBtn.style.color = '#30d158';
            correctBtn.style.borderColor = '#30d158';
        }
        
        if (tiltIndicator) tiltIndicator.style.display = 'none';
        if (endBtn) endBtn.style.display = 'flex';
        if (timerHud) timerHud.style.display = 'none';
    } else {
        // Standard Labels & Hide buttons by default
        if (btnContainer) btnContainer.style.display = 'none';
        if (passBtn) {
            passBtn.innerHTML = '<ion-icon name="close-circle-outline"></ion-icon> ข้าม (Pass)';
            passBtn.style.background = '';
            passBtn.style.color = '';
            passBtn.style.borderColor = '';
        }
        if (correctBtn) {
            correctBtn.innerHTML = '<ion-icon name="checkmark-circle-outline"></ion-icon> ถูก (Correct)';
            correctBtn.style.background = '';
            correctBtn.style.color = '';
            correctBtn.style.borderColor = '';
        }
        
        if (tiltIndicator) tiltIndicator.style.display = 'flex';
        if (endBtn) endBtn.style.display = 'none';
        if (timerHud) timerHud.style.display = 'flex';
        
        sensorReceived = false;
        if (sensorCheckTimeout) clearTimeout(sensorCheckTimeout);
        sensorCheckTimeout = setTimeout(() => {
            if (!sensorReceived) {
                console.log("No sensor activity detected. Showing touch controls fallback.");
                if (btnContainer) btnContainer.style.display = 'flex';
            }
        }, 2000);
    }
    
    document.getElementById('current-score').textContent = score;
    document.getElementById('game-time').textContent = secondsRemaining;
    document.getElementById('card-category-label').textContent = activeCategory.title;
    
    if (timerHud) timerHud.classList.remove('urgent');
    
    loadNextWord();
    
    // Start game timer
    if (gameInterval) clearInterval(gameInterval);
    if (activeCategory.id === 'forbidden_words') {
        document.getElementById('game-time').textContent = "♾️";
        if (roomCode && isHost) updateHostRoomWord();
    } else {
        if (roomCode && isHost) updateHostRoomWord();
        gameInterval = setInterval(() => {
            secondsRemaining--;
            document.getElementById('game-time').textContent = secondsRemaining;
            
            if (roomCode && isHost) updateHostRoomWord();
            
            if (secondsRemaining <= 10 && secondsRemaining > 5) {
                timerHud.classList.add('urgent');
                playTickTone();
            } else if (secondsRemaining <= 5 && secondsRemaining > 0) {
                timerHud.classList.add('urgent');
                playUrgentBeepTone();
            }
            
            if (secondsRemaining <= 0) {
                endGame();
            }
        }, 1000);
    }
}

function loadNextWord() {
    if (currentIndex >= wordPool.length) {
        // Refill pool if we run out of words
        wordPool = [...activeCategory.words];
        for (let i = wordPool.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [wordPool[i], wordPool[j]] = [wordPool[j], wordPool[i]];
        }
        currentIndex = 0;
    }
    
    const word = wordPool[currentIndex];
    const cardCategoryEl = document.getElementById('card-category-label');
    const cardWordEl = document.getElementById('card-word');
    
    if (activeCategory.id === 'forbidden_words') {
        const randomPlayer = playerList[Math.floor(Math.random() * playerList.length)];
        activePlayerName = randomPlayer;
        cardCategoryEl.textContent = `👉 ผู้เล่น: ${randomPlayer}`;
        cardWordEl.textContent = `ห้ามพูด: ${word}`;
    } else {
        cardCategoryEl.textContent = activeCategory.title;
        cardWordEl.textContent = word;
    }
    
    // Standard rotation is straight for friends to read easily
    cardWordEl.className = "word-text"; 

    const cardBox = document.getElementById('card-box');
    cardBox.className = "word-card";

    // Broadcast the new word to the room if Host
    if (roomCode && isHost) {
        updateHostRoomWord();
    }
}

function triggerCorrect() {
    if (tiltCooldown) return;
    tiltCooldown = true;
    
    // Add visual feedback
    const cardBox = document.getElementById('card-box');
    cardBox.className = "word-card correct";
    const cardWordEl = document.getElementById('card-word');
    
    if (activeCategory.id === 'forbidden_words') {
        cardWordEl.textContent = `${activePlayerName} รอดตัว! 🟢`;
        score++;
        roundRecords.push({ word: `${activePlayerName} (ห้ามพูด: ${wordPool[currentIndex]})`, status: 'yes' });
    } else {
        cardWordEl.textContent = "ถูกต้อง! ✅";
        score++;
        roundRecords.push({ word: wordPool[currentIndex], status: 'yes' });
    }
    
    document.getElementById('current-score').textContent = score;
    playCorrectTone();
    currentIndex++;
    
    // Brief lock before loading next word
    setTimeout(() => {
        loadNextWord();
        tiltCooldown = false;
    }, 1200);
}

function triggerPass() {
    if (tiltCooldown) return;
    tiltCooldown = true;
    
    // Add visual feedback
    const cardBox = document.getElementById('card-box');
    cardBox.className = "word-card pass";
    const cardWordEl = document.getElementById('card-word');
    
    if (activeCategory.id === 'forbidden_words') {
        cardWordEl.textContent = `${activePlayerName} โดนจับได้! 🔴`;
        roundRecords.push({ word: `${activePlayerName} (ห้ามพูด: ${wordPool[currentIndex]})`, status: 'no' });
    } else {
        cardWordEl.textContent = "ข้ามคำศัพท์ ❌";
        roundRecords.push({ word: wordPool[currentIndex], status: 'no' });
    }
    
    playPassTone();
    currentIndex++;
    
    // Brief lock before loading next word
    setTimeout(() => {
        loadNextWord();
        tiltCooldown = false;
    }, 1200);
}

function endGame() {
    if (gameInterval) clearInterval(gameInterval);
    
    // Broadcast end of play if host
    if (roomCode && isHost) {
        updateHostRoomEnd();
    }
    
    playGameOverFanfare();
    
    // Exit Fullscreen mode
    if (document.exitFullscreen) {
        document.exitFullscreen().catch(() => {});
    } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
    }
    
    document.getElementById('final-score').textContent = score;
    
    // Calculate Rank based on correct score
    let rank = "";
    let rankColor = "";
    if (score <= 3) {
        rank = "ROOKIE (มือใหม่หัดเล่น)";
        rankColor = "#94a3b8"; // Slate
    } else if (score <= 7) {
        rank = "IT GEEK (เซียนไอที)";
        rankColor = "#38bdf8"; // Sky
    } else if (score <= 12) {
        rank = "CYBER AGENT (สายลับไซเบอร์)";
        rankColor = "#a78bfa"; // Violet
    } else {
        rank = "NEON LEGEND (ตำนานนีออน)";
        rankColor = "#f43f5e"; // Rose
    }
    
    const rankEl = document.getElementById('rank-name');
    if (rankEl) {
        if (activeCategory.id === 'forbidden_words') {
            rankEl.textContent = "TABOO MASTER";
            rankEl.style.color = '#30d158';
            if (rankEl.parentElement) {
                rankEl.parentElement.style.borderColor = '#30d158';
                rankEl.parentElement.style.boxShadow = `0 0 12px rgba(48, 209, 88, 0.2)`;
            }
        } else {
            rankEl.textContent = rank;
            rankEl.style.color = rankColor;
            if (rankEl.parentElement) {
                rankEl.parentElement.style.borderColor = rankColor;
                rankEl.parentElement.style.boxShadow = `0 0 12px ${rankColor}20`;
            }
        }
    }
    
    // Update summary title counts
    const correctCount = roundRecords.filter(r => r.status === 'yes').length;
    const totalCount = roundRecords.length;
    const countEl = document.getElementById('summary-count');
    if (countEl) {
        if (activeCategory.id === 'forbidden_words') {
            countEl.textContent = `รอดตัว ${correctCount} คน | โดนจับ ${totalCount - correctCount} คน`;
        } else {
            countEl.textContent = `ทายทั้งหมด ${totalCount} คำ | ถูก ${correctCount} คำ`;
        }
    }
    
    switchScreen('screen-results');
    
    // Render summary list
    const summaryContainer = document.getElementById('summary-items');
    summaryContainer.innerHTML = '';
    
    if (roundRecords.length === 0) {
        summaryContainer.innerHTML = '<div style="text-align:center; padding:10px; color:var(--text-muted);">ไม่มีประวัติการเล่น</div>';
        return;
    }
    
    roundRecords.forEach(rec => {
        const item = document.createElement('div');
        item.className = `summary-item ${rec.status}`;
        let statusLabel = "";
        if (activeCategory.id === 'forbidden_words') {
            statusLabel = rec.status === 'yes' ? 'รอดตัว 🟢' : 'โดนจับ! 🔴';
        } else {
            statusLabel = rec.status === 'yes' ? 'ถูก ✅' : 'ข้าม ❌';
        }
        item.innerHTML = `
            <span>${rec.word}</span>
            <span class="item-status ${rec.status}">${statusLabel}</span>
        `;
        summaryContainer.appendChild(item);
    });
}

// ═══════════════════════════════════════════════════════════════
//  🎮 TABOO MULTIPLAYER LOBBY SYSTEM
// ═══════════════════════════════════════════════════════════════

let tabooMyName = '';
let tabooMyAvatar = 'paw-outline';
let tabooLobbyPollInterval = null;
let tabooGamePollInterval = null;
let tabooIsReady = false;
let tabooMyWord = '';

// Use session avatar URL for this player
tabooMyAvatar = SESSION_AVATAR;

function createTabooRoom() {
    tabooMyName = SESSION_NAME;
    tabooMyAvatar = SESSION_AVATAR;

    const fd = new FormData();
    fd.append('player_name', tabooMyName);
    fd.append('avatar_icon', tabooMyAvatar);

    fetch('api_room.php?action=create', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                roomCode = data.room_code;
                isHost = true;
                tabooIsReady = true;
                showTabooLobbyWaiting();
                startTabooLobbyPoll();
            } else {
                alert(data.message || 'เกิดข้อผิดพลาด');
            }
        })
        .catch(() => alert('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์'));
}

function joinTabooRoom() {
    const codeEl = document.getElementById('taboo-room-code');
    const code = codeEl ? codeEl.value.trim() : '';
    if (!code || code.length !== 4) { alert('กรุณากรอกรหัสห้อง 4 หลัก'); codeEl && codeEl.focus(); return; }
    tabooMyName = SESSION_NAME;
    tabooMyAvatar = SESSION_AVATAR;

    const fd = new FormData();
    fd.append('player_name', tabooMyName);
    fd.append('avatar_icon', tabooMyAvatar);
    fd.append('room_code', code);

    fetch('api_room.php?action=join', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                roomCode = code;
                isHost = false;
                tabooIsReady = false;
                showTabooLobbyWaiting();
                startTabooLobbyPoll();
            } else {
                alert(data.message || 'รหัสห้องไม่ถูกต้อง');
            }
        })
        .catch(() => alert('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์'));
}

function showTabooLobbyWaiting() {
    document.getElementById('taboo-setup-entry').style.display = 'none';
    const waiting = document.getElementById('taboo-lobby-waiting');
    waiting.style.display = 'flex';
    document.getElementById('taboo-room-title').textContent = `ห้อง: ${roomCode} ${isHost ? '👑 (คุณเป็น Host)' : ''}`;

    // Show correct buttons
    const readyBtn = document.getElementById('taboo-ready-btn');
    const startBtn = document.getElementById('taboo-start-btn');
    if (isHost) {
        if (readyBtn) readyBtn.style.display = 'none';
        if (startBtn) startBtn.style.display = 'block';
    } else {
        if (readyBtn) readyBtn.style.display = 'block';
        if (startBtn) startBtn.style.display = 'none';
    }
}

function startTabooLobbyPoll() {
    if (tabooLobbyPollInterval) clearInterval(tabooLobbyPollInterval);
    tabooLobbyPollInterval = setInterval(() => {
        fetch(`api_room.php?action=poll_taboo&room_code=${roomCode}`)
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'success') return;

                // If game has started, switch to gameplay view
                if (data.game_status === 'playing') {
                    clearInterval(tabooLobbyPollInterval);
                    tabooLobbyPollInterval = null;
                    const me = data.players.find(p => p.player_name === tabooMyName);
                    if (me) tabooMyWord = me.current_word || '';
                    renderTabooGameplay(data.players);
                    return;
                }

                renderTabooWaitingList(data.players);
                // Update start button state for host
                if (isHost) {
                    const allReady = data.players.filter(p => !p.is_host).every(p => p.is_ready == 1);
                    const startBtn = document.getElementById('taboo-start-btn');
                    const hasEnough = data.players.length >= 2;
                    if (startBtn) {
                        startBtn.disabled = !(allReady && hasEnough);
                        startBtn.style.opacity = (allReady && hasEnough) ? '1' : '0.5';
                        startBtn.textContent = '';
                        startBtn.innerHTML = `<ion-icon name="play-circle-outline" style="font-size:1.15rem;vertical-align:middle;margin-right:4px;"></ion-icon>${allReady && hasEnough ? 'เริ่มเล่นเกม 🚀' : `รอผู้เล่น (${data.players.filter(p=>p.is_ready==1).length}/${data.players.length} พร้อม)`}`;
                    }
                }
            })
            .catch(err => console.error('Lobby poll error:', err));
    }, 1000);
}

function renderTabooWaitingList(players) {
    const container = document.getElementById('taboo-waiting-players-list');
    if (!container) return;
    container.innerHTML = players.map(p => {
        // avatar_icon stores either a URL (profile img) or fallback icon name
        const isUrl = p.avatar_icon && (p.avatar_icon.startsWith('http') || p.avatar_icon.startsWith('../../') || p.avatar_icon.startsWith('/'));
        const avatarHtml = isUrl
            ? `<img src="${p.avatar_icon}" style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--accent-cyan);" onerror="this.src='../../assets/avatar/dog.png'">`
            : `<div style="width:38px;height:38px;border-radius:50%;background:rgba(0,212,255,0.12);display:flex;align-items:center;justify-content:center;"><ion-icon name="${p.avatar_icon}" style="color:var(--accent-cyan);font-size:1.2rem;"></ion-icon></div>`;
        return `
        <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;background:rgba(255,255,255,0.04);border:1px solid var(--card-border);">
            <div style="flex-shrink:0;">${avatarHtml}</div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:0.9rem;color:var(--text-main);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    ${p.player_name} ${p.is_host == 1 ? '👑' : ''}
                </div>
                <div style="font-size:0.75rem;color:var(--text-muted);">${p.is_host == 1 ? 'Host' : 'ผู้เล่น'}</div>
            </div>
            <div style="padding:4px 10px;border-radius:20px;font-size:0.72rem;font-weight:800;${p.is_ready == 1 ? 'background:rgba(48,209,88,0.15);color:#30d158;border:1px solid rgba(48,209,88,0.3);' : 'background:rgba(255,159,10,0.12);color:#ff9f0a;border:1px solid rgba(255,159,10,0.25);'}">
                ${p.is_ready == 1 ? '✅ พร้อม' : '⏳ รอ...'}
            </div>
        </div>`;
    }).join('');
}

function toggleTabooReady() {
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('player_name', tabooMyName);

    fetch('api_room.php?action=ready', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                tabooIsReady = !tabooIsReady;
                const btn = document.getElementById('taboo-ready-btn');
                if (btn) {
                    btn.style.background = tabooIsReady ? '#30d158' : '';
                    btn.style.color = tabooIsReady ? '#000' : '';
                    btn.innerHTML = `<ion-icon name="${tabooIsReady ? 'checkmark-done-circle-outline' : 'ellipse-outline'}" style="font-size:1.15rem;vertical-align:middle;margin-right:4px;"></ion-icon>${tabooIsReady ? 'พร้อมแล้ว ✅' : 'กดปุ่มพร้อม 👍'}`;
                }
                playTickTone();
            }
        });
}

function startTabooGame() {
    // Fetch current player list and assign words
    fetch(`api_room.php?action=players&room_code=${roomCode}`)
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'success') { alert('เกิดข้อผิดพลาด'); return; }
            const players = data.players;
            if (players.length < 2) { alert('ต้องมีอย่างน้อย 2 คนในห้อง'); return; }

            // Shuffle forbidden_words word pool
            const words = [...(activeCategory ? activeCategory.words : [])];
            for (let i = words.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [words[i], words[j]] = [words[j], words[i]];
            }

            // Assign one word per player
            const assignments = players.map((p, idx) => ({
                player_name: p.player_name,
                word: words[idx % words.length]
            }));

            const fd = new FormData();
            fd.append('room_code', roomCode);
            fd.append('assignments', JSON.stringify(assignments));

            fetch('api_room.php?action=start_taboo', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(startData => {
                    if (startData.status === 'success') {
                        // Host: find own word from assignments
                        const myAssign = assignments.find(a => a.player_name === tabooMyName);
                        tabooMyWord = myAssign ? myAssign.word : '';
                        renderTabooGameplay(players.map((p, idx) => ({
                            ...p,
                            current_word: assignments[idx].word,
                            is_caught: 0
                        })));
                    } else {
                        alert('ไม่สามารถเริ่มเกมได้');
                    }
                });
        });
}

function renderTabooGameplay(players) {
    // Clear lobby poll
    if (tabooLobbyPollInterval) { clearInterval(tabooLobbyPollInterval); tabooLobbyPollInterval = null; }

    // Switch to play screen
    switchScreen('screen-play');

    // Disable tilt sensor
    isSensorActive = false;

    // Hide HUD timer & score for taboo
    const timerHud = document.getElementById('timer-hud');
    if (timerHud) timerHud.style.display = 'none';
    const scoreHud = document.querySelector('.hud-score');
    if (scoreHud) scoreHud.innerHTML = `<ion-icon name="people-outline"></ion-icon> ห้อง: ${roomCode}`;

    // Show end game button for host
    const endBtn = document.getElementById('end-game-btn');
    if (isHost && endBtn) {
        endBtn.style.display = 'flex';
        endBtn.onclick = hostEndTabooGame;
        endBtn.innerHTML = `<ion-icon name="stop-circle-outline" style="font-size:1.15rem;vertical-align:middle;"></ion-icon> จบเกม`;
    }

    // Hide fallback touch buttons
    const fbBtns = document.getElementById('fallback-buttons');
    if (fbBtns) fbBtns.style.display = 'none';

    // Build taboo gameplay card
    const cardBox = document.getElementById('card-box');
    if (!cardBox) return;

    const otherPlayers = players.filter(p => p.player_name !== tabooMyName);

    cardBox.style.padding = '16px';
    cardBox.style.gap = '10px';
    cardBox.style.flexDirection = 'column';
    cardBox.style.justifyContent = 'flex-start';
    cardBox.style.height = 'auto';
    cardBox.style.overflowY = 'auto';
    cardBox.innerHTML = `
        <div style="text-align:center;padding:10px 0 6px;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);letter-spacing:2px;text-transform:uppercase;margin-bottom:4px;">คำห้ามพูดของคุณคือ</div>
            <div style="font-size:clamp(2rem,9vw,3.5rem);font-weight:900;color:#ff453a;letter-spacing:-1px;line-height:1.1;">${tabooMyWord || '???'}</div>
            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:4px;">⚠️ ห้ามพูดคำนี้เด็ดขาด!</div>
        </div>
        <div style="width:100%;height:1px;background:var(--card-border);margin:4px 0;"></div>
        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);display:flex;align-items:center;gap:4px;margin-bottom:2px;">
            <ion-icon name="people-outline" style="color:var(--accent-cyan);"></ion-icon>
            กดปุ่ม "จับได้!" เมื่อเพื่อนพูดคำห้าม:
        </div>
        <div id="taboo-catch-list" style="display:flex;flex-direction:column;gap:8px;width:100%;">
            ${otherPlayers.map(p => `
                <div id="taboo-player-card-${p.player_name.replace(/[^a-zA-Z0-9]/g,'_')}" style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:12px;background:rgba(255,255,255,0.04);border:1px solid var(--card-border);${p.is_caught == 1 ? 'opacity:0.45;' : ''}">
                    <div style="width:34px;height:34px;border-radius:50%;background:rgba(0,212,255,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <ion-icon name="${p.avatar_icon}" style="color:var(--accent-cyan);font-size:1.1rem;"></ion-icon>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;font-size:0.85rem;color:var(--text-main);">${p.player_name}</div>
                        <div style="font-size:0.7rem;color:var(--text-muted);">${p.is_caught == 1 ? '❌ โดนจับแล้ว' : 'กำลังเล่น...'}</div>
                    </div>
                    ${p.is_caught == 1
                        ? `<div style="padding:4px 10px;border-radius:20px;font-size:0.7rem;font-weight:800;background:rgba(255,69,58,0.15);color:#ff453a;border:1px solid rgba(255,69,58,0.3);">จับได้แล้ว</div>`
                        : `<button onclick="catchTabooPlayer('${p.player_name}')" style="padding:6px 12px;border-radius:10px;background:rgba(255,69,58,0.18);border:1px solid rgba(255,69,58,0.35);color:#ff453a;font-size:0.75rem;font-weight:800;cursor:pointer;white-space:nowrap;">🚨 จับได้!</button>`
                    }
                </div>
            `).join('')}
        </div>
    `;

    // Start polling game state for real-time caught updates
    startTabooGamePoll();
}

function startTabooGamePoll() {
    if (tabooGamePollInterval) clearInterval(tabooGamePollInterval);
    tabooGamePollInterval = setInterval(() => {
        fetch(`api_room.php?action=poll_taboo&room_code=${roomCode}`)
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'success') return;

                if (data.game_status === 'ended') {
                    clearInterval(tabooGamePollInterval);
                    tabooGamePollInterval = null;
                    showTabooResults(data.players);
                    return;
                }

                // Refresh catch status on each player card
                if (data.players) {
                    data.players.filter(p => p.player_name !== tabooMyName).forEach(p => {
                        const cardId = `taboo-player-card-${p.player_name.replace(/[^a-zA-Z0-9]/g,'_')}`;
                        const card = document.getElementById(cardId);
                        if (card) {
                            card.style.opacity = p.is_caught == 1 ? '0.45' : '1';
                            const statusEl = card.querySelector('div[style*="font-size:0.7rem"]');
                            if (statusEl) statusEl.textContent = p.is_caught == 1 ? '❌ โดนจับแล้ว' : 'กำลังเล่น...';
                            const catchBtn = card.querySelector('button');
                            if (catchBtn && p.is_caught == 1) {
                                catchBtn.outerHTML = `<div style="padding:4px 10px;border-radius:20px;font-size:0.7rem;font-weight:800;background:rgba(255,69,58,0.15);color:#ff453a;border:1px solid rgba(255,69,58,0.3);">จับได้แล้ว</div>`;
                            }
                        }
                    });
                }
            })
            .catch(err => console.error('Taboo game poll error:', err));
    }, 1200);
}

function catchTabooPlayer(playerName) {
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('target_name', playerName);

    fetch('api_room.php?action=catch_player', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                // Play buzzer sound
                initAudio();
                if (audioCtx) {
                    const now = audioCtx.currentTime;
                    [0, 0.15, 0.3].forEach(offset => {
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.type = 'sawtooth';
                        osc.frequency.setValueAtTime(180, now + offset);
                        gain.gain.setValueAtTime(0.08, now + offset);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + offset + 0.12);
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);
                        osc.start(now + offset);
                        osc.stop(now + offset + 0.12);
                    });
                }
                // Immediately update the card
                const cardId = `taboo-player-card-${playerName.replace(/[^a-zA-Z0-9]/g,'_')}`;
                const card = document.getElementById(cardId);
                if (card) {
                    card.style.opacity = '0.45';
                    const statusEl = card.querySelector('div[style*="font-size:0.7rem"]');
                    if (statusEl) statusEl.textContent = '❌ โดนจับแล้ว';
                    const catchBtn = card.querySelector('button');
                    if (catchBtn) {
                        catchBtn.outerHTML = `<div style="padding:4px 10px;border-radius:20px;font-size:0.7rem;font-weight:800;background:rgba(255,69,58,0.15);color:#ff453a;border:1px solid rgba(255,69,58,0.3);">จับได้แล้ว</div>`;
                    }
                }
            }
        })
        .catch(err => console.error('Catch error:', err));
}

function hostEndTabooGame() {
    if (!confirm('ยืนยันจบเกม?')) return;

    const fd = new FormData();
    fd.append('room_code', roomCode);

    fetch('api_room.php?action=end_taboo', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                clearInterval(tabooGamePollInterval);
                tabooGamePollInterval = null;
                // Fetch final state to show results
                fetch(`api_room.php?action=poll_taboo&room_code=${roomCode}`)
                    .then(r => r.json())
                    .then(d => showTabooResults(d.players || []));
            }
        });
}

function showTabooResults(players) {
    clearInterval(tabooGamePollInterval);
    tabooGamePollInterval = null;

    const caughtCount = players.filter(p => p.is_caught == 1).length;
    const survivorCount = players.length - caughtCount;
    const savedRoomCode = roomCode; // keep for restart

    // Helper: render avatar
    const avatarHtml = (p) => {
        const isUrl = p.avatar_icon && (p.avatar_icon.startsWith('http') || p.avatar_icon.startsWith('../../') || p.avatar_icon.startsWith('/'));
        return isUrl
            ? `<img src="${p.avatar_icon}" style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--accent-cyan);" onerror="this.src='../../assets/avatar/dog.png'">`
            : `<div style="width:38px;height:38px;border-radius:50%;background:rgba(0,212,255,0.1);display:flex;align-items:center;justify-content:center;"><ion-icon name="${p.avatar_icon}" style="color:var(--accent-cyan);font-size:1.1rem;"></ion-icon></div>`;
    };

    // Render results inside the card-box (still on play screen)
    const cardBox = document.getElementById('card-box');
    if (cardBox) {
        cardBox.style.padding = '16px';
        cardBox.style.gap = '10px';
        cardBox.style.height = 'auto';
        cardBox.style.overflowY = 'auto';
        cardBox.style.flexDirection = 'column';
        cardBox.style.justifyContent = 'flex-start';
        cardBox.innerHTML = `
            <div style="text-align:center;padding:8px 0 4px;">
                <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);letter-spacing:2px;text-transform:uppercase;margin-bottom:6px;">ผลการเล่นรอบนี้</div>
                <div style="font-size:clamp(2rem,9vw,3rem);font-weight:900;color:#30d158;line-height:1;">${survivorCount}<span style="font-size:1.2rem;"> / ${players.length}</span></div>
                <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px;">คนรอดชีวิต 🏆</div>
            </div>
            <div style="width:100%;height:1px;background:var(--card-border);margin:2px 0;"></div>
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);margin-bottom:2px;">ผลรายคน:</div>
            <div style="display:flex;flex-direction:column;gap:8px;width:100%;">
                ${players.map(p => `
                    <div style="display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:12px;background:rgba(255,255,255,0.04);border:1px solid var(--card-border);">
                        <div style="flex-shrink:0;">${avatarHtml(p)}</div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:0.85rem;color:var(--text-main);">${p.player_name} ${p.is_host == 1 ? '👑' : ''}</div>
                            <div style="font-size:0.7rem;color:var(--text-muted);">คำห้าม: <span style="color:#ff9f0a;font-weight:700;">${p.current_word || '(ไม่มี)'}</span></div>
                        </div>
                        <div style="padding:4px 10px;border-radius:20px;font-size:0.7rem;font-weight:800;${p.is_caught == 1 ? 'background:rgba(255,69,58,0.15);color:#ff453a;border:1px solid rgba(255,69,58,0.3);' : 'background:rgba(48,209,88,0.12);color:#30d158;border:1px solid rgba(48,209,88,0.25);'}">
                            ${p.is_caught == 1 ? '❌ โดนจับ' : '✅ รอดแล้ว'}
                        </div>
                    </div>
                `).join('')}
            </div>
            ${isHost ? `
            <button onclick="restartTabooLobby()" style="width:100%;padding:14px;border-radius:14px;background:#30d158;border:none;color:#000;font-weight:800;font-size:0.95rem;cursor:pointer;margin-top:6px;display:flex;align-items:center;justify-content:center;gap:6px;">
                <ion-icon name="refresh-circle-outline" style="font-size:1.2rem;vertical-align:middle;"></ion-icon>
                เล่นอีกรอบ (กลับห้องเดิม) 🔄
            </button>` : `
            <div style="text-align:center;padding:10px;border-radius:12px;background:rgba(255,255,255,0.04);border:1px solid var(--card-border);font-size:0.8rem;color:var(--text-muted);">
                รอ Host กด "เล่นอีกรอบ" เพื่อเริ่มรอบใหม่...
            </div>`}
        `;
    }

    // Update HUD score area to show result
    const scoreHud = document.querySelector('.hud-score');
    if (scoreHud) scoreHud.innerHTML = `<ion-icon name="trophy-outline" style="color:#30d158;"></ion-icon> รอด ${survivorCount}/${players.length}`;

    // Hide end-game button
    const endBtn = document.getElementById('end-game-btn');
    if (endBtn) endBtn.style.display = 'none';

    // Poll for restart signal (when host resets to 'setup')
    startTabooResultPoll(savedRoomCode);
}

function restartTabooLobby() {
    if (!roomCode || !isHost) return;
    const fd = new FormData();
    fd.append('room_code', roomCode);

    fetch('api_room.php?action=restart_taboo', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                // Host goes back to lobby waiting room
                returnToLobbyWaiting();
            }
        });
}

function startTabooResultPoll(code) {
    // Non-host clients poll to detect when host restarts
    if (isHost) return;
    const resultPoll = setInterval(() => {
        fetch(`api_room.php?action=poll_taboo&room_code=${code}`)
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'success') { clearInterval(resultPoll); return; }
                if (data.game_status === 'setup') {
                    clearInterval(resultPoll);
                    returnToLobbyWaiting();
                }
            })
            .catch(() => clearInterval(resultPoll));
    }, 1500);
}

function returnToLobbyWaiting() {
    // Restore HUD
    const timerHud = document.getElementById('timer-hud');
    if (timerHud) timerHud.style.display = 'none'; // keep hidden for taboo
    const endBtn = document.getElementById('end-game-btn');
    if (endBtn) { endBtn.style.display = 'none'; }
    const scoreHud = document.querySelector('.hud-score');
    if (scoreHud) scoreHud.innerHTML = `<ion-icon name="people-outline"></ion-icon> ห้อง: ${roomCode}`;

    // Reset ready state
    tabooIsReady = isHost; // host is always ready

    // Go back to setup screen showing the waiting lobby
    switchScreen('screen-setup');
    selectCategory(activeCategory); // re-trigger to show taboo lobby container
    // Show waiting room (not setup entry)
    document.getElementById('taboo-setup-entry').style.display = 'none';
    const waiting = document.getElementById('taboo-lobby-waiting');
    if (waiting) waiting.style.display = 'flex';
    document.getElementById('taboo-room-title').textContent = `ห้อง: ${roomCode} ${isHost ? '👑 (คุณเป็น Host)' : ''}`;

    // Update ready/start buttons
    const readyBtn = document.getElementById('taboo-ready-btn');
    const startBtn = document.getElementById('taboo-start-btn');
    if (isHost) {
        if (readyBtn) readyBtn.style.display = 'none';
        if (startBtn) {
            startBtn.style.display = 'block';
            startBtn.style.opacity = '0.5';
            startBtn.innerHTML = `<ion-icon name="play-circle-outline" style="font-size:1.15rem;vertical-align:middle;margin-right:4px;"></ion-icon>รอผู้เล่นพร้อม...`;
        }
    } else {
        if (readyBtn) {
            readyBtn.style.display = 'block';
            readyBtn.style.background = '';
            readyBtn.style.color = '';
            readyBtn.innerHTML = `<ion-icon name="checkmark-done-circle-outline" style="font-size:1.15rem;vertical-align:middle;margin-right:4px;"></ion-icon>กดปุ่มพร้อม 👍`;
        }
        if (startBtn) startBtn.style.display = 'none';
    }

    // Restart lobby polling
    startTabooLobbyPoll();
}

function exitTabooRoom() {
    if (!roomCode || !tabooMyName) {
        roomCode = null; isHost = false;
        if (tabooLobbyPollInterval) { clearInterval(tabooLobbyPollInterval); tabooLobbyPollInterval = null; }
        document.getElementById('taboo-setup-entry').style.display = 'flex';
        document.getElementById('taboo-lobby-waiting').style.display = 'none';
        return;
    }

    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('player_name', tabooMyName);

    fetch('api_room.php?action=exit', { method: 'POST', body: fd })
        .finally(() => {
            if (tabooLobbyPollInterval) { clearInterval(tabooLobbyPollInterval); tabooLobbyPollInterval = null; }
            roomCode = null;
            isHost = false;
            tabooIsReady = false;
            document.getElementById('taboo-setup-entry').style.display = 'flex';
            document.getElementById('taboo-lobby-waiting').style.display = 'none';
        });
}

function updateHostRoomWord() {
    if (!roomCode || !isHost) return;
    
    let wordToSend = "";
    if (activeCategory.id === 'forbidden_words') {
        wordToSend = `ผู้เล่น: ${activePlayerName}\nห้ามพูด: ${wordPool[currentIndex]}`;
    } else {
        wordToSend = wordPool[currentIndex];
    }
    
    const formData = new FormData();
    formData.append('room_code', roomCode);
    formData.append('current_word', wordToSend);
    formData.append('game_status', 'playing');
    formData.append('score', score);
    formData.append('seconds_remaining', secondsRemaining);
    formData.append('category_id', activeCategory.id);
    
    fetch('api_room.php?action=update', {
        method: 'POST',
        body: formData
    }).catch(err => console.error("Update error:", err));
}

function updateHostRoomEnd() {
    if (!roomCode || !isHost) return;
    
    const formData = new FormData();
    formData.append('room_code', roomCode);
    formData.append('current_word', '');
    formData.append('game_status', 'ended');
    formData.append('score', score);
    formData.append('seconds_remaining', 0);
    formData.append('category_id', activeCategory ? activeCategory.id : '');
    
    fetch('api_room.php?action=update', {
        method: 'POST',
        body: formData
    }).catch(err => console.error("End update error:", err));
}

function backToCategories() {
    // Clean up all intervals
    if (gameInterval) { clearInterval(gameInterval); gameInterval = null; }
    if (tabooLobbyPollInterval) { clearInterval(tabooLobbyPollInterval); tabooLobbyPollInterval = null; }
    if (tabooGamePollInterval) { clearInterval(tabooGamePollInterval); tabooGamePollInterval = null; }
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    
    // Reset room state
    roomCode = null;
    isHost = false;
    
    // Restore HUD elements in case they were hidden during Taboo
    const timerHud = document.getElementById('timer-hud');
    if (timerHud) timerHud.style.display = 'flex';
    const endBtn = document.getElementById('end-game-btn');
    if (endBtn) { endBtn.style.display = 'none'; endBtn.onclick = endGame; }
    const scoreHud = document.querySelector('.hud-score');
    if (scoreHud) scoreHud.innerHTML = '<ion-icon name="trophy-outline" style="color: var(--accent-cyan);"></ion-icon> คะแนน: <span id="current-score">0</span>';
    
    switchScreen('screen-category');
}

function exitGameToCategories() {
    if (gameInterval) clearInterval(gameInterval);
    
    // Clean up all taboo intervals
    if (tabooLobbyPollInterval) { clearInterval(tabooLobbyPollInterval); tabooLobbyPollInterval = null; }
    if (tabooGamePollInterval) { clearInterval(tabooGamePollInterval); tabooGamePollInterval = null; }
    
    // Clear legacy client polling if active
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
    
    // Play a click sound
    initAudio();
    if (audioCtx) {
        const now = audioCtx.currentTime;
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(330, now);
        gain.gain.setValueAtTime(0.04, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.1);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start(now);
        osc.stop(now + 0.1);
    }
    
    // Exit Fullscreen mode if active
    if (document.exitFullscreen) {
        document.exitFullscreen().catch(() => {});
    } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
    }
    
    // Reset room state
    roomCode = null;
    isHost = false;
    
    switchScreen('screen-category');
}

function startGameplayWithSameCat() {
    startCountdown();
}

function toggleManualButtons() {
    const btnContainer = document.getElementById('fallback-buttons');
    if (btnContainer) {
        if (btnContainer.style.display === 'none') {
            btnContainer.style.display = 'flex';
        } else {
            btnContainer.style.display = 'none';
        }
    }
}

function toggleFullscreen() {
    const icon = document.getElementById('fs-icon');
    if (!document.fullscreenElement && !document.webkitFullscreenElement) {
        // Enter fullscreen
        const elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen().catch(() => {});
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        }
        if (icon) icon.setAttribute('name', 'contract-outline');
    } else {
        // Exit fullscreen
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(() => {});
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        }
        if (icon) icon.setAttribute('name', 'expand-outline');
    }
}

document.addEventListener('fullscreenchange', updateFullscreenIcon);
document.addEventListener('webkitfullscreenchange', updateFullscreenIcon);

function updateFullscreenIcon() {
    const icon = document.getElementById('fs-icon');
    if (!icon) return;
    if (document.fullscreenElement || document.webkitFullscreenElement) {
        icon.setAttribute('name', 'contract-outline');
    } else {
        icon.setAttribute('name', 'expand-outline');
    }
}

function toggleTheme() {
    const body = document.body;
    body.classList.toggle('theme-cyber');
    const isCyber = body.classList.contains('theme-cyber');
    localStorage.setItem('head-guess-theme', isCyber ? 'cyber' : 'ios');
    
    // Play a nice feedback tone
    initAudio();
    if (audioCtx) {
        const now = audioCtx.currentTime;
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(isCyber ? 880 : 660, now);
        gain.gain.setValueAtTime(0.04, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start(now);
        osc.stop(now + 0.15);
    }
}

function setRoundTime(seconds) {
    customRoundDuration = seconds;
    
    // Play a subtle click note
    initAudio();
    if (audioCtx) {
        const now = audioCtx.currentTime;
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(440, now);
        gain.gain.setValueAtTime(0.04, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start(now);
        osc.stop(now + 0.08);
    }
    
    // Toggle active class on buttons
    const durations = [30, 60, 90, 120];
    durations.forEach(d => {
        const btn = document.getElementById(`time-opt-${d}`);
        if (btn) {
            if (d === seconds) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }
    });
}

// ═══════════════════════════════════════════════════════════════
//  🚀 SYSTEM BOOTSTRAP
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('head-guess-theme');
    if (savedTheme === 'cyber') {
        document.body.classList.add('theme-cyber');
    } else {
        document.body.classList.remove('theme-cyber');
    }
    renderCategories();
});
</script>

</body>
</html>
