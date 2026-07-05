<?php
$projectDir = '/gmaemini';
 

// ตรวจจับอย่างง่ายว่าเปิดผ่านมือถือหรือไม่
$isMobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (strpos($user_agent, 'mobile') !== false || strpos($user_agent, 'android') !== false || strpos($user_agent, 'iphone') !== false) {
        $isMobile = true;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>🎵 PREMIUM MUSIC STREAM CHALLENGE</title>
    
    <link href="game_music.css?v=<?=time();?>" rel="stylesheet" type="text/css">
    <script src="https://www.youtube.com/iframe_api"></script>
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="<?php echo $isMobile ? 'mobile-mode' : 'desktop-mode'; ?>">

<div class="app-container">
    
    <!-- ⏱️ COUNTDOWN SCREEN -->
    <div class="countdown-overlay" id="countdown-screen">
        <div class="countdown-card">
            <div class="countdown-sub" id="countdown-headline">จับเวลาถอยหลัง...</div>
            <div class="countdown-number" id="countdown-text">5</div>
            
            <button class="btn-interrupt-clock" id="btn-freeze-time" onclick="freezeCountdownForAnswer()">
                <ion-icon name="hand-left-outline" style="vertical-align: middle; margin-right: 6px;"></ion-icon>
                หยุดเวลาตอบ
            </button>

            <div class="live-answer-box" id="checker-box-panel" style="display: none;">
                <div class="hearts-display" id="hearts-zone">โควตาตอบผิด: ❤️ ❤️</div>
                
                <div class="guess-input-container">
                    <input 
                        type="text" 
                        id="player-guess-input" 
                        name="secret_music_guess_field"
                        placeholder="✍️ พิมพ์ชื่อเพลงตรงนี้..." 
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                    >
                </div>
                
                <div class="modal-button-group">
                    <button class="btn-check-answer" onclick="verifyPlayerAnswer()">
                        ✔️ ตรวจคำตอบ
                    </button>
                    <button class="btn-resume-clock" onclick="resumeCountdownClock()">
                        ⏱️ รันต่อ
                    </button>
                </div>
                <!-- 💡 HINT SYSTEM BUTTON -->
                <button class="btn-manual-wheel" style="margin-top: 12px; border-color: var(--accent-blue); color: var(--accent-blue);" onclick="giveHint()">
                    <ion-icon name="bulb-outline" style="vertical-align: middle; margin-right: 4px;"></ion-icon>
                    ขอคำใบ้เพลง
                </button>
            </div>
            
            <button class="btn-stop-success" onclick="stopCountdownWithSuccess()">
                <ion-icon name="eye-outline" style="vertical-align: middle; margin-right: 6px;"></ion-icon>
                เปิดเฉลย
            </button>
        </div>
    </div>

    <!-- 🎡 NEW FEATURE: CYBER REACTOR SHUFFLE (น่าเสียว + ลูกเล่นจัดเต็ม) -->
    <div class="wheel-overlay" id="wheel-screen">
        <div class="wheel-modal-box">
            <div class="wheel-header">💀 CLUB CHALLENGE!</div>
            <p style="color: var(--text-secondary); font-size: 0.72rem; margin-bottom: 12px; letter-spacing: 0.5px; text-transform: uppercase;">แกนปืนเลเซอร์จะหมุนกวาดความร้อนคัดสรรบทลงโทษ</p>
            
            <!-- Circular Reactor Viewport -->
            <div class="reactor-viewport" id="reactor-viewport">
                <!-- Laser Sweep Line -->
                <div class="reactor-laser" id="reactor-laser"></div>
                
                <!-- Center warning core -->
                <div class="reactor-core" id="reactor-core">
                    <ion-icon name="nuclear-outline" id="core-icon" style="font-size: 28px; color: var(--text-secondary);"></ion-icon>
                </div>
                
                <!-- Circular Nodes container -->
                <div id="reactor-nodes-container"></div>
            </div>

            <div class="wheel-control-btns">
                <button class="btn-wheel-spin" id="btn-spin-main" onclick="spinWheelByBtn()">
                    <ion-icon name="flash-outline" style="vertical-align: middle; margin-right: 6px;"></ion-icon>
                    เริ่มยิงลำแสงสุ่ม
                </button>
                <button class="btn-wheel-close" onclick="closeWheelScreen()">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>

    <!-- 💀 PUNISHMENT RESULT MODAL -->
    <div class="popup-overlay" id="punish-result-overlay" onclick="closePunishResult()">
        <div class="punish-result-modal" onclick="event.stopPropagation()">
            <div class="punish-icon">🚨</div>
            <div class="punish-title">บทลงโทษชมรมขำๆ ที่คุณได้รับ</div>
            <div class="punish-text-content" id="punish-result-text">🚨 บทลงโทษ</div>
            
            <div class="punish-action-row">
                <button class="btn-spin-again" onclick="spinAgainFromModal()">🔄 สุ่มใหม่</button>
                <button class="btn-close-punish" onclick="closePunishResult()">น้อมรับชะตากรรม 👌</button>
            </div>
        </div>
    </div>

    <!-- MAIN HEADER -->
    <div class="app-layout-header">
        <div class="badge-status-live">● AUDIO LIVE SYSTEM</div>
        <h1>MUSIC CHECKER</h1>
    </div>

    <!-- 🔊 AUDIO VISUALIZER WAVES -->
    <div class="visualizer-wrapper" id="audio-visualizer">
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
    </div>

    <!-- MAIN DASHBOARD PANEL -->
    <div class="dashboard-panel">
        
        <div class="status-indicator-bar" id="game-status">
            สุ่มศิลปินหรือวางลิงก์ YouTube ด้านล่างเพื่อเริ่มเกมสตรีมเพลงฮุกเป้าหมาย
        </div>

        <div class="search-grid-box">
            <div class="search-input-row">
                <input type="search" id="search-input" placeholder="พิมพ์ชื่อศิลปิน..." autocomplete="off">
                <button type="button" class="btn-random-keyword" onclick="generateRandomArtistKeyword()">🎲 สุ่มศิลปิน</button>
            </div>
            <button class="btn-action-core btn-spotify-green" onclick="loadRandomTrackFromDatabase()">🔮 สุ่มเพลงลับจากชื่อศิลปิน</button>
        </div>

        <div class="custom-url-panel">
            <label for="custom-audio-url">📺 วางลิงก์ YouTube:</label>
            <div class="url-search-row">
                <input type="search" id="custom-audio-url" placeholder="วางลิงก์ https://youtu.be/... ที่นี่" autocomplete="off">
                <button type="button" class="btn-search-url" onclick="fetchTrackDataByYouTubeUrl()">🔍 ค้นหา</button>
            </div>
        </div>

        <div class="audio-control-floor">
            <div class="duration-select-box">
                <span>⏱️ เวลาเสียงฮุก: </span>
                <select id="time-limit">
                    <option value="5">5 วินาที</option>
                    <option value="7" selected>7 วินาที</option>
                    <option value="10">10 วินาที</option>
                    <option value="15">15 วินาที</option>
                </select>
            </div>

            <div class="primary-buttons-rack">
                <button class="btn-giant btn-solid-white" id="btn-play-trigger" onclick="playTargetHook()" disabled>🔊 ปล่อยเสียงฮุก</button>
                <button class="btn-giant btn-outline-grey" id="btn-reveal" onclick="revealAnswer()" disabled>🔓 เปิดเฉลย</button>
            </div>
            
            <button class="btn-manual-wheel" onclick="openWheelScreen()">🎡 เรียกวงล้อลงโทษ</button>
        </div>

    </div>

    <div id="yt-hidden-player"></div>

    <!-- 🎉 ANSWER REVEAL OVERLAY -->
    <div class="popup-overlay" id="overlay-blur" onclick="closeReveal()">
        <div class="mega-reveal-modal" onclick="event.stopPropagation()">
            <div class="modal-badge-info">CORRECT ANSWER 🎉</div>
            <div class="modal-body-content">
                <img id="track-img" src="" alt="Cover Image">
                <div class="modal-metadata">
                    <h2 id="track-name">ชื่อเพลง: ???</h2>
                    <h3 id="track-artist">YouTube Video</h3>
                </div>
            </div>
            
            <div style="text-align: center; margin-bottom: 20px;">
                <a id="track-link" href="#" target="_blank" class="btn-listen-fulltrack">
                    <ion-icon name="logo-youtube" style="font-size: 16px; vertical-align: middle; margin-right: 4px;"></ion-icon>
                    เปิดดูคลิปเต็มบน YT
                </a>
            </div>

            <button class="btn-close-modal" onclick="closeReveal()">ปิดหน้าต่างเฉลย</button>
        </div>
    </div>

    <div class="app-layout-header" style="margin-top: 40px; margin-bottom: 0;">
        <a href="../../index.php" class="back-link-home">
            <ion-icon name="arrow-back-outline" style="vertical-align: middle; font-size: 16px;"></ion-icon>
            กลับสู่หน้าหลัก
        </a>
    </div>
</div>

<script>
const randomArtistsPool = [
    "Bodyslam", "Three Man Down", "Tilly Birds", "Paper Planes", "Potato", 
    "Cocktail", "Loso", "Scrubb", "Ink Waruntorn", "Bowkylion", "Violette Wautier",
    "Nont Tanont", "Jeff Satur", "Billkin", "PP Krit", "The Toys",
    "POLYCAT", "Safeplanet", "Tattoo Colour", "Slot Machine", "Paradox", "Palmy"
];

// 🎭 บทลงโทษแบบใหม่ (กิจกรรมในชมรมแบบขำๆ)
const punishments = [
    "🧹 กวาดฝุ่นใต้โต๊ะคอมในห้อง 1 รอบ",
    "🧼 เช็ดคีย์บอร์ดและเมาส์ให้โต๊ะเพื่อนข้างๆ 2 ตัว",
    "🥤 บริการเสิร์ฟน้ำดื่มให้เพื่อนทุกคนในห้องชมรม",
    "🧟 เดินท่าซอมบี้ไปอ้อนขอให้หัวหน้าชมรมเซ็นรับรองกิจกรรม",
    "🤖 พูดคำว่า 'ชมรมนี้เปิดโดยกระทรวงความมั่นคง 🛸' 5 รอบด้วยเสียงหุ่นยนต์",
    "🕴️ ยืนเคารพธงชาตินิ่งๆ ตรงหน้าพัดลมเบอร์ 3 เป็นเวลา 15 วินาที",
    "🎤 แนะนำตัวพร้อมร้องแร็ปโย่สด 15 วินาที",
    "🧘 นั่งสมาธิแผ่เมตตาอธิษฐานจิตไม่ให้คอมพิวเตอร์พัง 15 วินาที"
];

// ⚙️ ตัวแปรสถานะและเครื่องเล่น
let currentTrack = null; 
let playTimeout = null;  
let ytPlayer = null; 
let itunesAudio = null; 

let countdownValue = 5;
let countdownInterval = null;
let isClockFrozen = false;

let wrongAttempts = 0;
const maxWrongAttempts = 2;

let audioCtx = null;

function getAudioCtx() {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    return audioCtx;
}

function onYouTubeIframeAPIReady() {
    ytPlayer = new YT.Player('yt-hidden-player', {
        height: '1',
        width: '1',
        videoId: '',
        playerVars: {
            'autoplay': 0,
            'controls': 0,
            'disablekb': 1,
            'fs': 0,
            'rel': 0,
            'modestbranding': 1
        }
    });
}

function playTone(freq, type, duration, vol = 0.3) {
    try {
        const ctx = getAudioCtx();
        if (ctx.state === 'suspended') ctx.resume();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(freq, ctx.currentTime);
        gain.gain.setValueAtTime(vol, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.00001, ctx.currentTime + duration);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + duration);
    } catch(e){}
}

