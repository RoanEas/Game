<?php
session_start();

// Block non-admin users
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('⛔ เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถเปิดหน้าจอนี้ได้');
        window.location.href = '../../index.php';
    </script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปริศนาสายฟ้า ⚡ CONTROL PANEL</title>
    <link href="style.css?v=<?=time();?>" rel="stylesheet" type="text/css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>

<div class="grid-overlay"></div>

<div class="game-container" id="game-app">
    
    <!-- LOBBY SCREEN (SETUP PHASE) -->
    <div id="screen-setup" class="setup-screen" style="max-width:600px; margin: 40px auto; padding: 30px;">
        <h2 style="font-family: 'Chakra Petch', sans-serif; font-size: 1.8rem; color: #fff; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <ion-icon name="flash" style="color:var(--neon-cyan);"></ion-icon>
            <span>ปริศนาสายฟ้า ⚡ CONTROL</span>
        </h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 25px;">เลือกสายลับที่อยู่กับตัวท่านในห้องจัดกิจกรรมและกดเริ่มปฏิบัติภารกิจ</p>
        
        <div class="input-group" style="text-align: left; margin-bottom: 20px;">
            <label style="color: var(--neon-cyan); font-weight: bold; font-size: 0.95rem;">เลือกผู้เล่นสายลับ (Select Active Player)</label>
            <select id="player-selector" style="width: 100%; padding: 15px; margin-top: 8px; background: rgba(0,0,0,0.6); border: 2px solid var(--neon-cyan); border-radius: 12px; color: #fff; font-size: 1.15rem; font-weight: bold; cursor: pointer; outline: none; font-family: 'Chakra Petch';">
                <option value="">-- กำลังโหลดรายชื่อผู้เล่น... --</option>
            </select>
        </div>
        
        <button onclick="startGame()" class="btn-ctrl primary" style="width: 100%; justify-content: center; padding: 16px; border-radius: 12px; font-size: 1.2rem; font-weight: bold;">
            เริ่มเล่นเกม 🎮
        </button>
        
        <a href="../../index.php" class="btn-ctrl secondary" style="margin-top: 15px; display: inline-flex; justify-content: center; text-decoration: none; width: 100%;">
            กลับสู่หน้าหลักชมรม
        </a>
    </div>

    <!-- GAMEPLAY SCREEN (PLAYING PHASE) -->
    <div id="screen-game" style="display: none; flex-direction: column; flex: 1;">
        <div class="game-header">
            <div class="brand-title">
                <ion-icon name="flash" style="color:var(--neon-cyan);"></ion-icon>
                <span>ปริศนาสายฟ้า ⚡</span>
            </div>
            
            <div style="display:flex; align-items:center; gap:25px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <img id="active-player-avatar" src="../../assets/avatar/dog.png" style="width:44px; height:44px; object-fit:contain;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                    <div style="text-align: left;">
                        <span style="font-size:0.75rem; color:var(--text-muted); display:block;">ผู้ตอบ:</span>
                        <strong id="active-player-name" style="color:#fff; font-size:1.15rem; font-family:'Chakra Petch';">Spy Name</strong>
                    </div>
                </div>
                <div class="timer-display" id="timer-display">02:00</div>
            </div>
        </div>
        
        <div class="split-layout">
            <!-- LADDER TUBE (LEFT) -->
            <div class="ladder-tube-wrapper" style="position: relative;">
                <div class="ladder-tube">
                    <!-- SVG Lightning bolt inside the tube -->
                    <svg class="lightning-svg" viewBox="0 0 100 560" preserveAspectRatio="none">
                        <path id="lightning-path" d="M 50 0 L 35 60 L 65 120 L 30 180 L 70 240 L 35 300 L 65 360 L 30 420 L 70 480 L 40 520 L 50 560" />
                    </svg>
                    
                    <?php for ($level = 1; $level <= 10; $level++): ?>
                        <div class="ladder-step" id="level-<?php echo $level; ?>">
                            <div class="step-glow"></div>
                            <span>ขั้นที่ <?php echo $level; ?></span>
                            <span><?php echo $level; ?> PTS</span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- QUESTION & GRADING (RIGHT) -->
            <div class="content-panel">
                <div class="question-card" style="min-height: 180px;">
                    <div class="question-text" id="question-text">กำลังโหลดคำถาม...</div>
                </div>
                
                <div class="choices-grid" id="choices-grid" style="margin-bottom: 10px;">
                    <!-- JS renders choices dynamically -->
                </div>
                
                <!-- HOST GRADING CONTROLS -->
                <div class="host-controls" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); padding: 25px; border-radius: 20px;">
                    
                    <button onclick="handleCorrect()" class="btn-ctrl success" style="padding: 22px; font-size: 1.4rem; justify-content: center; border-radius: 16px; border: 2px solid var(--neon-green); box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);">
                        <ion-icon name="checkmark-circle-outline" style="font-size:1.8rem;"></ion-icon>
                        <strong>ถูก (Spacebar)</strong>
                    </button>
                    
                    <button onclick="handleIncorrect()" class="btn-ctrl danger" style="padding: 22px; font-size: 1.4rem; justify-content: center; border-radius: 16px; border: 2px solid var(--neon-pink); box-shadow: 0 0 15px rgba(236, 72, 153, 0.2);">
                        <ion-icon name="close-circle-outline" style="font-size:1.8rem;"></ion-icon>
                        <strong>ผิด (Backspace)</strong>
                    </button>
                    
                    <div style="grid-column: span 2; display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <button onclick="skipQuestion()" class="btn-ctrl secondary" style="padding: 10px 20px; font-size: 0.9rem;">
                            <ion-icon name="arrow-forward-outline"></ion-icon> ข้ามคำถาม (Next)
                        </button>
                        <button onclick="pauseResumeTimer()" id="btn-pause-timer" class="btn-ctrl secondary" style="padding: 10px 20px; font-size: 0.9rem; color: #fff;">
                            <ion-icon name="pause-outline"></ion-icon> หยุดเวลา (Pause)
                        </button>
                        <button onclick="forceEndGame()" class="btn-ctrl danger" style="padding: 10px 20px; font-size: 0.9rem;">
                            <ion-icon name="square-outline"></ion-icon> จบเกม/สรุปผล
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SUMMARY SCREEN (ENDED PHASE) -->
    <div id="screen-ended" class="setup-screen" style="display: none; max-width:550px; margin: 40px auto; padding: 30px;">
        <h2>จบการเล่นสะสม! 🏁</h2>
        <p style="color:var(--text-muted); margin-top:8px;">สรุปปฏิบัติการเก็บคะแนนสายลับ</p>
        
        <div style="margin:25px 0; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:25px; border-radius:20px;">
            <div style="display:flex; justify-content:center; align-items:center; gap:12px; margin-bottom:20px;">
                <img id="summary-avatar" src="../../assets/avatar/dog.png" style="width:50px; height:50px; object-fit:contain;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                <div style="text-align:left;">
                    <div style="font-size:0.8rem; color:var(--text-muted);">ผู้ร่วมเล่น:</div>
                    <h3 id="summary-player-name" style="color:#fff; font-size:1.3rem; font-family:'Chakra Petch';">Spy Name</h3>
                </div>
            </div>
            
            <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase;">ขั้นบันไดสายฟ้าสูงสุด:</div>
            <div id="summary-score" style="font-size:4.5rem; font-weight:900; color:var(--neon-cyan); font-family:'Chakra Petch'; text-shadow:0 0 20px rgba(6,182,212,0.3); margin:10px 0;">
                0 / 10 ขั้น
            </div>
            
            <p id="summary-bonus-text" style="font-size:1rem; color:var(--neon-green); font-weight:bold; font-family:'Chakra Petch';">
                เพิ่มคะแนนพิเศษ +0 PTS ลงโปรไฟล์เรียบร้อย
            </p>
        </div>
        
        <button onclick="resetGame()" class="btn-ctrl primary" style="width:100%; justify-content:center; padding:15px; border-radius:12px;">
            กลับสู่หน้าเลือกผู้เล่น 🔄
        </button>
    </div>

