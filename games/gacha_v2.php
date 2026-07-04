<?php
$vocational2Dir = '../assets/vocational_2/';

$studentsList = [
    ["id" => 1, "name" => "น้องปวช.2 คนที่ 1", "image" => $vocational2Dir . "student_1.jpg"],
    ["id" => 2, "name" => "น้องปวช.2 คนที่ 2", "image" => $vocational2Dir . "student_2.jpg"],
    ["id" => 3, "name" => "น้องปวช.2 คนที่ 3", "image" => $vocational2Dir . "student_3.jpg"],
    ["id" => 4, "name" => "น้องปวช.2 คนที่ 4", "image" => $vocational2Dir . "student_4.jpg"],
    ["id" => 5, "name" => "น้องปวช.2 คนที่ 5", "image" => $vocational2Dir . "student_5.jpg"],
    ["id" => 6, "name" => "น้องปวช.2 คนที่ 6", "image" => $vocational2Dir . "student_6.jpg"],
    ["id" => 7, "name" => "น้องปวช.2 คนที่ 7", "image" => $vocational2Dir . "student_7.jpg"],
    ["id" => 8, "name" => "น้องปวช.2 คนที่ 8", "image" => $vocational2Dir . "student_8.jpg"],
    ["id" => 9, "name" => "น้องปวช.2 คนที่ 9", "image" => $vocational2Dir . "student_9.jpg"],
    ["id" => 10, "name" => "น้องปวช.2 คนที่ 10", "image" => $vocational2Dir . "student_10.jpg"],
    ["id" => 11, "name" => "น้องปวช.2 คนที่ 11", "image" => $vocational2Dir . "student_11.jpg"],
    ["id" => 12, "name" => "น้องปวช.2 คนที่ 12", "image" => $vocational2Dir . "student_12.jpg"],
    ["id" => 13, "name" => "น้องปวช.2 คนที่ 13", "image" => $vocational2Dir . "student_13.jpg"],
    ["id" => 14, "name" => "น้องปวช.2 คนที่ 14", "image" => $vocational2Dir . "student_14.jpg"],
    ["id" => 15, "name" => "น้องปวช.2 คนที่ 15", "image" => $vocational2Dir . "student_15.jpg"],
    ["id" => 16, "name" => "น้องปวช.2 คนที่ 16", "image" => $vocational2Dir . "student_16.jpg"],
    ["id" => 17, "name" => "น้องปวช.2 คนที่ 17", "image" => $vocational2Dir . "student_17.jpg"]
];

$studentsJson = json_encode($studentsList);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎰 GACHA ELIMINATION — ปวช.2</title>
    <link rel="stylesheet" href="gacha_v2_style.css?v=<?=time();?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>

<!-- Cinematic Background -->
<div class="bg-cinematic"></div>

<!-- Particle Container -->
<div class="particle-container" id="particle-container"></div>

<!-- ═══════════════════ TOP NAVIGATION ═══════════════════ -->
<nav class="top-nav">
    <a href="../index.php" class="back-btn">
        <ion-icon name="chevron-back-outline"></ion-icon>
        กลับ
    </a>
    <span class="nav-center-title">GACHA ELIMINATION</span>
    <div class="survivor-pill">
        <div>
            <div class="survivor-num" id="remaining-count">17</div>
        </div>
        <div class="survivor-label">SURVIVORS<br>REMAINING</div>
    </div>
</nav>

