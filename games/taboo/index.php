<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}
$roomParam = $_GET['room'] ?? '';
$forbidden_words = ["ใช่", "ไม่ใช่", "เอ่อ...", "ครับ / ค่ะ", "จริงดิ", "ร้อน", "หิว", "นอน", "ไป", "กิน", "ทำไม", "อะไร", "เมื่อไหร่", "บ้า", "ชอบ", "รัก", "โอเค (OK)", "คิด", "พูด", "ดู", "เจ็บ", "เหนื่อย", "สวย", "หล่อ", "แพง", "ง่าย", "ยาก", "สนุก", "ซื้อ", "ขาย", "หยุด", "เดิน", "วิ่ง", "หัวเราะ", "ร้องไห้", "กลัว", "เบื่อ", "ชื่อ", "บ้าน", "โทรศัพท์", "พี่ / น้อง", "เพื่อน", "เงิน", "ข้าว"];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>🚫 ปาร์ตี้คำต้องห้าม — TABOO MULTI</title>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;700&family=Outfit:wght@400;600;800&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="style.css?v=<?=time();?>">
</head>
<body class="theme-cyber">

<div class="ambient-gradient bg-orb-1"></div>
<div class="ambient-gradient bg-orb-2"></div>
<div class="ambient-gradient bg-orb-3"></div>

