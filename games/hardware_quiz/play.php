<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$projectDir = '/gmaemini'; 
$jsonPath = dirname(__DIR__, 2) . '/data/bingo_items.json';

// Ensure json exists, otherwise read default or fallback
if (file_exists($jsonPath)) {
    $itemsJson = file_get_contents($jsonPath);
    $itemsData = json_decode($itemsJson, true);
    if (is_array($itemsData)) {
        $needsWrite = false;
        if (!isset($itemsData['round_id'])) {
            $itemsData['round_id'] = uniqid('r_', true);
            $needsWrite = true;
        }
        
        // Define the 5 decoy items
        $decoys = [
            ["id" => 25, "name" => "เครื่องปริ้นเตอร์ 3 มิติ", "image" => "https://images.unsplash.com/photo-1615811361523-6bd03d7748e7?auto=format&fit=crop&w=300&q=80", "decoy" => true],
            ["id" => 26, "name" => "แว่น VR", "image" => "https://images.unsplash.com/photo-1593508512255-86ab42a8e620?auto=format&fit=crop&w=300&q=80", "decoy" => true],
            ["id" => 27, "name" => "พล็อตเตอร์", "image" => "https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=300&q=80", "decoy" => true],
            ["id" => 28, "name" => "การ์ดเสียง", "image" => "https://images.unsplash.com/photo-1580584126903-c17d41830450?auto=format&fit=crop&w=300&q=80", "decoy" => true],
            ["id" => 29, "name" => "เครื่องสำรองไฟ (UPS)", "image" => "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=300&q=80", "decoy" => true]
        ];
        $existingIds = array_column($itemsData['items'], 'id');
        foreach ($decoys as $decoy) {
            if (!in_array($decoy['id'], $existingIds)) {
                $itemsData['items'][] = $decoy;
                $needsWrite = true;
            }
        }
        
        if (!isset($itemsData['draw_sequence']) || empty($itemsData['draw_sequence'])) {
            $itemIds = [];
            foreach (($itemsData['items'] ?? []) as $item) {
                if (empty($item['decoy'])) {
                    $itemIds[] = intval($item['id']);
                }
            }
            shuffle($itemIds);
            $itemsData['draw_sequence'] = $itemIds;
            $needsWrite = true;
        }
        if ($needsWrite) {
            file_put_contents($jsonPath, json_encode($itemsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        $itemsJson = json_encode($itemsData);
    }
} else {
    // Fallback if not generated yet
    $itemsJson = json_encode(["items" => []]);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>กระดานบิงโกของฉัน 🎴</title>
    <link href="quiz-style.css?v=<?=time();?>" rel="stylesheet" type="text/css">
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
<script>
    (function() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            document.body.classList.add('light-mode');
        }
    })();
</script>

<canvas id="canvas-particles"></canvas>
<canvas id="canvas-fireworks"></canvas>
<div class="modal-backdrop" id="backdrop" onclick="closeCompleteModal()"></div>

<div class="minimal-app" style="max-width: 480px;">

    <!-- TOP BAR -->
    <header class="hud-header">
        <h1 class="hud-title">กระดานบิงโกของฉัน</h1>
        <div class="hud-actions">
            <button class="circle-btn" onclick="window.location.href='../../index.php'" title="กลับหน้าแรกหลัก">
                <ion-icon name="home-outline"></ion-icon>
            </button>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <button class="circle-btn" onclick="window.location.href='index.php'" title="เครื่องควบคุมสุ่ม (Admin)" style="color: #facc15; border-color: rgba(250, 204, 21, 0.4); background: rgba(250, 204, 21, 0.1);">
                    <ion-icon name="settings-outline"></ion-icon>
                </button>
            <?php endif; ?>
            <button class="circle-btn" id="btn-theme" onclick="toggleTheme()" title="เปลี่ยนธีม มืด/สว่าง">
                <ion-icon name="moon-outline" id="theme-icon"></ion-icon>
            </button>
            <button class="circle-btn" id="btn-sound" onclick="toggleMusic()" title="เพลงประกอบกล่องดนตรี">
                <ion-icon name="musical-notes-outline" id="sound-icon"></ion-icon>
            </button>
        </div>
    </header>

    <!-- MAIN CARD -->
    <main class="main-card" style="padding: 20px 16px;">
        
        <span class="status-pill" id="bingo-status">จิ้มช่องรูปภาพเพื่อกากบาท (ติ๊ก)</span>

        <!-- 5x5 PLAY BOARD -->
        <div class="board-grid" id="player-grid-container" style="gap: 4px;"></div>


    </main>

    <!-- FLOATING TABBAR -->
    <div class="floating-tabbar">
        <a href="../../index.php" class="tab-item">หน้าแรกหลัก</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="index.php" class="tab-item">เครื่องสุ่มจับ</a>
            <a href="../../admin.php" class="tab-item" style="color: #facc15;">คุมระบบ (Admin)</a>
        <?php endif; ?>
        <div class="tab-item active">กระดานเล่นบิงโก</div>
        <a href="../../dashboard.php" class="tab-item">หน้าควบคุมหลัก</a>
    </div>


</div>

<!-- BINGO CELEBRATION OVERLAY -->
<div class="complete-overlay" id="complete-screen">
    <div class="complete-modal">
        <div class="complete-title" style="font-size: 2.2rem; color: #fbbf24; text-shadow: 0 0 10px rgba(251,191,36,0.5);">BINGO! 🎉</div>
        <div class="complete-subtitle">ยินดีด้วย! คุณได้ทำไลน์บิงโกสำเร็จแล้ว! ขอให้สนุกกับการเล่นบิงโกชิปตัวต่อไอทีต่อไป</div>
        <button class="btn-draw" onclick="closeCompleteModal()" style="box-shadow:none;">
            เล่นต่อบนกระดานนี้
        </button>
    </div>
</div>

<script>
const RAW = <?php echo $itemsJson; ?>;
const allItems = [...(RAW.items || [])];

// State variables
let playerCard = []; // 25 cells total representing player grid
let hasWonBingo = false; // Flag to prevent multiple win triggers

// Audio Engine - Cozy Music Box Synthesizer
let audioCtx = null;
let bgmTimer = null;
let bgmPlaying = false;
let bgmBeat = 0;

function initAudio() {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === 'suspended') audioCtx.resume();
}

function playTone(freq, type = 'sine', dur = 0.1, vol = 0.08) {
    try {
        initAudio();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        
        osc.type = type;
        osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
        
        gain.gain.setValueAtTime(vol, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + dur);
        
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        
        osc.start();
        osc.stop(audioCtx.currentTime + dur);
    } catch(e) {}
}

// Cozy Chimes SFX
function sndTickUp() { playTone(600 + Math.random() * 200, 'sine', 0.06, 0.04); }
function sndTickDown() { playTone(300 + Math.random() * 100, 'sine', 0.08, 0.04); }
function sndReset() { 
    playTone(600, 'sine', 0.08, 0.04);
    setTimeout(() => playTone(500, 'sine', 0.08, 0.04), 40);
    setTimeout(() => playTone(400, 'sine', 0.12, 0.04), 80);
}
function sndBingo() {
    let notes = [523.25, 587.33, 659.25, 783.99, 880.00, 1046.50, 1318.51]; // C5 to E6 arpeggio
    notes.forEach((freq, i) => {
        setTimeout(() => playTone(freq, 'sine', 0.5, 0.06), i * 70);
    });
}

// Cozy BGM Generator
const musicBoxMelody = [
    523.25, 0, 659.25, 0, 783.99, 880.00, 0, 783.99,
    587.33, 0, 698.46, 0, 880.00, 987.77, 0, 880.00,
    659.25, 0, 783.99, 0, 987.77, 1046.50, 0, 987.77,
    523.25, 0, 659.25, 0, 783.99, 880.00, 0, 1046.50
];

function startMusicBoxSynth() {
    if (bgmTimer) clearInterval(bgmTimer);
    bgmTimer = setInterval(() => {
        if (!bgmPlaying || !audioCtx) return;
        
        let note = musicBoxMelody[bgmBeat % musicBoxMelody.length];
        
        if (note > 0) {
            playTone(note, 'sine', 0.35, 0.03);
            if (bgmBeat % 4 === 0) {
                let bassNote = note / 4;
                playTone(bassNote, 'sine', 0.5, 0.05);
            }
        }
        
        bgmBeat++;
    }, 280);
}

function toggleMusic() {
    initAudio();
    const btn = document.getElementById('btn-sound');
    const icon = document.getElementById('sound-icon');
    
    if (bgmPlaying) {
        bgmPlaying = false;
        icon.setAttribute('name', 'volume-mute-outline');
        btn.classList.remove('active');
    } else {
        bgmPlaying = true;
        icon.setAttribute('name', 'volume-high-outline');
        btn.classList.add('active');
        startMusicBoxSynth();
    }
}

// Theme Management (Light / Dark Mode)
function updateThemeIcon() {
    const themeIcon = document.getElementById('theme-icon');
    const btnTheme = document.getElementById('btn-theme');
    if (document.body.classList.contains('light-mode')) {
        if (themeIcon) themeIcon.setAttribute('name', 'sun-outline');
        if (btnTheme) btnTheme.classList.add('active');
    } else {
        if (themeIcon) themeIcon.setAttribute('name', 'moon-outline');
        if (btnTheme) btnTheme.classList.remove('active');
    }
}

function toggleTheme() {
    initAudio();
    const body = document.body;
    if (body.classList.contains('light-mode')) {
        body.classList.remove('light-mode');
        localStorage.setItem('theme', 'dark');
        playTone(400, 'sine', 0.08, 0.04);
    } else {
        body.classList.add('light-mode');
        localStorage.setItem('theme', 'light');
        playTone(600, 'sine', 0.08, 0.04);
    }
    updateThemeIcon();
}

// Get relative path helper
function getImageUrl(path) {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }
    return '../../' + path;
}

