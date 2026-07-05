<?php
session_start();
$jsonPath = dirname(__DIR__, 2) . '/data/seniors_list.json';
$themePath = dirname(__DIR__, 2) . '/data/senior_theme.json';


// Load Seniors
if (file_exists($jsonPath)) {
    $seniorsJson = file_get_contents($jsonPath);
} else {
    $seniorsJson = json_encode([
        "seniors" => [
            ["id" => 1,  "name" => "สโร",  "image" => "assets/seniors/senior_1.jpg"],
            ["id" => 2,  "name" => "อิง",  "image" => "assets/seniors/senior_2.jpg"],
            ["id" => 3,  "name" => "เอ็ม", "image" => "assets/seniors/senior_3.jpg"],
            ["id" => 4,  "name" => "อัด",  "image" => "assets/seniors/senior_4.jpg"],
            ["id" => 5,  "name" => "ฟาด",  "image" => "assets/seniors/senior_5.jpg"],
            ["id" => 6,  "name" => "ไนท์", "image" => "assets/seniors/senior_6.jpg"],
            ["id" => 7,  "name" => "ขิม",  "image" => "assets/seniors/senior_7.jpg"],
            ["id" => 8,  "name" => "เจมส์","image" => "assets/seniors/senior_8.jpg"],
            ["id" => 9,  "name" => "โฟท์", "image" => "assets/seniors/senior_9.jpg"],
            ["id" => 10, "name" => "ไอซ์", "image" => "assets/seniors/senior_10.jpg"],
            ["id" => 11, "name" => "อุ้ม",  "image" => "assets/seniors/senior_11.jpg"],
            ["id" => 12, "name" => "เอิธ์","image" => "assets/seniors/senior_12.jpg"],
            ["id" => 13, "name" => "คิม",  "image" => "assets/seniors/senior_13.jpg"],
            ["id" => 14, "name" => "บาส",  "image" => "assets/seniors/senior_14.jpg"]
        ]
    ]);
}

