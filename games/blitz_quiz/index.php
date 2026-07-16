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
    <title>ปริศนาสายฟ้า ⚡ HOST PANEL</title>
    <link href="style.css?v=<?=time();?>" rel="stylesheet" type="text/css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>

<div class="grid-overlay"></div>

<div class="game-container" id="game-app" style="max-width: 900px; margin: 30px auto; padding: 20px;">
    
    <!-- MAIN HOST GAMEPLAY PANEL -->
    <div id="screen-game" style="display: flex; flex-direction: column; flex: 1; background: rgba(15, 23, 42, 0.4); border: 2px solid var(--card-border); padding: 30px; border-radius: 24px; box-shadow: 0 0 40px rgba(0,0,0,0.5);">
        
        <div class="game-header" style="border-bottom: 2px solid rgba(255,255,255,0.05); padding-bottom: 20px; margin-bottom: 25px;">
            <div class="brand-title">
                <ion-icon name="options-outline" style="color:var(--neon-cyan);"></ion-icon>
                <span>ปริศนาสายฟ้า ⚡ HOST CONTROL</span>
            </div>
            
            <div style="display:flex; align-items:center; gap:20px;">
                <a href="view.php" target="_blank" class="btn-ctrl secondary" style="padding: 10px 20px; font-size: 0.95rem; text-decoration: none; border-color: var(--neon-cyan); color: var(--neon-cyan);">
                    <ion-icon name="tv-outline"></ion-icon> เปิดหน้าจอสำหรับผู้เล่น
                </a>
                
                <div class="timer-display" id="timer-display" style="font-size: 1.8rem; padding: 5px 15px;">02:00</div>
            </div>
        </div>
        
        <div class="content-panel" style="padding: 0; gap: 20px;">
            <!-- QUESTION CARD -->
            <div class="question-card" style="min-height: 140px; padding: 25px; border-left: 5px solid var(--neon-cyan); background: rgba(0,0,0,0.3);">
                <div style="font-size: 0.8rem; color: var(--neon-cyan); text-transform: uppercase; font-weight: bold; margin-bottom: 8px; font-family:'Chakra Petch';">คำถามที่ปรากฎในระบบ (อ่านให้ผู้เข้าแข่งฟัง):</div>
                <div class="question-text" id="question-text" style="font-size: 1.6rem; line-height: 1.5; color: #fff;">กำลังโหลดคำถาม...</div>
            </div>
            
            <!-- CHOICES PREVIEW -->
            <div style="text-align: left; font-family: 'Chakra Petch';">
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px; font-weight: bold;">เฉลยตัวเลือก ( choices list ):</div>
                <div class="choices-grid" id="choices-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 25px;">
                    <!-- JS renders choices dynamically -->
                </div>
            </div>
            
            <!-- STATS COUNTER -->
            <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 15px 25px; border-radius: 12px; margin-bottom: 10px; font-family:'Chakra Petch';">
                <span style="color:var(--text-muted); font-size:1.05rem;">ขั้นคะแนนปัจจุบัน:</span>
                <strong style="color:var(--neon-green); font-size:2rem; text-shadow:0 0 10px rgba(16,185,129,0.3);" id="score-counter">0 / 10 ขั้น</strong>
            </div>
            
            <!-- HOST GRADING CONTROLS -->
            <div class="host-controls" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); padding: 25px; border-radius: 20px;">
                
                <button onclick="handleCorrect()" class="btn-ctrl success" style="padding: 22px; font-size: 1.4rem; justify-content: center; border-radius: 16px; border: 2px solid var(--neon-green); box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);">
                    <ion-icon name="checkmark-circle-outline" style="font-size:1.8rem;"></ion-icon>
                    <strong>ถูก (Spacebar / ↑)</strong>
                </button>
                
                <button onclick="handleIncorrect()" class="btn-ctrl danger" style="padding: 22px; font-size: 1.4rem; justify-content: center; border-radius: 16px; border: 2px solid var(--neon-pink); box-shadow: 0 0 15px rgba(236, 72, 153, 0.2);">
                    <ion-icon name="close-circle-outline" style="font-size:1.8rem;"></ion-icon>
                    <strong>ผิด (Backspace / ↓)</strong>
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
            
            <a href="../../index.php" class="btn-ctrl secondary" style="text-decoration:none; display:inline-flex; justify-content:center; padding:12px 0;">
                กลับสู่หน้าหลักชมรม
            </a>
        </div>
    </div>

    <!-- SUMMARY SCREEN (ENDED PHASE) -->
    <div id="screen-ended" class="setup-screen" style="display: none; max-width:550px; margin: 40px auto; padding: 30px;">
        <h2>จบการเล่นสะสม! 🏁</h2>
        <p style="color:var(--text-muted); margin-top:8px;">สรุปปฏิบัติการเก็บคะแนนสายลับ</p>
        
        <div style="margin:25px 0; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:25px; border-radius:20px;">
            <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase;">ขั้นบันไดสายฟ้าสูงสุด:</div>
            <div id="summary-score" style="font-size:4.5rem; font-weight:900; color:var(--neon-cyan); font-family:'Chakra Petch'; text-shadow:0 0 20px rgba(6,182,212,0.3); margin:10px 0;">
                0 / 10 ขั้น
            </div>
        </div>
        
        <button onclick="resetGame()" class="btn-ctrl primary" style="width:100%; justify-content:center; padding:15px; border-radius:12px;">
            เริ่มเล่นรอบใหม่ 🔄
        </button>
    </div>