// Load or Generate Player Card based on Server Round ID
const serverRoundId = RAW.round_id || 'default_round';

function loadOrCreatePlayerCard() {
    const saved = localStorage.getItem('bingo_player_card');
    const savedRound = localStorage.getItem('bingo_round_id');
    
    if (saved && savedRound === serverRoundId) {
        try {
            playerCard = JSON.parse(saved);
            hasWonBingo = localStorage.getItem('bingo_has_won') === 'true';
            return;
        } catch(e) {}
    }
    
    // Generate new card for the new server round
    generateNewCardForRound();
}

function generateNewCardForRound() {
    sndReset();
    stopFireworks();
    
    const realItems = allItems.filter(item => !item.decoy);
    const decoyItems = allItems.filter(item => item.decoy);
    
    let selectedItems = [];
    
    // 80% chance to generate an unlucky card (with decoys)
    const isUnlucky = Math.random() < 0.8;
    
    if (isUnlucky && decoyItems.length > 0) {
        // Unlucky card: contains 2-4 decoy items
        const numDecoys = Math.floor(Math.random() * 3) + 2; // 2, 3, or 4 decoys
        
        // Shuffle decoyItems and select numDecoys
        const shuffledDecoys = [...decoyItems];
        for (let i = shuffledDecoys.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffledDecoys[i], shuffledDecoys[j]] = [shuffledDecoys[j], shuffledDecoys[i]];
        }
        const selectedDecoys = shuffledDecoys.slice(0, numDecoys);
        
        // Shuffle realItems and select (24 - numDecoys)
        const shuffledReals = [...realItems];
        for (let i = shuffledReals.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffledReals[i], shuffledReals[j]] = [shuffledReals[j], shuffledReals[i]];
        }
        const selectedReals = shuffledReals.slice(0, 24 - numDecoys);
        
        // Combine and shuffle the final 24 items
        selectedItems = [...selectedDecoys, ...selectedReals];
        for (let i = selectedItems.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [selectedItems[i], selectedItems[j]] = [selectedItems[j], selectedItems[i]];
        }
    } else {
        // Lucky card: contains exactly all 24 real items (0 decoys)
        const shuffledReals = [...realItems];
        for (let i = shuffledReals.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffledReals[i], shuffledReals[j]] = [shuffledReals[j], shuffledReals[i]];
        }
        selectedItems = shuffledReals;
    }
    
    playerCard = [];
    let itemIdx = 0;
    
    for (let i = 0; i < 25; i++) {
        if (i === 12) {
            playerCard.push({ isFree: true, ticked: true });
        } else {
            const item = selectedItems[itemIdx++];
            playerCard.push({
                isFree: false,
                id: item.id,
                name: item.name,
                image: item.image,
                ticked: false
            });
        }
    }
    
    hasWonBingo = false;
    localStorage.setItem('bingo_round_id', serverRoundId);
    saveCardState();
    renderPlayerBoard();
    
    document.getElementById('bingo-status').textContent = "บิงโกเริ่มเกมรอบใหม่! กระดานสุ่มใหม่แล้ว";
}


