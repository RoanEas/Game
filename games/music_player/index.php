<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Cyber User';
$avatarSeed = $_SESSION['avatar_seed'] ?? $_SESSION['user_id'] ?? '1';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Cyber — Music Player</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;700&family=Prompt:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js" type="module"></script>
    <script src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js" nomodule></script>
    <link rel="stylesheet" href="style.css?v=<?=time();?>">
</head>
<body>

<div class="spotify-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand-logo">
            <ion-icon name="radio-waves-outline"></ion-icon>
            <span>Spotify Cyber</span>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item active" id="menu-home" onclick="switchTab('home')">
                <div><ion-icon name="home-outline"></ion-icon> หน้าแรก</div>
            </li>
            <li class="nav-item" id="menu-search" onclick="switchTab('search')">
                <div><ion-icon name="search-outline"></ion-icon> ค้นหาเพลง</div>
            </li>
            <li class="nav-item" id="menu-liked" onclick="switchTab('liked')">
                <div><ion-icon name="heart-outline"></ion-icon> เพลงที่ชอบ</div>
            </li>
        </ul>
        
        <div class="library-box">
            <div class="lib-header">
                <ion-icon name="library-outline"></ion-icon>
                <span>ห้องสมุดของคุณ</span>
            </div>
            
            <div class="playlist-card">
                <h4>สร้างเพลย์ลิสต์แรกของคุณ</h4>
                <p>สร้างความเพลิดเพลินในการจัดหมวดหมู่เพลง</p>
                <button class="btn-pill" onclick="alert('ฟีเจอร์จัดเก็บเพลย์ลิสต์คัสตอมจะเปิดให้บริการในอัปเดตถัดไป!')">สร้างเพลย์ลิสต์</button>
            </div>

            <div class="playlist-card" style="background-color: rgba(255, 255, 255, 0.02); border: 1px dashed rgba(255,255,255,0.1);">
                <h4 style="color: var(--spotify-green);">Lo-Fi Study Beats</h4>
                <p>เปิดคลื่นคลายเครียดสำหรับการอ่านหนังสือ</p>
                <button class="btn-pill" style="background-color: var(--spotify-green); color:#000;" onclick="playPresetLofi()">เปิดสตรีมสด</button>
            </div>
        </div>
    </aside>

    <!-- Main Content Panel -->
    <main class="main-panel" id="main-panel">
        <!-- Sticky Header -->
        <header class="panel-header">
            <div class="nav-arrows">
                <button class="arrow-btn" onclick="history.back()" title="ย้อนกลับ"><ion-icon name="chevron-back-outline"></ion-icon></button>
                <button class="arrow-btn" onclick="history.forward()" title="ไปข้างหน้า"><ion-icon name="chevron-forward-outline"></ion-icon></button>
                
                <!-- Dynamic Search bar in header -->
                <div class="search-container" id="header-search-bar" style="display: none;">
                    <div class="search-input-wrapper">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="search-query" placeholder="อยากฟังเพลงอะไร ค้นหาที่นี่เลย..." onkeydown="handleSearchKey(event)">
                    </div>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 16px;">
                <div class="user-pill">
                    <img src="https://api.dicebear.com/7.x/pixel-art/svg?seed=<?= urlencode($avatarSeed) ?>" alt="">
                    <span><?= htmlspecialchars($username) ?></span>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <div class="content-body">
            
            <!-- HOME TAB -->
            <section id="tab-home" class="tab-content">

                <!-- Greeting + shuffle -->
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
                    <h2 id="time-greeting" style="margin:0; font-size:1.6rem; font-weight:700;">สวัสดีตอนบ่าย</h2>
                    <button class="btn-pill" style="background:var(--spotify-green);color:#000;display:flex;align-items:center;gap:6px;padding:9px 20px;font-weight:700;" onclick="playLuckyShuffle()">
                        <ion-icon name="shuffle-outline" style="font-size:1.1rem;"></ion-icon> สุ่มฟังเลย!
                    </button>
                </div>

                <!-- Quick picks 2x4 grid -->
                <div class="welcome-grid" id="home-welcome-grid"></div>

                <!-- Thai Hits row -->
                <div class="section-header">
                    <h2>เพลงไทยยอดนิยม 🇹🇭</h2>
                    <a onclick="playAllCategory('thai')">เล่นทั้งหมด</a>
                </div>
                <div class="scroll-row" id="thai-shelf-grid"></div>

                <!-- Popular Artists row -->
                <div class="section-header">
                    <h2>ศิลปินยอดนิยม 🎤</h2>
                </div>
                <div class="scroll-row" id="artist-shelf-grid"></div>

                <!-- Global Hits row -->
                <div class="section-header">
                    <h2>เพลงสากลยอดฮิต 🌎</h2>
                    <a onclick="playAllCategory('global')">เล่นทั้งหมด</a>
                </div>
                <div class="scroll-row" id="global-shelf-grid"></div>

                <!-- K-Pop & Lofi row -->
                <div class="section-header">
                    <h2>K-Pop &amp; Lofi Chill 💜</h2>
                    <a onclick="playAllCategory('kpop_lofi')">เล่นทั้งหมด</a>
                </div>
                <div class="scroll-row" id="anime-shelf-grid"></div>

            </section>

            <!-- SEARCH TAB -->
            <section id="tab-search" class="tab-content" style="display: none;">
                <div class="welcome-section" style="margin-bottom: 24px;">
                    <h2>ค้นหาเพลงระดับโลก</h2>
                    <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0 0 16px 0;">ระบบจะสืบค้นสตรีมเพลงฟรีจาก YouTube และนำมาสังเคราะห์ลงคลังเครื่องเล่นไซเบอร์เพื่อเล่นแบบ Spotify ทันที</p>
                    
                    <!-- Search input form for mobile (or when header is narrow) -->
                    <div class="search-input-wrapper" style="max-width: 100%; width: 100%; display: none;" id="mobile-search-bar">
                        <ion-icon name="search-outline"></ion-icon>
                        <input type="text" id="search-query-mobile" placeholder="อยากฟังเพลงอะไร ค้นหาที่นี่เลย..." onkeydown="handleSearchKey(event)">
                    </div>
                </div>

                <div id="search-loading" style="display: none; text-align: center; padding: 40px;">
                    <div style="font-size: 1.5rem; animation: spin 1.5s infinite linear; display: inline-block;">
                        <ion-icon name="sync-outline" style="color: var(--spotify-green);"></ion-icon>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 10px;">กำลังขุดคุ้ยสตรีมเพลง...</p>
                </div>

                <div id="search-results-box" style="display: none;">
                    <h3 style="font-size: 1.2rem; margin-top: 0; margin-bottom: 16px;">ผลการค้นหาสตรีม</h3>
                    
                    <div class="songs-table-container">
                        <table class="songs-table">
                            <thead>
                                <tr>
                                    <th class="track-num">#</th>
                                    <th>ชื่อเพลง / ศิลปิน</th>
                                    <th>คลังสตรีม</th>
                                    <th class="track-duration"><ion-icon name="time-outline"></ion-icon></th>
                                </tr>
                            </thead>
                            <tbody id="search-results-tbody">
                                <!-- Results rows populate dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Genres grid if search is blank -->
                <div id="search-genres-box">
                    <h3 style="font-size: 1.2rem; margin-bottom: 16px;">หมวดหมู่แนะนำ</h3>
                    <div class="shelf-grid">
                        <div class="music-card" style="background: linear-gradient(145deg, #e8115b, #bc0f4b);" onclick="triggerDirectSearch('Lo-Fi Hip Hop Chill')">
                            <h4 style="font-size: 1.3rem; height: 100px;">Lo-Fi Chill</h4>
                            <p>ผ่อนคลายสบายสมอง</p>
                        </div>
                        <div class="music-card" style="background: linear-gradient(145deg, #1e3264, #132040);" onclick="triggerDirectSearch('Thai Pop Top Hits')">
                            <h4 style="font-size: 1.3rem; height: 100px;">เพลงไทยยอดฮิต</h4>
                            <p>เพลงฮิตติดกระแสในไทย</p>
                        </div>
                        <div class="music-card" style="background: linear-gradient(145deg, #509bf5, #3567a3);" onclick="triggerDirectSearch('Gaming Music EDM')">
                            <h4 style="font-size: 1.3rem; height: 100px;">Gaming Vibes</h4>
                            <p>เพลงบิวท์อารมณ์สตรีมเกม</p>
                        </div>
                        <div class="music-card" style="background: linear-gradient(145deg, #8d67ab, #5e4472);" onclick="triggerDirectSearch('Cyberpunk Synthwave Beats')">
                            <h4 style="font-size: 1.3rem; height: 100px;">Synthwave</h4>
                            <p>จังหวะนีออนแห่งอนาคต</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- LIKED SONGS TAB -->
            <section id="tab-liked" class="tab-content" style="display: none;">
                <div class="welcome-section" style="display: flex; align-items: center; gap: 24px; margin-bottom: 30px;">
                    <div style="width: 120px; height: 120px; border-radius: 8px; background: linear-gradient(135deg, #450e4b, #c33b65); display: flex; align-items: center; justify-content: center; font-size: 4rem; color: #fff; box-shadow: 0 8px 24px rgba(0,0,0,0.5);">
                        <ion-icon name="heart"></ion-icon>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">เพลย์ลิสต์</span>
                        <h2 style="font-size: 2.5rem; margin: 4px 0; font-family: 'Chakra Petch', sans-serif;">เพลงที่คุณชอบ</h2>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;"><span id="liked-count-badge">0</span> เพลง</p>
                    </div>
                </div>

                <div id="liked-empty-state" style="text-align: center; padding: 60px 20px;">
                    <ion-icon name="heart-dislike-outline" style="font-size: 3rem; color: var(--text-muted);"></ion-icon>
                    <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 12px;">ยังไม่มีเพลงที่ถูกใจในห้องสมุดนี้<br><small style="color: #636366;">กดเครื่องหมายหัวใจที่แผงเล่นเพลงด้านล่างเพื่อเพิ่มเพลงโปรด!</small></p>
                    <button class="btn-pill" style="margin-top: 15px;" onclick="switchTab('search')">ไปส่องเพลงใหม่</button>
                </div>

                <div id="liked-table-box" style="display: none;">
                    <div class="songs-table-container">
                        <table class="songs-table">
                            <thead>
                                <tr>
                                    <th class="track-num">#</th>
                                    <th>ชื่อเพลง / ศิลปิน</th>
                                    <th>คลังสตรีม</th>
                                    <th class="track-duration"><ion-icon name="time-outline"></ion-icon></th>
                                </tr>
                            </thead>
                            <tbody id="liked-results-tbody">
                                <!-- Liked rows populate dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>
    </main>