<!-- ═══════════════════ SLOT MACHINE ═══════════════════ -->
<section class="slot-machine-section">
    <div class="slot-frame">

        <!-- Frame Header -->
        <div class="slot-frame-header">
            <span class="slot-label">⬡ ELIMINATION REEL SYSTEM v2</span>
            <div class="slot-indicator-dots">
                <div class="dot red"></div>
                <div class="dot orange"></div>
                <div class="dot blue"></div>
            </div>
        </div>

        <!-- The Reel Window -->
        <div class="gacha-window" id="gacha-window">
            <!-- Center target zone -->
            <div class="target-zone">
                <div class="target-label">▼ TARGET ▼</div>
            </div>
            <!-- Card Strip -->
            <div class="gacha-track" id="gacha-track"></div>
        </div>

        <!-- Status Bar -->
        <div class="status-section">
            <div class="status-icon" id="status-icon">🚨</div>
            <div class="status-text-group">
                <div class="status-eyebrow">SYSTEM STATUS</div>
                <div class="status-message" id="result-banner">
                    STANDBY — กดปุ่มเพื่อเริ่มปฏิบัติการสไลด์สายพาน
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ═══════════════════ CONTROLS ═══════════════════ -->
<div class="controls-section">
    <button class="ctrl-btn btn-roll" id="btn-roll" onclick="rollGacha()">
        <ion-icon name="flame-outline"></ion-icon>
        เริ่มสุ่มวงล้อ
    </button>
    <button class="ctrl-btn btn-next" id="btn-next" onclick="prepareNextRound()" disabled>
        <ion-icon name="play-skip-forward-outline"></ion-icon>
        รอบถัดไป
    </button>
    <button class="ctrl-btn btn-reset" id="btn-reset" onclick="resetGame()">
        <ion-icon name="refresh-outline"></ion-icon>
        รีเซ็ต
    </button>
</div>

<!-- ═══════════════════ ELIMINATION LOG ═══════════════════ -->
<div class="history-section">
    <div class="history-header">
        <span class="history-eyebrow">💀 ELIMINATION LOG</span>
        <div class="history-divider"></div>
    </div>
    <div class="history-chips" id="history-list">
        <span class="history-empty">ยังไม่มีผู้ถูกคัดออก...</span>
    </div>
</div>

<!-- ═══════════════════ JAVASCRIPT ═══════════════════ -->
<script>
const originalData = <?php echo $studentsJson; ?>;
let currentPool = [...originalData];
let chosenWinner = null;
let audioCtx = null;
let eliminatedList = [];

const CARD_W = 170;
const CARD_GAP = 20;
const CARD_STRIDE = CARD_W + CARD_GAP;

// ── Audio ──────────────────────────────────────────────
function getCtx() {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    return audioCtx;
}
function playTick(vol = 0.05, freq = 800) {
    try {
        const c = getCtx();
        const o = c.createOscillator(), g = c.createGain();
        o.type = 'sine'; o.frequency.value = freq + Math.random() * 300;
        g.gain.setValueAtTime(vol, c.currentTime);
        g.gain.exponentialRampToValueAtTime(0.0001, c.currentTime + 0.04);
        o.connect(g); g.connect(c.destination);
        o.start(); o.stop(c.currentTime + 0.04);
    } catch(e) {}
}
function playDrumroll() {
    for (let i = 0; i < 18; i++) setTimeout(() => playTick(0.07, 90 + Math.random() * 60), i * 35);
}
function playEliminate() {
    try {
        const c = getCtx();
        // Bass drop
        const o1 = c.createOscillator(), g1 = c.createGain();
        o1.type = 'sawtooth'; o1.frequency.setValueAtTime(200, c.currentTime);
        o1.frequency.linearRampToValueAtTime(40, c.currentTime + 0.7);
        g1.gain.setValueAtTime(0.5, c.currentTime);
        g1.gain.exponentialRampToValueAtTime(0.001, c.currentTime + 0.8);
        o1.connect(g1); g1.connect(c.destination);
        o1.start(); o1.stop(c.currentTime + 0.8);
        // High shriek
        const o2 = c.createOscillator(), g2 = c.createGain();
        o2.type = 'sine'; o2.frequency.setValueAtTime(1400, c.currentTime);
        o2.frequency.exponentialRampToValueAtTime(300, c.currentTime + 0.5);
        g2.gain.setValueAtTime(0.18, c.currentTime);
        g2.gain.exponentialRampToValueAtTime(0.001, c.currentTime + 0.5);
        o2.connect(g2); g2.connect(c.destination);
        o2.start(); o2.stop(c.currentTime + 0.5);
    } catch(e) {}
}

// ── Particles ──────────────────────────────────────────
function spawnParticles(n = 50) {
    const cont = document.getElementById('particle-container');
    const cols = ['#ff3b30','#ff9f0a','#ec4899','#ff6b6b','#ffd60a'];
    for (let i = 0; i < n; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = 4 + Math.random() * 7;
        p.style.cssText = `
            left:${Math.random()*100}vw; top:-10px;
            width:${size}px; height:${size}px;
            background:${cols[~~(Math.random()*cols.length)]};
            animation-duration:${1.5+Math.random()*2}s;
            animation-delay:${Math.random()*0.4}s;
        `;
        cont.appendChild(p);
        setTimeout(() => p.remove(), 4000);
    }
}

