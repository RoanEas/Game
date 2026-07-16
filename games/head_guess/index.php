<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}
require_once dirname(__DIR__, 2) . '/db.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>📱 เกมทายคำบนหัว — Heads Up Cyber</title>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;700&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="style.css?v=<?=time();?>">
</head>
<body>

<div class="game-container">

    <header class="game-header">
        <a href="../../index.php" class="back-btn">
            <ion-icon name="chevron-back-outline"></ion-icon>
            กลับหน้าแรก
        </a>
        <h1 class="hud-title">HEADS UP CYBER</h1>
        <div style="display: flex; align-items: center; gap: 8px;">
            <button class="back-btn" onclick="toggleTheme()" style="border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; padding: 0;" title="สลับธีม">
                <ion-icon name="color-palette-outline" id="theme-icon" style="font-size: 1.2rem;"></ion-icon>
            </button>
            <button class="back-btn" onclick="toggleFullscreen()" style="border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; padding: 0;" title="ขยายจอเต็ม">
                <ion-icon name="expand-outline" id="fs-icon" style="font-size: 1.2rem;"></ion-icon>
            </button>
        </div>
    </header>

    <!-- 1. CATEGORY / HOME SCREEN -->
    <div id="screen-category" class="category-screen">
        <div class="screen-title" style="margin-bottom: 5px;">เลือกโหมดและหมวดหมู่คำใบ้</div>
        <div class="screen-desc" style="margin-bottom: 20px;">เลือกโหมดที่เหมาะสมแล้วเตรียมตัวสนุกไปกับเพื่อนนอกสถานที่หรือออนไลน์</div>
        
        <!-- Mode Switcher -->
        <div class="tabber-container" style="margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto;">
            <button class="tab-btn active" id="btn-mode-local" onclick="switchMainMode('local')">
                <ion-icon name="person-outline"></ion-icon>เล่นเครื่องเดียว
            </button>
            <button class="tab-btn" id="btn-mode-online" onclick="switchMainMode('online')">
                <ion-icon name="people-outline"></ion-icon>เล่นปาร์ตี้ออนไลน์
            </button>
        </div>

        <!-- Online Lobby Panel (Hidden by default) -->
        <div id="online-auth-panel" style="display: none; width: 100%; max-width: 400px; margin: 0 auto 24px auto;">
            <div class="tabber-container" style="margin-bottom: 16px; background: rgba(0,0,0,0.25);">
                <button class="tab-btn active" id="btn-sub-create" onclick="switchOnlineTab('create')">
                    <ion-icon name="add-circle-outline"></ion-icon>สร้างห้อง
                </button>
                <button class="tab-btn" id="btn-sub-join" onclick="switchOnlineTab('join')">
                    <ion-icon name="enter-outline"></ion-icon>เข้าร่วมห้อง
                </button>
            </div>

            <div id="tab-online-create" style="display: block; text-align: center; background: rgba(255,255,255,0.02); border: 1px solid var(--card-border); padding: 20px; border-radius: 16px;">
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px; line-height: 1.4;">👑 สร้างห้องใหม่เพื่อเป็น "ผู้ถือจอทายคำ" และให้เพื่อนๆ ของคุณเชื่อมต่อเข้ามาช่วยใบ้ให้ผ่านหน้าจอโทรศัพท์ของพวกเขา!</p>
                <button class="btn-primary" onclick="createOnlineRoom()" style="width: 100%;">สร้างห้องปาร์ตี้ใหม่ 👑</button>
            </div>

            <div id="tab-online-join" style="display: none; text-align: center; background: rgba(255,255,255,0.02); border: 1px solid var(--card-border); padding: 20px; border-radius: 16px;">
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px;">ป้อนรหัสห้อง 4 หลักที่เพื่อนแชร์มาให้เพื่อร่วมเข้าวงเป็นผู้คอยบอกคำใบ้</p>
                <div class="otp-inputs-container" style="display: flex; justify-content: center; gap: 10px; margin-bottom: 18px;">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="0" placeholder="•" autocomplete="off">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="1" placeholder="•" autocomplete="off">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="2" placeholder="•" autocomplete="off">
                    <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="3" placeholder="•" autocomplete="off">
                </div>
                <button class="btn-primary" onclick="joinOnlineRoom()" style="width: 100%;">เข้าร่วมห้องปาร์ตี้ 🚀</button>
            </div>
        </div>

        <!-- Local Categories list -->
        <div id="local-categories-panel" style="width: 100%;">
            <div class="categories-grid" id="categories-list"></div>
        </div>
    </div>

    <!-- 2. LOBBY WAITING SCREEN -->
    <div id="screen-lobby" class="setup-screen" style="display: none; flex-direction: column; width: 100%;">
        <div class="lobby-header-row" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--card-border); padding-bottom: 14px; margin-bottom: 20px;">
            <div>
                <h2 class="lobby-room-title" id="room-code-display" style="font-family: var(--font-heading); color: var(--accent-cyan); font-size: 1.8rem; text-shadow: 0 0 10px rgba(6, 182, 212, 0.45);">ห้อง: 0000</h2>
                <span class="lobby-room-subtitle" style="font-size: 0.78rem; color: var(--text-muted);">ส่งรหัสห้องให้เพื่อนในกลุ่มเพื่อร่วมวงใบ้คำ</span>
            </div>
            <div style="display: flex; gap: 8px;">
                <button class="btn-invite-link" onclick="openFriendsInviteModal()" style="background: rgba(139, 92, 246, 0.1); color: var(--accent-purple); border: 1px solid rgba(139, 92, 246, 0.2); padding: 8px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                    <ion-icon name="people-outline"></ion-icon>เชิญเพื่อน
                </button>
                <button class="btn-leave-room" onclick="exitOnlineRoom()" style="background: rgba(255, 69, 58, 0.12); color: #ff453a; border: 1px solid rgba(255, 69, 58, 0.25); padding: 8px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 700; cursor: pointer;">ออกห้อง</button>
            </div>
        </div>

        <!-- Category & Time configuration (Host only) -->
        <div id="host-config-panel" style="display: none; flex-direction: column; gap: 14px; background: rgba(255,255,255,0.02); border: 1px solid var(--card-border); padding: 16px; border-radius: 16px; margin-bottom: 20px; text-align: left;">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; letter-spacing: 0.5px;">⚙️ แผงควบคุมสำหรับหัวหน้าห้อง (Host)</div>
            
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 0.8rem; color: var(--text-main); font-weight: 600;">เลือกหมวดหมู่ศัพท์ที่จะเล่น:</label>
                <select id="lobby-category-select" onchange="updateLobbyConfig()" style="background: #080c16; color: #fff; border: 1px solid var(--card-border); padding: 10px; border-radius: 10px; outline: none; font-size: 0.85rem; width: 100%;">
                    <!-- Populated dynamically with categories -->
                </select>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 0.8rem; color: var(--text-main); font-weight: 600;">เวลาต่อหนึ่งรอบ:</label>
                <div style="display: flex; gap: 8px;">
                    <button class="time-opt-btn" onclick="setLobbyTime(30)" id="l-time-30">30 วิ</button>
                    <button class="time-opt-btn active" onclick="setLobbyTime(60)" id="l-time-60">1 นาที</button>
                    <button class="time-opt-btn" onclick="setLobbyTime(90)" id="l-time-90">1.5 นาที</button>
                    <button class="time-opt-btn" onclick="setLobbyTime(120)" id="l-time-120">2 นาที</button>
                </div>
            </div>
        </div>

        <!-- Client Info Panel (Non-hosts see the selected configuration) -->
        <div id="client-config-panel" style="display: block; background: rgba(255,255,255,0.02); border: 1px solid var(--card-border); padding: 16px; border-radius: 16px; margin-bottom: 20px; text-align: left;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 0.85rem; color: var(--text-muted);">หมวดหมู่คำศัพท์:</span>
                <span id="client-selected-cat" style="font-size: 0.9rem; font-weight: 700; color: var(--accent-purple);">อุปกรณ์คอมพิวเตอร์</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.85rem; color: var(--text-muted);">เวลาที่กำหนด:</span>
                <span id="client-selected-time" style="font-size: 0.9rem; font-weight: 700; color: var(--accent-cyan);">1 นาที (60 วิ)</span>
            </div>
        </div>

        <!-- Players List inside Lobby -->
        <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px; text-align: left;">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">ผู้เล่นในห้องทั้งหมด</div>
            <div id="lobby-players-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 200px; overflow-y: auto; padding-right: 4px;">
                <!-- Dynamically populated players list with Add Friend button -->
            </div>
        </div>

        <!-- Action Button -->
        <button id="btn-lobby-start" class="btn-primary" onclick="startOnlineGame()" style="display: none; width: 100%;">
            <ion-icon name="play-outline" style="font-size: 1.25rem; margin-right: 6px;"></ion-icon>
            เริ่มเกมทายคำใบ้ 🚀
        </button>
        <button id="btn-lobby-ready" class="btn-primary btn-clay-blue" onclick="toggleOnlineReady()" style="display: block; width: 100%;">
            <ion-icon name="checkmark-circle-outline" style="font-size: 1.25rem; margin-right: 6px;"></ion-icon>
            <span id="ready-btn-text">เตรียมความพร้อม 🚀</span>
        </button>
    </div>

    <!-- 3. SETUP / INSTRUCTION SCREEN (Local) -->
    <div id="screen-setup" class="setup-screen" style="display: none;">
        <div class="screen-title" id="selected-category-title">หมวดหมู่</div>

        <div class="instruction-card">
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

        <div style="width: 100%; margin-top: 14px; margin-bottom: 12px; display: flex; flex-direction: column; gap: 8px; text-align: left;">
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

        <button class="btn-primary" onclick="requestPermissionAndStart()" id="standard-start-btn" style="width: 100%; margin-top: 10px;">
            <ion-icon name="play-outline" style="font-size: 1.2rem; vertical-align: middle; margin-right: 6px;"></ion-icon>
            เริ่มเล่นเกมคำนี้
        </button>
    </div>

    <!-- 4. COUNTDOWN SCREEN -->
    <div id="screen-countdown" class="countdown-overlay" style="display: none;">
        <div class="countdown-num" id="countdown-timer">3</div>
    </div>

    <!-- 5. GAMEPLAY SCREEN -->
    <div id="screen-play" class="play-screen" style="display: none;">
        <div class="game-hud">
            <button onclick="exitGameToCategories()" style="background: rgba(255, 255, 255, 0.08); border: 1px solid var(--card-border); color: var(--text-main); padding: 8px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; z-index: 9999; position: relative; pointer-events: auto;">
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
            <button id="end-game-btn" onclick="endGame()" style="display: none; padding: 8px 16px; border-radius: 12px; background: rgba(255, 69, 58, 0.15); border: 1px solid rgba(255, 69, 58, 0.3); color: #ff453a; font-size: 0.8rem; font-weight: 700; cursor: pointer; align-items: center; justify-content: center; gap: 4px; border: 1px solid rgba(255, 69, 58, 0.25);">
                <ion-icon name="stop-circle-outline" style="font-size: 1.15rem; vertical-align: middle;"></ion-icon>
                <span>จบเกม</span>
            </button>
        </div>

        <!-- Font Size Adjuster for Mobile -->
        <div class="font-size-adjuster" id="fs-adjuster-bar" style="display: flex; justify-content: center; gap: 8px; margin-top: 10px; margin-bottom: 10px; z-index: 100; pointer-events: auto;">
            <span style="font-size: 0.72rem; color: var(--text-muted); align-self: center; margin-right: 4px;">ขนาดตัวอักษร:</span>
            <button class="size-opt-btn active" onclick="changeWordFontSize('normal')">ปกติ</button>
            <button class="size-opt-btn" onclick="changeWordFontSize('100')">100px</button>
            <button class="size-opt-btn" onclick="changeWordFontSize('160')">160px</button>
            <button class="size-opt-btn" onclick="changeWordFontSize('200')">200px</button>
        </div>

        <div class="word-card-container">
            <div class="word-card" id="card-box">
                <span class="word-sub" id="card-category-label">CATEGORY</span>
                <div class="word-text" id="card-word">LOADING...</div>
                
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 15px; display: flex; flex-direction: column; align-items: center; gap: 4px; pointer-events: auto;" id="tilt-indicator">
                    <div style="display: flex; align-items: center; gap: 4px;">
                        <ion-icon name="phone-portrait-outline"></ion-icon>
                        <span id="instruction-title-mode">พร้อมเอียงหน้าจอเพื่อเริ่มควบคุมคำศัพท์</span>
                    </div>
                    <div id="tilt-debug" style="font-size: 0.7rem; color: var(--accent-cyan); font-family: monospace;">B: 0 (0) | G: 0 (0)</div>
                    <span id="manual-btn-toggle" onclick="toggleManualButtons()" style="font-size: 0.75rem; color: var(--accent-purple); cursor: pointer; text-decoration: underline; margin-top: 8px; z-index: 100;">เปิด/ปิดปุ่มกดสัมผัสสำรอง</span>
                </div>
            </div>
        </div>

        <!-- Manual action buttons (Pass / Correct) -->
        <div class="fallback-actions" id="fallback-buttons" style="display: none; padding: 0 24px; margin-top: -20px; margin-bottom: 20px; z-index: 12; width: 100%; pointer-events: auto;">
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

    <!-- 6. RESULTS SCREEN -->
    <div id="screen-results" class="result-screen" style="display: none;">
        <div class="screen-title" style="margin-bottom: 5px;">สิ้นสุดการทายคำ!</div>
        
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
                หน้าแรก
            </button>
            <button class="btn-primary" id="btn-result-replay" onclick="replayGame()">
                <ion-icon name="refresh-outline" style="font-size: 1.25rem; vertical-align: middle; margin-right: 6px;"></ion-icon>
                เล่นอีกรอบ
            </button>
        </div>
    </div>