</div>

<!-- Bottom Player Bar -->
<footer class="player-bar">
    <!-- Left: Track details -->
    <div class="player-left">
        <img class="player-track-thumb" id="p-thumb" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'><rect width='80' height='80' fill='%23282828'/></svg>" alt="">
        <div class="player-track-info">
            <div class="player-track-name" id="p-title">เลือกเปิดเพลงโปรดของคุณ</div>
            <div class="player-track-artist" id="p-artist">พร้อมเชื่อมต่อ YouTube</div>
        </div>
        <button class="like-btn" id="p-like-btn" onclick="toggleLikeCurrentTrack()" title="ถูกใจเพลงนี้">
            <ion-icon name="heart-outline"></ion-icon>
        </button>
    </div>

    <!-- Center: Playback controls -->
    <div class="player-center">
        <div class="control-buttons">
            <button class="control-btn" id="p-shuffle" onclick="toggleShuffle()" title="สุ่มคิวเพลง"><ion-icon name="shuffle-outline"></ion-icon></button>
            <button class="control-btn" onclick="playPrev()" title="เพลงก่อนหน้า"><ion-icon name="play-skip-back-outline"></ion-icon></button>
            <button class="control-btn btn-play-pause" id="p-play" onclick="togglePlayPause()" title="เล่น/หยุด"><ion-icon name="play-circle"></ion-icon></button>
            <button class="control-btn" onclick="playNext()" title="เพลงถัดไป"><ion-icon name="play-skip-forward-outline"></ion-icon></button>
            <button class="control-btn" id="p-repeat" onclick="toggleRepeat()" title="เล่นวนซ้ำ"><ion-icon name="repeat-outline"></ion-icon></button>
        </div>

        <div class="progress-bar-container">
            <span class="progress-time" id="time-current">0:00</span>
            <div class="progress-slider-wrapper">
                <input type="range" id="p-progress" min="0" max="100" value="0" oninput="handleSeek(this.value)">
            </div>
            <span class="progress-time" id="time-total">0:00</span>
        </div>
    </div>

    <!-- Right: Volume controls -->
    <div class="player-right">
        <button class="control-btn" onclick="toggleMute()" id="p-mute-btn" title="ปิด/เปิดเสียง">
            <ion-icon name="volume-high-outline" id="p-vol-icon"></ion-icon>
        </button>
        <div class="volume-bar-wrapper">
            <input type="range" id="p-volume" min="0" max="100" value="80" oninput="handleVolume(this.value)">
        </div>
    </div>