// ── Screen Shake ───────────────────────────────────────
function shakeScreen() {
    document.body.style.animation = 'shakeX 0.45s ease';
    setTimeout(() => document.body.style.animation = '', 450);
}

// ── Build / Render Track ───────────────────────────────
function buildCard(item, specId = '') {
    const fallback = `https://api.dicebear.com/7.x/pixel-art/svg?seed=${item.id}`;
    return `<div class="gacha-card" ${specId ? `id="${specId}"` : ''}>
                <img class="card-photo" src="${item.image}" onerror="this.src='${fallback}'" alt="student">
                <div class="card-name">${item.name}</div>
            </div>`;
}

function renderEmptyTrack() {
    const track = document.getElementById('gacha-track');
    const winW = document.getElementById('gacha-window').offsetWidth || 900;
    track.style.transition = 'none';
    const initX = (winW / 2) - (CARD_W / 2) - CARD_GAP;
    track.style.transform = `translateX(${initX}px)`;
    track.innerHTML = currentPool.map(item => buildCard(item)).join('');
    document.getElementById('remaining-count').textContent = currentPool.length;
}

// ── MAIN ROLL ──────────────────────────────────────────
function rollGacha() {
    if (!currentPool.length) return;
    if (currentPool.length < 3) { executeGachaStandard(); return; }

    setButtons(false, false, false);
    document.getElementById('status-icon').textContent = '🎰';
    document.getElementById('result-banner').innerHTML =
        '<span style="color:var(--neon-orange);animation:blink 0.4s infinite">🔄 วงล้อกำลังหมุน...</span>';

    const track = document.getElementById('gacha-track');
    const winW  = document.getElementById('gacha-window').offsetWidth;

    track.style.transition = 'none';
    track.style.transform  = 'translateX(0px)';
    track.innerHTML = '';

    // Pick 3 finalists
    const shuffled  = [...currentPool].sort(() => Math.random() - 0.5);
    const [cL, cC, cR] = shuffled;
    const winIdx    = Math.floor(Math.random() * 3);
    chosenWinner    = [cL, cC, cR][winIdx];

    // Build decoy reel (5 shuffled rounds + locked trio at pos 29,30,31)
    let reel = [];
    for (let r = 0; r < 5; r++) reel = reel.concat([...currentPool].sort(() => Math.random() - 0.5));
    const PL = 29, PC = 30, PR = 31;
    reel[PL] = cL; reel[PC] = cC; reel[PR] = cR;

    track.innerHTML = reel.map((item, i) => {
        const sid = i === PL ? 'card-L' : i === PC ? 'card-C' : i === PR ? 'card-R' : '';
        return buildCard(item, sid);
    }).join('');

    // Stop exactly on center card
    const stopX = -((PC * CARD_STRIDE) - (winW / 2) + (CARD_W / 2) + CARD_GAP);

    // Tick sounds while spinning
    let t = 0;
    const ti = setInterval(() => { if (++t < 44) playTick(0.04); else clearInterval(ti); }, 75);

    // Kick off spin
    requestAnimationFrame(() => {
        track.style.transition = 'transform 3.6s cubic-bezier(0.04, 0.72, 0.1, 1)';
        track.style.transform  = `translateX(${stopX}px)`;
    });

    // After spin settles — flash phase
    setTimeout(() => {
        document.getElementById('result-banner').innerHTML =
            '<span style="color:var(--neon-orange);font-weight:700;animation:blink 0.45s infinite">⚠️ ระบบสุ่มไฟกระพริบ 3 คนสุดท้าย...</span>';
        playDrumroll();
        runFlash(winIdx);
    }, 3700);
}

function runFlash(winIdx) {
    const ids = ['card-L', 'card-C', 'card-R'];
    let cur = 0, speed = 80, elapsed = 0;

    function step() {
        ids.forEach(id => document.getElementById(id)?.classList.remove('active-flash-box'));
        document.getElementById(ids[cur])?.classList.add('active-flash-box');
        playTick(0.045, 500);

        cur = (cur + 1) % 3;
        elapsed += speed;

        if (elapsed < 1800) {
            setTimeout(step, speed);
        } else if (speed < 420) {
            speed += 55;
            setTimeout(step, speed);
        } else {
            finalReveal(winIdx);
        }
    }
    step();
}