</div>

<!-- 👥 ONLINE FRIENDS INVITATION MODAL -->
<div id="head-guess-friends-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center; padding: 16px;">
    <div style="background: var(--card-bg, #111422); border: 1px solid var(--card-border, #1f2538); width: 100%; max-width: 400px; padding: 24px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); text-align: center;">
        <h3 style="font-family: 'Chakra Petch', sans-serif; font-size: 1.2rem; color: #fff; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 6px;">
            <ion-icon name="people-outline" style="color: var(--accent-cyan);"></ion-icon>
            เชิญเพื่อนเข้าเล่นห้อง
        </h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 18px;">ระบบจะส่งการแจ้งเตือนคำเชิญร่วมเล่นไปให้เพื่อนที่กำลังออนไลน์อยู่</p>
        
        <!-- Online Friends List -->
        <div id="online-friends-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 250px; overflow-y: auto; margin-bottom: 20px; text-align: left;">
            <p style="font-size: 0.85rem; color: var(--text-muted); text-align: center; margin-top: 15px;">กำลังโหลดรายชื่อเพื่อนที่ออนไลน์...</p>
        </div>
        
        <button onclick="closeFriendsInviteModal()" class="btn-primary btn-clay-blue" style="padding: 12px; font-size: 0.9rem; font-weight: 800; border-radius: 12px; margin: 0; width: 100%;">ปิดหน้าต่าง</button>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════
