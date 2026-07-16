<?php
// mobile.php - Player Controller View for Blitz Quiz
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>รีโมทคอนโทรล 📱 ปริศนาสายฟ้า</title>
    <link href="style.css?v=<?=time();?>" rel="stylesheet" type="text/css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body style="min-height: 100vh; display: flex; flex-direction: column;">

<div class="grid-overlay"></div>

<div class="game-container" id="mobile-app" style="padding: 16px;">
    <!-- RENDERED BY JAVASCRIPT -->
</div>

<script>
let roomCode = "<?php echo $_GET['room'] ?? ''; ?>";
let userId = <?php echo $_SESSION['user_id']; ?>;
let pollInterval = null;
let lastSelectedChoice = null;

function initMobile() {
    if (!roomCode) {
        renderJoinForm();
    } else {
        startPolling();
    }
}

function renderJoinForm() {
    const app = document.getElementById('mobile-app');
    app.innerHTML = `
        <div class="setup-screen" style="margin-top: 30px; padding: 25px;">
            <h2 style="font-family: 'Chakra Petch', sans-serif; font-size: 1.6rem; color: #fff;">📱 เข้าร่วมรหัสห้องเล่น</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 8px; margin-bottom: 20px;">ป้อนรหัสห้อง 4 หลักที่แสดงอยู่บนจอคอมพิวเตอร์หลัก</p>
            
            <form onsubmit="joinRoom(event)" id="join-form">
                <div style="margin-bottom: 20px;">
                    <input type="number" id="room-input" required 
                           style="width: 100%; padding: 15px; background: rgba(0,0,0,0.5); border: 2px solid var(--neon-cyan); border-radius: 12px; color: #fff; font-size: 2rem; font-weight: bold; text-align: center; letter-spacing: 5px; font-family: 'Chakra Petch';"
                           placeholder="0000" pattern="[0-9]{4}" min="1000" max="9999">
                </div>
                
                <button type="submit" class="btn-ctrl primary" style="width: 100%; justify-content: center; padding: 15px; border-radius: 12px; font-size: 1.1rem;">
                    เชื่อมต่อเข้าร่วมห้อง 🔌
                </button>
            </form>
            
            <a href="../../index.php" class="btn-ctrl secondary" style="margin-top: 15px; display: inline-flex; justify-content: center; text-decoration: none; width: 100%;">
                กลับหน้าหลัก
            </a>
        </div>
    `;
}

function joinRoom(e) {
    e.preventDefault();
    const code = document.getElementById('room-input').value.trim();
    if (!code) return;
    
    const fd = new FormData();
    fd.append('room_code', code);
    
    fetch('api_room.php?action=join', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                roomCode = data.room_code;
                window.history.replaceState(null, null, `?room=${roomCode}`);
                startPolling();
            } else {
                alert(data.message);
            }
        });
}

function startPolling() {
    pollState();
    pollInterval = setInterval(pollState, 1200);
}

function pollState() {
    fetch(`api_room.php?action=poll&room_code=${roomCode}`)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                renderMobileInterface(data);
            } else {
                clearInterval(pollInterval);
                alert('ห้องนี้ไม่มีอยู่ หรือ ปิดตัวลงแล้ว');
                roomCode = '';
                window.history.replaceState(null, null, '?');
                initMobile();
            }
        });
}

