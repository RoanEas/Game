<?php
session_start();
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
    <title>โมดูลคัดกรองฮาร์ดแวร์คอมพิวเตอร์</title>
    <link href="quiz-style.css" rel="stylesheet" type="text/css">
</head>
<body>

<div class="full-container">

    <div id="intro-screen" class="screen active">
        <div class="minimal-intro">
            <span class="intro-badge">การประเมินความรู้</span>
            <h1 class="intro-title">HARDWARE<br>IDENTIFIER</h1>
            <p class="intro-subtitle">วิเคราะห์โครงสร้างและสถาปัตยกรรมคลังอุปกรณ์คอมพิวเตอร์ระบบความเร็วสูง</p>
            <button class="btn-start" onclick="startTheGame()">เริ่มทำแบบทดสอบ</button>
        </div>
    </div>

    <div id="play-screen" class="screen">
        <div class="game-info-bar">
            <div>ข้อที่: <span id="current-q-num">1</span> / 10</div>
            <div>คะแนนสะสม: <span id="score-view">0</span></div>
        </div>
        
        <div class="timer-bar-container">
            <div id="t-bar" class="timer-bar"></div>
        </div>

        <div class="bento-layout">
            <div class="bento-media">
                <img id="q-image" src="" alt="ภาพฮาร์ดแวร์">
            </div>
            
            <div class="bento-control">
                <div>
                    <div class="question-text" id="q-title">อุปกรณ์ในภาพมีชื่อเรียกปฏิบัติการว่าอะไร?</div>
                    <div class="options-grid" id="options-area"></div>
                </div>
                
                <div>
                    <div id="status-msg" style="font-weight:500; font-size:0.95rem; min-height:24px; text-align:center; margin-bottom:10px;"></div>
                    <button id="next-btn" class="btn-next-action" style="display:none;" onclick="goNext()">ข้อถัดไป ➔</button>
                </div>
            </div>
        </div>
    </div>

    <div id="result-screen" class="screen">
        <div class="minimal-intro">
            <span class="intro-badge" style="background: var(--ios-card);">การทดสอบเสร็จสิ้น</span>
            <h1 class="intro-title" style="font-size: 3rem; margin-top: 10px; color: var(--ios-blue);">สรุปผลลัพธ์</h1>
            <p class="intro-subtitle" style="font-size:1.1rem; margin-bottom: 40px;">ผลการประเมินของคุณได้ระดับคะแนนสุทธิ <span id="final-score" style="font-weight:600; text-decoration: underline;">0</span> แต้ม</p>
            <button class="btn-start" style="background: #FFFFFF; color: #000000;" onclick="location.reload()">ทำใหม่อีกครั้ง</button>
        </div>
    </div>

    <div id="quiz-tabbar" class="floating-tabbar">
        <div class="tab-item active" onclick="sndClick();">คลังข้อสอบ</div>
        <a href="../../dashboard.php" class="tab-item" onclick="sndClick();">หน้าหลักแผงควบคุม</a>
        <a href="../../logout.php" class="tab-item" style="color: var(--ios-red);" onclick="sndClick();">ออกจากระบบ</a>
    </div>

</div>

<div id="giant-countdown-view" class="giant-countdown">5</div>

<script>
const questionBank = [
    { img: "images/cpu.jpg", answer: "CPU (หน่วยประมวลผลกลาง)", choices: ["CPU", "RAM", "GPU", "Mainboard"] },
    { img: "images/gpu.jpg", answer: "GPU (การ์ดแสดงผล)", choices: ["GPU", "Power Supply", "SSD", "Sound Card"] },
    { img: "images/ram.jpg", answer: "RAM (หน่วยความจำ)", choices: ["RAM", "CPU", "Harddisk", "Network Card"] },
    { img: "images/mainboard.jpg", answer: "Mainboard (แผงวงจรหลัก)", choices: ["Mainboard", "Power Supply", "Heatsink", "RAM Slots"] },
    { img: "images/psu.jpg", answer: "Power Supply (แหล่งจ่ายไฟ)", choices: ["Power Supply", "Mainboard", "Computer Case", "UPS"] },
    { img: "images/ssd.jpg", answer: "SSD M.2 (หน่วยความจำความเร็วสูง)", choices: ["SSD M.2", "RAM", "CPU", "Harddisk SATA"] },
    { img: "images/water_cooling.jpg", answer: "Liquid Cooling (ชุดน้ำระบายความร้อน)", choices: ["Liquid Cooling", "Power Supply", "Air Cooler", "Case Fan"] },
    { img: "images/hdd.jpg", answer: "Harddisk Drive (ฮาร์ดดิสก์จานหมุน)", choices: ["Harddisk Drive", "SSD M.2", "USB Flashdrive", "RAM"] },
    { img: "images/fan.jpg", answer: "Case Fan (พัดลมระบายความร้อนเคส)", choices: ["Case Fan", "CPU Cooler", "Power Supply Fan", "GPU Fan"] },
    { img: "images/case.jpg", answer: "Computer Case (เคสคอมพิวเตอร์)", choices: ["Computer Case", "Mainboard", "Monitor", "Power Supply"] }
];

let activeQuestions = [];
let currentIndex = 0;
let score = 0;
let timeLeft = 10;
let timerInterval = null;
let isSelected = false;

let audioCtx = null;
let bgmInterval = null;
let beatCount = 0;

function initAudio() {
    if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
}