// Load Theme Initial Colors
$bg = "#000000"; $surface = "#111111"; $surface2 = "#1c1c1e"; $surface3 = "#2c2c2e"; $accent = "#0a84ff";
if (file_exists($themePath)) {
    $parsed = json_decode(file_get_contents($themePath), true);
    if (isset($parsed['theme'])) {
        $t = $parsed['theme'];
        $bg = $t['bg'] ?? $bg;
        $surface = $t['surface'] ?? $surface;
        $surface2 = $t['surface2'] ?? $surface2;
        $surface3 = $t['surface3'] ?? $surface3;
        $accent = $t['accent'] ?? $accent;
    }
}

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใครเอ่ย? — Senior Roulette</title>
    <link href="game_shuffle.css?v=<?=time();?>" rel="stylesheet">
    <style>
        :root {
            --bg: <?= $bg ?>;
            --surface: <?= $surface ?>;
            --surface-2: <?= $surface2 ?>;
            --surface-3: <?= $surface3 ?>;
            --blue: <?= $accent ?>;
        }
        
        .card-container.gather {
            transform: translate(var(--tx), var(--ty)) rotate(var(--rot)) scale(0.8) !important;
            z-index: 100;
            box-shadow: 0 20px 40px rgba(0,0,0,0.8);
        }
        @keyframes shuffleSpin {
            0% { transform: translate(var(--tx), var(--ty)) rotate(var(--rot)) scale(0.8); }
            50% { transform: translate(calc(var(--tx) + 15px), calc(var(--ty) - 10px)) rotate(calc(var(--rot) + 10deg)) scale(0.82); }
            100% { transform: translate(var(--tx), var(--ty)) rotate(var(--rot)) scale(0.8); }
        }
        .card-board.is-shuffling .card-container.gather {
            animation: shuffleSpin 0.15s infinite;
        }
        
        /* ADMIN WYSIWYG STYLES */
        .admin-notice {
            background: rgba(48,209,88,0.15); border: 1px solid #30d158; color: #30d158;
            padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600;
            text-align: center; margin-bottom: 20px;
        }
        .card-front.editable:hover {
            box-shadow: 0 0 0 2px var(--blue); cursor: pointer;
        }
        .edit-badge {
            position: absolute; top: 6px; right: 6px; background: rgba(0,0,0,0.7); color: #fff;
            font-size: 0.6rem; padding: 2px 6px; border-radius: 4px; pointer-events: none; opacity: 0;
            transition: opacity 0.2s;
        }
        .card-front.editable:hover .edit-badge { opacity: 1; }
        .editable-name:hover {
            background: rgba(10,132,255,0.2); color: var(--blue);
        }
        
        .theme-toggle-btn {
            position: fixed; bottom: 20px; right: 20px; z-index: 1000;
            background: #1c1c1e; color: #fff; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 50%; width: 54px; height: 54px; font-size: 1.5rem;
            cursor: pointer; box-shadow: 0 8px 24px rgba(0,0,0,0.6);
            display: flex; justify-content: center; align-items: center;
            transition: transform 0.2s;
        }
        .theme-toggle-btn:hover { transform: scale(1.1); background: #2c2c2e; }
        
        .theme-editor-panel {
            position: fixed; bottom: 85px; right: 20px; z-index: 1000;
            background: rgba(20,20,22,0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1); border-radius: 16px;
            padding: 24px; box-shadow: 0 15px 40px rgba(0,0,0,0.8);
            color: #fff; width: 280px;
            opacity: 0; visibility: hidden; transform: translateY(20px);
            transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        }
        .theme-editor-panel.active { opacity: 1; visibility: visible; transform: translateY(0); }
        .theme-editor-panel h3 { margin-top: 0; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 12px; margin-bottom: 16px; }
        .theme-editor-panel label { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 0.85rem; color: #aaa; }
        .theme-editor-panel input[type="color"] { background: transparent; border: none; cursor: pointer; width: 34px; height: 34px; padding: 0; }
        .save-indicator {
            font-size: 0.75rem; color: #30d158; text-align: center; margin-top: 10px; opacity: 0; transition: opacity 0.3s;
        }
        .save-indicator.show { opacity: 1; }
    </style>
</head>
<body>

<?php if ($isAdmin): ?>
<!-- THEME FLOATING EDITOR -->
<div id="theme-editor" class="theme-editor-panel">
    <h3>🎨 ปรับแต่งสีธีมสด (Auto-Save)</h3>
    <label>Background <input type="color" id="tc-bg" value="<?= $bg ?>" oninput="updateThemeLive()"></label>
    <label>Surface (กล่องเกม) <input type="color" id="tc-surface" value="<?= $surface ?>" oninput="updateThemeLive()"></label>
    <label>Surface 2 (หลังไพ่) <input type="color" id="tc-surface2" value="<?= $surface2 ?>" oninput="updateThemeLive()"></label>
    <label>Surface 3 (หน้าไพ่) <input type="color" id="tc-surface3" value="<?= $surface3 ?>" oninput="updateThemeLive()"></label>
    <label>Accent (สีหลัก) <input type="color" id="tc-accent" value="<?= $accent ?>" oninput="updateThemeLive()"></label>
    <div id="save-status" class="save-indicator">💾 บันทึกธีมเรียบร้อย...</div>
</div>
<button class="theme-toggle-btn" onclick="document.getElementById('theme-editor').classList.toggle('active')">🎨</button>
<?php endif; ?>

<div class="game-box" id="game-stage">
    <?php if ($isAdmin): ?>
        <div class="admin-notice">👨‍💻 [Admin Mode] คุณสามารถคลิกที่รูปภาพหรือชื่อบนไพ่เพื่อแก้ไขได้ทันที (Auto-Save)</div>
    <?php endif; ?>
    
    <div class="game-header">
        <h1>ใครเอ่ย? 🃏</h1>
        <p>ให้คุณ "สับไพ่" แล้วกดเลือกเป้าหมาย 2 คนด้วยมือของคุณเอง</p>
    </div>

    <div id="game-status">กด "🎰 สับและแจกไพ่" เพื่อเริ่มเกม</div>

    <!-- Target slots at top -->
    <div class="target-zone">
        <div class="target-slot" id="slot-0">
            <span class="slot-placeholder">🎯 เป้าหมายที่ 1<br><small style="color:#48484a; font-size:0.7rem;">รอเลือกไพ่...</small></span>
        </div>
        <div class="target-slot" id="slot-1">
            <span class="slot-placeholder">🎯 เป้าหมายที่ 2<br><small style="color:#48484a; font-size:0.7rem;">รอเลือกไพ่...</small></span>
        </div>
    </div>

    <!-- Card grid -->
    <div class="card-board" id="card-board"></div>

    <!-- Action buttons -->
    <div class="action-bar">
        <div class="btn-row">
            <button class="btn-main" id="btn-start" onclick="beginShuffle()">🎰 สับและแจกไพ่</button>
            <button class="btn-outline" id="btn-reset" onclick="resetBoard()" disabled>↺ รีเซ็ตใหม่</button>
        </div>
        <div style="display:flex; justify-content:center; align-items:center; margin-top:10px;">
            <a href="../../index.php" class="back-link">← กลับสู่หน้าหลัก</a>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<input type="file" id="image-upload-input" style="display:none" accept="image/*">
<?php endif; ?>

<script>
const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
const RAW = <?php echo $seniorsJson; ?>;
const baseSeniors = [...(RAW.seniors || [])].slice(0, 14);
let currentSeniors = [...baseSeniors];

const board       = document.getElementById('card-board');
const statusEl    = document.getElementById('game-status');
const btnStart    = document.getElementById('btn-start');
const btnReset    = document.getElementById('btn-reset');

let gameReady = false;
let pickedCount = 0;
let pickedIndices = [];

// ------- ADMIN WYSIWYG SYSTEM -------
let saveTimeout;
let activeUploadIdx = null;

if (IS_ADMIN) {
    const imageUploadInput = document.getElementById('image-upload-input');
    if (imageUploadInput) {
        imageUploadInput.addEventListener('change', async (e) => {
            if (!e.target.files.length || activeUploadIdx === null) return;
            
            const file = e.target.files[0];
            const formData = new FormData();
            formData.append('image', file);
            
            const status = document.getElementById('game-status');
            const oldText = status.textContent;
            status.textContent = "⏳ กำลังอัปโหลดรูปภาพ...";
            
            try {
                const res = await fetch('api_upload.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    const s = currentSeniors[activeUploadIdx];
                    s.image = data.filepath;
                    document.getElementById(`img-senior-${activeUploadIdx}`).src = `../../${s.image}?v=` + Date.now();
                    saveSeniorsAPI();
                } else {
                    alert("อัปโหลดไม่สำเร็จ: " + data.message);
                    status.textContent = oldText;
                }
            } catch(err) {
                alert("เกิดข้อผิดพลาดในการเชื่อมต่อ: " + err);
                status.textContent = oldText;
            }
            
            imageUploadInput.value = '';
            activeUploadIdx = null;
        });
    }
}

function updateThemeLive() {
    const theme = {
        bg: document.getElementById('tc-bg').value,
        surface: document.getElementById('tc-surface').value,
        surface2: document.getElementById('tc-surface2').value,
        surface3: document.getElementById('tc-surface3').value,
        accent: document.getElementById('tc-accent').value
    };
    // Apply live
    document.documentElement.style.setProperty('--bg', theme.bg);
    document.documentElement.style.setProperty('--surface', theme.surface);
    document.documentElement.style.setProperty('--surface-2', theme.surface2);
    document.documentElement.style.setProperty('--surface-3', theme.surface3);
    document.documentElement.style.setProperty('--blue', theme.accent);
    
    // Auto-save debounce
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(async () => {
        await fetch('api_save.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'save_theme', theme: theme })
        });
        const ind = document.getElementById('save-status');
        ind.classList.add('show');
        setTimeout(() => ind.classList.remove('show'), 2000);
    }, 1000);
}

function editField(e, idx, field) {
    if (!IS_ADMIN) return;
    e.stopPropagation(); // Stop card flipping
    
    const s = currentSeniors[idx];
    if (field === 'image') {
        activeUploadIdx = idx;
        document.getElementById('image-upload-input').click();
    } else if (field === 'name') {
        const newName = prompt(`เปลี่ยนชื่อสำหรับเบอร์ ${idx+1} (ปัจจุบัน: ${s.name})`, s.name);
        if (newName !== null && newName.trim() !== '') {
            s.name = newName.trim();
            document.getElementById(`name-senior-${idx}`).textContent = s.name;
            saveSeniorsAPI();
        }
    }
}

async function saveSeniorsAPI() {
    // Sync baseSeniors
    for(let i=0; i<14; i++) {
        baseSeniors[i].name = currentSeniors[i].name;
        baseSeniors[i].image = currentSeniors[i].image;
    }
    await fetch('api_save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'save_seniors', seniors: baseSeniors })
    });
    // Visual blip
    const status = document.getElementById('game-status');
    const oldText = status.textContent;
    status.textContent = "💾 บันทึกไพ่เรียบร้อยแล้ว!";
    setTimeout(() => status.textContent = oldText, 1500);
}