function renderMobileInterface(data) {
    const app = document.getElementById('mobile-app');
    if (!app) return;
    
    const room = data.room;
    const isMyTurn = room.current_player_id === userId;
    
    if (room.game_status === 'setup') {
        // --- LOBBY WAITING SCREEN ---
        app.innerHTML = `
            <div class="setup-screen" style="margin-top: 20px; padding: 25px;">
                <h2 style="font-family:'Chakra Petch'; color:var(--neon-cyan);">🔌 เชื่อมต่อแล้ว</h2>
                <div class="room-badge" style="font-size:2.2rem; margin:10px 0;">ห้อง: ${room.room_code}</div>
                <p style="color:var(--neon-green); font-weight:bold; margin-bottom:20px;">🛡️ รอโฮสต์ (หน้าจอหลัก) เลือกตัวละครของคุณเพื่อเริ่มเล่น...</p>
                
                <div style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:15px; border-radius:16px; text-align:left; margin-bottom:20px;">
                    <strong style="color:var(--text-muted); font-size:0.85rem; display:block; margin-bottom:10px;">สายลับในห้องนี้:</strong>
                    <div style="max-height: 150px; overflow-y: auto;">
                        ${data.players.map(p => `
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                                <img src="../../assets/avatar/${p.avatar_img || 'dog.png'}" style="width:24px; height:24px;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                                <span style="font-size:0.95rem; color:${p.user_id === userId ? 'var(--neon-cyan); font-weight:bold;' : '#fff'}">${p.real_name}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <button onclick="leaveRoom()" class="btn-ctrl danger" style="width:100%; justify-content:center; padding:12px;">
                    ออกจากห้อง (Leave Room)
                </button>
            </div>
        `;
    } else if (room.game_status === 'playing') {
        if (isMyTurn) {
            // --- ACTIVE CONTROLLER SCREEN ---
            const question = data.question;
            const choices = question ? question.choices : [];
            const min = String(Math.floor(room.seconds_remaining / 60)).padStart(2, '0');
            const sec = String(room.seconds_remaining % 60).padStart(2, '0');
            
            // Check if user has already tapped a choice in this state
            if (room.selected_choice === null) {
                lastSelectedChoice = null;
            }
            
            app.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <div style="font-family:'Chakra Petch'; font-weight:bold; color:var(--neon-green);">
                        ⚡ เทิร์นของคุณแล้ว!
                    </div>
                    <div style="font-size:1.3rem; font-weight:900; font-family:'Chakra Petch'; color:#ef4444; border:1px solid rgba(239, 68, 68, 0.2); background:rgba(239,68,68,0.05); padding:3px 12px; border-radius:8px;">
                        ⏰ ${min}:${sec}
                    </div>
                </div>
                
                <div style="background:rgba(6, 182, 212, 0.08); border:1px solid var(--neon-cyan); border-radius:16px; padding:15px; margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:0.8rem; color:var(--text-muted); font-weight:bold;">
                        <span>ความสูงสะสม:</span>
                        <span>เป้าหมาย 10 ขั้น</span>
                    </div>
                    <div style="font-size:1.5rem; font-weight:900; color:var(--neon-cyan); font-family:'Chakra Petch';">
                        ขั้นบันไดที่ ${room.score} / 10 
                    </div>
                </div>
                
                <div class="choices-grid" style="grid-template-columns:1fr; gap:12px;">
                    ${choices.map((c, i) => {
                        const isTapped = lastSelectedChoice === c || room.selected_choice === c;
                        const hasSelectedAny = room.selected_choice !== null;
                        
                        return `
                            <button onclick="selectChoice('${c}')" 
                                    class="choice-btn ${isTapped ? 'selected' : ''}" 
                                    style="width:100%; border-radius:14px; padding:18px; text-align:left; font-size:1.1rem; line-height:1.4;"
                                    ${hasSelectedAny ? 'disabled style="opacity:0.65; pointer-events:none;"' : ''}>
                                <div style="display:flex; align-items:center;">
                                    <span class="choice-label" style="width:28px; height:28px; font-size:0.9rem; margin-right:12px;">${String.fromCharCode(65 + i)}</span>
                                    <span style="font-weight:bold;">${c}</span>
                                </div>
                            </button>
                        `;
                    }).join('')}
                </div>
                
                <div style="text-align:center; color:var(--text-muted); font-size:0.8rem; margin-top:20px; font-family:'Chakra Petch';">
                    ${room.selected_choice !== null ? '⏳ รอโฮสต์เฉลยคำตอบ...' : '👉 กดเลือกคำตอบเพื่อส่งไปยังจอคอมพิวเตอร์'}
                </div>
            `;
        } else {
            // --- SPECTATOR VIEW ---
            app.innerHTML = `
                <div class="setup-screen" style="margin-top: 20px; padding: 25px;">
                    <h2 style="font-family:'Chakra Petch'; font-size:1.3rem; color:var(--neon-orange);">🎮 การแข่งกำลังดำเนิน</h2>
                    <p style="color:var(--text-muted); font-size:0.9rem; margin-top:8px; margin-bottom:20px;">สายลับท่านอื่นกำลังตอบคำถาม</p>
                    
                    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); padding:20px; border-radius:20px; margin-bottom:20px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:10px;">
                        <img src="../../assets/avatar/${room.player_avatar || 'dog.png'}" style="width:50px; height:50px;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                        <div>
                            <span style="font-size:0.8rem; color:var(--text-muted); display:block;">ผู้ตอบขณะนี้:</span>
                            <strong style="font-size:1.15rem; color:#fff;">${room.player_name}</strong>
                        </div>
                        <div style="font-size:1.8rem; font-weight:900; color:var(--neon-cyan); font-family:'Chakra Petch'; margin-top:10px;">
                            ขั้นบันไดที่: ${room.score} / 10
                        </div>
                    </div>
                    
                    <button onclick="leaveRoom()" class="btn-ctrl danger" style="width:100%; justify-content:center; padding:12px;">
                        ออกจากห้องแข่ง
                    </button>
                </div>
            `;
        }
    } else if (room.game_status === 'ended') {
        // --- SUMMARY SCREEN ---
        app.innerHTML = `
            <div class="setup-screen" style="margin-top: 20px; padding: 25px;">
                <h2 style="font-family:'Chakra Petch'; color:var(--neon-cyan);">🏁 สรุปภารกิจ</h2>
                <p style="color:var(--text-muted); margin-top:8px;">เกมจบลงเรียบร้อยแล้ว</p>
                
                <div style="margin:20px 0; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:20px; border-radius:16px;">
                    <div style="display:flex; justify-content:center; align-items:center; gap:8px; margin-bottom:12px;">
                        <img src="../../assets/avatar/${room.player_avatar || 'dog.png'}" style="width:36px; height:36px;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                        <strong style="color:#fff;">${room.player_name}</strong>
                    </div>
                    <div style="font-size:2.5rem; font-weight:900; color:var(--neon-green); font-family:'Chakra Petch';">
                        ${room.score} ขั้น
                    </div>
                </div>
                
                <button onclick="leaveRoom()" class="btn-ctrl secondary" style="width:100%; justify-content:center; padding:12px;">
                    กลับหน้าหลัก (Leave Room)
                </button>
            </div>
        `;
    }
}

function selectChoice(choiceText) {
    if (lastSelectedChoice !== null) return; // Prevent double select
    
    lastSelectedChoice = choiceText;
    
    // Play touch vibrator feedback
    if (navigator.vibrate) navigator.vibrate(15);
    
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('choice', choiceText);
    
    fetch('api_room.php?action=select_choice', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(() => {
            // Update ui locally immediately
            pollState();
        });
}

function leaveRoom() {
    if (!confirm('ยืนยันออกจากห้องนี้?')) return;
    
    const fd = new FormData();
    fd.append('room_code', roomCode);
    
    fetch('api_room.php?action=leave', { method: 'POST', body: fd })
        .then(() => {
            clearInterval(pollInterval);
            window.location.href = '../../index.php';
        });
}

// Start
initMobile();
</script>

</body>
</html>