</footer>

<!-- Hidden YouTube Iframe Player Container -->
<div id="youtube-player-container" style="position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; overflow: hidden; bottom: 0; left: 0;">
    <div id="yt-player"></div>
</div>

<script>
// HUD Indicator helper for status updates
const hudIndicator = {
    set textContent(val) {
        console.log("HUD Status:", val);
        if (val.includes("กำลังเล่น:")) {
            document.title = val.replace("🎵 กำลังเล่น:", "▶️ ");
        } else {
            document.title = "Spotify Cyber";
        }
    }
};

// Dynamic thumbnail helper — always fetches fresh from YouTube CDN at render time
function thumb(id) {
    return `https://i.ytimg.com/vi/${id}/hqdefault.jpg`;
}

// Hardcoded track list (IDs & metadata fixed, thumbnails fetched live via thumb())
const defaultTracks = [
    // Thai Hits
    { id: "m9h12P0m-7I", title: "ฝนตกไหม", author: "Three Man Down", duration: "4:23", category: "thai" },
    { id: "Hl9_4B-5-uI", title: "คิดแต่ไม่ถึง", author: "Tilly Birds", duration: "4:32", category: "thai" },
    { id: "C-Edz_RXi8E", title: "สายตาหลอกกันไม่ได้", author: "Ink Waruntorn", duration: "3:40", category: "thai" },
    { id: "S0A4E_XjB_M", title: "โต๊ะริม (Melt)", author: "Nont Tanont", duration: "4:05", category: "thai" },
    { id: "F4_iA76-aCg", title: "ลืมไปแล้วว่าลืมยังไง", author: "Jeff Satur", duration: "3:58", category: "thai" },
    { id: "ZllG6Qe_5_Q", title: "ทรงอย่างแบด", author: "Paper Planes", duration: "3:30", category: "thai" },
    { id: "57n-1w1J82I", title: "วาดไว้ (Recall)", author: "Bowkylion", duration: "4:10", category: "thai" },
    { id: "XcBPiTgDVHU", title: "ยังไงก็รัก", author: "Musketeers", duration: "4:00", category: "thai" },

    // Global Hits
    { id: "4NRXx6U8ABQ", title: "Blinding Lights", author: "The Weeknd", duration: "3:22", category: "global" },
    { id: "JGwWNGJdvx8", title: "Shape of You", author: "Ed Sheeran", duration: "4:24", category: "global" },
    { id: "kJQP7kiw5Fk", title: "Despacito", author: "Luis Fonsi ft. Daddy Yankee", duration: "4:41", category: "global" },
    { id: "ic8j13U_Nns", title: "Cruel Summer", author: "Taylor Swift", duration: "3:35", category: "global" },
    { id: "e-ORhEE9VVg", title: "Blank Space", author: "Taylor Swift", duration: "3:51", category: "global" },
    { id: "TUVcZfQe-Kw", title: "Levitating", author: "Dua Lipa", duration: "3:23", category: "global" },
    { id: "yKNxeF4KMsY", title: "Yellow", author: "Coldplay", duration: "4:29", category: "global" },
    { id: "gNi_6U5ZI7g", title: "good 4 u", author: "Olivia Rodrigo", duration: "2:58", category: "global" },
    { id: "ApXoWVFHpVU", title: "Sunflower", author: "Post Malone & Swae Lee", duration: "2:36", category: "global" },
    { id: "Gd9OhYroLN0", title: "bad guy", author: "Billie Eilish", duration: "3:14", category: "global" },
    { id: "OPf0YbXqDm0", title: "Uptown Funk", author: "Mark Ronson ft. Bruno Mars", duration: "4:30", category: "global" },
    { id: "09R8_2nJtjg", title: "Sugar", author: "Maroon 5", duration: "5:01", category: "global" },

    // K-Pop & Anime/Gaming
    { id: "IHNzOHi8sJs", title: "DDU-DU DDU-DU", author: "BLACKPINK", duration: "3:36", category: "anime_kpop" },
    { id: "gdZLi9oWNZg", title: "Dynamite", author: "BTS", duration: "3:43", category: "anime_kpop" },
    { id: "sVTy_wkv5mc", title: "OMG", author: "NewJeans", duration: "3:32", category: "anime_kpop" },
    { id: "11cta61wi0g", title: "Hype Boy", author: "NewJeans", duration: "2:59", category: "anime_kpop" },
    { id: "r6zIGXun57U", title: "Legends Never Die", author: "League of Legends", duration: "3:45", category: "anime_kpop" },

    // Lo-Fi & Relaxing
    { id: "5qap5aO4i9A", title: "Lofi Girl Study Beats", author: "Lofi Girl Beats", duration: "LIVE", category: "lofi" },
    { id: "T0r7Mv952pI", title: "1 A.M Study Session", author: "Lofi Girl", duration: "1:00:15", category: "lofi" },
    { id: "wAPCSnAMR70", title: "Late Night Chill Lofi", author: "Lofi Girl Mix", duration: "58:40", category: "lofi" }
];