<div class="game-container">

    <header class="game-header">
        <a href="../../index.php" class="back-btn" onclick="exitTabooRoom()">
            <ion-icon name="chevron-back-outline"></ion-icon>
            กลับหน้าหลัก
        </a>
        <h1 class="hud-title">TABOO CHAMP</h1>
        <div style="display: flex; align-items: center; gap: 8px;">
            <button class="icon-toggle-btn" onclick="toggleTabooTheme()">
                <ion-icon name="color-palette-outline"></ion-icon>
            </button>
        </div>
    </header>

    <!-- 1. SETUP / ENTRY SCREEN -->
    <div id="taboo-setup-entry" class="category-screen">
        <div class="instruction-card">
            <div class="game-icon-badge">🚫</div>
            <h2 class="title-main">ปาร์ตี้คำต้องห้าม</h2>
            <p class="desc-main">เกมแอบจับผิดคำต้องห้ามแบ่งทีม 3v3 / 6v6 หรือเล่นบทบาทผู้คุมเกม</p>
            
            <div class="user-profile-bar">
                <?php
                    $displayName = htmlspecialchars($_SESSION['real_name'] ?? $_SESSION['username'] ?? 'ผู้เล่น');
                    $avatarSrc = 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($displayName) . '&backgroundColor=b6e3f4,c0aede,d1c4e9';
                ?>
                <img src="<?= $avatarSrc ?>" alt="avatar" class="avatar-img">
                <div class="profile-meta">
                    <div class="name-display"><?= $displayName ?></div>
                    <div class="tag-display">@<?= htmlspecialchars($_SESSION['username'] ?? '') ?></div>
                </div>
            </div>

            <div class="actions-group">
                <!-- Floating Tabber -->
                <div class="tabber-container">
                    <button class="tab-btn active" data-tab="create" onclick="switchTab('create')">
                        <ion-icon name="add-circle-outline"></ion-icon>สร้างห้อง
                    </button>
                    <button class="tab-btn" data-tab="join" onclick="switchTab('join')">
                        <ion-icon name="enter-outline"></ion-icon>เข้าร่วมห้อง
                    </button>
                </div>

                <!-- Tab Content: Create Room -->
                <div id="tab-content-create" class="tab-content">
                    <div class="tab-desc-card">
                        <p class="tab-info-text">✨ สร้างห้องใหม่เพื่อเป็นหัวหน้าห้อง (Host) กำหนดกติกา และชวนเพื่อนร่วมวงปาร์ตี้</p>
                        <button class="btn-primary btn-clay-blue" onclick="createTabooRoom()">สร้างห้องใหม่ 👑</button>
                    </div>
                </div>

                <!-- Tab Content: Join Room -->
                <div id="tab-content-join" class="tab-content" style="display: none;">
                    <div class="join-room-section">
                        <div class="otp-inputs-container">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="0" placeholder="•" autocomplete="off">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="1" placeholder="•" autocomplete="off">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="2" placeholder="•" autocomplete="off">
                            <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="3" placeholder="•" autocomplete="off">
                        </div>
                        <input type="hidden" id="taboo-room-code" value="<?= htmlspecialchars($roomParam) ?>">
                        <button class="btn-primary btn-clay-pink btn-join" onclick="joinTabooRoom()">
                            <ion-icon name="enter-outline" style="font-size: 1.25rem;"></ion-icon>
                            เข้าร่วมห้องปาร์ตี้ 🚀
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. LOBBY WAITING SCREEN -->
    <div id="taboo-lobby-waiting" class="setup-screen" style="display: none; width: 100%;">
        <div class="instruction-card">
            
            <!-- Room Title, Invite Link and Leave Room -->
            <div class="lobby-header-row">
                <div>
                    <h2 class="lobby-room-title" id="taboo-room-title">ห้อง: 0000</h2>
                    <span class="lobby-room-subtitle">แชร์รหัสให้เพื่อนเข้าเล่น</span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button class="btn-invite-link" onclick="openFriendsInviteModal()" style="background: rgba(161, 0, 255, 0.08); color: var(--neon-purple); border-color: rgba(161, 0, 255, 0.15);">
                        <ion-icon name="people-outline" style="font-size: 1.15rem;"></ion-icon>
                        เชิญเพื่อนออนไลน์
                    </button>
                    <button class="btn-invite-link" onclick="copyInviteLink()">
                        <ion-icon name="share-social-outline" style="font-size: 1.1rem;"></ion-icon>
                        เชิญเพื่อน
                    </button>
                    <button class="btn-leave-room" onclick="exitTabooRoom()">ออกห้อง</button>
                </div>
            </div>

            <!-- Host Config Options -->
            <div id="host-config-panel" style="display: none; flex-direction: column; gap: 16px; margin-bottom: 20px;">
                <div>
                    <span class="config-label">รูปแบบการเล่น:</span>
                    <div class="segment-container">
                        <button class="segment-btn active" id="mode-single" onclick="setTabooMode('single')">Solo</button>
                        <button class="segment-btn" id="mode-team_3v3" onclick="setTabooMode('team_3v3')">ทีม 3v3</button>
                        <button class="segment-btn" id="mode-team_6v6" onclick="setTabooMode('team_6v6')">ทีม 6v6</button>
                    </div>
                </div>
                <div>
                    <span class="config-label">เวลาในการแข่ง:</span>
                    <div class="segment-container">
                        <button class="segment-btn active" onclick="setTabooTime(120)" id="t-120">2 นาที</button>
                        <button class="segment-btn" onclick="setTabooTime(300)" id="t-300">5 นาที</button>
                        <button class="segment-btn" onclick="setTabooTime(600)" id="t-600">10 นาที</button>
                    </div>
                </div>
            </div>

            <!-- Role selectors -->
            <div class="role-selector-section" style="margin-bottom: 20px;">
                <span class="config-label">เลือกบทบาทของคุณ:</span>
                <div class="role-grid">
                    <button class="role-btn" onclick="switchMyTabooRole('A')" id="role-btn-A">ทีม A</button>
                    <button class="role-btn" onclick="switchMyTabooRole('B')" id="role-btn-B">ทีม B</button>
                    <button class="role-btn" onclick="switchMyTabooRole('spectator')" id="role-btn-spec">👁️ ผู้ชม</button>
                    <button class="role-btn" onclick="switchMyTabooRole('controller')" id="role-btn-ctrl">🚨 ผู้คุมเกม</button>
                </div>
            </div>

            <!-- Slots and List display -->
            <div class="lobby-members-box">
                <span class="config-label" style="margin-bottom: 8px;">ผู้เข้าร่วมในห้อง:</span>
                
                <!-- Solo / FFA view -->
                <div id="solo-lobby-list" class="slots-vertical-list"></div>
                
                <!-- Team 3v3 / 6v6 view -->
                <div id="teams-lobby-grid" class="teams-container" style="display: none;">
                    <div class="team-panel">
                        <div class="team-header team-a-header">🔴 TEAM A</div>
                        <div id="team-a-slots" class="team-slots-list"></div>
                    </div>
                    <div class="team-panel">
                        <div class="team-header team-b-header">🔵 TEAM B</div>
                        <div id="team-b-slots" class="team-slots-list"></div>
                    </div>
                </div>
                
                <!-- Spectators and Controller list -->
                <div class="spec-controllers-list-row" style="margin-top: 14px;">
                    <div class="other-role-sublist">
                        <div class="sublist-title">🚨 ผู้คุมเกม (GM):</div>
                        <div id="lobby-controllers-list" class="sublist-content">-</div>
                    </div>
                    <div class="other-role-sublist">
                        <div class="sublist-title">👁️ ผู้ชม (Spec):</div>
                        <div id="lobby-spectators-list" class="sublist-content">-</div>
                    </div>
                </div>
            </div>

            <!-- Start / Ready Actions -->
            <div style="margin-top: 20px; width: 100%;">
                <button class="btn-primary btn-clay-blue" id="taboo-ready-btn" onclick="toggleTabooReady()" style="padding: 16px;">
                    พร้อมเข้าแข่ง 👍
                </button>
                <button class="btn-primary btn-clay-green" id="taboo-start-btn" onclick="processStartTabooGame()" style="display: none; padding: 16px;">
                    เริ่มเล่นเกม 🚀
                </button>
            </div>
        </div>
    </div>

    <!-- 3. GAMEPLAY SCREEN -->
    <div id="screen-play" class="play-screen" style="display: none;">
        <div class="game-hud">
            <button onclick="confirmExitDuringGame()" class="btn-hud-exit">ออกห้อง</button>
            <div class="hud-score" id="taboo-hud-room">ห้อง: ----</div>
            <div class="hud-timer" id="taboo-timer-hud">เวลา: 02:00</div>
            <button id="end-game-btn" onclick="hostEndTabooGame()" class="btn-hud-end" style="display: none;">
                จบแมตช์ 🛑
            </button>
        </div>
        <div class="word-card-container">
            <div class="word-card" id="card-box">
                <!-- Word card is rendered dynamically by JS depending on player's role -->
            </div>
        </div>
    </div>

