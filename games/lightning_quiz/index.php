<?php
session_start();
// Check if user is logged in
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
    <title>⚡ ปริศนาฟ้าแลบ — Lightning Display</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;700&family=Outfit:wght@400;600;800&family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <!-- Custom Responsive Styling -->
    <link rel="stylesheet" href="style.css?v=<?=time();?>">
</head>
<body>

<div class="game-container">
    
    <!-- 🛸 HEADER HUD -->
    <header class="game-header">
        <a href="../../index.php" class="back-btn">
            <ion-icon name="chevron-back-outline"></ion-icon>
            กลับหน้าหลัก
        </a>
        <h1 class="hud-title">LIGHTNING QUIZ</h1>
        <div style="display: flex; gap: 8px;">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="admin.php" class="back-btn" style="background: var(--neon-green); color: #000; border-color: var(--neon-green);">
                <ion-icon name="options-outline"></ion-icon>
                แผงควบคุม (Host)
            </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="quiz-grid">
        
        <!-- LEFT: LIGHTNING TUBE DISPLAY -->
        <div class="tube-side">
            <div class="lightning-tube-container">
                <div class="lightning-cap top-cap"></div>
                <div class="lightning-tube" id="tube-element">
                    <!-- Dynamic Wave/Water Background -->
                    <div class="tube-water" id="tube-water">
                        <div class="wave-layer"></div>
                        <div class="wave-layer second"></div>
                    </div>
                    <!-- Dynamic JS Lightning Canvas -->
                    <canvas id="lightning-canvas" class="tube-lightning-canvas"></canvas>
                    <!-- Energy levels from 10 down to 1 -->
                    <div class="tube-level" id="level-10"><span class="level-num">10</span></div>
                    <div class="tube-level" id="level-9"><span class="level-num">9</span></div>
                    <div class="tube-level" id="level-8"><span class="level-num">8</span></div>
                    <div class="tube-level" id="level-7"><span class="level-num">7</span></div>
                    <div class="tube-level" id="level-6"><span class="level-num">6</span></div>
                    <div class="tube-level" id="level-5"><span class="level-num">5</span></div>
                    <div class="tube-level" id="level-4"><span class="level-num">4</span></div>
                    <div class="tube-level" id="level-3"><span class="level-num">3</span></div>
                    <div class="tube-level" id="level-2"><span class="level-num">2</span></div>
                    <div class="tube-level" id="level-1"><span class="level-num">1</span></div>
                </div>
                <div class="lightning-cap bottom-cap"></div>
                <div class="tube-logo-label">ปริศนาฟ้าแลบ</div>
            </div>
        </div>

        <!-- RIGHT: ACTIVE QUESTION & TIMER -->
        <div class="question-side">
            
            <!-- Timer & controls row -->
            <div class="hud-row">
                <div class="large-timer" id="display-timer">00:60</div>
                <div class="font-resizer-group">
                    <button class="size-btn" onclick="changeFontSize(-4)">A-</button>
                    <button class="size-btn" onclick="changeFontSize(4)">A+</button>
                </div>
            </div>

            <!-- Question Glassmorphism Box -->
            <div class="question-card-box">
                <div class="q-badge">คำถามปัจจุบัน (CURRENT QUESTION)</div>
                <div class="question-text" id="display-question">กำลังรอสัญญาณเริ่มเกมจากพิธีกร...</div>
            </div>

        </div>

    </div>

</div>

<script>
let currentFontSize = parseInt(localStorage.getItem('lightning_font_size') || '40');

function applyFontSize() {
    const qEl = document.getElementById('display-question');
    if (qEl) qEl.style.fontSize = currentFontSize + 'px';
}

function changeFontSize(diff) {
    currentFontSize = Math.min(80, Math.max(24, currentFontSize + diff));
    localStorage.setItem('lightning_font_size', currentFontSize);
    applyFontSize();
}

function syncState() {
    fetch('api.php?action=get_state')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const state = data.state;
                const questions = data.questions;
                
                // 1. Sync Level display (1-10) in the lightning tube
                const level = state.current_level;
                targetLevel = level; // Set global level for canvas animation
                const tubeEl = document.getElementById('tube-element');
                if (tubeEl) {
                    tubeEl.style.setProperty('--current-level', level);
                }
                for (let i = 1; i <= 10; i++) {
                    const el = document.getElementById(`level-${i}`);
                    if (el) {
                        if (i <= level) {
                            el.classList.add('active');
                        } else {
                            el.classList.remove('active');
                        }
                        // Specifically highlight current active target level
                        if (i === level && level > 0) {
                            el.classList.add('current-head');
                        } else {
                            el.classList.remove('current-head');
                        }
                    }
                }
                
                // 2. Sync Active Question
                const activeQ = questions.find(q => parseInt(q.id) === state.current_question_id);
                const qDisplay = document.getElementById('display-question');
                if (qDisplay) {
                    qDisplay.textContent = activeQ ? activeQ.question_text : 'ไม่มีคำถามที่เลือก';
                }
                
                // 3. Sync countdown timer
                let remaining = state.timer_seconds;
                if (state.timer_running === 1) {
                    const now = Date.now();
                    const elapsed = Math.floor((now - state.timer_sync_time) / 1000);
                    remaining = Math.max(0, state.timer_seconds - elapsed);
                }
                
                const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
                const seconds = String(remaining % 60).padStart(2, '0');
                
                const timerEl = document.getElementById('display-timer');
                if (timerEl) {
                    timerEl.textContent = `${minutes}:${seconds}`;
                    if (remaining <= 10) {
                        timerEl.classList.add('danger-pulse');
                    } else {
                        timerEl.classList.remove('danger-pulse');
                    }
                }
            }
        })
        .catch(e => console.error(e));
}