function playTone(freq, type, duration, vol=0.1) {
    if(!audioCtx) return;
    let osc = audioCtx.createOscillator();
    let gainNode = audioCtx.createGain();
    osc.type = type;
    osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
    gainNode.gain.setValueAtTime(vol, audioCtx.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
    osc.connect(gainNode);
    gainNode.connect(audioCtx.destination);
    osc.start();
    osc.stop(audioCtx.currentTime + duration);
}

function sndClick() { playTone(650, 'sine', 0.06, 0.05); }
function sndCorrect() { playTone(523.25, 'sine', 0.08, 0.06); setTimeout(() => playTone(783.99, 'sine', 0.15), 50); }
function sndWrong() { playTone(140, 'triangle', 0.25, 0.12); }
function sndTick() { playTone(950, 'sine', 0.02, 0.04); }

function startMinimalBGM() {
    if (bgmInterval) clearInterval(bgmInterval);
    bgmInterval = setInterval(() => {
        if (!audioCtx) return;
        let bassLines = [55.00, 55.00, 65.41, 65.41, 55.00, 55.00, 48.99, 58.27];
        let currentBass = bassLines[beatCount % bassLines.length];
        playTone(currentBass, 'triangle', 0.25, 0.15);
        if (beatCount % 2 === 0) {
            playTone(45, 'sine', 0.08, 0.25);
        }
        beatCount++;
    }, 300);
}

function stopMinimalBGM() {
    if (bgmInterval) {
        clearInterval(bgmInterval);
        bgmInterval = null;
    }
}

function shuffle(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

function changeScreen(screenId) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    document.getElementById(screenId).classList.add('active');
}

function startTheGame() {
    initAudio(); 
    sndClick();
    startMinimalBGM(); 
    
    // 🚨 สั่งเพิ่มคลาส .hidden สไลด์ซ่อน Tabbar ลงใต้จอทันทีก่อนเริ่ม เพื่อไม่ให้ไปทับ 10s ล่างขวา!
    document.getElementById('quiz-tabbar').classList.add('hidden');
    
    activeQuestions = shuffle([...questionBank]);
    currentIndex = 0;
    score = 0;
    changeScreen('play-screen');
    loadQuestion();
}

function loadQuestion() {
    isSelected = false;
    document.getElementById('next-btn').style.display = 'none';
    document.getElementById('status-msg').innerText = '';
    
    let q = activeQuestions[currentIndex];
    document.getElementById('current-q-num').innerText = currentIndex + 1;
    document.getElementById('score-view').innerText = score;
    document.getElementById('q-image').src = q.img;

    let area = document.getElementById('options-area');
    area.innerHTML = '';

    let choices = shuffle([...q.choices]);
    choices.forEach(c => {
        let btn = document.createElement('button');
        btn.className = 'choice-btn';
        btn.innerText = c;
        btn.onclick = () => selectAnswer(btn, c, q.answer);
        area.appendChild(btn);
    });

    timeLeft = 10;
    document.getElementById('t-bar').style.width = '100%';
    if(timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(countDownEngine, 1000);
}

function countDownEngine() {
    timeLeft--;
    document.getElementById('t-bar').style.width = (timeLeft * 10) + '%';

    if (timeLeft <= 5 && timeLeft > 0) {
        sndTick(); 
        let giantView = document.getElementById('giant-countdown-view');
        giantView.innerText = timeLeft;
        giantView.classList.remove('pulse');
        void giantView.offsetWidth; 
        giantView.classList.add('pulse');
    }

    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        timeOutLoss();
    }
}

function timeOutLoss() {
    isSelected = true;
    sndWrong();
    document.getElementById('status-msg').innerHTML = "<span style='color: var(--ios-red);'>หมดเวลาวิเคราะห์!</span>";
    revealCorrectChoice();
}

function selectAnswer(btn, chosen, correct) {
    if(isSelected) return;
    isSelected = true;
    clearInterval(timerInterval);

    if (chosen === correct || correct.startsWith(chosen)) {
        btn.classList.add('correct');
        document.getElementById('status-msg').innerHTML = "<span style='color: var(--ios-green);'>✓ ถูกต้อง</span>";
        score += 10;
        document.getElementById('score-view').innerText = score;
        sndCorrect();
    } else {
        btn.classList.add('wrong');
        document.getElementById('status-msg').innerHTML = "<span style='color: var(--ios-red);'>✕ คลาดเคลื่อน</span>";
        sndWrong();
        revealCorrectChoice();
    }
    document.getElementById('next-btn').style.display = 'block';
}

function revealCorrectChoice() {
    let q = activeQuestions[currentIndex];
    let btns = document.querySelectorAll('.choice-btn');
    btns.forEach(b => {
        if (q.answer.startsWith(b.innerText)) {
            b.classList.add('correct');
        }
    });
    document.getElementById('next-btn').style.display = 'block';
}

function goNext() {
    sndClick();
    currentIndex++;
    if(currentIndex < activeQuestions.length) {
        loadQuestion();
    } else {
        stopMinimalBGM(); 
        clearInterval(timerInterval);
        document.getElementById('final-score').innerText = score;
        
        // 🚨 สั่งเรียกคืน Tabbar กลับขึ้นมาแสดงผลตามเดิมในหน้าสรุปผลสำเร็จ
        document.getElementById('quiz-tabbar').classList.remove('hidden');
        
        changeScreen('result-screen');
    }
}
</script>

</body>
</html>