</div>

<script>
const SESSION_NAME = <?= json_encode($displayName) ?>;
const SESSION_AVATAR = <?= json_encode($avatarSrc) ?>;
const WORD_POOL = <?= json_encode($forbidden_words) ?>;
const INITIAL_ROOM_PARAM = <?= json_encode($roomParam) ?>;
</script>
<script src="script.js?v=<?=time();?>"></script>
<script>
function switchTab(tabId) {
    // Update active tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        if (btn.getAttribute('data-tab') === tabId) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Toggle content displays
    const createContent = document.getElementById('tab-content-create');
    const joinContent = document.getElementById('tab-content-join');

    if (tabId === 'create') {
        createContent.style.display = 'block';
        joinContent.style.display = 'none';
    } else {
        createContent.style.display = 'block';
        createContent.style.display = 'none'; // hide create
        joinContent.style.display = 'block';
        // Auto-focus on first OTP box
        const firstOtp = document.querySelector('.otp-box[data-index="0"]');
        if (firstOtp) firstOtp.focus();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const otpBoxes = document.querySelectorAll('.otp-box');
    const hiddenInput = document.getElementById('taboo-room-code');

    function updateHiddenInput() {
        let code = '';
        otpBoxes.forEach(box => {
            code += box.value;
        });
        hiddenInput.value = code;
    }

    // Pre-fill otp boxes if hiddenInput already has a value
    if (hiddenInput && hiddenInput.value.length === 4) {
        const val = hiddenInput.value;
        otpBoxes.forEach((box, i) => {
            box.value = val[i] || '';
            if (box.value) box.classList.add('filled');
        });
        
        // Auto switch to join tab on page load if parameter is present
        switchTab('join');
    }

    otpBoxes.forEach((box, index) => {
        // Handle character input
        box.addEventListener('input', (e) => {
            const val = e.target.value.replace(/[^0-9]/g, '');
            box.value = val.substring(0, 1);

            if (box.value) {
                box.classList.add('filled');
                // Move to next box
                if (index < otpBoxes.length - 1) {
                    otpBoxes[index + 1].focus();
                }
            } else {
                box.classList.remove('filled');
            }
            updateHiddenInput();
        });

        // Handle backspace key
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace') {
                if (!box.value && index > 0) {
                    otpBoxes[index - 1].focus();
                    otpBoxes[index - 1].value = '';
                    otpBoxes[index - 1].classList.remove('filled');
                    updateHiddenInput();
                } else {
                    box.value = '';
                    box.classList.remove('filled');
                    updateHiddenInput();
                }
            }
        });

        // Handle paste event
        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
            if (text.length >= 4) {
                otpBoxes.forEach((b, i) => {
                    b.value = text[i];
                    b.classList.add('filled');
                });
                updateHiddenInput();
                otpBoxes[3].focus();
            }
        });

        // Select all text on focus
        box.addEventListener('focus', () => {
            box.select();
        });
    });
});
</script>
<!-- 👥 ONLINE FRIENDS INVITATION MODAL -->
<div id="taboo-friends-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center; padding: 16px;">
    <div style="background: rgba(13, 20, 35, 0.95); border: 2px solid var(--neon-purple); border-radius: 24px; padding: 24px; max-width: 400px; width: 100%; text-align: center; box-shadow: 0 20px 50px rgba(161,0,255,0.35);">
        <h3 style="font-family: 'Chakra Petch', sans-serif; font-size: 1.25rem; font-weight: 900; margin-bottom: 12px; color: #fff; letter-spacing: 1px;">เชิญเพื่อนออนไลน์</h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 16px;">ส่งคำเชิญตรงเข้าสู่เบราว์เซอร์ของเพื่อนที่กำลังออนไลน์</p>
        
        <!-- Online Friends List -->
        <div id="taboo-online-friends-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 250px; overflow-y: auto; margin-bottom: 20px; text-align: left;">
            <div style="font-size:0.8rem; color:var(--text-muted); text-align:center; padding:16px;">กำลังดึงข้อมูลเพื่อน...</div>
        </div>
        
        <button onclick="closeFriendsInviteModal()" class="btn-primary btn-clay-blue" style="padding: 12px; font-size: 0.9rem; font-weight: 800; border-radius: 12px; margin: 0;">ปิดหน้าต่าง</button>
    </div>
</div>

</body>
</html>