//  👤 SESSION DATA
// ═══════════════════════════════════════════════════════════════
const SESSION_NAME = <?= json_encode($_SESSION['real_name'] ?? $_SESSION['username'] ?? 'ผู้เล่น') ?>;
const SESSION_AVATAR = <?= json_encode($_SESSION['avatar_img'] ?? 'dog.png') ?>;
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
function playCorrectTone() { initAudio(); if (!audioCtx) return; const now = audioCtx.currentTime; const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain(); osc.type = 'sine'; osc.frequency.setValueAtTime(523.25, now); osc.frequency.setValueAtTime(659.25, now + 0.08); gain.gain.setValueAtTime(0.08, now); gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3); osc.connect(gain); gain.connect(audioCtx.destination); osc.start(now); osc.stop(now + 0.3); }
function playPassTone() { initAudio(); if (!audioCtx) return; const now = audioCtx.currentTime; const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain(); osc.type = 'sawtooth'; osc.frequency.setValueAtTime(220, now); osc.frequency.exponentialRampToValueAtTime(110, now + 0.25); gain.gain.setValueAtTime(0.06, now); gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25); osc.connect(gain); gain.connect(audioCtx.destination); osc.start(now); osc.stop(now + 0.25); }
function playTickTone() { initAudio(); if (!audioCtx) return; const now = audioCtx.currentTime; const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain(); osc.type = 'triangle'; osc.frequency.setValueAtTime(1000, now); gain.gain.setValueAtTime(0.05, now); gain.gain.exponentialRampToValueAtTime(0.001, now + 0.05); osc.connect(gain); gain.connect(audioCtx.destination); osc.start(now); osc.stop(now + 0.05); }
function playUrgentBeepTone() { initAudio(); if (!audioCtx) return; const now = audioCtx.currentTime; const osc1 = audioCtx.createOscillator(); const gain1 = audioCtx.createGain(); osc1.type = 'sine'; osc1.frequency.setValueAtTime(1500, now); gain1.gain.setValueAtTime(0.12, now); gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.08); osc1.connect(gain1); gain1.connect(audioCtx.destination); osc1.start(now); osc1.stop(now + 0.08); setTimeout(() => { if (!audioCtx) return; const now2 = audioCtx.currentTime; const osc2 = audioCtx.createOscillator(); const gain2 = audioCtx.createGain(); osc2.type = 'sine'; osc2.frequency.setValueAtTime(1500, now2); gain2.gain.setValueAtTime(0.12, now2); gain2.gain.exponentialRampToValueAtTime(0.001, now2 + 0.08); osc2.connect(gain2); gain2.connect(audioCtx.destination); osc2.start(now2); osc2.stop(now2 + 0.08); }, 120); }
function playGameOverFanfare() { initAudio(); if (!audioCtx) return; const now = audioCtx.currentTime; const chords = [523.25, 659.25, 783.99, 1046.50]; chords.forEach((freq, index) => { const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain(); osc.type = 'sine'; osc.frequency.setValueAtTime(freq, now + index * 0.1); gain.gain.setValueAtTime(0, now); gain.gain.linearRampToValueAtTime(0.05, now + index * 0.1 + 0.05); gain.gain.exponentialRampToValueAtTime(0.001, now + 1.2); osc.connect(gain); gain.connect(audioCtx.destination); osc.start(now + index * 0.1); osc.stop(now + 1.5); }); }