// App State
let currentTab = 'home';
let likedTracks = [];
let currentQueue = [...defaultTracks];
let currentTrackIdx = -1;
let currentTrack = null;
let ytPlayer = null;
let isYtAPIReady = false;
let isPlayerReady = false;
let pendingTrackId = null;
let isShuffle = false;
let isRepeat = false;
let isMuted = false;
let lastVolume = 80;
let progressInterval = null;

// Dynamic welcome greeting based on time of day
function setWelcomeGreeting() {
    const hours = new Date().getHours();
    let greeting = "สวัสดีตอนเช้า 🌅";
    if (hours >= 12 && hours < 17) {
        greeting = "สวัสดีตอนบ่าย ☀️";
    } else if (hours >= 17 && hours < 24) {
        greeting = "สวัสดีตอนเย็น 🌆";
    } else {
        greeting = "ราตรีสวัสดิ์ยามดึก 🌌";
    }
    const greetingEl = document.getElementById('time-greeting');
    if (greetingEl) greetingEl.textContent = greeting;
}

// Switch between Home, Search, Liked tabs
function switchTab(tabId) {
    currentTab = tabId;
    
    // Manage sidebar active class
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    const activeMenuItem = document.getElementById(`menu-${tabId}`);
    if (activeMenuItem) activeMenuItem.classList.add('active');
    
    // Toggle tab panels
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.getElementById(`tab-${tabId}`).style.display = 'block';
    
    // Search bar display in header
    const searchBar = document.getElementById('header-search-bar');
    const mobileSearchBar = document.getElementById('mobile-search-bar');
    
    if (tabId === 'search') {
        searchBar.style.display = 'block';
        if (window.innerWidth <= 768) {
            searchBar.style.display = 'none';
            mobileSearchBar.style.display = 'block';
        } else {
            mobileSearchBar.style.display = 'none';
        }
        document.getElementById('search-query').focus();
    } else {
        searchBar.style.display = 'none';
        mobileSearchBar.style.display = 'none';
    }
    
    if (tabId === 'liked') {
        renderLikedSongs();
    }
}

// Load Liked Songs from localStorage
function loadLikedSongs() {
    const saved = localStorage.getItem('spotify_liked_tracks');
    if (saved) {
        try {
            likedTracks = JSON.parse(saved);
        } catch(e) {
            likedTracks = [];
        }
    } else {
        likedTracks = [];
    }
}

function saveLikedSongs() {
    localStorage.setItem('spotify_liked_tracks', JSON.stringify(likedTracks));
}

// Render Liked songs table
function renderLikedSongs() {
    const tbody      = document.getElementById('liked-results-tbody');
    const emptyState = document.getElementById('liked-empty-state');
    const tableBox   = document.getElementById('liked-table-box');
    const countBadge = document.getElementById('liked-count-badge');

    if (countBadge) countBadge.textContent = likedTracks.length;

    if (likedTracks.length === 0) {
        if (emptyState) emptyState.style.display = 'block';
        if (tableBox)   tableBox.style.display   = 'none';
        return;
    }

    if (emptyState) emptyState.style.display = 'none';
    if (tableBox)   tableBox.style.display   = 'block';
    if (!tbody) return;
    tbody.innerHTML = '';

    likedTracks.forEach((track, idx) => {
        const tr = document.createElement('tr');
        const isPlaying = currentTrack && currentTrack.id === track.id;
        if (isPlaying) tr.className = 'playing';
        tr.onclick = () => { currentQueue = [...likedTracks]; playTrackById(track.id); };
        const thumbSrc = (track.thumbnail && track.thumbnail.startsWith('http'))
            ? track.thumbnail
            : `https://i.ytimg.com/vi/${track.id}/hqdefault.jpg`;
        tr.innerHTML = `
            <td class="track-num">${idx + 1}</td>
            <td>
                <div class="track-title-col">
                    <img class="track-thumb" src="${thumbSrc}"
                        onerror="this.onerror=null;this.src='https://i.ytimg.com/vi/${track.id}/default.jpg'">
                    <div class="track-info-txt">
                        <div class="track-name">${track.title}</div>
                        <div class="track-artist">${track.author || ''}</div>
                    </div>
                </div>
            </td>
            <td><div class="track-album">YouTube Stream</div></td>
            <td class="track-duration">${track.duration || '--:--'}</td>`;
        tbody.appendChild(tr);
    });
}