// ------- BACKGROUND POLLING (Only for Non-Admins) -------
if (!IS_ADMIN) {
    async function pollRealtimeUpdates() {
        try {
            const themeRes = await fetch('../../data/senior_theme.json?v=' + Date.now());
            const themeData = await themeRes.json();
            if (themeData.theme) {
                document.documentElement.style.setProperty('--bg', themeData.theme.bg);
                document.documentElement.style.setProperty('--surface', themeData.theme.surface);
                document.documentElement.style.setProperty('--surface-2', themeData.theme.surface2);
                document.documentElement.style.setProperty('--surface-3', themeData.theme.surface3);
                document.documentElement.style.setProperty('--blue', themeData.theme.accent);
            }
            
            const seniorsRes = await fetch('../../data/seniors_list.json?v=' + Date.now());
            const seniorsData = await seniorsRes.json();
            if (seniorsData.seniors) {
                const list = seniorsData.seniors.slice(0, 14);
                list.forEach((s, i) => {
                    const img = document.getElementById(`img-senior-${i}`);
                    const nameTag = document.getElementById(`name-senior-${i}`);
                    if (img && nameTag) {
                        const newSrc = '../../' + s.image;
                        if (!img.src.includes(s.image)) img.src = newSrc;
                        if (nameTag.textContent !== s.name) nameTag.textContent = s.name;
                    }
                });
                for(let k=0; k<14; k++) {
                    if(baseSeniors[k]) {
                        baseSeniors[k].name = list[k].name;
                        baseSeniors[k].image = list[k].image;
                        currentSeniors[k].name = list[k].name;
                        currentSeniors[k].image = list[k].image;
                    }
                }
            }
        } catch(e) {}
    }
    setInterval(pollRealtimeUpdates, 2500);
}