const gameStatus = document.getElementById('game-status');
const searchInput = document.getElementById('search-input');
const playerGuessInput = document.getElementById('player-guess-input');
const customAudioUrl = document.getElementById('custom-audio-url');
const heartsZone = document.getElementById('hearts-zone');
const btnPlayTrigger = document.getElementById('btn-play-trigger');
const btnReveal = document.getElementById('btn-reveal');
const overlayBlur = document.getElementById('overlay-blur');
const countdownScreen = document.getElementById('countdown-screen');
const countdownText = document.getElementById('countdown-text');
const countdownHeadline = document.getElementById('countdown-headline');
const btnFreezeTime = document.getElementById('btn-freeze-time');
const checkerBoxPanel = document.getElementById('checker-box-panel');
const wheelScreen = document.getElementById('wheel-screen');
const punishResultOverlay = document.getElementById('punish-result-overlay');
const punishResultText = document.getElementById('punish-result-text');
const visualizer = document.getElementById('audio-visualizer');

function generateRandomArtistKeyword() {
    const randomIndex = Math.floor(Math.random() * randomArtistsPool.length);
    searchInput.value = randomArtistsPool[randomIndex];
    gameStatus.style.color = "var(--accent-blue)";
    gameStatus.innerText = "สุ่มศิลปินสำเร็จ! สามารถกดสุ่มเลือกเพลงเป้าหมายได้เลย";
}