function saveCardState() {
    localStorage.setItem('bingo_player_card', JSON.stringify(playerCard));
    localStorage.setItem('bingo_has_won', hasWonBingo ? 'true' : 'false');
}

let serverDrawnIds = [];

// Toggle cell ticked status
function toggleCell(cellIdx) {
    initAudio();
    const cell = playerCard[cellIdx];
    if (cell.isFree) return; // FREE cell always ticked
    
    // Anti-cheat check: only allow ticking if the item ID has been drawn on the server!
    if (!cell.ticked && !serverDrawnIds.includes(cell.id)) {
        // Play error buzzer sound
        playTone(180, 'sawtooth', 0.2, 0.08);
        document.getElementById('bingo-status').textContent = `❌ อุปกรณ์ '${cell.name}' ยังไม่ถูกสุ่มออกรางวัล!`;
        return;
    }
    
    cell.ticked = !cell.ticked;
    
    if (cell.ticked) {
        sndTickUp();
    } else {
        sndTickDown();
    }
    
    saveCardState();
    renderPlayerBoard();
    
    // Check if player won Bingo
    checkBingoWin();
}

// Render Board grid html
function renderPlayerBoard() {
    const container = document.getElementById('player-grid-container');
    container.innerHTML = '';
    
    playerCard.forEach((cell, idx) => {
        const div = document.createElement('div');
        
        if (cell.isFree) {
            div.className = 'board-cell cell-free player-cell cell-ticked';
            div.innerHTML = `
                <ion-icon name="star"></ion-icon>
                <span class="cell-free-text">FREE</span>
            `;
        } else {
            div.className = 'board-cell player-cell';
            if (cell.ticked) div.classList.add('cell-ticked');
            
            div.setAttribute('onclick', `toggleCell(${idx})`);
            div.innerHTML = `
                <img src="${getImageUrl(cell.image)}" alt="">
                <span class="cell-check-icon"><ion-icon name="close-outline"></ion-icon></span>
                <span class="cell-name-label">${cell.name}</span>
            `;
        }
        container.appendChild(div);
    });
}