</div>

<script>
// state management
let activePlayer = null;
let gameStatus = 'setup'; // 'setup', 'playing', 'ended'
let currentScore = 0; // 0 to 10
let secondsRemaining = 120;
let timerInterval = null;
let currentQuestion = null;
let isTimerRunning = false;

// Audio synth helper
function playSound(type) {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        
        if (type === 'correct') {
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880.00, ctx.currentTime + 0.12); // A5
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } else if (type === 'incorrect') {
            osc.frequency.setValueAtTime(220.00, ctx.currentTime); // A3
            osc.frequency.setValueAtTime(146.83, ctx.currentTime + 0.12); // D3
            gain.gain.setValueAtTime(0.12, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.35);
        } else if (type === 'tick') {
            osc.frequency.setValueAtTime(650, ctx.currentTime);
            gain.gain.setValueAtTime(0.04, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.05);
        } else if (type === 'thunder') {
            // Thunder simulation noise
            const bufferSize = ctx.sampleRate * 1.0;
            const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
            const data = buffer.getChannelData(0);
            for (let i = 0; i < bufferSize; i++) {
                data[i] = Math.random() * 2 - 1;
            }
            const noise = ctx.createBufferSource();
            noise.buffer = buffer;
            const filter = ctx.createBiquadFilter();
            filter.type = 'lowpass';
            filter.frequency.value = 300;
            
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.9);
            
            noise.connect(filter);
            filter.connect(gain);
            noise.start();
            noise.stop(ctx.currentTime + 1.0);
        }
    } catch(e) {}
}

