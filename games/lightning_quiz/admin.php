<?php
session_start();
// Strict admin restriction
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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
    <title>⚙️ แผงควบคุมปริศนาฟ้าแลบ — Host Panel</title>
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
        <a href="index.php" class="back-btn">
            <ion-icon name="arrow-back-outline"></ion-icon>
            กลับหน้าจอแสดงผล
        </a>
        <h1 class="hud-title">HOST CONTROLLER</h1>
        <div style="width: 80px;"></div>
    </header>

    <div class="admin-grid-v2">
        
        <!-- LEFT COLUMN: LIVE CONTROL STAGE -->
        <div class="admin-left-col">
            
            <!-- Card 1: Score Controller -->
            <div class="admin-panel-card-v2 primary-glow">
                <div class="admin-card-header-v2">
                    <ion-icon name="game-controller-outline" style="color:var(--neon-green);"></ion-icon>
                    <span>แผงควบคุมคะแนนสด (Live Stage Score)</span>
                </div>
                
                <div class="futuristic-score-wheel">
                    <div class="score-ring-glow"></div>
                    <div class="score-wheel-content">
                        <span class="sw-label">LEVEL</span>
                        <span class="sw-val" id="admin-curr-level">0</span>
                        <span class="sw-max">/ 10</span>
                    </div>
                </div>

                <!-- CORRECT / WRONG ACTION BUTTONS -->
                <div class="live-action-buttons-v2">
                    <button class="btn-correct-v2" onclick="updateLevel('correct')">
                        <ion-icon name="checkmark-done-outline"></ion-icon>
                        <span>ถูกต้อง (Correct)</span>
                    </button>
                    <button class="btn-wrong-v2" onclick="updateLevel('wrong')">
                        <ion-icon name="close-outline"></ion-icon>
                        <span>ผิด (Wrong)</span>
                    </button>
                </div>

                <button class="btn-action-v2 btn-danger-v2" onclick="resetGame()">
                    <ion-icon name="refresh-outline"></ion-icon>
                    รีเซ็ตเกมใหม่ทั้งหมด (เริ่มเวลากลับมาเริ่ม 0 แต้ม)
                </button>
            </div>

            <!-- Card 2: Timer Panel -->
            <div class="admin-panel-card-v2 secondary-glow" style="margin-top:20px;">
                <div class="admin-card-header-v2">
                    <ion-icon name="time-outline" style="color:var(--neon-yellow);"></ion-icon>
                    <span>ระบบควบคุมเวลา (Stage Timer)</span>
                </div>
                
                <div class="futuristic-timer-display" id="admin-timer-display">00:60</div>
                
                <div class="admin-timer-controls-v2">
                    <button class="btn-control-v2 btn-green-v2" onclick="controlTimer('start')">START</button>
                    <button class="btn-control-v2 btn-pink-v2" onclick="controlTimer('pause')">PAUSE</button>
                    <button class="btn-control-v2 btn-yellow-v2" onclick="controlTimer('reset')">RESET</button>
                </div>

                <div class="admin-duration-adjust-v2">
                    <label>ตั้งเวลาเล่นต่อรอบ:</label>
                    <select id="select-timer-duration" class="custom-select-v2" onchange="changeTimerDuration(this.value)">
                        <option value="30">30 วินาที</option>
                        <option value="45">45 วินาที</option>
                        <option value="60" selected>60 วินาที</option>
                        <option value="90">90 วินาที</option>
                        <option value="120">120 วินาที</option>
                    </select>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: QUESTION HUB (TABBED SYSTEM) -->
        <div class="admin-right-col">
            
            <div class="admin-panel-card-v2" style="height:100%; display:flex; flex-direction:column;">
                
                <div class="admin-card-header-v2">
                    <ion-icon name="library-outline" style="color:var(--neon-cyan);"></ion-icon>
                    <span>คลังจัดการคำถาม (Question Hub)</span>
                </div>

                <!-- Futuristic Question Adder Form -->
                <div class="add-q-form-v2">
                    <textarea id="input-new-question" rows="2" placeholder="พิมพ์หัวข้อคำถามใหม่ที่นี่..."></textarea>
                    <button class="btn-add-q" onclick="addQuestion()">
                        <ion-icon name="add-outline"></ion-icon>
                        เพิ่มคำถามลงฐานข้อมูล
                    </button>
                </div>

                <!-- Tab Headers -->
                <div class="tab-header-row-v2">
                    <button class="tab-header-btn-v2 active" id="tab-btn-select" onclick="switchRightTab('select')">
                        <ion-icon name="play-outline"></ion-icon>
                        เปิดคำถามขึ้นจอ
                    </button>
                    <button class="tab-header-btn-v2" id="tab-btn-crud" onclick="switchRightTab('crud')">
                        <ion-icon name="create-outline"></ion-icon>
                        แก้ไข/ลบคำถาม
                    </button>
                </div>

                <!-- Active Question Banner -->
                <div class="current-selected-box-v2">
                    <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">กำลังแสดงบนโปรเจกเตอร์:</div>
                    <div class="curr-q-text-v2" id="admin-active-q-text">ไม่มีคำถามที่เลือก</div>
                </div>

                <!-- Tab 1 Container: Select Question -->
                <div id="tab-select-container" class="tab-panel-container-v2 active-panel">
                    <div class="questions-list-scroller-v2" id="admin-select-questions-list">
                        <!-- JS populated selector list -->
                    </div>
                </div>

                <!-- Tab 2 Container: CRUD list -->
                <div id="tab-crud-container" class="tab-panel-container-v2">
                    <div class="questions-crud-list-v2" id="admin-crud-questions-list">
                        <!-- JS populated CRUD list -->
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