// Bingo win checker
function checkBingoWin() {
    if (hasWonBingo) return; // Already won
    
    // Lines combination indices (0-indexed flat list of 25 items)
    const winCombinations = [
        // 5 Rows
        [0, 1, 2, 3, 4],
        [5, 6, 7, 8, 9],
        [10, 11, 12, 13, 14],
        [15, 16, 17, 18, 19],
        [20, 21, 22, 23, 24],
        // 5 Columns
        [0, 5, 10, 15, 20],
        [1, 6, 11, 16, 21],
        [2, 7, 12, 17, 22],
        [3, 8, 13, 18, 23],
        [4, 9, 14, 19, 24],
        // 2 Diagonals
        [0, 6, 12, 18, 24],
        [4, 8, 12, 16, 20]
    ];
    
    let isBingo = false;
    
    for (let combo of winCombinations) {
        let isLineCompleted = combo.every(idx => playerCard[idx].ticked);
        if (isLineCompleted) {
            isBingo = true;
            break;
        }
    }
    
    if (isBingo) {
        hasWonBingo = true;
        saveCardState();
        
        // Notify the server of Bingo claim
        fetch('api_save.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'claim_bingo' })
        });
        
        // Show celebration
        document.getElementById('complete-screen').classList.add('active');
        document.getElementById('backdrop').classList.add('active');
        sndBingo();
        startFireworks();
        
        document.getElementById('bingo-status').textContent = "บิงโกสำเร็จแล้ว! 🎉";
    }
}