// Fetch players list on startup
function loadPlayersList() {
    fetch('api_room.php?action=list_players')
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('player-selector');
            if (data.status === 'success' && data.players.length > 0) {
                select.innerHTML = '<option value="">-- เลือกสายลับเพื่อเข้าปฏิบัติการ --</option>' + 
                    data.players.map(p => `<option value="${p.id}" data-name="${p.real_name}" data-avatar="${p.avatar}">${p.real_name} (${p.score} PTS)</option>`).join('');
            } else {
                select.innerHTML = '<option value="">ยังไม่มีชื่อผู้เล่นในฐานข้อมูล</option>';
            }
        });
}

// Start player turn
function startGame() {
    const select = document.getElementById('player-selector');
    const selectedOption = select.options[select.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        alert('กรุณาเลือกผู้เล่นก่อนเข้าแข่ง!');
        return;
    }
    
    activePlayer = {
        id: parseInt(selectedOption.value),
        real_name: selectedOption.getAttribute('data-name'),
        avatar: selectedOption.getAttribute('data-avatar')
    };
    
    // Set UI
    document.getElementById('active-player-name').textContent = activePlayer.real_name;
    document.getElementById('active-player-avatar').src = '../../assets/avatar/' + activePlayer.avatar;
    
    // Reset state variables
    currentScore = 0;
    secondsRemaining = 120;
    gameStatus = 'playing';
    isTimerRunning = true;
    
    // Render setup
    document.getElementById('screen-setup').style.display = 'none';
    document.getElementById('screen-game').style.display = 'flex';
    document.getElementById('screen-ended').style.display = 'none';
    
    // Reset indicators
    updateScoreLadderUI();
    updateTimerUI();
    
    // Fetch first question
    loadNextQuestion();
    
    // Start countdown loop
    startTimerLoop();
    
    // Trigger thunder on start
    playSound('thunder');
    triggerLightningAnimation();
}

// Fetch next question
function loadNextQuestion() {
    const excludeId = currentQuestion ? currentQuestion.id : 0;
    document.getElementById('question-text').textContent = "กำลังจับคู่สุ่มคำถาม...";
    document.getElementById('choices-grid').innerHTML = "";
    
    fetch('api_room.php?action=get_question&exclude=' + excludeId)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                currentQuestion = data.question;
                document.getElementById('question-text').textContent = currentQuestion.question_text;
                
                // Renders choice buttons
                const letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                document.getElementById('choices-grid').innerHTML = currentQuestion.choices.map((c, i) => `
                    <div class="choice-btn">
                        <div style="display:flex; align-items:center;">
                            <span class="choice-label">${letters[i]}</span>
                            <span>${c}</span>
                        </div>
                    </div>
                `).join('');
            } else {
                alert(data.message);
                forceEndGame();
            }
        });
}

// Climb level on Correct
function handleCorrect() {
    if (gameStatus !== 'playing') return;
    
    playSound('correct');
    currentScore++;
    
    triggerLightningAnimation();
    updateScoreLadderUI();
    
    if (currentScore >= 10) {
        playSound('thunder');
        // Win!
        saveAndEndGame();
    } else {
        loadNextQuestion();
    }
}

// Reset level on Incorrect
function handleIncorrect() {
    if (gameStatus !== 'playing') return;
    
    playSound('incorrect');
    currentScore = 0; // Resets climb score to 0
    
    updateScoreLadderUI();
    loadNextQuestion();
}

function skipQuestion() {
    if (gameStatus !== 'playing') return;
    loadNextQuestion();
}