// ═══════════════════════════════════════════════════════════════
//  🕹️ GAME LOGIC STATE
// ═══════════════════════════════════════════════════════════════
let gameMode = 'local'; // 'local' or 'online'
let activeCategory = categories[0];
let wordPool = [];
let currentIndex = 0;
let score = 0;
let secondsRemaining = 60;
let customRoundDuration = 60;
let gameInterval = null;
let isSensorActive = false;
let tiltCooldown = false;
let roundRecords = [];
let baseGz = null;
let smoothGz = null;
let warmupFrames = 0;
let sensorLocked = false;
let sensorReceived = false;
let sensorCheckTimeout = null;
let isSensorListenerAdded = false;

// Multiplayer variables
let roomCode = null;
let isHost = false;
let pollInterval = null;
let lastLobbyData = null;

const EMA_ALPHA = 0.15;
const TRIGGER_THRESHOLD = 0.5;
const NEUTRAL_THRESHOLD = 0.15;
const WARMUP_FRAMES = 12;

// ═══════════════════════════════════════════════════════════════
//  📲 ROTATION / GENERAL UI HANDLERS
// ═══════════════════════════════════════════════════════════════
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

function selectCategory(cat) {
    activeCategory = cat;
    
    // Redirect taboo forbidden words category to the dedicated taboo game
    if (cat.id === 'forbidden_words') {
        window.location.href = '../taboo/index.php';
        return;
    }

    document.getElementById('selected-category-title').textContent = cat.title;
    switchScreen('screen-setup');
}

function switchScreen(screenId) {
    const screens = ['screen-category', 'screen-setup', 'screen-lobby', 'screen-play', 'screen-results', 'screen-countdown'];
    screens.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = (id === screenId) ? 'flex' : 'none';
    });
    
    const header = document.querySelector('.game-header');
    if (header) {
        header.style.display = (screenId === 'screen-play' || screenId === 'screen-countdown') ? 'none' : 'flex';
    }
    
    // Show/hide time adjuster depending on screen
    const fsAdjuster = document.getElementById('fs-adjuster-bar');
    if (fsAdjuster) {
        fsAdjuster.style.display = (screenId === 'screen-play') ? 'flex' : 'none';
    }
}

function switchMainMode(mode) {
    gameMode = mode;
    
    const localBtn = document.getElementById('btn-mode-local');
    const onlineBtn = document.getElementById('btn-mode-online');
    const onlinePanel = document.getElementById('online-auth-panel');
    const localPanel = document.getElementById('local-categories-panel');
    
    if (mode === 'local') {
        localBtn.classList.add('active');
        onlineBtn.classList.remove('active');
        onlinePanel.style.display = 'none';
        localPanel.style.display = 'block';
    } else {
        localBtn.classList.remove('active');
        onlineBtn.classList.add('active');
        onlinePanel.style.display = 'block';
        localPanel.style.display = 'none';
    }
    
    initAudio();
}

function switchOnlineTab(tab) {
    const createBtn = document.getElementById('btn-sub-create');
    const joinBtn = document.getElementById('btn-sub-join');
    const createTab = document.getElementById('tab-online-create');
    const joinTab = document.getElementById('tab-online-join');
    
    if (tab === 'create') {
        createBtn.classList.add('active');
        joinBtn.classList.remove('active');
        createTab.style.display = 'block';
        joinTab.style.display = 'none';
    } else {
        createBtn.classList.remove('active');
        joinBtn.classList.add('active');
        createTab.style.display = 'none';
        joinTab.style.display = 'block';
    }
    
    initAudio();
}

// ═══════════════════════════════════════════════════════════════
//  🎮 FONTS SIZE MANAGER
// ═══════════════════════════════════════════════════════════════
function changeWordFontSize(size) {
    const wordEl = document.getElementById('card-word');
    if (!wordEl) return;
    
    document.querySelectorAll('.size-opt-btn').forEach(btn => btn.classList.remove('active'));
    
    if (size === 'normal') {
        wordEl.style.fontSize = ''; 
    } else {
        wordEl.style.fontSize = size + 'px';
    }
    
    const targetText = size === 'normal' ? 'ปกติ' : size + 'px';
    document.querySelectorAll('.size-opt-btn').forEach(btn => {
        if (btn.textContent.trim() === targetText) {
            btn.classList.add('active');
        }
    });
    
    localStorage.setItem('head-guess-fontsize', size);
}

// ═══════════════════════════════════════════════════════════════
//  🔗 MULTIPLAYER LOBBY COMMANDS & API CONNECTORS
// ═══════════════════════════════════════════════════════════════
function createOnlineRoom() {
    const fd = new FormData();
    fd.append('player_name', SESSION_NAME);
    fd.append('avatar_icon', SESSION_AVATAR);
    
    fetch('api_room.php?action=create', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            roomCode = data.room_code;
            isHost = true;
            setupLobbyScreen();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาดในการสร้างห้อง');
        }
    })
    .catch(() => alert('การติดต่อเซิร์ฟเวอร์ล้มเหลว'));
}

function joinOnlineRoom() {
    let code = '';
    document.querySelectorAll('.otp-box').forEach(box => {
        code += box.value.trim();
    });
    
    if (code.length < 4) {
        alert('กรุณากรอกรหัสห้อง 4 หลักให้ครบถ้วน');
        return;
    }
    
    const fd = new FormData();
    fd.append('room_code', code);
    fd.append('player_name', SESSION_NAME);
    fd.append('avatar_icon', SESSION_AVATAR);
    
    fetch('api_room.php?action=join', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            roomCode = code;
            isHost = false;
            setupLobbyScreen();
        } else {
            alert(data.message || 'ไม่สามารถเข้าร่วมห้องได้');
        }
    })
    .catch(() => alert('การติดต่อเซิร์ฟเวอร์ล้มเหลว'));
}

function setupLobbyScreen() {
    document.getElementById('room-code-display').textContent = `ห้อง: ${roomCode}`;
    
    const hostPanel = document.getElementById('host-config-panel');
    const clientPanel = document.getElementById('client-config-panel');
    const startBtn = document.getElementById('btn-lobby-start');
    const readyBtn = document.getElementById('btn-lobby-ready');
    
    if (isHost) {
        hostPanel.style.display = 'flex';
        clientPanel.style.display = 'none';
        startBtn.style.display = 'block';
        readyBtn.style.display = 'none';
        
        // Populate category dropdown
        const catSelect = document.getElementById('lobby-category-select');
        catSelect.innerHTML = '';
        categories.forEach(cat => {
            if (cat.id !== 'forbidden_words') {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.title;
                catSelect.appendChild(opt);
            }
        });
    } else {
        hostPanel.style.display = 'none';
        clientPanel.style.display = 'block';
        startBtn.style.display = 'none';
        readyBtn.style.display = 'block';
    }
    
    switchScreen('screen-lobby');
    startLobbyPolling();
}