function extractYouTubeId(url) {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
}

// 💡 NEW FEATURE: HINT SYSTEM
function giveHint() {
    if (!currentTrack) return;
    const name = currentTrack.trackName.trim();
    const firstChar = name.charAt(0);
    const len = name.length;
    
    const hintMsg = `คำใบ้: เพลงนี้ขึ้นต้นด้วยตัว "${firstChar}" ความยาวประมาณ ${len} ตัวอักษร`;
    
    gameStatus.style.color = "var(--accent-blue)";
    gameStatus.innerText = `💡 ${hintMsg}`;
    
    playTone(600, 'sine', 0.15, 0.2);
}

async function loadRandomTrackFromDatabase() {
    const artistKeyword = searchInput.value.trim();
    if (!artistKeyword) {
        alert('กรุณากรอกชื่อศิลปินหรือสุ่มศิลปินก่อนครับ');
        return;
    }
    customAudioUrl.value = "";
    gameStatus.style.color = "var(--text-secondary)";
    gameStatus.innerText = "⏳ กำลังสุ่มเลือกเพลงลับจากระบบ...";
    btnPlayTrigger.disabled = true;
    btnReveal.disabled = true;
    overlayBlur.classList.remove('active');
    clearInterval(countdownInterval);
    if (playTimeout) clearTimeout(playTimeout);
    
    if (ytPlayer && ytPlayer.pauseVideo) ytPlayer.pauseVideo();
    if (itunesAudio) { itunesAudio.pause(); itunesAudio = null; }
    const oldAudio = document.getElementById('itunes-temp-audio');
    if (oldAudio) oldAudio.remove();
    visualizer.classList.remove('playing');

    wrongAttempts = 0;
    updateHeartsUI();

    try {
        const response = await fetch(`https://itunes.apple.com/search?term=${encodeURIComponent(artistKeyword)}&country=TH&media=music&entity=musicTrack&limit=25`);
        const data = await response.json();
        if (data.results && data.results.length > 0) {
            const randomIndex = Math.floor(Math.random() * data.results.length);
            const track = data.results[randomIndex];
            
            currentTrack = {
                type: "itunes",
                previewUrl: track.previewUrl,
                trackName: track.trackName,
                artistName: track.artistName,
                artworkUrl100: track.artworkUrl100,
                trackViewUrl: track.trackViewUrl
            };

            gameStatus.style.color = "var(--accent-blue)";
            gameStatus.innerText = "🎲 ระบบเตรียมเพลงลับพร้อมแล้ว! กดปล่อยคิวเสียงฮุกได้เลย";
            btnPlayTrigger.disabled = false;
            btnReveal.disabled = false;
        } else {
            gameStatus.style.color = "var(--accent-red)";
            gameStatus.innerText = "❌ ไม่พบเพลงของศิลปินนี้ ลองเปลี่ยนคีย์เวิร์ดดูครับ";
        }
    } catch (error) {
        gameStatus.style.color = "var(--accent-red)";
        gameStatus.innerText = "❌ การเชื่อมต่ออินเทอร์เน็ตผิดพลาด";
    }
}