// Animate lightning SVG path
function triggerLightningAnimation() {
    const path = document.getElementById('lightning-path');
    if (!path) return;
    
    // Toggle active animations
    path.classList.remove('lightning-flash-animation');
    void path.offsetWidth; // Force reflow
    path.classList.add('lightning-flash-animation');
}

// Update tube elements
function updateScoreLadderUI() {
    for (let level = 1; level <= 10; level++) {
        const el = document.getElementById('level-' + level);
        if (!el) continue;
        
        // Remove old tags
        el.classList.remove('active-level', 'completed-level');
        
        if (level === currentScore) {
            el.classList.add('active-level');
        } else if (level < currentScore) {
            el.classList.add('completed-level');
        }
    }
}

// TIMER COUNTDOWN LOOP
function startTimerLoop() {
    if (timerInterval) clearInterval(timerInterval);
    
    timerInterval = setInterval(() => {
        if (!isTimerRunning) return;
        
        if (secondsRemaining > 0) {
            secondsRemaining--;
            updateTimerUI();
            
            // Pulsing ticks for final seconds
            if (secondsRemaining <= 15) {
                playSound('tick');
            }
        } else {
            clearInterval(timerInterval);
            timerInterval = null;
            saveAndEndGame();
        }
    }, 1000);
}

function updateTimerUI() {
    const el = document.getElementById('timer-display');
    if (!el) return;
    
    const min = String(Math.floor(secondsRemaining / 60)).padStart(2, '0');
    const sec = String(secondsRemaining % 60).padStart(2, '0');
    el.textContent = `${min}:${sec}`;
    
    if (secondsRemaining <= 30) {
        el.classList.add('running-low');
    } else {
        el.classList.remove('running-low');
    }
}

function pauseResumeTimer() {
    isTimerRunning = !isTimerRunning;
    const btn = document.getElementById('btn-pause-timer');
    if (isTimerRunning) {
        btn.innerHTML = `<ion-icon name="pause-outline"></ion-icon> หยุดเวลา (Pause)`;
        btn.style.color = '#fff';
    } else {
        btn.innerHTML = `<ion-icon name="play-outline"></ion-icon> เล่นต่อ (Resume)`;
        btn.style.color = 'var(--neon-green)';
    }
}

// Save result to profile
function saveAndEndGame() {
    isTimerRunning = false;
    clearInterval(timerInterval);
    timerInterval = null;
    gameStatus = 'ended';
    
    // Send score to API
    const fd = new FormData();
    fd.append('player_id', activePlayer.id);
    fd.append('score', currentScore);
    
    fetch('api_room.php?action=save_score', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            // Render results
            document.getElementById('summary-avatar').src = '../../assets/avatar/' + activePlayer.avatar;
            document.getElementById('summary-player-name').textContent = activePlayer.real_name;
            document.getElementById('summary-score').textContent = `${currentScore} / 10 ขั้น`;
            
            const bonusPts = data.points_earned || 0;
            document.getElementById('summary-bonus-text').textContent = `เพิ่มคะแนนพิเศษ +${bonusPts} PTS ลงในโปรไฟล์สำเร็จ! 🏆`;
            
            document.getElementById('screen-setup').style.display = 'none';
            document.getElementById('screen-game').style.display = 'none';
            document.getElementById('screen-ended').style.display = 'block';
        });
}

function forceEndGame() {
    if (confirm('คุณยืนยันที่จะจบเกมนิ้ทันทีหรือไม่?')) {
        saveAndEndGame();
    }
}

function resetGame() {
    gameStatus = 'setup';
    activePlayer = null;
    currentQuestion = null;
    
    document.getElementById('screen-setup').style.display = 'block';
    document.getElementById('screen-game').style.display = 'none';
    document.getElementById('screen-ended').style.display = 'none';
    
    loadPlayersList();
}

// KEYBOARD CONTROLLER SHORTCUTS
document.addEventListener('keydown', (e) => {
    if (gameStatus !== 'playing') return;
    
    // Prevent default browser shortcuts for Spacebar
    if (e.key === ' ' || e.key === 'Spacebar') {
        e.preventDefault();
        handleCorrect();
    } else if (e.key === 'Backspace' || e.key === 'Delete') {
        e.preventDefault();
        handleIncorrect();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        handleCorrect();
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        handleIncorrect();
    }
});

// Startup
loadPlayersList();
</script>

</body>
</html>