// Initial layout sizer
applyFontSize();

// Start Polling every 1 second
syncState();
setInterval(syncState, 1000);

// ⚡ Dynamic Canvas Lightning Renderer Logic (3D Spiral Helix)
const canvas = document.getElementById('lightning-canvas');
const ctx = canvas.getContext('2d');
let targetLevel = 0; // Synced global level

function resizeCanvas() {
    if (!canvas) return;
    const rect = canvas.getBoundingClientRect();
    if (canvas.width !== Math.floor(rect.width) || canvas.height !== Math.floor(rect.height)) {
        canvas.width = rect.width;
        canvas.height = rect.height;
    }
}

function strokeSubPath(path, isFront) {
    if (path.length < 2) return;
    ctx.save();
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    
    if (isFront) {
        // Front loop wrapping around the front of the tube (bright neon)
        ctx.shadowBlur = 18;
        ctx.shadowColor = '#10b981';
        ctx.strokeStyle = 'rgba(16, 185, 129, 0.95)';
        ctx.lineWidth = 5;
        
        ctx.beginPath();
        ctx.moveTo(path[0].x, path[0].y);
        for (let i = 1; i < path.length; i++) {
            ctx.lineTo(path[i].x, path[i].y);
        }
        ctx.stroke();
        
        // White core wire
        ctx.shadowBlur = 4;
        ctx.shadowColor = '#fff';
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 1.8;
        
        ctx.beginPath();
        ctx.moveTo(path[0].x, path[0].y);
        for (let i = 1; i < path.length; i++) {
            ctx.lineTo(path[i].x, path[i].y);
        }
        ctx.stroke();
    } else {
        // Back loop wrapping behind the tube (faded neon)
        ctx.shadowBlur = 0;
        ctx.strokeStyle = 'rgba(16, 185, 129, 0.35)';
        ctx.lineWidth = 2.5;
        
        ctx.beginPath();
        ctx.moveTo(path[0].x, path[0].y);
        for (let i = 1; i < path.length; i++) {
            ctx.lineTo(path[i].x, path[i].y);
        }
        ctx.stroke();
        
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.45)';
        ctx.lineWidth = 1.0;
        
        ctx.beginPath();
        ctx.moveTo(path[0].x, path[0].y);
        for (let i = 1; i < path.length; i++) {
            ctx.lineTo(path[i].x, path[i].y);
        }
        ctx.stroke();
    }
    ctx.restore();
}

function drawSpiralLightning(h, w, surfaceY) {
    const centerX = w / 2;
    const radius = w * 0.52; // Slightly wider than the tube body to wrap outside
    const turnsPerLevel = 0.5; // Spiral frequency per level step
    const totalTurns = targetLevel * turnsPerLevel; 
    const rotationSpeed = 0.0035;
    const rotationOffset = Date.now() * rotationSpeed;
    
    // We draw from bottom (h) up to the water surface (surfaceY)
    const segments = Math.max(20, targetLevel * 18);
    let currentPath = [];
    let currentIsFront = true;
    
    for (let i = 0; i <= segments; i++) {
        const progress = i / segments;
        // Calculate Y from bottom (h) up to surfaceY
        const y = h - progress * (h - surfaceY);
        
        // Spiral angle
        const angle = progress * (totalTurns * 2 * Math.PI) + rotationOffset;
        
        const cosVal = Math.cos(angle);
        const sinVal = Math.sin(angle);
        
        const baseX = centerX + radius * sinVal;
        
        // Add random lightning jaggedness displacement
        const displaceX = (Math.random() - 0.5) * 14;
        const displaceY = (Math.random() - 0.5) * 5;
        
        const x = baseX + displaceX;
        const finalY = y + displaceY;
        const isFront = cosVal >= 0;
        
        if (i === 0) {
            currentIsFront = isFront;
            currentPath.push({x, y: finalY});
        } else if (isFront !== currentIsFront) {
            // Stroke previous segment
            strokeSubPath(currentPath, currentIsFront);
            // Start next segment from transition point
            currentPath = [{x, y: finalY}];
            currentIsFront = isFront;
        } else {
            currentPath.push({x, y: finalY});
        }
    }
    
    if (currentPath.length > 0) {
        strokeSubPath(currentPath, currentIsFront);
    }
}

function animateLightning() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (targetLevel > 0) {
        resizeCanvas();
        const w = canvas.width;
        const h = canvas.height;
        
        // Water surface height
        const waterHeightRatio = targetLevel / 10;
        const surfaceY = h - (waterHeightRatio * h);
        
        // Draw the 3D spiral lightning wrapping outside the water
        drawSpiralLightning(h, w, surfaceY);
        
        // Spark boundary burst effect
        if (Math.random() < 0.7) {
            ctx.save();
            ctx.beginPath();
            ctx.arc(w / 2, surfaceY, Math.random() * 12 + 6, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
            ctx.shadowBlur = 25;
            ctx.shadowColor = '#10b981';
            ctx.fill();
            ctx.restore();
        }
    }
    
    requestAnimationFrame(animateLightning);
}

// Start loop
requestAnimationFrame(animateLightning);
</script>

</body>
</html>