// Shuffle and play all tracks in currentQueue
function playLuckyShuffle() {
    const pool = currentQueue.length > 0 ? currentQueue : [...defaultTracks];
    const shuffled = [...pool];
    for (let i = shuffled.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    currentQueue = shuffled;
    playTrackById(currentQueue[0].id);
    isShuffle = true;
    const btn = document.getElementById('p-shuffle');
    if (btn) btn.classList.add('active');
}

// Play all tracks of a given category (filters from live currentQueue)
function playAllCategory(cat) {
    const cats = cat === 'thai'   ? ['thai']
               : cat === 'global' ? ['global']
               :                    ['anime_kpop', 'lofi'];
    const list = currentQueue.filter(t => cats.includes(t.category));
    if (!list.length) { playLuckyShuffle(); return; }
    currentQueue = [...list];
    playTrackById(list[0].id);
}

// ── Render Home Shelves ──────────────────────────────────────────────────────
// Strategy: render hardcoded tracks INSTANTLY, then silently upgrade with
// real API data in the background (with 5s abort timeout).
const SHELF_CACHE_KEY = 'music_shelves_v4';

// IMG helper: uses track.thumbnail (from API) or builds from video ID
function tImg(track, round = false) {
    const src = (track.thumbnail && track.thumbnail.startsWith('http'))
        ? track.thumbnail
        : `https://i.ytimg.com/vi/${track.id}/hqdefault.jpg`;
    return `<img src="${src}"
        onerror="this.onerror=null;this.src='https://i.ytimg.com/vi/${track.id}/default.jpg'"
        alt="" loading="lazy" style="${round ? 'border-radius:50%;' : ''}">`;
}

// Shelf card renderer (shared)
function populateShelf(container, allQ, list, isArtist = false) {
    if (!container) return;
    container.innerHTML = '';
    if (!list.length) {
        container.innerHTML = '<div style="padding:12px;color:var(--text-muted);">ไม่พบรายการ</div>';
        return;
    }
    list.forEach(track => {
        const card = document.createElement('div');
        card.className = isArtist ? 'music-card artist-card' : 'music-card';
        card.onclick = () => { currentQueue = [...allQ]; playTrackById(track.id); };
        card.innerHTML = `
            <div class="music-card-img-wrapper">
                ${tImg(track, isArtist)}
                <div class="play-hover-btn"><ion-icon name="play"></ion-icon></div>
            </div>
            <h4>${isArtist ? (track.author || track.title) : track.title}</h4>
            <p>${isArtist ? 'Artist' : (track.author || '')}</p>`;
        container.appendChild(card);
    });
}

// Build shelves object from a flat track array
function buildShelves(tracks) {
    return {
        thai:       tracks.filter(t => t.category === 'thai'),
        global:     tracks.filter(t => t.category === 'global'),
        anime_kpop: tracks.filter(t => t.category === 'anime_kpop'),
        lofi:       tracks.filter(t => t.category === 'lofi')
    };
}

// Render from a shelves object immediately
function drawShelves(shelves) {
    const welcomeGrid = document.getElementById('home-welcome-grid');
    const thaiShelf   = document.getElementById('thai-shelf-grid');
    const artistShelf = document.getElementById('artist-shelf-grid');
    const globalShelf = document.getElementById('global-shelf-grid');
    const animeShelf  = document.getElementById('anime-shelf-grid');
    if (!welcomeGrid) return;

    const allTracks = [...shelves.thai, ...shelves.global, ...shelves.anime_kpop, ...shelves.lofi];
    currentQueue = [...allTracks];

    // Quick-picks
    welcomeGrid.innerHTML = '';
    [...allTracks].sort(() => 0.5 - Math.random()).slice(0, 8).forEach(track => {
        const card = document.createElement('div');
        card.className = 'welcome-card';
        card.onclick = () => { currentQueue = [...allTracks]; playTrackById(track.id); };
        card.innerHTML = `${tImg(track)}<h3>${track.title}</h3><div class="play-hover-btn"><ion-icon name="play"></ion-icon></div>`;
        welcomeGrid.appendChild(card);
    });

    populateShelf(thaiShelf,   allTracks, shelves.thai);
    populateShelf(globalShelf, allTracks, shelves.global);
    populateShelf(animeShelf,  allTracks, [...shelves.anime_kpop, ...shelves.lofi]);

    // Artist circles
    const seen = new Set();
    const artistTracks = allTracks.filter(t => {
        const a = t.author || t.title;
        if (seen.has(a)) return false;
        seen.add(a); return true;
    }).slice(0, 12);
    populateShelf(artistShelf, allTracks, artistTracks, true);
}

function renderHomeShelves() {
    // ── Step 1: Render hardcoded tracks IMMEDIATELY (never blank) ─────────
    const fallbackShelves = buildShelves(defaultTracks);
    drawShelves(fallbackShelves);

    // ── Step 2: Try sessionStorage cache for upgraded data ────────────────
    try {
        const c = sessionStorage.getItem(SHELF_CACHE_KEY);
        if (c) {
            const cached = JSON.parse(c);
            drawShelves(cached);
            return; // Already have good data — done
        }
    } catch(e) {}

    // ── Step 3: Background API fetch with 5s abort timeout ───────────────
    const queries = [
        { q: 'เพลงไทยฮิต official MV',              cat: 'thai'       },
        { q: 'pop hits 2024 official music video',   cat: 'global'     },
        { q: 'BLACKPINK BTS NewJeans kpop MV',       cat: 'anime_kpop' },
        { q: 'lofi hip hop chill study beats',       cat: 'lofi'       }
    ];

    const ctrl = new AbortController();
    const timer = setTimeout(() => ctrl.abort(), 5000); // 5s hard limit

    Promise.all(
        queries.map(s =>
            fetch('api_search.php?q=' + encodeURIComponent(s.q), { signal: ctrl.signal })
                .then(r => r.json())
                .catch(() => ({ results: [] }))  // individual query failure = empty
        )
    ).then(dataArr => {
        clearTimeout(timer);
        const apiShelves = { thai: [], global: [], anime_kpop: [], lofi: [] };
        dataArr.forEach((data, i) => {
            const cat = queries[i].cat;
            const tracks = (data.results || []).slice(0, 10).map(t => ({...t, category: cat}));
            if (tracks.length > 0) apiShelves[cat] = tracks;
            else apiShelves[cat] = fallbackShelves[cat]; // keep fallback for empty results
        });
        try { sessionStorage.setItem(SHELF_CACHE_KEY, JSON.stringify(apiShelves)); } catch(e) {}
        drawShelves(apiShelves);  // Silently upgrade the rendered shelves
    }).catch(() => {
        clearTimeout(timer);
        // API totally failed — fallback already rendered, nothing more to do
    });
}

// Initialize YouTube Player API
// YouTube script loads asynchronously and calls this globally
const tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
const firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

window.onYouTubeIframeAPIReady = function() {
    isYtAPIReady = true;
    ytPlayer = new YT.Player('yt-player', {
        height: '100',
        width: '100',
        videoId: 'tntOCGkgt98', // Initialize with a valid default song to prevent player error
        playerVars: {
            'playsinline': 1,
            'controls': 0,
            'disablekb': 1,
            'origin': window.location.origin
        },
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange,
            'onError': onPlayerError
        }
    });
};