async function fetchTrackDataByYouTubeUrl() {
    const targetUrl = customAudioUrl.value.trim();
    if (!targetUrl) {
        alert('กรุณาวางลิงก์ YouTube ก่อนกดค้นหาครับ');
        return;
    }

    const videoId = extractYouTubeId(targetUrl);
    if (!videoId) {
        alert('รูปแบบลิงก์ YouTube ไม่ถูกต้อง กรุณาตรวจสอบอีกครั้งครับ');
        return;
    }

    gameStatus.style.color = "var(--text-secondary)";
    gameStatus.innerText = "⏳ กำลังดึงข้อมูลและดึงคิวเสียงจาก YouTube API...";
    btnPlayTrigger.disabled = true;
    btnReveal.disabled = true;
    clearInterval(countdownInterval);
    if (playTimeout) clearTimeout(playTimeout);
    
    if (ytPlayer && ytPlayer.pauseVideo) ytPlayer.pauseVideo();
    if (itunesAudio) { itunesAudio.pause(); itunesAudio = null; }
    const oldAudio = document.getElementById('itunes-temp-audio');
    if (oldAudio) oldAudio.remove();
    visualizer.classList.remove('playing');

    wrongAttempts = 0;
    updateHeartsUI();

    try {
        const response = await fetch(`https://noembed.com/embed?url=https://www.youtube.com/watch?v=${videoId}`);
        const data = await response.json();

        if (data && data.title) {
            currentTrack = {
                type: "youtube",
                ytId: videoId,
                trackName: data.title,
                artistName: data.author_name || "YouTube Video",
                artworkUrl100: `https://img.youtube.com/vi/${videoId}/maxresdefault.jpg`,
                trackViewUrl: `https://www.youtube.com/watch?v=${videoId}`
            };

            if (ytPlayer && ytPlayer.cueVideoById) {
                ytPlayer.cueVideoById({videoId: videoId});
            }

            gameStatus.style.color = "var(--accent-blue)";
            gameStatus.innerText = `📺 ล็อกลิงก์ YouTube สำเร็จ! (กดปุ่มเริ่มปล่อยคิวเสียงได้เลย)`;
            btnPlayTrigger.disabled = false;
            btnReveal.disabled = false;
        } else {
            throw new Error("No data");
        }
    } catch (error) {
        gameStatus.style.color = "var(--accent-red)";
        gameStatus.innerText = "❌ ไม่สามารถดึงข้อมูลได้ โปรดเปลี่ยนลิงก์หรือเช็คอินเทอร์เน็ตอีกครั้ง";
    }
}