function finalReveal(winIdx) {
    playEliminate();
    shakeScreen();
    spawnParticles(60);

    document.getElementById('status-icon').textContent = '💀';

    document.querySelectorAll('.gacha-card').forEach(c => c.classList.add('fade-dark'));

    const ids = ['card-L', 'card-C', 'card-R'];
    const chosen = document.getElementById(ids[winIdx]);
    if (chosen) {
        chosen.classList.remove('fade-dark', 'active-flash-box');
        chosen.classList.add('the-chosen-one');
    }

    document.getElementById('result-banner').innerHTML =
        `💀 คัดออก: <span style="color:var(--neon-red);font-weight:800;">"${chosenWinner.name}"</span>`;

    addHistory(chosenWinner);
    setButtons(false, true, true); // only next + reset
}

// ── Fallback for ≤ 2 survivors ─────────────────────────
function executeGachaStandard() {
    const track = document.getElementById('gacha-track');
    const winW  = document.getElementById('gacha-window').offsetWidth;
    chosenWinner = currentPool[Math.floor(Math.random() * currentPool.length)];
    const stopX  = (winW / 2) - (CARD_W / 2) - CARD_GAP;
    track.style.transition = 'transform 2s ease-out';
    track.style.transform  = `translateX(${stopX}px)`;
    setTimeout(() => {
        playEliminate(); shakeScreen(); spawnParticles(30);
        document.getElementById('status-icon').textContent = '💀';
        document.getElementById('result-banner').innerHTML =
            `💀 คัดออก: <span style="color:var(--neon-red);font-weight:800;">"${chosenWinner.name}"</span>`;
        addHistory(chosenWinner);
        setButtons(false, true, true);
    }, 2100);
}

// ── Round Management ───────────────────────────────────
function prepareNextRound() {
    if (!chosenWinner) return;
    currentPool = currentPool.filter(s => s.id !== chosenWinner.id);
    chosenWinner = null;
    renderEmptyTrack();
    document.getElementById('status-icon').textContent = '🚨';
    if (!currentPool.length) {
        document.getElementById('result-banner').textContent = '🏁 กระบวนการคัดออกเสร็จสมบูรณ์ทั้ง 17 คน';
        setButtons(false, false, true);
    } else {
        document.getElementById('result-banner').textContent =
            `พร้อมสุ่มรอบถัดไป — เหลือ ${currentPool.length} คน`;
        setButtons(true, false, true);
    }
}

function resetGame() {
    currentPool  = [...originalData];
    chosenWinner = null;
    eliminatedList = [];
    renderEmptyTrack();
    document.getElementById('status-icon').textContent = '🚨';
    document.getElementById('result-banner').textContent = 'รีเซ็ตระบบเรียบร้อย — พร้อมสุ่มได้ทันที';
    document.getElementById('history-list').innerHTML =
        '<span class="history-empty">ยังไม่มีผู้ถูกคัดออก...</span>';
    setButtons(true, false, true);
}

function setButtons(roll, next, reset) {
    document.getElementById('btn-roll').disabled  = !roll;
    document.getElementById('btn-next').disabled  = !next;
    document.getElementById('btn-reset').disabled = !reset;
}

// ── History Log ────────────────────────────────────────
function addHistory(student) {
    eliminatedList.push(student);
    const list = document.getElementById('history-list');
    const fallback = `https://api.dicebear.com/7.x/pixel-art/svg?seed=${student.id}`;
    // Remove the empty placeholder if present
    const placeholder = list.querySelector('.history-empty');
    if (placeholder) placeholder.remove();
    const chip = document.createElement('div');
    chip.className = 'history-chip';
    chip.innerHTML = `
        <img src="${student.image}" onerror="this.src='${fallback}'" alt="">
        <span>#${eliminatedList.length} ${student.name}</span>
    `;
    list.appendChild(chip);
}

// ── Init ───────────────────────────────────────────────
window.addEventListener('load', renderEmptyTrack);
window.addEventListener('resize', renderEmptyTrack);
</script>
</body>
</html>