// ------- AUDIO -------
let audioCtx = null;
function ac() {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === 'suspended') audioCtx.resume();
    return audioCtx;
}
function beep(freq, type = 'sine', dur = 0.06, vol = 0.12) {
    try {
        const ctx = ac();
        const o = ctx.createOscillator(), g = ctx.createGain();
        o.type = type; o.frequency.value = freq;
        g.gain.setValueAtTime(vol, ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + dur);
        o.connect(g); g.connect(ctx.destination);
        o.start(); o.stop(ctx.currentTime + dur);
    } catch(e) {}
}

// ------- BUILD BOARD -------
function buildBoard(faceUp = true) {
    board.innerHTML = '';
    
    currentSeniors.forEach((s, i) => {
        const el = document.createElement('div');
        el.className = `card-container ${faceUp ? 'flipped' : ''}`;
        el.id = `card-${i}`;
        el.dataset.idx = i;
        el.onclick = () => handleCardClick(i);
        
        let editableClass = IS_ADMIN ? 'editable' : '';
        let imgClick = IS_ADMIN ? `onclick="editField(event, ${i}, 'image')"` : '';
        let nameClick = IS_ADMIN ? `onclick="editField(event, ${i}, 'name')"` : '';
        let editBadge = IS_ADMIN ? `<div class="edit-badge">✏️ Edit</div>` : '';

        el.innerHTML = `
            <div class="card-inner">
                <div class="card-back">
                    <div class="card-num">${i + 1}</div>
                </div>
                <div class="card-front ${editableClass}">
                    ${editBadge}
                    <img id="img-senior-${i}" src="../../${s.image}" onerror="this.src='https://api.dicebear.com/7.x/pixel-art/svg?seed=${s.id}'" ${imgClick}>
                    <div class="name-tag ${IS_ADMIN ? 'editable-name' : ''}" id="name-senior-${i}" ${nameClick}>${s.name}</div>
                </div>
            </div>
        `;
        board.appendChild(el);
    });
}

function resetSlots() {
    [0,1].forEach(i => {
        const slot = document.getElementById(`slot-${i}`);
        slot.classList.remove('filled');
        slot.innerHTML = `<span class="slot-placeholder">🎯 เป้าหมายที่ ${i+1}<br><small style="color:#48484a; font-size:0.7rem;">รอเลือกไพ่...</small></span>`;
    });
}

function resetBoard() {
    gameReady = false;
    pickedCount = 0;
    pickedIndices = [];
    currentSeniors = JSON.parse(JSON.stringify(baseSeniors)); // deep copy clone
    
    btnStart.disabled = false;
    btnReset.disabled = true;
    
    statusEl.style.cssText = '';
    statusEl.textContent = 'กด "🎰 สับและแจกไพ่" เพื่อเริ่มเกม';
    
    resetSlots();
    buildBoard(true); 
}