function playTargetHook() {
    if (!currentTrack) return;
    clearInterval(countdownInterval);
    if (playTimeout) clearTimeout(playTimeout);
    overlayBlur.classList.remove('active');

    if (ytPlayer && ytPlayer.pauseVideo) ytPlayer.pauseVideo();
    if (itunesAudio) { itunesAudio.pause(); itunesAudio = null; }
    const oldAudio = document.getElementById('itunes-temp-audio');
    if (oldAudio) oldAudio.remove();

    wrongAttempts = 0;
    updateHeartsUI();
    playerGuessInput.value = "";

    isClockFrozen = false;
    checkerBoxPanel.style.display = "none";
    btnFreezeTime.style.display = "inline-block";
    countdownHeadline.innerText = "จับเวลาถอยหลัง...";

    const existingNextBtn = document.getElementById('btn-next-to-wheel');
    if (existingNextBtn) existingNextBtn.remove();

    gameStatus.style.color = "var(--accent-blue)";
    gameStatus.innerText = `🔊 กำลังปล่อยคิวเสียงฮุกปริศนาจากคลังข้อมูล...`;

    // Start Visualizer
    visualizer.classList.add('playing');

    if (currentTrack.type === "youtube") {
        if (ytPlayer && ytPlayer.playVideo) {
            ytPlayer.seekTo(30, true); 
            ytPlayer.setVolume(60);
            ytPlayer.playVideo();
        }
    } else if (currentTrack.type === "itunes" && currentTrack.previewUrl) {
        itunesAudio = new Audio(currentTrack.previewUrl);
        itunesAudio.volume = 0.6;
        itunesAudio.id = "itunes-temp-audio";
        document.body.appendChild(itunesAudio);
        itunesAudio.play();
    }

    const seconds = parseInt(document.getElementById('time-limit').value) || 7;

    playTimeout = setTimeout(() => {
        if (currentTrack.type === "youtube") {
            if (ytPlayer && ytPlayer.pauseVideo) ytPlayer.pauseVideo();
        } else {
            if (itunesAudio) { itunesAudio.pause(); itunesAudio = null; }
            const dummyAudio = document.getElementById('itunes-temp-audio');
            if (dummyAudio) { dummyAudio.remove(); }
        }

        // Stop Visualizer
        visualizer.classList.remove('playing');

        gameStatus.style.color = "var(--accent-orange)";
        gameStatus.innerText = "⏸️ สิ้นสุดเวลาเพลงฮุก! กำลังเข้าสู่ช่วงตอบคำถาม...";
        
        setTimeout(() => {
            initiateThrillerClock();
        }, 1500);
        
    }, seconds * 1000);
}