function updateLobbyConfig() {
    if (!isHost) return;
    
    const categoryId = document.getElementById('lobby-category-select').value;
    const duration = customRoundDuration;
    
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('category_id', categoryId);
    fd.append('seconds_remaining', duration);
    fd.append('game_mode', 'single');
    
    fetch('api_room.php?action=set_config', {
        method: 'POST',
        body: fd
    });
}

function setLobbyTime(seconds) {
    customRoundDuration = seconds;
    [30, 60, 90, 120].forEach(d => {
        const btn = document.getElementById(`l-time-${d}`);
        if (btn) btn.classList.toggle('active', d === seconds);
    });
    updateLobbyConfig();
}

function toggleOnlineReady() {
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('player_name', SESSION_NAME);
    
    fetch('api_room.php?action=ready', {
        method: 'POST',
        body: fd
    });
}

function switchMyLobbyRole(role) {
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('player_name', SESSION_NAME);
    fd.append('role', role);
    
    fetch('api_room.php?action=switch_role', {
        method: 'POST',
        body: fd
    });
}

function startLobbyPolling() {
    if (pollInterval) clearInterval(pollInterval);
    
    pollLobbyData();
    pollInterval = setInterval(pollLobbyData, 1000);
}

function pollLobbyData() {
    if (!roomCode) return;
    
    fetch(`api_room.php?action=poll&room_code=${roomCode}`)
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            lastLobbyData = data;
            renderLobbyPlayers(data.players);
            
            if (!isHost) {
                // Sync settings for clients
                const matchedCat = categories.find(c => c.id === data.category_id) || categories[0];
                document.getElementById('client-selected-cat').textContent = matchedCat.title;
                document.getElementById('client-selected-time').textContent = `${data.seconds_remaining} วินาที`;
                customRoundDuration = data.seconds_remaining;
                activeCategory = matchedCat;
                
                // Sync ready button text
                const me = data.players.find(p => p.player_name === SESSION_NAME);
                const rBtn = document.getElementById('ready-btn-text');
                if (me && rBtn) {
                    rBtn.textContent = me.is_ready ? 'ยกเลิกเตรียมพร้อม ❌' : 'เตรียมความพร้อม 🚀';
                }
            } else {
                // Host checks if all other players are ready
                const guests = data.players.filter(p => !p.is_host);
                const allReady = guests.every(p => p.is_ready);
                const startBtn = document.getElementById('btn-lobby-start');
                if (startBtn) {
                    startBtn.disabled = !allReady && guests.length > 0;
                    startBtn.style.opacity = startBtn.disabled ? '0.5' : '1';
                }
            }
            
            // Check status transition
            if (data.game_status === 'playing') {
                clearInterval(pollInterval);
                pollInterval = null;
                
                if (isHost) {
                    startCountdown();
                } else {
                    // Clients jump directly to gameplay screen in spectator mode
                    startSpectatorGameplay();
                }
            } else if (data.game_status === 'ended') {
                clearInterval(pollInterval);
                pollInterval = null;
                score = data.score;
                endGame();
            }
        } else {
            // Room closed or error
            exitOnlineRoom();
        }
    })
    .catch(() => {});
}

function renderLobbyPlayers(players) {
    const list = document.getElementById('lobby-players-list');
    list.innerHTML = '';
    
    players.forEach(p => {
        const card = document.createElement('div');
        card.className = 'lobby-player-card';
        
        let subText = p.is_host ? '👑 โฮสต์ / ผู้ถือจอทาย' : (p.is_ready ? '✅ พร้อมแล้ว' : '⏳ รอสแตนบาย');
        let badgeStyle = p.is_host ? 'color: #facc15;' : (p.is_ready ? 'color: var(--accent-cyan);' : 'color: var(--text-muted);');
        
        // Build Add Friend / Friend HTML
        let friendActionHtml = '';
        if (p.player_name !== SESSION_NAME && p.username !== SESSION_USERNAME) {
            if (p.friendship_status === 'accepted') {
                friendActionHtml = `<span style="color: var(--accent-cyan); font-size: 0.72rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; border: 1px solid rgba(0, 240, 255, 0.2); background: rgba(0, 240, 255, 0.04); padding: 4px 8px; border-radius: 8px;"><ion-icon name="people"></ion-icon>เพื่อน</span>`;
            } else if (p.friendship_status === 'pending') {
                friendActionHtml = `<span style="color: var(--text-muted); font-size: 0.72rem; font-weight: 600; border: 1px dashed var(--card-border); padding: 4px 8px; border-radius: 8px;">รอรับแอด...</span>`;
            } else {
                friendActionHtml = `<button onclick="sendLobbyFriendRequest(${p.user_id}, this)" class="btn-lobby-add-friend"><ion-icon name="person-add-outline" style="vertical-align: middle; margin-right: 2px;"></ion-icon>แอดเพื่อน</button>`;
            }
        }

        // Handle avatar fallback seed dynamically
        const avMapping = {'dog.png': '0', 'cat.png': '1', 'bear.png': '2', 'boy.png': '3', 'girl.png': '4'};
        const seed = avMapping[p.avatar_img] ?? '1';
        const avatarUrl = '../../assets/avatar/' + p.avatar_img;

        card.innerHTML = `
            <div class="player-identity">
                <div class="player-avatar-circle">
                    <img src="${avatarUrl}" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=${seed}'" alt="Avatar">
                </div>
                <div style="display: flex; flex-direction: column;">
                    <span style="font-weight: 700; font-size: 0.9rem; color: #fff;">${p.real_name || p.player_name}</span>
                    <span style="font-size: 0.7rem; ${badgeStyle}">${subText}</span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                ${friendActionHtml}
            </div>
        `;
        list.appendChild(card);
    });
}