function onPlayerReady(event) {
    isPlayerReady = true;
    // Sync initial volume
    ytPlayer.setVolume(lastVolume);
    
    // Play the song if it was clicked before player was fully loaded
    if (pendingTrackId) {
        playTrackById(pendingTrackId);
        pendingTrackId = null;
    }
}

function onPlayerStateChange(event) {
    const playIcon = document.getElementById('p-play').querySelector('ion-icon');
    
    if (event.data === YT.PlayerState.PLAYING) {
        playIcon.setAttribute('name', 'pause-circle');
        startProgressTimer();
    } else {
        playIcon.setAttribute('name', 'play-circle');
        stopProgressTimer();
        
        // Auto-play next song when current finishes
        if (event.data === YT.PlayerState.ENDED) {
            if (isRepeat) {
                // Repeat single track
                ytPlayer.playVideo();
            } else {
                playNext();
            }
        }
    }
}

function onPlayerError(event) {
    // If stream fails, print error in hud and skip
    console.error("YouTube Player Error:", event.data);
    hudIndicator.textContent = "❌ ลิงก์สตรีมเสีย! ข้ามไปยังเพลงถัดไปอัตโนมัติ";
    setTimeout(playNext, 2000);
}

// Player progress timer
function startProgressTimer() {
    stopProgressTimer();
    const slider = document.getElementById('p-progress');
    const timeCurrent = document.getElementById('time-current');
    const timeTotal = document.getElementById('time-total');
    
    progressInterval = setInterval(() => {
        if (!ytPlayer || typeof ytPlayer.getCurrentTime !== 'function') return;
        
        const cur = ytPlayer.getCurrentTime();
        const dur = ytPlayer.getDuration() || 0;
        
        timeCurrent.textContent = formatDuration(cur);
        timeTotal.textContent = formatDuration(dur);
        
        if (dur > 0) {
            const pct = (cur / dur) * 100;
            slider.value = pct;
            slider.style.setProperty('--value', `${pct}%`);
        }
    }, 500);
}

function stopProgressTimer() {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
}

function formatDuration(sec) {
    if (isNaN(sec) || sec === null || !isFinite(sec)) return "0:00";
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    return `${m}:${s.toString().padStart(2, '0')}`;
}

// Parse string duration (e.g. '4:32') to seconds
function parseDurationToSeconds(durStr) {
    if (durStr === 'LIVE') return 0;
    const parts = durStr.split(':').map(Number);
    if (parts.length === 2) {
        return parts[0] * 60 + parts[1];
    } else if (parts.length === 3) {
        return parts[0] * 3600 + parts[1] * 60 + parts[2];
    }
    return 0;
}

