<?php
// pc.php - Host and Display Screen for Blitz Quiz
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปริศนาสายฟ้า ⚡ HOST CONTROL CENTER</title>
    <link href="style.css?v=<?=time();?>" rel="stylesheet" type="text/css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>

<div class="grid-overlay"></div>

<div class="game-container" id="game-app">
    <!-- RENDERED BY JAVASCRIPT -->
</div>

<script>
let roomCode = "<?php echo $_GET['room'] ?? ''; ?>";
let userId = <?php echo $_SESSION['user_id']; ?>;
let pollInterval = null;
let timerInterval = null;
let gameState = null;

// Audio sound helper (pure JS synthesize or simple beep)
function playSound(type) {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        
        if (type === 'correct') {
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880.00, ctx.currentTime + 0.15); // A5
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.35);
        } else if (type === 'incorrect') {
            osc.frequency.setValueAtTime(220.00, ctx.currentTime); // A3
            osc.frequency.setValueAtTime(146.83, ctx.currentTime + 0.15); // D3
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.4);
        } else if (type === 'tick') {
            osc.frequency.setValueAtTime(600, ctx.currentTime);
            gain.gain.setValueAtTime(0.05, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.05);
        }
    } catch(e) {}
}

// Create room automatically if no room parameter is in URL
function initApp() {
    if (!roomCode) {
        fetch('api_room.php?action=create')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    roomCode = data.room_code;
                    window.history.replaceState(null, null, `?room=${roomCode}`);
                    startPolling();
                } else {
                    alert('ไม่สามารถสร้างห้องได้: ' + data.message);
                }
            });
    } else {
        startPolling();
    }
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
                gameState = data;
                renderGame(data);
                syncTimerState(data.room);
            } else {
                clearInterval(pollInterval);
                alert('ห้องนี้ถูกปิดแล้ว หรือ เกิดข้อผิดพลาด');
                window.location.href = '../../index.php';
            }
        });
}