function sendLobbyFriendRequest(friendId, btn) {
    if (!friendId) return;
    
    const fd = new FormData();
    fd.append('friend_id', friendId);
    
    fetch('../../api_social.php?action=add_friend', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            btn.outerHTML = `<span style="color: var(--text-muted); font-size: 0.72rem; font-weight: 600; border: 1px dashed var(--card-border); padding: 4px 8px; border-radius: 8px;">ส่งคำขอแล้ว</span>`;
        } else {
            alert(data.message || 'ส่งคำขอไม่สำเร็จ');
        }
    });
}

function startOnlineGame() {
    if (!isHost) return;
    
    // Choose category words & draw first word
    const categoryId = document.getElementById('lobby-category-select').value;
    const cat = categories.find(c => c.id === categoryId);
    activeCategory = cat;
    
    wordPool = [...activeCategory.words];
    // Shuffle
    for (let i = wordPool.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [wordPool[i], wordPool[j]] = [wordPool[j], wordPool[i]];
    }
    
    const firstWord = wordPool[0];
    
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('category_id', categoryId);
    fd.append('first_word', firstWord);
    
    fetch('api_room.php?action=start', {
        method: 'POST',
        body: fd
    });
}

function updateOnlineWordOnServer() {
    if (!isHost || gameMode !== 'online') return;
    
    const nextWord = wordPool[currentIndex];
    
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('word', nextWord);
    fd.append('score', score);
    fd.append('seconds_remaining', secondsRemaining);
    
    fetch('api_room.php?action=update_word', {
        method: 'POST',
        body: fd
    });
}

function startSpectatorGameplay() {
    switchScreen('screen-play');
    
    // Disable inputs / orientation
    isSensorActive = false;
    if (isSensorListenerAdded) {
        window.removeEventListener('deviceorientation', handleOrientation);
        isSensorListenerAdded = false;
    }
    
    document.getElementById('instruction-title-mode').textContent = "📢 หน้าจอสำหรับบอกคำใบ้! กรุณาช่วยพูดบอกโฮสต์";
    document.getElementById('tilt-debug').style.display = 'none';
    document.getElementById('manual-btn-toggle').style.display = 'none';
    document.getElementById('fallback-buttons').style.display = 'none';
    
    document.getElementById('card-category-label').textContent = activeCategory.title;
    
    // Start Spectator polling interval
    if (gameInterval) clearInterval(gameInterval);
    gameInterval = setInterval(pollSpectatorData, 1000);
}

function pollSpectatorData() {
    if (!roomCode) return;
    
    fetch(`api_room.php?action=poll&room_code=${roomCode}`)
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            if (data.game_status === 'ended') {
                clearInterval(gameInterval);
                score = data.score;
                endGame();
                return;
            }
            
            // Sync current word, score, time
            document.getElementById('card-word').textContent = data.current_word;
            document.getElementById('current-score').textContent = data.score;
            document.getElementById('game-time').textContent = data.seconds_remaining;
        } else {
            exitOnlineRoom();
        }
    })
    .catch(() => {});
}

function exitOnlineRoom() {
    if (pollInterval) clearInterval(pollInterval);
    if (gameInterval) clearInterval(gameInterval);
    pollInterval = null;
    gameInterval = null;
    
    if (roomCode) {
        const fd = new FormData();
        fd.append('room_code', roomCode);
        fd.append('player_name', SESSION_NAME);
        fetch('api_room.php?action=leave', {
            method: 'POST',
            body: fd
        });
    }
    
    roomCode = null;
    isHost = false;
    switchScreen('screen-category');
}

// ═══════════════════════════════════════════════════════════════
//  👥 LOBBY ONLINE FRIENDS INVITATION MODAL
// ═══════════════════════════════════════════════════════════════
function openFriendsInviteModal() {
    const listContainer = document.getElementById('online-friends-list');
    listContainer.innerHTML = '<p style="font-size: 0.85rem; color: var(--text-muted); text-align: center; margin-top: 15px;">กำลังค้นหาเพื่อนออนไลน์...</p>';
    document.getElementById('head-guess-friends-modal').style.display = 'flex';
    
    fetch('../../api_social.php?action=list_friends')
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            const onlineFriends = data.friends.filter(f => f.is_online);
            if (onlineFriends.length === 0) {
                listContainer.innerHTML = '<p style="font-size: 0.85rem; color: var(--text-muted); text-align: center; margin-top: 15px;">ไม่มีเพื่อนคนไหนออนไลน์ในขณะนี้ 📴</p>';
            } else {
                listContainer.innerHTML = onlineFriends.map(f => `
                    <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); padding: 10px 14px; border-radius: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <img src="${f.avatar}" style="width: 32px; height: 32px; border-radius: 50%;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'" alt="avatar">
                            <span style="font-weight: 700; font-size: 0.85rem; color: #fff;">${f.real_name}</span>
                        </div>
                        <button onclick="sendLobbyInvite(${f.user_id}, this)" class="btn-lobby-add-friend">เชิญเข้าร่วม</button>
                    </div>
                `).join('');
            }
        } else {
            listContainer.innerHTML = '<p style="font-size: 0.85rem; color: #ff453a; text-align: center;">ไม่สามารถโหลดข้อมูลเพื่อนได้</p>';
        }
    })
    .catch(() => {
        listContainer.innerHTML = '<p style="font-size: 0.85rem; color: #ff453a; text-align: center;">เชื่อมต่อเครือข่ายขัดข้อง</p>';
    });
}

function closeFriendsInviteModal() {
    document.getElementById('head-guess-friends-modal').style.display = 'none';
}

function sendLobbyInvite(friendId, btn) {
    if (!friendId || !roomCode) return;
    
    const fd = new FormData();
    fd.append('receiver_id', friendId);
    fd.append('room_code', roomCode);
    fd.append('game_name', 'head_guess');
    
    fetch('../../api_social.php?action=send_invite', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            btn.disabled = true;
            btn.textContent = 'เชิญแล้ว ✅';
            btn.style.background = 'rgba(255,255,255,0.05)';
            btn.style.color = 'var(--text-muted)';
            btn.style.borderColor = 'transparent';
        } else {
            alert(data.message || 'เชิญไม่สำเร็จ');
        }
    });
}