// Play a selected track
function playTrackById(videoId) {
    const idx = currentQueue.findIndex(t => t.id === videoId);
    if (idx === -1) return;
    
    currentTrackIdx = idx;
    currentTrack = currentQueue[idx];
    
    // Update player controller layout
    document.getElementById('p-title').textContent = currentTrack.title;
    document.getElementById('p-artist').textContent = currentTrack.author;
    const pThumb = document.getElementById('p-thumb');
    pThumb.src = `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`;
    pThumb.onerror = () => { pThumb.onerror = null; pThumb.src = `https://i.ytimg.com/vi/${videoId}/default.jpg`; };
    document.getElementById('time-total').textContent = currentTrack.duration;
    
    // Sync heart like icon
    updateLikeButtonState();
    
    // Start playback
    if (isPlayerReady && ytPlayer && typeof ytPlayer.loadVideoById === 'function') {
        ytPlayer.loadVideoById(videoId);
        ytPlayer.playVideo();
        hudIndicator.textContent = `🎵 กำลังเล่น: ${currentTrack.title}`;
    } else {
        pendingTrackId = videoId;
        hudIndicator.textContent = `⏳ กำลังเตรียมการเชื่อมต่อตัวเล่นเพลง...`;
    }
    
    // Re-render table active playing status if visual
    if (currentTab === 'liked') {
        renderLikedSongs();
    } else if (currentTab === 'search') {
        renderSearchResultsUI(lastResults);
    }
}

function updateLikeButtonState() {
    const btn = document.getElementById('p-like-btn');
    const icon = btn.querySelector('ion-icon');
    
    if (!currentTrack) {
        btn.classList.remove('liked');
        icon.setAttribute('name', 'heart-outline');
        return;
    }
    
    const isLiked = likedTracks.some(t => t.id === currentTrack.id);
    if (isLiked) {
        btn.classList.add('liked');
        icon.setAttribute('name', 'heart');
    } else {
        btn.classList.remove('liked');
        icon.setAttribute('name', 'heart-outline');
    }
}

function toggleLikeCurrentTrack() {
    if (!currentTrack) return;
    
    const index = likedTracks.findIndex(t => t.id === currentTrack.id);
    if (index === -1) {
        likedTracks.push(currentTrack);
        beep(800, 'sine', 0.08, 0.1);
    } else {
        likedTracks.splice(index, 1);
        beep(400, 'sine', 0.08, 0.1);
    }
    
    saveLikedSongs();
    updateLikeButtonState();
    
    if (currentTab === 'liked') {
        renderLikedSongs();
    }
}

// Controller Actions
function togglePlayPause() {
    if (!currentTrack) {
        // Play first recommendation if empty
        if (currentQueue.length > 0) {
            playTrackById(currentQueue[0].id);
        }
        return;
    }
    
    if (!ytPlayer || typeof ytPlayer.getPlayerState !== 'function') return;
    
    const state = ytPlayer.getPlayerState();
    if (state === YT.PlayerState.PLAYING) {
        ytPlayer.pauseVideo();
    } else {
        ytPlayer.playVideo();
    }
}

function playNext() {
    if (currentQueue.length === 0) return;
    
    let nextIdx = currentTrackIdx + 1;
    if (isShuffle) {
        nextIdx = Math.floor(Math.random() * currentQueue.length);
    }
    
    if (nextIdx >= currentQueue.length) {
        nextIdx = 0; // loop back to first track
    }
    
    const nextTrack = currentQueue[nextIdx];
    if (nextTrack) {
        playTrackById(nextTrack.id);
    }
}

function playPrev() {
    if (currentQueue.length === 0) return;
    
    let prevIdx = currentTrackIdx - 1;
    if (isShuffle) {
        prevIdx = Math.floor(Math.random() * currentQueue.length);
    }
    
    if (prevIdx < 0) {
        prevIdx = currentQueue.length - 1; // loop back to end
    }
    
    const prevTrack = currentQueue[prevIdx];
    if (prevTrack) {
        playTrackById(prevTrack.id);
    }
}

function toggleShuffle() {
    isShuffle = !isShuffle;
    const btn = document.getElementById('p-shuffle');
    if (isShuffle) {
        btn.classList.add('active');
        beep(600, 'sine', 0.05, 0.1);
    } else {
        btn.classList.remove('active');
        beep(400, 'sine', 0.05, 0.1);
    }
}

function toggleRepeat() {
    isRepeat = !isRepeat;
    const btn = document.getElementById('p-repeat');
    if (isRepeat) {
        btn.classList.add('active');
        beep(600, 'sine', 0.05, 0.1);
    } else {
        btn.classList.remove('active');
        beep(400, 'sine', 0.05, 0.1);
    }
}

function handleSeek(val) {
    if (!ytPlayer || typeof ytPlayer.getDuration !== 'function') return;
    const dur = ytPlayer.getDuration();
    if (dur > 0) {
        const targetSeconds = (val / 100) * dur;
        ytPlayer.seekTo(targetSeconds, true);
    }
}

function handleVolume(val) {
    lastVolume = val;
    if (ytPlayer && typeof ytPlayer.setVolume === 'function') {
        ytPlayer.setVolume(val);
    }
    
    // Update mute icon status
    const volIcon = document.getElementById('p-vol-icon');
    if (val == 0) {
        volIcon.setAttribute('name', 'volume-mute-outline');
    } else if (val < 50) {
        volIcon.setAttribute('name', 'volume-low-outline');
    } else {
        volIcon.setAttribute('name', 'volume-high-outline');
    }
    
    // Update slider CSS gradient value
    const slider = document.getElementById('p-volume');
    slider.style.setProperty('--value', `${val}%`);
}

