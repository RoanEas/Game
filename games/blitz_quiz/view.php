<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปริศนาสายฟ้า ⚡ PLAYER SCREEN</title>
    <link href="style.css?v=<?=time();?>" rel="stylesheet" type="text/css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="view-screen-body">

<div class="grid-overlay"></div>

<div class="game-container" id="game-app">

    <!-- LOBBY / WAITING SCREEN (SETUP STATUS) -->
    <div id="screen-setup" class="setup-screen" style="max-width:600px; margin: 80px auto; padding: 40px; text-align: center;">
        <div class="spin-icon-wrapper" style="margin-bottom: 25px;">
            <ion-icon name="flash" style="font-size: 5rem; color: var(--neon-cyan); filter: drop-shadow(0 0 15px var(--neon-cyan)); animation: pulseGlow 1.5s infinite alternate;"></ion-icon>
        </div>
        <h2 style="font-family: 'Chakra Petch', sans-serif; font-size: 2.2rem; color: #fff; margin-bottom: 12px;">
            ⚡ ปริศนาสายฟ้า ⚡
        </h2>
        <p style="color: var(--neon-cyan); font-size: 1.1rem; letter-spacing: 1px; font-weight: bold; font-family: 'Chakra Petch';">
            รอกรรมการเริ่มเกม... (WAITING FOR HOST)
        </p>
    </div>

    <!-- MAIN GAMEPLAY (PLAYING STATUS) -->
    <div id="screen-game" style="display: none; flex-direction: column; flex: 1;">
        <div class="game-header">
            <div class="brand-title">
                <ion-icon name="flash" style="color:var(--neon-cyan);"></ion-icon>
                <span>ปริศนาสายฟ้า ⚡</span>
            </div>
            
            <div style="display:flex; align-items:center; gap:25px;">
                <div class="score-badge">
                    <span>คะแนน</span>
                    <strong id="header-score">0</strong>
                </div>
                <div class="timer-display" id="timer-display">02:00</div>
            </div>
        </div>
        
        <div class="split-layout" style="margin-top: 10px;">
            <!-- GLASS LADDER TUBE (LEFT) -->
            <div class="ladder-tube-wrapper" style="position: relative;">
                <div class="ladder-tube">
                    <!-- SVG Lightning bolt inside the tube -->
                    <svg class="lightning-svg" viewBox="0 0 100 560" preserveAspectRatio="none">
                        <path id="lightning-path" d="M 50 0 L 35 60 L 65 120 L 30 180 L 70 240 L 35 300 L 65 360 L 30 420 L 70 480 L 40 520 L 50 560" />
                    </svg>
                    
                    <?php for ($level = 1; $level <= 10; $level++): ?>
                        <div class="ladder-step" id="level-<?php echo $level; ?>">
                            <div class="step-glow"></div>
                            <span class="level-num"><?php echo $level; ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- STACKED HORIZONTAL CHOICES (RIGHT) -->
            <div class="content-panel" style="justify-content: center;">
                <div style="font-size: 1.1rem; color: var(--text-muted); text-align: left; margin-bottom: 15px; font-weight: bold; font-family: 'Chakra Petch'; text-transform: uppercase; letter-spacing: 2px;">
                    🎯 ตัวเลือกคำตอบ (Answer Options)
                </div>
                
                <div class="choices-stack" id="choices-container">
                    <!-- JS renders horizontal choice bars -->
                </div>
            </div>
        </div>
    </div>

    <!-- SUMMARY SCREEN (ENDED STATUS) -->
    <div id="screen-ended" class="setup-screen" style="display: none; max-width:550px; margin: 80px auto; padding: 40px; text-align: center;">
        <ion-icon name="trophy" style="font-size: 5rem; color: #facc15; filter: drop-shadow(0 0 15px rgba(250, 204, 21, 0.4)); margin-bottom: 20px;"></ion-icon>
        <h2 style="font-family: 'Chakra Petch', sans-serif; font-size: 2.2rem; color: #fff;">จบเกมนิ้แล้ว! 🏁</h2>
        <p style="color:var(--text-muted); margin-top:8px; font-family:'Chakra Petch';">สถิติระดับคะแนนที่ทำได้สำเร็จ</p>
        
        <div style="margin:30px 0; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); padding:30px; border-radius:24px;">
            <div style="font-size:0.95rem; color:var(--text-muted); text-transform:uppercase; letter-spacing: 1px; font-family:'Chakra Petch';">ขั้นบันไดสายฟ้าสูงสุด:</div>
            <div id="summary-score" style="font-size:5.5rem; font-weight:900; color:var(--neon-cyan); font-family:'Chakra Petch'; text-shadow:0 0 25px rgba(6,182,212,0.4); margin:15px 0;">
                0 / 10 ขั้น
            </div>
        </div>
        <p style="color: var(--neon-cyan); font-weight: bold; font-family:'Chakra Petch'; font-size: 1rem;">รอผู้ดูแลระบบรีเซ็ตห้อง...</p>
    </div>