// ═══════════════════════════════════════════════════════════════
//  🕹️ SINGLE PLAYER GAMEPLAY LOGIC
// ═══════════════════════════════════════════════════════════════
function requestPermissionAndStart() {
    initAudio();
    
    const elem = document.documentElement;
    if (elem.requestFullscreen) {
        elem.requestFullscreen().catch(() => {});
    } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen();
    }
    
    if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
        DeviceOrientationEvent.requestPermission()
        .then(permissionState => {
            if (permissionState === 'granted') {
                if (!isSensorListenerAdded) {
                    window.addEventListener('deviceorientation', handleOrientation);
                    isSensorListenerAdded = true;
                }
                isSensorActive = true;
            }
            startCountdown();
        })
        .catch(err => {
            console.error(err);
            startCountdown();
        });
    } else {
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
    
    const rad = Math.PI / 180;
    const rawGz = Math.cos(beta * rad) * Math.cos(gamma * rad);
    
    if (smoothGz === null) {
        smoothGz = rawGz;
    } else {
        smoothGz = EMA_ALPHA * rawGz + (1 - EMA_ALPHA) * smoothGz;
    }
    
    if (baseGz === null) {
        baseGz = smoothGz;
        warmupFrames = 0;
        return;
    }
    
    warmupFrames++;
    const diffGz = smoothGz - baseGz;
    
    const debugEl = document.getElementById('tilt-debug');
    if (debugEl) {
        const bar = diffGz > 0 ? '▲'.repeat(Math.min(5, Math.round(diffGz * 10))) : '▼'.repeat(Math.min(5, Math.round(-diffGz * 10)));
        debugEl.textContent = `Gz: ${smoothGz.toFixed(2)} Δ${diffGz.toFixed(2)} ${bar} ${sensorLocked ? '🔒' : (warmupFrames < WARMUP_FRAMES ? '⏳' : '✅')}`;
    }
    
    if (sensorLocked) {
        if (Math.abs(diffGz) < NEUTRAL_THRESHOLD) {
            sensorLocked = false;
            const cardBox = document.getElementById('card-box');
            if (cardBox) cardBox.className = "word-card";
        }
        return;
    }
    
    if (tiltCooldown) return;
    if (warmupFrames < WARMUP_FRAMES) return;
    
    if (diffGz < -TRIGGER_THRESHOLD) {
        sensorLocked = true;
        triggerCorrect();
    } else if (diffGz > TRIGGER_THRESHOLD) {
        sensorLocked = true;
        triggerPass();
    }
}

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
    
    // Normal gameplay screen adjustments
    document.getElementById('instruction-title-mode').textContent = "พร้อมเอียงหน้าจอเพื่อเริ่มควบคุมคำศัพท์";
    document.getElementById('tilt-debug').style.display = 'block';
    document.getElementById('manual-btn-toggle').style.display = 'inline-block';
    
    const cardBox = document.getElementById('card-box');
    if (cardBox) {
        cardBox.style.padding = ''; cardBox.style.gap = ''; cardBox.style.flexDirection = ''; cardBox.style.justifyContent = '';
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
    
    // Restore size preference
    const savedFontSize = localStorage.getItem('head-guess-fontsize') || 'normal';
    changeWordFontSize(savedFontSize);
    
    if (gameMode === 'local') {
        wordPool = [...activeCategory.words];
        for (let i = wordPool.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [wordPool[i], wordPool[j]] = [wordPool[j], wordPool[i]];
        }
    }
    
    currentIndex = 0; score = 0; secondsRemaining = customRoundDuration; roundRecords = [];
    tiltCooldown = false; baseGz = null; smoothGz = null; warmupFrames = 0; sensorLocked = false;
    
    const btnContainer = document.getElementById('fallback-buttons');
    if (btnContainer) btnContainer.style.display = 'none';
    document.getElementById('current-score').textContent = score;
    document.getElementById('game-time').textContent = secondsRemaining;
    document.getElementById('card-category-label').textContent = activeCategory.title;
    
    loadNextWord();
    
    // Fallback if sensor does not trigger quickly
    sensorReceived = false;
    if (sensorCheckTimeout) clearTimeout(sensorCheckTimeout);
    sensorCheckTimeout = setTimeout(() => { 
        if (!sensorReceived && btnContainer && gameMode === 'local') btnContainer.style.display = 'flex'; 
    }, 2000);

    if (gameInterval) clearInterval(gameInterval);
    gameInterval = setInterval(() => {
        secondsRemaining--;
        document.getElementById('game-time').textContent = secondsRemaining;
        
        if (secondsRemaining <= 10 && secondsRemaining > 5) { playTickTone(); }
        else if (secondsRemaining <= 5 && secondsRemaining > 0) { playUrgentBeepTone(); }
        
        if (gameMode === 'online' && isHost) {
            updateOnlineWordOnServer();
        }
        
        if (secondsRemaining <= 0) { 
            if (gameMode === 'online' && isHost) {
                const fd = new FormData();
                fd.append('room_code', roomCode);
                fetch('api_room.php?action=end', { method: 'POST', body: fd });
            } else {
                endGame(); 
            }
        }
    }, 1000);
}

function loadNextWord() {
    if (currentIndex >= wordPool.length) {
        wordPool = [...activeCategory.words];
        for (let i = wordPool.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [wordPool[i], wordPool[j]] = [wordPool[j], wordPool[i]];
        }
        currentIndex = 0;
    }
    
    const wordEl = document.getElementById('card-word');
    if (wordEl) {
        wordEl.textContent = wordPool[currentIndex];
        wordEl.className = "word-text";
    }
    
    const box = document.getElementById('card-box');
    if (box) box.className = "word-card";
    
    document.getElementById('card-category-label').textContent = activeCategory.title;
}

function triggerCorrect() {
    if (tiltCooldown) return; tiltCooldown = true;
    
    const box = document.getElementById('card-box');
    const wordEl = document.getElementById('card-word');
    if (box) box.className = "word-card correct";
    if (wordEl) wordEl.textContent = "ถูกต้อง! ✅";
    
    score++;
    roundRecords.push({ word: wordPool[currentIndex], status: 'yes' });
    document.getElementById('current-score').textContent = score;
    playCorrectTone(); 
    
    currentIndex++;
    
    if (gameMode === 'online' && isHost) {
        updateOnlineWordOnServer();
    }
    
    setTimeout(() => { loadNextWord(); tiltCooldown = false; }, 1200);
}