// ------- NEW SHUFFLE MECHANIC -------
function beginShuffle() {
    if (gameReady) return;
    btnStart.disabled = true;
    btnReset.disabled = true;
    
    const cards = document.querySelectorAll('.card-container');
    cards.forEach((c, i) => {
        c.classList.remove('flipped');
        setTimeout(() => beep(400 + i*20, 'sine', 0.05, 0.1), i * 30);
    });
    
    statusEl.style.color = 'var(--blue)';
    statusEl.style.background = 'rgba(10,132,255,0.1)';
    statusEl.style.borderColor = 'rgba(10,132,255,0.3)';
    statusEl.textContent = '🔮 กำลังรวบรวมไพ่เพื่อสับ...';

    setTimeout(() => {
        const boardRect = board.getBoundingClientRect();
        const bx = boardRect.left + boardRect.width / 2;
        const by = boardRect.top + boardRect.height / 2;

        cards.forEach((c, i) => {
            const cardRect = c.getBoundingClientRect();
            const cx = cardRect.left + cardRect.width / 2;
            const cy = cardRect.top + cardRect.height / 2;
            
            const tx = bx - cx;
            const ty = by - cy;
            const rot = (i - cards.length / 2) * 5; 

            c.style.setProperty('--tx', `${tx}px`);
            c.style.setProperty('--ty', `${ty}px`);
            c.style.setProperty('--rot', `${rot}deg`);
            
            c.classList.add('gather');
        });
        
        beep(300, 'triangle', 0.2, 0.2);
    }, 600);

    setTimeout(() => {
        board.classList.add('is-shuffling');
        statusEl.textContent = '🎰 สับไพ่กระจายตำแหน่ง...';
        
        let tickInt = setInterval(() => {
            beep(200 + Math.random()*100, 'sawtooth', 0.05, 0.15);
        }, 80);
        
        setTimeout(() => {
            clearInterval(tickInt);
            board.classList.remove('is-shuffling');
            
            for (let i = currentSeniors.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [currentSeniors[i], currentSeniors[j]] = [currentSeniors[j], currentSeniors[i]];
            }
            
            cards.forEach((c, i) => {
                const s = currentSeniors[i];
                c.querySelector('.card-front img').src = `../../${s.image}`;
                c.querySelector('.card-front .name-tag').textContent = s.name;
            });
            
            cards.forEach((c, i) => {
                setTimeout(() => {
                    c.classList.remove('gather');
                    beep(500 + i*30, 'sine', 0.05, 0.1);
                }, i * 40);
            });
            
            setTimeout(() => {
                gameReady = true;
                btnReset.disabled = false;
                statusEl.style.color = '#30d158';
                statusEl.style.background = 'rgba(48,209,88,0.1)';
                statusEl.style.borderColor = 'rgba(48,209,88,0.3)';
                statusEl.textContent = '✅ แจกไพ่เสร็จแล้ว! คลิกเลือกไพ่ 2 ใบเพื่อกำหนดเป้าหมาย';
            }, cards.length * 40 + 200);
            
        }, 1500);
    }, 1200);
}

// ------- MANUAL CLICK LOGIC -------
function handleCardClick(idx) {
    if (!gameReady) return;
    
    const cardEl = document.getElementById(`card-${idx}`);
    if (pickedIndices.includes(idx) || cardEl.classList.contains('flipped')) return;
    
    const senior = currentSeniors[idx];
    
    if (pickedCount < 2) {
        pickedIndices.push(idx);
        const slotNum = pickedCount;
        pickedCount++;
        
        cardEl.classList.add('hidden-card');
        
        const slot = document.getElementById(`slot-${slotNum}`);
        slot.classList.add('filled');
        slot.innerHTML = `
            <div class="widget-card" id="widget-${slotNum}" onclick="toggleReveal(${slotNum})">
                <img src="../../${senior.image}" onerror="this.src='https://api.dicebear.com/7.x/pixel-art/svg?seed=${senior.id}'">
                <div class="w-name">${senior.name}</div>
                <div class="w-overlay">❌</div>
            </div>
        `;
        
        beep(600 + slotNum*200, 'triangle', 0.2, 0.2);
        
        if (pickedCount === 1) {
            statusEl.textContent = '🎯 เลือกเป้าหมายที่ 1 แล้ว! คลิกเลือกใบที่ 2 ต่อเลย';
        } else if (pickedCount === 2) {
            statusEl.textContent = '🔥 เลือกเป้าหมายครบแล้ว! กดที่ตู้ด้านบนเพื่อสุ่มเฉลย หรือส่องไพ่ด้านล่างได้เลย';
        }
    } else {
        cardEl.classList.add('flipped');
        beep(450, 'sine', 0.1, 0.1);
    }
}

function toggleReveal(slotNum) {
    const wc = document.getElementById(`widget-${slotNum}`);
    if (wc) {
        if (!wc.classList.contains('revealed')) {
            wc.classList.add('revealed');
            beep(250, 'sawtooth', 0.3, 0.3);
            setTimeout(() => beep(180, 'sawtooth', 0.3, 0.25), 100);
        } else {
            wc.classList.remove('revealed');
        }
    }
}

buildBoard(true); 
</script>
</body>
</html>