function syncTimerState(room) {
    if (room.game_status !== 'playing') {
        clearInterval(timerInterval);
        timerInterval = null;
        return;
    }

    if (room.timer_running && !timerInterval) {
        // Start local timer loop
        timerInterval = setInterval(() => {
            if (gameState && gameState.room.seconds_remaining > 0) {
                gameState.room.seconds_remaining--;
                if (gameState.room.seconds_remaining <= 15) {
                    playSound('tick');
                }
                updateLocalTimerUI(gameState.room.seconds_remaining);
                
                // Sync to database every 1 second
                const fd = new FormData();
                fd.append('room_code', roomCode);
                fd.append('seconds_remaining', gameState.room.seconds_remaining);
                fd.append('timer_running', 1);
                fetch('api_room.php?action=update_timer', { method: 'POST', body: fd });
            } else if (gameState && gameState.room.seconds_remaining <= 0) {
                clearInterval(timerInterval);
                timerInterval = null;
                endGame();
            }
        }, 1000);
    } else if (!room.timer_running && timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

function updateLocalTimerUI(seconds) {
    const el = document.getElementById('timer-display');
    if (!el) return;
    const min = String(Math.floor(seconds / 60)).padStart(2, '0');
    const sec = String(seconds % 60).padStart(2, '0');
    el.textContent = `${min}:${sec}`;
    
    if (seconds <= 30) {
        el.classList.add('running-low');
    } else {
        el.classList.remove('running-low');
    }
}

// RENDER FUNCTION
function renderGame(data) {
    const app = document.getElementById('game-app');
    if (!app) return;
    
    const room = data.room;
    
    if (room.game_status === 'setup') {
        // --- 1. LOBBY VIEW ---
        app.innerHTML = `
            <div class="game-header">
                <div class="brand-title">
                    <ion-icon name="flash" style="color:var(--neon-cyan);"></ion-icon>
                    <span>ปริศนาสายฟ้า ⚡ LOBBY</span>
                </div>
                <div>
                    <a href="../../index.php" class="btn-ctrl secondary" style="text-decoration:none;">
                        <ion-icon name="exit-outline"></ion-icon> ออกกลับหน้าหลัก
                    </a>
                </div>
            </div>
            
            <div class="setup-screen">
                <h2>เชิญผู้เล่นเข้าร่วมห้องเล่นเกม</h2>
                <p style="color:var(--text-muted); margin-top:10px;">เปิดแท็บเช็คชื่อบนหน้าแรก หรือให้ผู้เล่นกรอกรหัสห้องนี้บนโทรศัพท์มือถือ</p>
                <div class="room-badge">${room.room_code}</div>
                
                <h3 style="margin-bottom:15px; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:8px; text-align:left;">
                    👥 ผู้เข้าร่วมในห้องนี้ (${data.players.length})
                </h3>
                
                <div class="social-list-wrapper" style="text-align:left; max-height:260px; overflow-y:auto; margin-bottom:30px;">
                    ${data.players.length === 0 ? '<div class="empty-state-text">ยังไม่มีผู้เล่นเข้ามาในห้อง...</div>' : ''}
                    ${data.players.map(p => `
                        <div class="social-user-row" style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid rgba(255,255,255,0.03);">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <img src="../../assets/avatar/${p.avatar_img || 'dog.png'}" style="width:36px; height:36px; object-fit:contain;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                                <strong style="color:#fff;">${p.real_name}</strong>
                            </div>
                            <button onclick="selectPlayer(${p.user_id})" class="btn-ctrl primary" style="padding:6px 12px; font-size:0.85rem;">
                                เลือกสายลับนี้เล่น 🎮
                            </button>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    } else if (room.game_status === 'playing') {
        // --- 2. GAME SCREEN ---
        const question = data.question;
        const choices = question ? question.choices : [];
        const score = room.score;
        const min = String(Math.floor(room.seconds_remaining / 60)).padStart(2, '0');
        const sec = String(room.seconds_remaining % 60).padStart(2, '0');
        
        app.innerHTML = `
            <div class="game-header">
                <div class="brand-title">
                    <ion-icon name="flash" style="color:var(--neon-cyan);"></ion-icon>
                    <span>ปริศนาสายฟ้า ⚡ กำลังเล่น</span>
                </div>
                <div style="display:flex; align-items:center; gap:20px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <img src="../../assets/avatar/${room.player_avatar || 'dog.png'}" style="width:40px; height:40px; object-fit:contain;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">ผู้ตอบกำลังเล่น:</div>
                            <strong style="color:#fff;">${room.player_name}</strong>
                        </div>
                    </div>
                    <div class="timer-display ${room.seconds_remaining <= 30 ? 'running-low' : ''}" id="timer-display">${min}:${sec}</div>
                </div>
            </div>
            
            <div class="split-layout">
                <!-- LADDER TUBE -->
                <div class="ladder-tube-wrapper">
                    <div class="ladder-tube">
                        ${[1,2,3,4,5,6,7,8,9,10].map(level => {
                            let statusClass = '';
                            if (level === score) {
                                statusClass = 'active-level';
                            } else if (level < score) {
                                statusClass = 'completed-level';
                            }
                            return `
                                <div class="ladder-step ${statusClass}">
                                    <div class="step-glow"></div>
                                    <span>ขั้นที่ ${level}</span>
                                    <span>${level} PTS</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
                
                <!-- QUESTION & CHOICES -->
                <div class="content-panel">
                    <div class="question-card">
                        <div class="question-text">${question ? question.question_text : 'กำลังโหลดคำถาม...'}</div>
                    </div>
                    
                    <div class="choices-grid">
                        ${choices.map((c, i) => {
                            const isSelected = room.selected_choice === c;
                            return `
                                <div class="choice-btn ${isSelected ? 'selected' : ''}">
                                    <div style="display:flex; align-items:center;">
                                        <span class="choice-label">${String.fromCharCode(65 + i)}</span>
                                        <span>${c}</span>
                                    </div>
                                    ${isSelected ? '<ion-icon name="ellipse" style="color:var(--neon-orange);"></ion-icon>' : ''}
                                </div>
                            `;
                        }).join('')}
                    </div>
                    
                    <!-- HOST CONTROLS -->
                    <div class="host-controls">
                        <div style="font-weight:bold; font-family:'Chakra Petch'; color:var(--neon-cyan); margin-right:10px;">
                            ⚙️ แผงควบคุมผู้ดำเนินรายการ:
                        </div>
                        
                        ${room.timer_running ? 
                            `<button onclick="toggleTimer(0)" class="btn-ctrl secondary"><ion-icon name="pause"></ion-icon> หยุดเวลา</button>` : 
                            `<button onclick="toggleTimer(1)" class="btn-ctrl primary"><ion-icon name="play"></ion-icon> เริ่มเวลา</button>`
                        }
                        
                        <button onclick="gradeAnswer('correct')" class="btn-ctrl success">
                            <ion-icon name="checkmark-circle"></ion-icon> ตอบถูก (Climb Up)
                        </button>
                        
                        <button onclick="gradeAnswer('incorrect')" class="btn-ctrl danger">
                            <ion-icon name="close-circle"></ion-icon> ตอบผิด (Fall to 0)
                        </button>
                        
                        <button onclick="skipQuestion()" class="btn-ctrl secondary">
                            <ion-icon name="arrow-forward"></ion-icon> ข้ามคำถาม
                        </button>
                        
                        <button onclick="endGame()" class="btn-ctrl danger" style="margin-left:auto;">
                            <ion-icon name="square"></ion-icon> จบเทิร์น/สรุปคะแนน
                        </button>
                    </div>
                </div>
            </div>
        `;
    } else if (room.game_status === 'ended') {
        // --- 3. END GAME SUMMARY ---
        app.innerHTML = `
            <div class="game-header">
                <div class="brand-title">
                    <ion-icon name="flash" style="color:var(--neon-cyan);"></ion-icon>
                    <span>ปริศนาสายฟ้า ⚡ สรุปผลคะแนน</span>
                </div>
            </div>
            
            <div class="setup-screen" style="max-width:550px;">
                <h2>สิ้นสุดภารกิจแล้ว! 🏁</h2>
                <p style="color:var(--text-muted); margin-top:10px;">สายลับทำผลงานได้ยอดเยี่ยม</p>
                
                <div style="margin:30px 0; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); padding:30px; border-radius:24px;">
                    <div style="display:flex; justify-content:center; align-items:center; gap:12px; margin-bottom:20px;">
                        <img src="../../assets/avatar/${room.player_avatar || 'dog.png'}" style="width:64px; height:64px; object-fit:contain;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                        <div style="text-align:left;">
                            <div style="font-size:0.85rem; color:var(--text-muted);">สายลับผู้ท้าชิง:</div>
                            <h3 style="color:#fff; font-size:1.4rem;">${room.player_name}</h3>
                        </div>
                    </div>
                    
                    <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase;">คะแนนความสูงขั้นบันไดสูงสุด:</div>
                    <div style="font-size:4rem; font-weight:900; color:${room.score >= 10 ? 'var(--neon-green)' : 'var(--neon-cyan)'}; font-family:'Chakra Petch'; text-shadow:0 0 20px rgba(6,182,212,0.3); margin:10px 0;">
                        ${room.score} / 10 ขั้น
                    </div>
                    
                    <p style="font-size:0.95rem; color:var(--neon-green); font-weight:bold;">
                        ${room.score >= 10 ? '🏆 พิชิต 10 ขั้นเรียบร้อย! รับรางวัลใหญ่ 100 PTS' : `รับคะแนนสะสมพิเศษ ${room.score * 5} PTSเข้าโปรไฟล์`}
                    </p>
                </div>
                
                <button onclick="resetGame()" class="btn-ctrl primary" style="width:100%; justify-content:center; padding:15px;">
                    กลับสู่หน้าล็อบบี้ห้อง (Back to Lobby) 🔄
                </button>
            </div>
        `;
    }
}

// CONTROLLER ACTIONS CALLING API
function selectPlayer(playerId) {
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('player_id', playerId);
    
    fetch('api_room.php?action=select_player', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(() => pollState());
}

function toggleTimer(start) {
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('seconds_remaining', gameState.room.seconds_remaining);
    fd.append('timer_running', start);
    
    fetch('api_room.php?action=update_timer', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(() => pollState());
}

function gradeAnswer(result) {
    playSound(result);
    
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('result', result);
    
    fetch('api_room.php?action=grade_answer', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(() => pollState());
}

function skipQuestion() {
    const fd = new FormData();
    fd.append('room_code', roomCode);
    
    fetch('api_room.php?action=skip_question', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(() => pollState());
}

function endGame() {
    clearInterval(timerInterval);
    timerInterval = null;
    playSound('incorrect');
    
    const fd = new FormData();
    fd.append('room_code', roomCode);
    
    fetch('api_room.php?action=end_game', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(() => pollState());
}

function resetGame() {
    const fd = new FormData();
    fd.append('room_code', roomCode);
    
    fetch('api_room.php?action=reset', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(() => pollState());
}

// Initialise App
initApp();
</script>

</body>
</html>