function closeCompleteModal() {
    document.getElementById('complete-screen').classList.remove('active');
    document.getElementById('backdrop').classList.remove('active');
}

// 🎆 GENTLE DUST PARTICLES ENGINE (HTML5 CANVAS)
const partCanvas = document.getElementById('canvas-particles');
const partCtx = partCanvas.getContext('2d');
let particlesList = [];

function resizePartCanvas() {
    partCanvas.width = window.innerWidth;
    partCanvas.height = window.innerHeight;
}
window.addEventListener('resize', resizePartCanvas);
resizePartCanvas();

class DustParticle {
    constructor() {
        this.x = Math.random() * partCanvas.width;
        this.y = Math.random() * partCanvas.height;
        this.vx = (Math.random() - 0.5) * 0.2;
        this.vy = (Math.random() - 0.5) * 0.2;
        this.radius = Math.random() * 1.5 + 0.5;
        this.alpha = Math.random() * 0.3 + 0.1;
    }
    update() {
        this.x += this.vx;
        this.y += this.vy;
        
        if (this.x < 0) this.x = partCanvas.width;
        if (this.x > partCanvas.width) this.x = 0;
        if (this.y < 0) this.y = partCanvas.height;
        if (this.y > partCanvas.height) this.y = 0;
    }
    draw() {
        partCtx.beginPath();
        partCtx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
        const isLight = document.body.classList.contains('light-mode');
        partCtx.fillStyle = isLight 
            ? `rgba(0, 0, 0, ${this.alpha * 0.35})` 
            : `rgba(255, 255, 255, ${this.alpha})`;
        partCtx.fill();
    }
}

function initDustNodes() {
    particlesList = [];
    let count = Math.min(45, Math.floor((partCanvas.width * partCanvas.height) / 25000));
    for (let i = 0; i < count; i++) {
        particlesList.push(new DustParticle());
    }
}

function animateDustNodes() {
    requestAnimationFrame(animateDustNodes);
    partCtx.clearRect(0, 0, partCanvas.width, partCanvas.height);
    
    particlesList.forEach(particle => {
        particle.update();
        particle.draw();
    });
}
initDustNodes();
animateDustNodes();

// 🎆 FIREWORKS CANVAS ENGINE
const canvas = document.getElementById('canvas-fireworks');
const ctx = canvas.getContext('2d');
let fireworks = [];
let particles = [];
let animationFrameId = null;

function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
window.addEventListener('resize', resizeCanvas);

class Firework {
    constructor(sx, sy, tx, ty) {
        this.x = sx;
        this.y = sy;
        this.sx = sx;
        this.sy = sy;
        this.tx = tx;
        this.ty = ty;
        this.distanceToTarget = Math.sqrt(Math.pow(tx - sx, 2) + Math.pow(ty - sy, 2));
        this.distanceTraveled = 0;
        this.coordinates = [];
        this.coordinateCount = 3;
        while(this.coordinateCount--) {
            this.coordinates.push([this.x, this.y]);
        }
        this.angle = Math.atan2(ty - sy, tx - sx);
        this.speed = 2.5;
        this.acceleration = 1.05;
        this.brightness = Math.random() * 20 + 50;
        this.targetRadius = 1;
    }
    update(index) {
        this.coordinates.pop();
        this.coordinates.unshift([this.x, this.y]);
        
        if (this.targetRadius < 8) {
            this.targetRadius += 0.3;
        } else {
            this.targetRadius = 1;
        }
        
        this.speed *= this.acceleration;
        
        let vx = Math.cos(this.angle) * this.speed;
        let vy = Math.sin(this.angle) * this.speed;
        this.distanceTraveled = Math.sqrt(Math.pow(this.x + vx - this.sx, 2) + Math.pow(this.y + vy - this.sy, 2));
        
        if (this.distanceTraveled >= this.distanceToTarget) {
            createParticles(this.tx, this.ty);
            fireworks.splice(index, 1);
        } else {
            this.x += vx;
            this.y += vy;
        }
    }
    draw() {
        ctx.beginPath();
        ctx.moveTo(this.coordinates[this.coordinates.length - 1][0], this.coordinates[this.coordinates.length - 1][1]);
        ctx.lineTo(this.x, this.y);
        ctx.strokeStyle = `hsl(${Math.random() * 360}, 100%, ${this.brightness}%)`;
        ctx.stroke();
    }
}