function initiateThrillerClock() {
    const ctx = getAudioCtx();
    if (ctx.state === 'suspended') ctx.resume();
    clearInterval(countdownInterval);
    countdownValue = 5; 
    isClockFrozen = false;
    countdownText.innerText = countdownValue;
    countdownText.style.color = "var(--accent-blue)";
    countdownScreen.classList.add('active');
    
    const existingNextBtn = document.getElementById('btn-next-to-wheel');
    if (existingNextBtn) existingNextBtn.remove();
    btnFreezeTime.style.display = "inline-block";
    
    playTone(140, 'sine', 0.5, 0.6);
    
    countdownInterval = setInterval(() => {
        if (isClockFrozen) return; 
        countdownValue--;
        
        if (countdownValue > 0) {
            countdownText.innerText = countdownValue;
            playTone(140, 'sine', 0.5, 0.6);
        } else {
            clearInterval(countdownInterval);
            countdownText.innerText = "หมดเวลา! 💥";
            countdownText.style.color = "var(--accent-red)";
            playTone(360, 'sawtooth', 0.8, 0.3);
            
            btnFreezeTime.style.display = "none";
            checkerBoxPanel.style.display = "none";
            
            setTimeout(() => {
                countdownScreen.classList.remove('active');
                openWheelScreen();
                
                setTimeout(() => {
                    spinWheelByBtn();
                }, 800); 
            }, 1500);
        }
    }, 2000); 
}

function freezeCountdownForAnswer() {
    if (countdownValue <= 0) return; 
    isClockFrozen = true;
    playTone(440, 'sine', 0.15, 0.3);
    countdownHeadline.innerText = "🛑 หยุดเวลาตรวจสอบคำตอบ";
    btnFreezeTime.style.display = "none";
    checkerBoxPanel.style.display = "block"; 
    
    setTimeout(() => { playerGuessInput.focus(); }, 100);
}

function resumeCountdownClock() {
    isClockFrozen = false;
    countdownHeadline.innerText = "จับเวลาถอยหลัง...";
    checkerBoxPanel.style.display = "none";
    btnFreezeTime.style.display = "inline-block";
    playerGuessInput.value = "";
}

function updateHeartsUI() {
    let hearts = "";
    const remaining = maxWrongAttempts - wrongAttempts;
    for (let i = 0; i < remaining; i++) { hearts += "❤️ "; }
    for (let i = 0; i < wrongAttempts; i++) { hearts += "🖤 "; }
    heartsZone.innerText = `โควตาตอบผิด: ${hearts}`;
}