function triggerPass() {
    if (tiltCooldown) return; tiltCooldown = true;
    
    const box = document.getElementById('card-box');
    const wordEl = document.getElementById('card-word');
    if (box) box.className = "word-card pass";
    if (wordEl) wordEl.textContent = "ข้ามคำศัพท์ ❌";
    
    roundRecords.push({ word: wordPool[currentIndex], status: 'no' });
    playPassTone(); 
    
    currentIndex++;
    
    if (gameMode === 'online' && isHost) {
        updateOnlineWordOnServer();
    }
    
    setTimeout(() => { loadNextWord(); tiltCooldown = false; }, 1200);
}

function endGame() {
    if (gameInterval) clearInterval(gameInterval);
    playGameOverFanfare();
    
    if (document.exitFullscreen) { document.exitFullscreen().catch(() => {}); }
    
    document.getElementById('final-score').textContent = score;
    
    let rank = "ROOKIE (มือใหม่หัดเล่น)"; let rankColor = "#94a3b8";
    if (score > 3 && score <= 7) { rank = "IT GEEK (เซียนไอที)"; rankColor = "#38bdf8"; }
    else if (score > 7 && score <= 12) { rank = "CYBER AGENT (สายลับไซเบอร์)"; rankColor = "#a78bfa"; }
    else if (score > 12) { rank = "NEON LEGEND (ตำนานนีออน)"; rankColor = "#f43f5e"; }
    
    const rankEl = document.getElementById('rank-name');
    if (rankEl) {
        rankEl.textContent = rank; rankEl.style.color = rankColor;
        if (rankEl.parentElement) { rankEl.parentElement.style.borderColor = rankColor; }
    }
    
    document.getElementById('summary-count').textContent = `ทายทั้งหมด ${roundRecords.length} คำ | ถูก ${roundRecords.filter(r => r.status === 'yes').length} คำ`;
    
    // On replay action adjustments
    const replayBtn = document.getElementById('btn-result-replay');
    if (gameMode === 'online') {
        replayBtn.style.display = isHost ? 'inline-block' : 'none';
    } else {
        replayBtn.style.display = 'inline-block';
    }
    
    switchScreen('screen-results');
    
    const summaryContainer = document.getElementById('summary-items');
    summaryContainer.innerHTML = '';
    roundRecords.forEach(rec => {
        const item = document.createElement('div');
        item.className = `summary-item ${rec.status}`;
        item.innerHTML = `<span>${rec.word}</span><span class="item-status ${rec.status}">${rec.status === 'yes' ? 'ถูก ✅' : 'ข้าม ❌'}</span>`;
        summaryContainer.appendChild(item);
    });
}

function replayGame() {
    if (gameMode === 'online') {
        if (!isHost) return;
        const fd = new FormData();
        fd.append('room_code', roomCode);
        fetch('api_room.php?action=reset', { method: 'POST', body: fd })
        .then(() => {
            setupLobbyScreen();
        });
    } else {
        startCountdown();
    }
}

function backToCategories() {
    if (gameInterval) { clearInterval(gameInterval); gameInterval = null; }
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    
    if (gameMode === 'online') {
        exitOnlineRoom();
    } else {
        switchScreen('screen-category');
    }
}

function exitGameToCategories() {
    if (gameInterval) clearInterval(gameInterval);
    if (document.exitFullscreen) { document.exitFullscreen().catch(() => {}); }
    
    if (gameMode === 'online') {
        exitOnlineRoom();
    } else {
        switchScreen('screen-category');
    }
}

// ═══════════════════════════════════════════════════════════════
//  🛠️ SYSTEM UI UTILS
// ═══════════════════════════════════════════════════════════════
function toggleManualButtons() { const btnContainer = document.getElementById('fallback-buttons'); if (btnContainer) btnContainer.style.display = (btnContainer.style.display === 'none') ? 'flex' : 'none'; }
function toggleFullscreen() { const icon = document.getElementById('fs-icon'); if (!document.fullscreenElement) { document.documentElement.requestFullscreen().catch(() => {}); if (icon) icon.setAttribute('name', 'contract-outline'); } else { document.exitFullscreen(); if (icon) icon.setAttribute('name', 'expand-outline'); } }
document.addEventListener('fullscreenchange', () => { const icon = document.getElementById('fs-icon'); if (icon) icon.setAttribute('name', document.fullscreenElement ? 'contract-outline' : 'expand-outline'); });

function toggleTheme() {
    const body = document.body;
    body.classList.toggle('theme-cyber');
    const isCyber = body.classList.contains('theme-cyber');
    localStorage.setItem('head-guess-theme', isCyber ? 'cyber' : 'ios');
    initAudio();
    if (audioCtx) {
        const now = audioCtx.currentTime; const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain();
        osc.type = 'sine'; osc.frequency.setValueAtTime(isCyber ? 880 : 660, now);
        gain.gain.setValueAtTime(0.04, now); gain.gain.exponentialRampToValueAtTime(0.001, now + 0.15);
        osc.connect(gain); gain.connect(audioCtx.destination); osc.start(now); osc.stop(now + 0.15);
    }
}

function setRoundTime(seconds) {
    customRoundDuration = seconds;
    initAudio();
    if (audioCtx) {
        const now = audioCtx.currentTime; const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain();
        osc.type = 'sine'; osc.frequency.setValueAtTime(440, now);
        gain.gain.setValueAtTime(0.04, now); gain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
        osc.connect(gain); gain.connect(audioCtx.destination); osc.start(now); osc.stop(now + 0.08);
    }
    [30, 60, 90, 120].forEach(d => {
        const btn = document.getElementById(`time-opt-${d}`);
        if (btn) btn.classList.toggle('active', d === seconds);
    });
}

// OTP Auto-focus logic
document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('head-guess-theme') === 'cyber') document.body.classList.add('theme-cyber');
    
    // OTP Boxes Focus switching
    const otpBoxes = document.querySelectorAll('.otp-box');
    otpBoxes.forEach((box, idx) => {
        box.addEventListener('input', (e) => {
            if (box.value.length === 1 && idx < otpBoxes.length - 1) {
                otpBoxes[idx + 1].focus();
            }
        });
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && box.value.length === 0 && idx > 0) {
                otpBoxes[idx - 1].focus();
            }
        });
    });
    
    renderCategories();
});
</script>

</body>
</html>