</div>

<script>
// state management
let activePlayer = { id: 0, real_name: 'ผู้ท้าชิงสายลับ', avatar: 'dog.png' };
let gameStatus = 'playing'; // 'playing', 'ended'
let currentScore = 0; // 0 to 10
let secondsRemaining = 120;
let timerInterval = null;
let currentQuestion = null;
let isTimerRunning = true;

// Sync state to DB
function syncState() {
    const fd = new FormData();
    fd.append('score', currentScore);
    fd.append('seconds_remaining', secondsRemaining);
    fd.append('timer_running', isTimerRunning ? 1 : 0);
    fd.append('current_question_id', currentQuestion ? currentQuestion.id : 0);
    fd.append('game_status', gameStatus);
    
    fetch('api_room.php?action=sync', { method: 'POST', body: fd });
}

// Start player turn
function startGame() {
    currentScore = 0;
    secondsRemaining = 120;
    gameStatus = 'playing';
    isTimerRunning = true;
    
    document.getElementById('screen-game').style.display = 'flex';
    document.getElementById('screen-ended').style.display = 'none';
    
    document.getElementById('score-counter').textContent = `${currentScore} / 10 ขั้น`;
    updateTimerUI();
    
    // Fetch first question
    loadNextQuestion();
    
    // Start countdown loop
    startTimerLoop();
    
    syncState();
}

// Fetch next question
function loadNextQuestion() {
    const excludeId = currentQuestion ? currentQuestion.id : 0;
    document.getElementById('question-text').textContent = "กำลังสุ่มคำถามถัดไป...";
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
                    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); padding:12px; border-radius:10px; color:#fff; font-size:1.05rem;">
                        <strong style="color:var(--neon-cyan); margin-right:8px;">${letters[i]}:</strong> ${c}
                    </div>
                `).join('');
                
                syncState();
            } else {
                alert(data.message);
                forceEndGame();
            }
        });
}

// Climb level on Correct
function handleCorrect() {
    if (gameStatus !== 'playing') return;
    
    currentScore++;
    document.getElementById('score-counter').textContent = `${currentScore} / 10 ขั้น`;
    
    if (currentScore >= 10) {
        saveAndEndGame();
    } else {
        loadNextQuestion();
    }
}

// Reset level on Incorrect
function handleIncorrect() {
    if (gameStatus !== 'playing') return;
    
    currentScore = 0; // Resets climb score to 0
    document.getElementById('score-counter').textContent = `${currentScore} / 10 ขั้น`;
    
    loadNextQuestion();
}

function skipQuestion() {
    if (gameStatus !== 'playing') return;
    loadNextQuestion();
}

// TIMER COUNTDOWN LOOP
function startTimerLoop() {
    if (timerInterval) clearInterval(timerInterval);
    
    timerInterval = setInterval(() => {
        if (!isTimerRunning) return;
        
        if (secondsRemaining > 0) {
            secondsRemaining--;
            updateTimerUI();
            
            // Sync state every second during active play
            syncState();
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
    syncState();
}

// End game and show results directly
function saveAndEndGame() {
    isTimerRunning = false;
    clearInterval(timerInterval);
    timerInterval = null;
    gameStatus = 'ended';
    
    document.getElementById('summary-score').textContent = `${currentScore} / 10 ขั้น`;
    
    document.getElementById('screen-game').style.display = 'none';
    document.getElementById('screen-ended').style.display = 'block';
    
    syncState();
}

function forceEndGame() {
    if (confirm('คุณยืนยันที่จะจบเกมนิ้ทันทีหรือไม่?')) {
        saveAndEndGame();
    }
}

function resetGame() {
    gameStatus = 'playing';
    currentQuestion = null;
    
    document.getElementById('screen-game').style.display = 'flex';
    document.getElementById('screen-ended').style.display = 'none';
    
    startGame();
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

// Startup: Clear database sync and start local game immediately
startGame();
</script>

</body>
</html>