function verifyPlayerAnswer() {
    if (!currentTrack) return;
    const rawInput = playerGuessInput.value.trim().toLowerCase();
    if (!rawInput) {
        alert('กรุณากรอกคำตอบก่อนตรวจสอบครับ');
        return;
    }

    const correctTrackName = currentTrack.trackName.toLowerCase();
    
    if (correctTrackName.includes(rawInput) || rawInput.includes(correctTrackName)) {
        playTone(587.33, 'sine', 0.15, 0.4); 
        setTimeout(() => playTone(880, 'sine', 0.4, 0.4), 120);
        stopCountdownWithSuccess();
    } else {
        wrongAttempts++;
        updateHeartsUI();
        playTone(110, 'sawtooth', 0.4, 0.5);
        alert(`❌ คำตอบยังไม่ถูกต้อง ลองใหม่อีกครั้ง`);
        playerGuessInput.value = ""; 
        playerGuessInput.focus();

        if (wrongAttempts >= maxWrongAttempts) {
            clearInterval(countdownInterval);
            alert('💀 ตอบผิดครบกำหนด! เข้าสู่วงล้อบทลงโทษทันที');
            countdownScreen.classList.remove('active');
            openWheelScreen();
        }
    }
}

function stopCountdownWithSuccess() {
    clearInterval(countdownInterval); 
    countdownScreen.classList.remove('active');
    gameStatus.style.color = "var(--accent-blue)";
    gameStatus.innerText = "🎉 ทายถูกต้องเรียบร้อย ยินดีด้วย!";
    revealAnswer(); 
}

function revealAnswer() {
    if (!currentTrack) return;
    
    if (overlayBlur.classList.contains('active')) {
        closeReveal();
        return;
    }

    let displayImg = currentTrack.artworkUrl100;
    if (currentTrack.type === "youtube") {
        document.getElementById('track-img').src = displayImg;
        document.getElementById('track-img').onerror = function() {
            this.src = displayImg.replace("maxresdefault.jpg", "hqdefault.jpg");
            this.onerror = null;
        };
    } else {
        const highResImg = currentTrack.artworkUrl100.includes("100x100bb.jpg") ? currentTrack.artworkUrl100.replace("100x100bb.jpg", "600x600bb.jpg") : currentTrack.artworkUrl100;
        document.getElementById('track-img').src = highResImg;
    }

    document.getElementById('track-name').innerText = currentTrack.trackName;
    document.getElementById('track-artist').innerText = `ข้อมูลศิลปิน/ช่อง: ${currentTrack.artistName}`;
    document.getElementById('track-link').href = currentTrack.trackViewUrl;

    overlayBlur.classList.add('active');
    
    if (currentTrack.type === "youtube") {
        if (ytPlayer && ytPlayer.playVideo) {
            ytPlayer.seekTo(0, true);
            ytPlayer.setVolume(25);
            ytPlayer.playVideo();
        }
    }
}

function closeReveal() {
    overlayBlur.classList.remove('active');
    if (ytPlayer && ytPlayer.pauseVideo) ytPlayer.pauseVideo();
    if (itunesAudio) { itunesAudio.pause(); itunesAudio = null; }
    const oldAudio = document.getElementById('itunes-temp-audio');
    if (oldAudio) oldAudio.remove();
    visualizer.classList.remove('playing');
}

// ==========================================================================
// 🎡 CYBER REACTOR MATRIX CHOICE (สุ่มแบบแกนเลเซอร์ปืนหมุนกวาด - น่าเสียวจัดเต็ม)
// ==========================================================================
let isSpinning = false;
let isWheelActive = false;

function playWarningBeep(step) {
    try {
        const ctx = getAudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(220 + (step * 8), ctx.currentTime);
        gain.gain.setValueAtTime(0.06, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.08);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(); osc.stop(ctx.currentTime + 0.08);
    } catch(e){}
}

function playSiren() {
    try {
        const ctx = getAudioCtx();
        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(500, now);
        osc.frequency.linearRampToValueAtTime(900, now + 0.3);
        osc.frequency.linearRampToValueAtTime(500, now + 0.6);
        gain.gain.setValueAtTime(0.15, now);
        gain.gain.linearRampToValueAtTime(0.15, now + 0.4);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.7);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(); osc.stop(now + 0.7);
    } catch(e){}
}

function shakeScreen() {
    document.body.style.animation = 'shakeX 0.45s ease';
    setTimeout(() => document.body.style.animation = '', 450);
}