class Particle {
    constructor(x, y) {
        this.x = x;
        this.y = y;
        this.coordinates = [];
        this.coordinateCount = 5;
        while(this.coordinateCount--) {
            this.coordinates.push([this.x, this.y]);
        }
        this.angle = Math.random() * Math.PI * 2;
        this.speed = Math.random() * 8 + 1;
        this.friction = 0.95;
        this.gravity = 1;
        this.hue = Math.random() * 360;
        this.brightness = Math.random() * 20 + 60;
        this.alpha = 1;
        this.decay = Math.random() * 0.015 + 0.015;
    }
    update(index) {
        this.coordinates.pop();
        this.coordinates.unshift([this.x, this.y]);
        this.speed *= this.friction;
        this.x += Math.cos(this.angle) * this.speed;
        this.y += Math.sin(this.angle) * this.speed + this.gravity;
        this.alpha -= this.decay;
        
        if (this.alpha <= this.decay) {
            particles.splice(index, 1);
        }
    }
    draw() {
        ctx.beginPath();
        ctx.moveTo(this.coordinates[this.coordinates.length - 1][0], this.coordinates[this.coordinates.length - 1][1]);
        ctx.lineTo(this.x, this.y);
        ctx.strokeStyle = `hsla(${this.hue}, 100%, ${this.brightness}%, ${this.alpha})`;
        ctx.stroke();
    }
}

function createParticles(x, y) {
    let particleCount = 50;
    while(particleCount--) {
        particles.push(new Particle(x, y));
    }
}

function startFireworks() {
    canvas.classList.add('active');
    resizeCanvas();
    fireworks = [];
    particles = [];
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    animateFireworks();
}

function stopFireworks() {
    canvas.classList.remove('active');
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

let timerTick = 0;
function animateFireworks() {
    animationFrameId = requestAnimationFrame(animateFireworks);
    
    ctx.globalCompositeOperation = 'destination-out';
    ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.globalCompositeOperation = 'lighter';
    
    let i = fireworks.length;
    while(i--) {
        fireworks[i].draw();
        fireworks[i].update(i);
    }
    
    let j = particles.length;
    while(j--) {
        particles[j].draw();
        particles[j].update(j);
    }
    
    if (timerTick >= 20) {
        let startX = canvas.width / 2 + (Math.random() * 200 - 100);
        let startY = canvas.height;
        let targetX = Math.random() * canvas.width;
        let targetY = Math.random() * (canvas.height / 2);
        fireworks.push(new Firework(startX, startY, targetX, targetY));
        timerTick = 0;
    } else {
        timerTick++;
    }
}

// Real-Time Server State Syncing
async function syncDrawnItems() {
    try {
        const res = await fetch('../../data/bingo_items.json?v=' + Date.now());
        const data = await res.json();
        
        // If round ID changed on server, force reload to get a new board
        if (data.round_id && data.round_id !== serverRoundId) {
            window.location.reload();
            return;
        }
        
        serverDrawnIds = data.drawn_ids || [];
        
        // Anti-cheat validation: untick any cell that is NOT drawn on the server (except FREE cell)
        let stateChanged = false;
        playerCard.forEach(cell => {
            if (!cell.isFree && cell.ticked && !serverDrawnIds.includes(cell.id)) {
                cell.ticked = false;
                stateChanged = true;
            }
        });
        
        if (stateChanged) {
            saveCardState();
            renderPlayerBoard();
        }
    } catch(e) {}
}

// Start Setup
updateThemeIcon();
loadOrCreatePlayerCard();
renderPlayerBoard();

// Poll every 2 seconds
setInterval(syncDrawnItems, 2000);
syncDrawnItems();
</script>


</body>
</html>