function toggleMute() {
    isMuted = !isMuted;
    const volIcon = document.getElementById('p-vol-icon');
    const slider = document.getElementById('p-volume');
    
    if (isMuted) {
        if (ytPlayer && typeof ytPlayer.setVolume === 'function') ytPlayer.setVolume(0);
        volIcon.setAttribute('name', 'volume-mute-outline');
        slider.value = 0;
        slider.style.setProperty('--value', `0%`);
    } else {
        if (ytPlayer && typeof ytPlayer.setVolume === 'function') ytPlayer.setVolume(lastVolume);
        handleVolume(lastVolume);
    }
}

// ------- WEB AUDIO SOUND BEEP HELPER -------
function beep(freq, type = 'sine', dur = 0.06, vol = 0.12) {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const o = audioCtx.createOscillator(), g = audioCtx.createGain();
        o.type = type; o.frequency.value = freq;
        g.gain.setValueAtTime(vol, audioCtx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + dur);
        o.connect(g); g.connect(audioCtx.destination);
        o.start(); o.stop(audioCtx.currentTime + dur);
    } catch(e) {}
}

// Play custom preset Lo-Fi stream dynamically fetched
async function playPresetLofi() {
    const playIcon = document.getElementById('p-play').querySelector('ion-icon');
    hudIndicator.textContent = `⏳ กำลังเชื่อมต่อไปยังคลื่นวิทยุ Lofi Girl...`;
    
    try {
        const res = await fetch(`api_search.php?q=${encodeURIComponent('Lofi Girl Study Session')}`);
        const data = await res.json();
        
        if (data.results && data.results.length > 0) {
            currentQueue = data.results;
            playTrackById(data.results[0].id);
        } else {
            alert("ไม่สามารถเชื่อมต่อสัญญาณวิทยุ Lofi ได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง!");
        }
    } catch(err) {
        console.error("Lofi preset fetch failed:", err);
        alert("เกิดข้อผิดพลาดในการโหลดคลื่นสัญญาณ!");
    }
}

function playPresetItem(id, title, author, thumb, dur) {
    currentQueue = [{ id: id, title: title, author: author, thumbnail: thumb, duration: dur }];
    playTrackById(id);
}

// Direct search from categories
function triggerDirectSearch(queryText) {
    const input = document.getElementById('search-query');
    const inputMobile = document.getElementById('search-query-mobile');
    input.value = queryText;
    inputMobile.value = queryText;
    runMusicSearch(queryText);
}

function handleSearchKey(event) {
    if (event.key === 'Enter') {
        const q = event.target.value.trim();
        if (q !== '') {
            runMusicSearch(q);
        }
    }
}

// Search API caller
let lastResults = [];

async function runMusicSearch(query) {
    const resultsBox = document.getElementById('search-results-box');
    const genresBox = document.getElementById('search-genres-box');
    const loading = document.getElementById('search-loading');
    
    genresBox.style.display = 'none';
    resultsBox.style.display = 'none';
    loading.style.display = 'block';
    
    try {
        const res = await fetch(`api_search.php?q=${encodeURIComponent(query)}`);
        const data = await res.json();
        
        loading.style.display = 'none';
        
        if (data.results && data.results.length > 0) {
            lastResults = data.results;
            renderSearchResultsUI(data.results);
            resultsBox.style.display = 'block';
        } else {
            alert("ไม่พบสตรีมเพลงสัญญานดังกล่าว กรุณาระบุคำค้นหาใหม่อีกครั้ง!");
            genresBox.style.display = 'block';
        }
    } catch(err) {
        loading.style.display = 'none';
        genresBox.style.display = 'block';
        alert("เกิดข้อผิดพลาดในการเชื่อมต่อคลังค้นหา!");
    }
}

function renderSearchResultsUI(results) {
    const tbody = document.getElementById('search-results-tbody');
    tbody.innerHTML = '';
    
    results.forEach((track, idx) => {
        const tr = document.createElement('tr');
        const isCurrentPlaying = currentTrack && currentTrack.id === track.id;
        if (isCurrentPlaying) tr.className = 'playing';
        
        tr.onclick = () => {
            currentQueue = [...results];
            playTrackById(track.id);
        };
        
        tr.innerHTML = `
            <td class="track-num">${idx + 1}</td>
            <td>
                <div class="track-title-col">
                    <img class="track-thumb" src="https://i.ytimg.com/vi/${track.id}/hqdefault.jpg" onerror="this.onerror=null;this.src='https://i.ytimg.com/vi/${track.id}/default.jpg'">
                    <div class="track-info-txt">
                        <div class="track-name">${track.title}</div>
                        <div class="track-artist">${track.author}</div>
                    </div>
                </div>
            </td>
            <td><div class="track-album">YouTube Stream</div></td>
            <td class="track-duration">${track.duration}</td>
        `;
        tbody.appendChild(tr);
    });
}

// Window load init setup
window.addEventListener('load', () => {
    setWelcomeGreeting();
    loadLikedSongs();
    renderHomeShelves();
    
    // Sync initial range value gradient fills
    handleVolume(80);
});

// Watch resize to toggling search bars responsive placement
window.addEventListener('resize', () => {
    if (currentTab === 'search') {
        const searchBar = document.getElementById('header-search-bar');
        const mobileSearchBar = document.getElementById('mobile-search-bar');
        if (window.innerWidth <= 768) {
            searchBar.style.display = 'none';
            mobileSearchBar.style.display = 'block';
        } else {
            searchBar.style.display = 'block';
            mobileSearchBar.style.display = 'none';
        }
    }
});
</script>

</body>
</html>