<script>
let activeQuestionId = 0;
let currentLevel = 0;
let activeTab = 'select'; // 'select' or 'crud'

function switchRightTab(tabName) {
    activeTab = tabName;
    
    // Toggle active tab buttons
    document.getElementById('tab-btn-select').classList.toggle('active', tabName === 'select');
    document.getElementById('tab-btn-crud').classList.toggle('active', tabName === 'crud');
    
    // Toggle active panel containers
    document.getElementById('tab-select-container').classList.toggle('active-panel', tabName === 'select');
    document.getElementById('tab-crud-container').classList.toggle('active-panel', tabName === 'crud');
}

function syncAdminState() {
    fetch('api.php?action=get_state')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const state = data.state;
                const questions = data.questions;
                
                // 1. Level display
                currentLevel = state.current_level;
                document.getElementById('admin-curr-level').textContent = currentLevel;
                
                // 2. Active Question text
                activeQuestionId = state.current_question_id;
                const activeQ = questions.find(q => parseInt(q.id) === activeQuestionId);
                document.getElementById('admin-active-q-text').textContent = activeQ ? activeQ.question_text : 'ไม่มีคำถามที่เลือก';
                
                // 3. Render select list & CRUD list
                renderQuestionsLists(questions);
                
                // 4. Sync countdown timer
                let remaining = state.timer_seconds;
                if (state.timer_running === 1) {
                    const now = Date.now();
                    const elapsed = Math.floor((now - state.timer_sync_time) / 1000);
                    remaining = Math.max(0, state.timer_seconds - elapsed);
                }
                
                const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
                const seconds = String(remaining % 60).padStart(2, '0');
                
                document.getElementById('admin-timer-display').textContent = `${minutes}:${seconds}`;
            }
        })
        .catch(e => console.error(e));
}