</div>

<script>
let lastScore = -1;
let currentScore = 0;
let secondsRemaining = 120;
let timerRunning = false;
let gameStatus = 'setup';
let choicesList = [];

// Audio synth helper
function playSound(type) {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        
        if (type === 'correct') {
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.setValueAtTime(880.00, ctx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } else if (type === 'incorrect') {
            osc.frequency.setValueAtTime(220.00, ctx.currentTime);
            osc.frequency.setValueAtTime(146.83, ctx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.12, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.35);
        } else if (type === 'tick') {
            osc.frequency.setValueAtTime(650, ctx.currentTime);
            gain.gain.setValueAtTime(0.04, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.05);
        } else if (type === 'thunder') {
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

function triggerLightningAnimation() {
    const path = document.getElementById('lightning-path');
    if (!path) return;
    
    path.classList.remove('lightning-flash-animation');
    void path.offsetWidth;
    path.classList.add('lightning-flash-animation');
}

function updateScoreLadderUI() {
    for (let level = 1; level <= 10; level++) {
        const el = document.getElementById('level-' + level);
        if (!el) continue;
        
        el.classList.remove('active-level', 'completed-level');
        
        if (level === currentScore) {
            el.classList.add('active-level');
        } else if (level < currentScore) {
            el.classList.add('completed-level');
        }
    }
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

// Poll state every 0.6 seconds
function pollState() {
    fetch('api_room.php?action=poll_view')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                // Check state transitions
                const statusChanged = data.game_status !== gameStatus;
                const scoreChanged = data.score !== currentScore;
                
                gameStatus = data.game_status;
                currentScore = data.score;
                secondsRemaining = data.seconds_remaining;
                timerRunning = data.timer_running;
                choicesList = data.choices || [];
                
                // Sound effects for transitions
                if (scoreChanged) {
                    if (currentScore > lastScore && lastScore !== -1) {
                        playSound('correct');
                        triggerLightningAnimation();
                    } else if (currentScore < lastScore && lastScore !== -1) {
                        playSound('incorrect');
                    }
                    lastScore = currentScore;
                }
                if (statusChanged) {
                    if (gameStatus === 'playing') {
                        playSound('thunder');
                        triggerLightningAnimation();
                        lastScore = 0;
                    }
                }
                
                // Redraw UI based on active screen
                if (gameStatus === 'setup') {
                    document.getElementById('screen-setup').style.display = 'block';
                    document.getElementById('screen-game').style.display = 'none';
                    document.getElementById('screen-ended').style.display = 'none';
                } else if (gameStatus === 'playing') {
                    document.getElementById('screen-setup').style.display = 'none';
                    document.getElementById('screen-game').style.display = 'flex';
                    document.getElementById('screen-ended').style.display = 'none';
                    
                    document.getElementById('header-score').textContent = currentScore;
                    updateScoreLadderUI();
                    updateTimerUI();
                    
                    // Render horizontal choices rows
                    const letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                    const container = document.getElementById('choices-container');
                    if (choicesList.length > 0) {
                        container.innerHTML = choicesList.map((c, i) => `
                            <div class="choice-row">
                                <span class="choice-num-badge">${letters[i]}</span>
                                <span class="choice-text-val">${c}</span>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div style="color:var(--text-muted); padding:30px;">ไม่มีตัวเลือกข้อมูลคำถาม</div>';
                    }
                } else if (gameStatus === 'ended') {
                    document.getElementById('screen-setup').style.display = 'none';
                    document.getElementById('screen-game').style.display = 'none';
                    document.getElementById('screen-ended').style.display = 'block';
                    document.getElementById('summary-score').textContent = `${currentScore} / 10 ขั้น`;
                }
            }
        });
}

// Start polling loop
setInterval(pollState, 600);
pollState();
</script>

</body>
</html>