function renderPunishGrid() {
    const container = document.getElementById('reactor-nodes-container');
    container.innerHTML = '';
    
    const viewport = document.getElementById('reactor-viewport');
    const width = viewport.offsetWidth || 320;
    
    // Calculate radius dynamically based on screen sizing (responsive)
    const radius = width <= 280 ? 105 : 125; 
    
    const emojis = ["🧹", "🧼", "🥤", "🧟", "🤖", "🕴️", "🎤", "🧘"];
    
    punishments.forEach((p, i) => {
        const angle = (i * 360 / punishments.length) * Math.PI / 180;
        // Basic polar conversion
        const x = radius * Math.cos(angle);
        const y = radius * Math.sin(angle);
        
        let displayTxt = p;
        if (displayTxt.length > 20) { displayTxt = displayTxt.substring(0, 18) + "..."; }
        
        container.innerHTML += `
            <div class="reactor-node" id="punish-node-${i}" style="left: 50%; top: 50%; transform: translate(-50%, -50%) translate(${x}px, ${y}px);">
                <span class="node-emoji">${emojis[i]}</span>
                <span class="node-text">${displayTxt}</span>
            </div>
        `;
    });
}

function spinWheelByBtn() {
    if (isSpinning) return;
    isSpinning = true;
    
    document.getElementById('btn-spin-main').disabled = true;
    
    const core = document.getElementById('reactor-core');
    const laser = document.getElementById('reactor-laser');
    
    core.classList.add('warning-active');
    laser.classList.add('laser-active');
    
    const winnerIndex = Math.floor(Math.random() * punishments.length);
    
    let currentIndex = 0;
    let duration = 40; // Super fast starting delay
    let stepsCount = 0;
    const minSteps = 30 + Math.floor(Math.random() * 8); // tension steps
    
    function highlightStep() {
        // Clear highlights
        document.querySelectorAll('.reactor-node').forEach(node => {
            node.classList.remove('active-node', 'winner-node');
        });
        
        // Highlight active node
        const activeNode = document.getElementById(`punish-node-${currentIndex}`);
        if (activeNode) activeNode.classList.add('active-node');
        
        // Rotate laser beam to point at the current active node
        const angleDegrees = currentIndex * 45;
        laser.style.transform = `translate(0, -50%) rotate(${angleDegrees}deg)`;
        
        // play tense warning sound
        playWarningBeep(stepsCount);
        
        stepsCount++;
        
        if (stepsCount < minSteps) {
            currentIndex = (currentIndex + 1) % punishments.length;
            setTimeout(highlightStep, duration);
        } else if (duration < 500) {
            duration += 60; // slow down significantly
            currentIndex = (currentIndex + 1) % punishments.length;
            setTimeout(highlightStep, duration);
        } else {
            // Target final deceleration lock
            if (currentIndex !== winnerIndex) {
                currentIndex = (currentIndex + 1) % punishments.length;
                setTimeout(highlightStep, duration + 40);
            } else {
                // Meltdown STOP! Explosion reveal
                laser.classList.remove('laser-active');
                core.classList.remove('warning-active');
                
                activeNode.classList.remove('active-node');
                activeNode.classList.add('winner-node');
                
                playSiren();
                shakeScreen();
                
                setTimeout(() => {
                    announcePunishmentResult(winnerIndex);
                    isSpinning = false;
                    document.getElementById('btn-spin-main').disabled = false;
                }, 1300);
            }
        }
    }
    
    highlightStep();
}

function announcePunishmentResult(winningIndex) {
    punishResultText.innerText = `${punishments[winningIndex]}`;
    punishResultOverlay.classList.add('active');
}

function spinAgainFromModal() {
    punishResultOverlay.classList.remove('active');
    spinWheelByBtn(); 
}

function closePunishResult() {
    punishResultOverlay.classList.remove('active');
}

function openWheelScreen() {
    const ctx = getAudioCtx();
    if (ctx.state === 'suspended') ctx.resume();
    wheelScreen.classList.add('active');
    isWheelActive = true;
    setTimeout(() => {
        renderPunishGrid();
    }, 100);
}

function closeWheelScreen() { 
    wheelScreen.classList.remove('active'); 
    isWheelActive = false; 
}
</script>
</body>
</html>