function renderQuestionsLists(questions) {
    const selectList = document.getElementById('admin-select-questions-list');
    const crudList = document.getElementById('admin-crud-questions-list');
    
    // Remember scroll positions
    const selectScroll = selectList.scrollTop;
    const crudScroll = crudList.scrollTop;
    
    selectList.innerHTML = '';
    crudList.innerHTML = '';
    
    if (questions.length === 0) {
        selectList.innerHTML = '<div style="text-align:center;color:var(--text-muted);padding:20px;">ไม่มีคำถามในระบบ</div>';
        crudList.innerHTML = '<div style="text-align:center;color:var(--text-muted);padding:20px;">ไม่มีคำถามในระบบ</div>';
        return;
    }
    
    questions.forEach((q, idx) => {
        const isCurrent = parseInt(q.id) === activeQuestionId;
        
        // 1. Selector row
        const selRow = document.createElement('div');
        selRow.className = `select-q-row-v2 ${isCurrent ? 'active' : ''}`;
        selRow.onclick = () => selectQuestion(q.id);
        selRow.innerHTML = `
            <span class="q-num-v2">#${idx + 1}</span>
            <div class="q-body-text-v2">${q.question_text}</div>
            <button class="btn-display-v2 ${isCurrent ? 'btn-live' : 'btn-standby'}">
                ${isCurrent ? 'LIVE' : 'SHOW'}
            </button>
        `;
        selectList.appendChild(selRow);
        
        // 2. CRUD row
        const crudRow = document.createElement('div');
        crudRow.className = 'crud-q-row-v2';
        crudRow.innerHTML = `
            <div class="crud-body-text-v2">${q.question_text}</div>
            <div class="crud-actions-v2">
                <button class="action-edit-btn-v2" onclick="editQuestion(${q.id}, '${q.question_text.replace(/'/g, "\\'")}')">
                    <ion-icon name="create-outline"></ion-icon> แก้ไข
                </button>
                <button class="action-delete-btn-v2" onclick="deleteQuestion(${q.id})">
                    <ion-icon name="trash-outline"></ion-icon> ลบ
                </button>
            </div>
        `;
        crudList.appendChild(crudRow);
    });
    
    // Restore scroll positions
    selectList.scrollTop = selectScroll;
    crudList.scrollTop = crudScroll;
}

function selectQuestion(id) {
    const fd = new FormData();
    fd.append('question_id', id);
    fetch('api.php?action=set_question', { method: 'POST', body: fd })
        .then(() => syncAdminState());
}

function updateLevel(type) {
    const fd = new FormData();
    fd.append('type', type);
    fetch('api.php?action=update_level', { method: 'POST', body: fd })
        .then(() => syncAdminState());
}

function resetGame() {
    if (!confirm("ต้องการรีเซ็ตสเตจ คะแนน และเวลากลับมาเริ่มต้นใหม่หรือไม่?")) return;
    fetch('api.php?action=reset_game', { method: 'POST' })
        .then(() => syncAdminState());
}

function controlTimer(timerAction) {
    const fd = new FormData();
    fd.append('timer_action', timerAction);
    
    if (timerAction === 'pause') {
        const timerText = document.getElementById('admin-timer-display').textContent;
        const pts = timerText.split(':');
        const rem = parseInt(pts[0]) * 60 + parseInt(pts[1]);
        fd.append('remaining_seconds', rem);
    }
    
    fetch('api.php?action=timer_control', { method: 'POST', body: fd })
        .then(() => syncAdminState());
}

function changeTimerDuration(seconds) {
    const fd = new FormData();
    fd.append('timer_action', 'set_duration');
    fd.append('duration', seconds);
    
    fetch('api.php?action=timer_control', { method: 'POST', body: fd })
        .then(() => syncAdminState());
}

function addQuestion() {
    const textEl = document.getElementById('input-new-question');
    const text = textEl.value.trim();
    if (!text) return alert("กรุณาใส่หัวข้อคำถาม");
    
    const fd = new FormData();
    fd.append('question_text', text);
    
    fetch('api.php?action=add_question', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                textEl.value = '';
                syncAdminState();
            } else {
                alert(data.message);
            }
        });
}

function editQuestion(id, currentText) {
    const newText = prompt("แก้ไขคำถาม:", currentText);
    if (newText === null || newText.trim() === '') return;
    
    const fd = new FormData();
    fd.append('id', id);
    fd.append('question_text', newText.trim());
    
    fetch('api.php?action=edit_question', { method: 'POST', body: fd })
        .then(() => syncAdminState());
}

function deleteQuestion(id) {
    if (!confirm("ลบคำถามนี้ออกจากคลังหรือไม่?")) return;
    
    const fd = new FormData();
    fd.append('id', id);
    
    fetch('api.php?action=delete_question', { method: 'POST', body: fd })
        .then(() => syncAdminState());
}

// Initial pull and setup poll
syncAdminState();
setInterval(syncAdminState, 1000);
</script>

</body>
</html>
