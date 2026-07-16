// ═══════════════════════════════════════════════════════════════
//  ⚙️ TABOO GAME ENGINE & CLIENT LOGIC
// ═══════════════════════════════════════════════════════════════

let roomCode = null;
let isHost = false;
let currentMyRole = 'A';
let gameDurationSeconds = 120;
let currentActiveGameMode = 'single';

let tabooLobbyPollInterval = null;
let tabooGamePollInterval = null;
let tabooIsReady = false;
let tabooMyWord = '';
let isSelfCaught = false;

let audioCtx = null;

function initAudio() {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === 'suspended') audioCtx.resume();
}

function playTick() {
    try {
        initAudio();
        if (!audioCtx) return;
        const now = audioCtx.currentTime;
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(850, now);
        gain.gain.setValueAtTime(0.04, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.05);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start(now);
        osc.stop(now + 0.05);
    } catch (e) {}
}

function playCatchSound() {
    try {
        initAudio();
        if (!audioCtx) return;
        const now = audioCtx.currentTime;
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(220, now);
        osc.frequency.linearRampToValueAtTime(110, now + 0.3);
        gain.gain.setValueAtTime(0.12, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start(now);
        osc.stop(now + 0.3);
    } catch (e) {}
}

function toggleTabooTheme() {
    const body = document.body;
    body.classList.toggle('theme-white');
    localStorage.setItem('taboo-theme', body.classList.contains('theme-white') ? 'white' : 'cyber');
    playTick();
}

function switchScreen(id) {
    const screens = ['taboo-setup-entry', 'taboo-lobby-waiting', 'screen-play'];
    screens.forEach(s => {
        const el = document.getElementById(s);
        if (el) el.style.display = (s === id) ? 'flex' : 'none';
    });
    // Hide header panel during active play to maximize screen real estate
    const header = document.querySelector('.game-header');
    if (header) header.style.display = (id === 'screen-play') ? 'none' : 'flex';
}

// 👑 HOST ROOM CREATION
function createTabooRoom() {
    initAudio();
    const fd = new FormData();
    fd.append('player_name', SESSION_NAME);
    fd.append('avatar_icon', SESSION_AVATAR);

    fetch('api.php?action=create', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                roomCode = data.room_code;
                isHost = true;
                tabooIsReady = true;
                currentMyRole = 'A';
                document.getElementById('host-config-panel').style.display = 'flex';
                showTabooLobbyWaiting();
                startTabooLobbyPoll();
                highlightRoleButtons();
            } else {
                showCustomAlert(data.message || 'เกิดข้อผิดพลาดในการสร้างห้อง');
            }
        });
}

// 👥 JOIN ROOM
function joinTabooRoom() {
    initAudio();
    const codeEl = document.getElementById('taboo-room-code');
    const code = codeEl ? codeEl.value.trim() : '';
    if (!code || code.length !== 4) {
        showCustomAlert('กรุณากรอกรหัสห้อง 4 หลัก');
        if (codeEl) codeEl.focus();
        return;
    }

    const fd = new FormData();
    fd.append('player_name', SESSION_NAME);
    fd.append('avatar_icon', SESSION_AVATAR);
    fd.append('room_code', code);

    fetch('api.php?action=join', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                roomCode = code;
                isHost = false;
                tabooIsReady = false;
                currentMyRole = 'A';
                document.getElementById('host-config-panel').style.display = 'none';
                showTabooLobbyWaiting();
                startTabooLobbyPoll();
                highlightRoleButtons();
            } else {
                showCustomAlert(data.message);
            }
        });
}

function showTabooLobbyWaiting() {
    switchScreen('taboo-lobby-waiting');
    document.getElementById('taboo-room-title').textContent = `ห้อง: ${roomCode} ${isHost ? '👑' : ''}`;
    document.getElementById('taboo-ready-btn').style.display = isHost ? 'none' : 'block';
    document.getElementById('taboo-start-btn').style.display = isHost ? 'block' : 'none';
}

function setTabooMode(mode) {
    currentActiveGameMode = mode;
    playTick();
    ['single', 'team_3v3', 'team_6v6'].forEach(m => {
        const btn = document.getElementById(`mode-${m}`);
        if (btn) btn.classList.toggle('active', m === mode);
    });
    updateHostSettings();
}

function setTabooTime(seconds) {
    gameDurationSeconds = seconds;
    playTick();
    ['120', '300', '600'].forEach(s => {
        const btn = document.getElementById(`t-${s}`);
        if (btn) btn.classList.toggle('active', parseInt(s) === seconds);
    });
    updateHostSettings();
}

function updateHostSettings() {
    if (!isHost) return;
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('game_mode', currentActiveGameMode);
    fd.append('seconds_remaining', gameDurationSeconds);
    fetch('api.php?action=set_config', { method: 'POST', body: fd })
        .then(() => triggerImmediateLobbyPoll());
}

function switchMyTabooRole(role) {
    currentMyRole = role;
    highlightRoleButtons();
    playTick();
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('player_name', SESSION_NAME);
    fd.append('role', role);
    fetch('api.php?action=switch_role', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'error') {
                showCustomAlert(data.message);
                // Switch back role in client memory
                currentMyRole = 'spectator';
                highlightRoleButtons();
            }
            triggerImmediateLobbyPoll();
        });
}

function highlightRoleButtons() {
    ['A', 'B', 'spec', 'ctrl'].forEach(r => {
        const btn = document.getElementById(`role-btn-${r}`);
        let match = false;
        if (r === 'spec' && currentMyRole === 'spectator') match = true;
        else if (r === 'ctrl' && currentMyRole === 'controller') match = true;
        else if (r === currentMyRole) match = true;
        if (btn) btn.classList.toggle('active', match);
    });
}

function toggleTabooReady() {
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('player_name', SESSION_NAME);
    fetch('api.php?action=ready', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                tabooIsReady = !tabooIsReady;
                const btn = document.getElementById('taboo-ready-btn');
                if (btn) {
                    btn.classList.toggle('btn-clay-green', tabooIsReady);
                    btn.classList.toggle('btn-clay-blue', !tabooIsReady);
                    btn.innerHTML = tabooIsReady ? 'พร้อมแล้ว ✅' : 'พร้อมเข้าแข่ง 👍';
                }
                playTick();
                triggerImmediateLobbyPoll();
            }
        });
}

// 🔗 COPY INVITE LINK
function copyInviteLink() {
    if (!roomCode) return;
    const inviteLink = `${window.location.origin}${window.location.pathname}?room=${roomCode}`;
    
    navigator.clipboard.writeText(inviteLink).then(() => {
        showCustomAlert('คัดลอกลิงก์เชิญเรียบร้อยแล้ว!', 'success');
    }).catch(err => {
        // Fallback for browsers that don't support clipboard API
        const dummy = document.createElement("input");
        document.body.appendChild(dummy);
        dummy.value = inviteLink;
        dummy.select();
        document.execCommand("copy");
        document.body.removeChild(dummy);
        showCustomAlert('คัดลอกลิงก์เชิญเรียบร้อยแล้ว!', 'success');
    });
}

function triggerImmediateLobbyPoll() {
    if (!roomCode) return;
    fetch(`api.php?action=poll_taboo&room_code=${roomCode}`)
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'success') return;

            if (data.game_status === 'playing') {
                if (tabooLobbyPollInterval) clearInterval(tabooLobbyPollInterval);
                tabooLobbyPollInterval = null;
                currentActiveGameMode = data.game_mode;
                gameDurationSeconds = data.seconds_remaining;
                const me = data.players.find(p => p.player_name === SESSION_NAME);
                if (me) {
                    tabooMyWord = me.current_word || '';
                    currentMyRole = me.is_controller == 1 ? 'controller' : (me.is_spectator == 1 ? 'spectator' : me.team_side);
                    isSelfCaught = me.is_caught == 1;
                }
                renderTabooGameplay(data.players);
                return;
            }

            currentActiveGameMode = data.game_mode;
            if (!isHost) {
                ['single', 'team_3v3', 'team_6v6'].forEach(m => {
                    const btn = document.getElementById(`mode-${m}`);
                    if (btn) btn.classList.toggle('active', data.game_mode === m);
                });
                gameDurationSeconds = data.seconds_remaining;
                ['120', '300', '600'].forEach(s => {
                    const btn = document.getElementById(`t-${s}`);
                    if (btn) btn.classList.toggle('active', parseInt(s) === data.seconds_remaining);
                });
            }

            renderLobbyPlayers(data.players, data.game_mode);

            if (isHost) {
                const competitors = data.players.filter(p => p.is_spectator == 0 && p.is_controller == 0);
                const allReady = competitors.every(p => p.is_ready == 1);
                const startBtn = document.getElementById('taboo-start-btn');
                const canStart = allReady && competitors.length >= 2;

                if (startBtn) {
                    startBtn.disabled = !canStart;
                    startBtn.style.opacity = canStart ? '1' : '0.5';
                    startBtn.innerHTML = canStart ? 'เริ่มเล่นเกม 🚀' : `รอผู้เล่นพร้อม (${competitors.filter(p=>p.is_ready==1).length}/${competitors.length})`;
                }
            }
        }).catch(err => console.error(err));
}

function triggerImmediateGamePoll() {
    if (!roomCode) return;
    fetch(`api.php?action=poll_taboo&room_code=${roomCode}`)
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'success') return;
            if (data.game_status === 'ended') {
                if (tabooGamePollInterval) clearInterval(tabooGamePollInterval);
                tabooGamePollInterval = null;
                playCatchSound();
                showTabooResults(data.players);
                return;
            }
            
            updateTabooTimerUI(data.seconds_remaining);
            
            const me = data.players.find(p => p.player_name === SESSION_NAME);
            if (me) {
                isSelfCaught = me.is_caught == 1;
            }

            if (currentMyRole === 'controller' || currentMyRole === 'spectator') {
                renderAdminGameplayList(data.players);
            } else {
                renderPlayerGameplayList(data.players);
            }
        }).catch(err => console.error(err));
}

function startTabooLobbyPoll() {
    if (tabooLobbyPollInterval) clearInterval(tabooLobbyPollInterval);
    tabooLobbyPollInterval = setInterval(() => {
        triggerImmediateLobbyPoll();
    }, 400);
}

function renderLobbyPlayers(players, gameMode) {
    const soloList = document.getElementById('solo-lobby-list');
    const teamsGrid = document.getElementById('teams-lobby-grid');
    const specContainer = document.getElementById('lobby-spectators-list');
    const ctrlContainer = document.getElementById('lobby-controllers-list');
    
    // Spec/Controller rendering
    const spectators = players.filter(p => p.is_spectator == 1);
    const controllers = players.filter(p => p.is_controller == 1);

    specContainer.textContent = spectators.length > 0 ? spectators.map(p => p.player_name).join(', ') : '-';
    ctrlContainer.textContent = controllers.length > 0 ? controllers.map(p => p.player_name).join(', ') : '-';

    if (gameMode === 'single') {
        soloList.style.display = 'flex';
        teamsGrid.style.display = 'none';
        
        const competitors = players.filter(p => p.is_spectator == 0 && p.is_controller == 0);
        soloList.innerHTML = competitors.map(p => renderPlayerCardHTML(p)).join('');
    } else {
        soloList.style.display = 'none';
        teamsGrid.style.display = 'grid';
        
        const teamSizeLimit = (gameMode === 'team_3v3') ? 3 : 6;
        
        // Render Team A Slots
        const teamAPlayers = players.filter(p => p.team_side === 'A' && p.is_spectator == 0 && p.is_controller == 0);
        let teamAHtml = '';
        for (let i = 0; i < teamSizeLimit; i++) {
            if (teamAPlayers[i]) {
                teamAHtml += renderPlayerCardHTML(teamAPlayers[i]);
            } else {
                teamAHtml += `<div class="taboo-live-card empty-slot"><span class="empty-slot-text">+ ว่าง</span></div>`;
            }
        }
        document.getElementById('team-a-slots').innerHTML = teamAHtml;

        // Render Team B Slots
        const teamBPlayers = players.filter(p => p.team_side === 'B' && p.is_spectator == 0 && p.is_controller == 0);
        let teamBHtml = '';
        for (let i = 0; i < teamSizeLimit; i++) {
            if (teamBPlayers[i]) {
                teamBHtml += renderPlayerCardHTML(teamBPlayers[i]);
            } else {
                teamBHtml += `<div class="taboo-live-card empty-slot"><span class="empty-slot-text">+ ว่าง</span></div>`;
            }
        }
        document.getElementById('team-b-slots').innerHTML = teamBHtml;
    }
}

function getAvatarUrl(p) {
    if (p.avatar_icon && p.avatar_icon.startsWith('http')) {
        return p.avatar_icon;
    }
    return 'https://api.dicebear.com/7.x/bottts/svg?seed=' + encodeURIComponent(p.player_name) + '&backgroundColor=b6e3f4,c0aede,d1c4e9';
}

function renderPlayerCardHTML(p) {
    let roleBadge = `<span class="badge-role team-a-badge">ทีม A</span>`;
    if (p.team_side === 'B') roleBadge = `<span class="badge-role team-b-badge">ทีม B</span>`;
    if (currentActiveGameMode === 'single') roleBadge = `<span class="badge-role spec-badge">Solo</span>`;

    const avatar = getAvatarUrl(p);

    // Build friend action element in lobby
    let friendHtml = '';
    if (p.user_id && p.player_name !== SESSION_NAME) {
        if (p.friendship_status === 'accepted') {
            friendHtml = `<span style="color: var(--neon-cyan); font-size: 0.72rem; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; border: 1px solid rgba(0, 240, 255, 0.15); background: rgba(0, 240, 255, 0.04); padding: 4px 8px; border-radius: 8px;" title="เป็นเพื่อนกันแล้ว"><ion-icon name="people" style="font-size: 0.95rem;"></ion-icon>เพื่อน</span>`;
        } else if (p.friendship_status === 'pending') {
            friendHtml = `<span style="color: var(--text-muted); font-size: 0.72rem; font-weight: 800; border: 1px dashed var(--card-border); padding: 4px 8px; border-radius: 8px;" title="รอการตอบรับจากเป้าหมาย">รอแอด...</span>`;
        } else {
            friendHtml = `<button onclick="sendLobbyFriendRequest(${p.user_id}, this)" class="btn-lobby-add-friend" title="แอดเพื่อน">
                <ion-icon name="person-add-outline" style="font-size: 0.9rem;"></ion-icon>
                <span>แอด</span>
            </button>`;
        }
    }

    return `
    <div class="taboo-live-card">
        <img src="${avatar}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--card-border);" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
        <div style="flex:1;min-width:0;text-align: left; margin-left: 8px;">
            <div style="font-weight:700;font-size:0.85rem;color:var(--text-main);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${p.player_name} ${p.is_host == 1 ? '👑' : ''}</div>
            <div style="margin-top:2px;">${roleBadge}</div>
        </div>
        
        <!-- Friend Action -->
        <div class="lobby-friend-action" style="margin-right: 8px; display: flex; align-items: center;">
            ${friendHtml}
        </div>

        <div class="ready-status-pill ${p.is_ready == 1 ? 'status-ready' : 'status-wait'}">
            ${p.is_ready == 1 ? 'พร้อม' : 'รอ...'}
        </div>
    </div>`;
}

function sendLobbyFriendRequest(friendId, btn) {
    const fd = new FormData();
    fd.append('friend_id', friendId);
    fetch('../../api_social.php?action=add_friend', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                btn.outerHTML = `<span style="color: var(--text-muted); font-size: 0.72rem; font-weight: 800; border: 1px dashed var(--card-border); padding: 4px 8px; border-radius: 8px;">รอแอด...</span>`;
                // Poll immediately to update statuses
                triggerImmediateLobbyPoll();
            } else {
                showCustomAlert(data.message || 'เกิดข้อผิดพลาดในการแอดเพื่อน');
            }
        });
}

function processStartTabooGame() {
    fetch(`api.php?action=players&room_code=${roomCode}`)
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'success') return;
            const competitors = data.players.filter(p => p.is_spectator == 0 && p.is_controller == 0);
            
            // Shuffle forbidden words pool
            const words = [...WORD_POOL];
            for (let i = words.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [words[i], words[j]] = [words[j], words[i]];
            }
            
            const assignments = data.players.map((p, idx) => {
                const isCompetitor = (p.is_spectator == 0 && p.is_controller == 0);
                return {
                    player_name: p.player_name,
                    word: isCompetitor ? words[idx % words.length] : ''
                };
            });
            
            const fd = new FormData();
            fd.append('room_code', roomCode);
            fd.append('assignments', JSON.stringify(assignments));
            fetch('api.php?action=start_taboo', { method: 'POST', body: fd })
                .then(() => triggerImmediateLobbyPoll());
        });
}

function renderTabooGameplay(players) {
    switchScreen('screen-play');
    document.getElementById('taboo-hud-room').innerHTML = `<ion-icon name="people-outline"></ion-icon>&nbsp;ห้อง: ${roomCode}`;
    document.getElementById('end-game-btn').style.display = isHost ? 'block' : 'none';

    updateTabooTimerUI(gameDurationSeconds);
    const cardBox = document.getElementById('card-box');
    if (!cardBox) return;

    if (currentMyRole === 'controller') {
        // GM Viewport
        cardBox.style.padding = '20px';
        cardBox.style.height = '100%';
        cardBox.style.overflowY = 'auto';
        cardBox.style.flexDirection = 'column';
        cardBox.style.justifyContent = 'space-between';
        cardBox.innerHTML = `
            <div class="fullscreen-word-box" id="player-active-screen">
                <span class="fullscreen-word-title" style="color: var(--neon-orange);">เวลาคุมเกมที่เหลือ</span>
                <span class="fullscreen-word-value" id="gm-huge-timer" style="color: var(--neon-cyan); text-shadow: 0 0 35px rgba(0, 240, 255, 0.45); font-family: var(--font-mono);">--:--</span>
                <span class="fullscreen-word-warning" style="background: rgba(255, 149, 0, 0.08); color: var(--neon-orange); border: 1px solid rgba(255, 149, 0, 0.15);">🚨 แผงควบคุมผู้คุมเกม (GM)</span>
            </div>
            <div style="width:100%;height:1px;background:var(--card-border);margin:12px 0;"></div>
            <div id="admin-live-list" style="display:flex;flex-direction:column;gap:8px;width:100%;margin-top:4px;"></div>
        `;
    } else if (currentMyRole === 'spectator') {
        // Spectator Viewport
        cardBox.style.padding = '20px';
        cardBox.style.height = '100%';
        cardBox.style.overflowY = 'auto';
        cardBox.style.flexDirection = 'column';
        cardBox.style.justifyContent = 'space-between';
        cardBox.innerHTML = `
            <div class="fullscreen-word-box" id="player-active-screen">
                <span class="fullscreen-word-title" style="color: var(--neon-cyan);">เวลาแข่งขันที่เหลือ</span>
                <span class="fullscreen-word-value" id="gm-huge-timer" style="color: var(--neon-blue); text-shadow: 0 0 35px rgba(0, 240, 255, 0.45); font-family: var(--font-mono);">--:--</span>
                <span class="fullscreen-word-warning" style="background: rgba(255, 255, 255, 0.04); color: var(--text-muted); border: 1px solid var(--card-border);">👁️ โหมดผู้เฝ้าชม (Spectator)</span>
            </div>
            <div style="width:100%;height:1px;background:var(--card-border);margin:12px 0;"></div>
            <div id="admin-live-list" style="display:flex;flex-direction:column;gap:8px;width:100%;margin-top:4px;"></div>
        `;
    } else {
        // Regular Player Viewport
        cardBox.style.padding = '20px';
        cardBox.style.height = '100%';
        cardBox.style.overflowY = 'auto';
        cardBox.style.flexDirection = 'column';
        cardBox.style.justifyContent = 'space-between';
        
        cardBox.innerHTML = `
            <div class="fullscreen-word-box" id="player-active-screen">
                <div class="font-adjuster-bar">
                    <span class="font-scale-text" style="margin-right: 4px;">ขนาดอักษร:</span>
                    <button onclick="setLobbyFontScale(1.0, this)" class="btn-font-preset ${tabooFontMultiplier === 1.0 ? 'active' : ''}">100%</button>
                    <button onclick="setLobbyFontScale(1.6, this)" class="btn-font-preset ${tabooFontMultiplier === 1.6 ? 'active' : ''}">160%</button>
                    <button onclick="setLobbyFontScale(2.0, this)" class="btn-font-preset ${tabooFontMultiplier === 2.0 ? 'active' : ''}">200%</button>
                </div>
                <span class="fullscreen-word-title">คำห้ามพูดของคุณคือ</span>
                <span class="fullscreen-word-value" id="huge-word-text">${tabooMyWord || '???'}</span>
                <span class="fullscreen-word-warning">⚠️ อย่าให้คู่แข่งเห็นเด็ดขาด!</span>
            </div>
            <div style="width:100%;height:1px;background:var(--card-border);margin:12px 0;"></div>
            <div style="width: 100%; text-align: left;">
                <span class="config-label">เป้าหมายที่คุณต้องจับผิด:</span>
                <div id="taboo-catch-list" style="display:flex;flex-direction:column;gap:8px;width:100%;margin-top:4px;"></div>
            </div>
        `;
    }
    startTabooGamePoll();
}

function startTabooGamePoll() {
    if (tabooGamePollInterval) clearInterval(tabooGamePollInterval);
    tabooGamePollInterval = setInterval(() => {
        triggerImmediateGamePoll();
    }, 350);
}

function updateTabooTimerUI(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    const timeText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    
    const timerHud = document.getElementById('taboo-timer-hud');
    if (timerHud) timerHud.textContent = `เวลา: ${timeText}`;
    
    const gmHugeTimer = document.getElementById('gm-huge-timer');
    if (gmHugeTimer) gmHugeTimer.textContent = timeText;
}

function renderAdminGameplayList(players) {
    const listContainer = document.getElementById('admin-live-list');
    if (!listContainer) return;
    
    // Only display non-spectators and non-controllers
    const activeTargets = players.filter(p => p.is_spectator == 0 && p.is_controller == 0);
    
    listContainer.innerHTML = activeTargets.map(p => {
        const teamLabel = (currentActiveGameMode !== 'single') ? `[ทีม ${p.team_side}] ` : '';
        const caughtClass = p.is_caught == 1 ? 'is-dead' : '';
        const avatar = getAvatarUrl(p);
        
        return `
        <div class="gm-player-row ${caughtClass}">
            <img src="${avatar}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--card-border);" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
            <div class="gm-player-meta">
                <div class="gm-player-name">${teamLabel}${p.player_name}</div>
            </div>
            <div>
                ${p.is_caught == 1 
                    ? `<span style="font-size:0.75rem;color:var(--danger-red);font-weight:bold;">💥 ตายแล้ว</span>`
                    : (currentMyRole === 'controller' 
                        ? `<button onclick="catchTabooPlayer('${p.player_name}')" class="btn-gm-catch">🚨 จับผิด!</button>`
                        : `<span style="font-size:0.75rem;color:var(--success-green);font-weight:bold;">✅ กำลังเล่น</span>`
                      )
                }
            </div>
        </div>`;
    }).join('');
}

function renderPlayerGameplayList(players) {
    // 1. If self is dead, replace word box layout completely
    const wordBox = document.getElementById('player-active-screen');
    if (isSelfCaught && wordBox) {
        wordBox.className = 'caught-announcement-box';
        wordBox.innerHTML = `
            <span class="caught-announcement-title">💥 คัดออก!</span>
            <span class="caught-announcement-sub">คุณเผลอพูดคำต้องห้าม หรือมีคนแอบจับผิดคุณได้แล้ว</span>
        `;
    }

    const listContainer = document.getElementById('taboo-catch-list');
    if (!listContainer) return;
    
    // 2. Select opponents
    let opponents = players.filter(p => p.player_name !== SESSION_NAME && p.is_spectator == 0 && p.is_controller == 0);
    if (currentActiveGameMode !== 'single') {
        opponents = players.filter(p => p.team_side !== currentMyRole && p.is_spectator == 0 && p.is_controller == 0);
    }
    
    if (opponents.length === 0) {
        listContainer.innerHTML = `<div style="font-size:0.8rem;color:var(--text-muted);padding:8px;text-align:center;">ไม่มีฝั่งตรงข้ามในสนาม</div>`;
        return;
    }

    listContainer.innerHTML = opponents.map(p => {
        const teamLabel = (currentActiveGameMode !== 'single') ? `[ทีม ${p.team_side}] ` : '';
        const caughtClass = p.is_caught == 1 ? 'is-dead' : '';
        const avatar = getAvatarUrl(p);
        
        return `
        <div class="gm-player-row ${caughtClass}" style="padding: 8px 12px;">
            <img src="${avatar}" style="width:30px;height:30px;border-radius:50%;object-fit:cover;border:1px solid var(--card-border);" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
            <div class="gm-player-meta" style="margin-left: 6px;">
                <div class="gm-player-name" style="font-size: 0.8rem;">${teamLabel}${p.player_name}</div>
                <div style="font-size:0.7rem;color:var(--text-dim);margin-top:2px;">${p.is_caught == 1 ? '❌ โดนจับผิดแล้ว' : 'กำลังคุย...'}</div>
            </div>
            <div>
                ${p.is_caught == 1
                    ? `<div class="ready-status-pill status-wait" style="font-size: 0.65rem; background:rgba(255,69,58,0.1); color:var(--danger-red);">คัดออก</div>`
                    : `<span style="font-size: 0.75rem; color: var(--success-green); font-weight: bold; display: flex; align-items: center; gap: 4px;"><span style="display:inline-block; width:6px; height:6px; background:var(--success-green); border-radius:50%; box-shadow:0 0 8px var(--success-green);"></span>กำลังเล่น</span>`
                }
            </div>
        </div>`;
    }).join('');
}

function catchTabooPlayer(playerName) {
    playCatchSound();
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('target_name', playerName);
    fetch('api.php?action=catch_player', { method: 'POST', body: fd })
        .then(() => triggerImmediateGamePoll());
}

function hostEndTabooGame() {
    if (!confirm('ยืนยันจบแมตช์นี้?')) return;
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fetch('api.php?action=end_taboo', { method: 'POST', body: fd })
        .then(() => triggerImmediateGamePoll());
}

// 🏆 SCOREBOARD RESULT RENDER
function showTabooResults(players) {
    if (tabooGamePollInterval) { clearInterval(tabooGamePollInterval); tabooGamePollInterval = null; }
    const savedRoomCode = roomCode;
    const competitors = players.filter(p => p.is_spectator == 0 && p.is_controller == 0);
    const caughtCount = competitors.filter(p => p.is_caught == 1).length;
    const survivorCount = competitors.length - caughtCount;

    const cardBox = document.getElementById('card-box');
    if (!cardBox) return;

    cardBox.style.padding = '20px';
    cardBox.style.height = 'auto';
    cardBox.style.overflowY = 'auto';
    cardBox.style.flexDirection = 'column';
    cardBox.style.justifyContent = 'flex-start';

    let scoreSummaryTitleHtml = "";
    if (currentActiveGameMode !== 'single') {
        const teamACaught = competitors.filter(p => p.team_side === 'A' && p.is_caught == 1).length;
        const teamBCaught = competitors.filter(p => p.team_side === 'B' && p.is_caught == 1).length;
        scoreSummaryTitleHtml = `
            <div style="font-size:0.85rem;font-weight:700;color:var(--text-muted);margin-bottom:6px;">คะแนนรวมผู้เล่นที่โดนจับคำห้ามพูด</div>
            <div style="font-size:1.15rem;font-weight:800;color:var(--text-main);">ทีม A คัดออก: <span style="color:var(--neon-pink);">${teamACaught}</span> | ทีม B คัดออก: <span style="color:var(--neon-blue);">${teamBCaught}</span></div>
        `;
    } else {
        scoreSummaryTitleHtml = `
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:2px;">ผู้รอดชีวิตทั้งหมด</div>
            <div style="font-size:2.5rem;font-weight:900;color:var(--success-green);line-height:1; font-family: var(--font-mono);">${survivorCount} <span style="font-size:1.1rem;color:var(--text-muted);">/ ${competitors.length} คน</span></div>
        `;
    }

    cardBox.innerHTML = `
        <div style="text-align:center;padding:10px 0; width: 100%;">${scoreSummaryTitleHtml}</div>
        <div style="width:100%;height:1px;background:var(--card-border);margin:10px 0;"></div>
        <div style="display:flex;flex-direction:column;gap:6px;width:100%;">
            ${players.map(p => {
                let badge = "";
                if (p.is_controller == 1) badge = `<span class="badge-role ctrl-badge">ผู้คุม</span>`;
                else if (p.is_spectator == 1) badge = `<span class="badge-role spec-badge">ผู้ชม</span>`;
                else if (p.is_caught == 1) badge = `<span class="badge-role" style="background:rgba(255,69,58,0.15);color:var(--danger-red);">❌ โดนคัดออก</span>`;
                else badge = `<span class="badge-role" style="background:rgba(48,209,88,0.15);color:var(--success-green);">🏆 ชนะ/รอด</span>`;

                const teamInfo = (currentActiveGameMode !== 'single' && p.is_spectator == 0 && p.is_controller == 0) ? `[ทีม ${p.team_side}] ` : '';
                const avatar = getAvatarUrl(p);
                
                return `
                <div class="taboo-live-card">
                    <img src="${avatar}" style="width:30px;height:30px;border-radius:50%;object-fit:cover;border:1px solid var(--card-border);" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                    <div style="flex:1;min-width:0;text-align:left; margin-left: 8px;">
                        <div style="font-weight:700;font-size:0.8rem;color:var(--text-main);">${teamInfo}${p.player_name} ${p.is_host == 1 ? '👑' : ''}</div>
                        <div style="font-size:0.7rem;color:var(--text-muted);margin-top:2px;">คำห้าม: <span style="color:var(--neon-orange);font-weight:bold;">${p.current_word || '-'}</span></div>
                    </div>
                    <div>${badge}</div>
                </div>`;
            }).join('')}
        </div>
        ${isHost ? `
        <button class="btn-primary btn-clay-green" onclick="restartTabooLobby()" style="width:100%;margin-top:14px;padding:12px;font-size:0.9rem;">
            เล่นแมตช์ต่อไป (ใช้ห้องเดิม) 🔄
        </button>` : `
        <div style="text-align:center;padding:12px;border-radius:12px;background:rgba(255,255,255,0.02);border:1px solid var(--card-border);font-size:0.75rem;color:var(--text-muted);width:100%;margin-top:12px;">
            ⏳ กำลังรอแอดมินรีเซ็ตห้องเพื่อเริ่มแมตช์ใหม่...
        </div>`}
    `;
    startTabooResultPoll(savedRoomCode);
}

function restartTabooLobby() {
    if (!roomCode || !isHost) return;
    const fd = new FormData();
    fd.append('room_code', roomCode);
    fetch('api.php?action=restart_taboo', { method: 'POST', body: fd }).then(() => returnToLobbyWaiting());
}

function startTabooResultPoll(code) {
    if (isHost) return;
    const resultPoll = setInterval(() => {
        fetch(`api.php?action=poll_taboo&room_code=${code}`)
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'success') { clearInterval(resultPoll); return; }
                if (data.game_status === 'setup') { clearInterval(resultPoll); returnToLobbyWaiting(); }
            }).catch(() => clearInterval(resultPoll));
    }, 1500);
}

function returnToLobbyWaiting() {
    tabooIsReady = isHost;
    switchScreen('taboo-lobby-waiting');
    document.getElementById('taboo-setup-entry').style.display = 'none';
    document.getElementById('taboo-lobby-waiting').style.display = 'flex';
    document.getElementById('taboo-room-title').textContent = `ห้อง: ${roomCode} ${isHost ? '👑' : ''}`;
    document.getElementById('taboo-ready-btn').style.display = isHost ? 'none' : 'block';
    
    // Reset ready button state
    const readyBtn = document.getElementById('taboo-ready-btn');
    if (readyBtn) {
        readyBtn.classList.remove('btn-clay-green');
        readyBtn.classList.add('btn-clay-blue');
        readyBtn.innerHTML = 'พร้อมเข้าแข่ง 👍';
    }

    const startBtn = document.getElementById('taboo-start-btn');
    if (isHost && startBtn) {
        startBtn.style.display = 'block';
        startBtn.style.opacity = '0.5';
        startBtn.innerHTML = `รอผู้เล่นพร้อม...`;
    }
    startTabooLobbyPoll();
}

function confirmExitDuringGame() {
    if (confirm('ต้องการออกจากแมตช์แข่งขัน?')) exitTabooRoom();
}

function exitTabooRoom() {
    if (tabooLobbyPollInterval) { clearInterval(tabooLobbyPollInterval); tabooLobbyPollInterval = null; }
    if (tabooGamePollInterval) { clearInterval(tabooGamePollInterval); tabooGamePollInterval = null; }
    
    if (!roomCode) {
        switchScreen('taboo-setup-entry');
        return;
    }

    const fd = new FormData();
    fd.append('room_code', roomCode);
    fd.append('player_name', SESSION_NAME);
    
    fetch('api.php?action=exit', { method: 'POST', body: fd }).finally(() => {
        roomCode = null;
        isHost = false;
        tabooIsReady = false;
        switchScreen('taboo-setup-entry');
    });
}

// ═══════════════════════════════════════════════════════════════
//  👥 LOBBY ONLINE FRIENDS INVITATION
// ═══════════════════════════════════════════════════════════════
function openFriendsInviteModal() {
    playTick();
    const listContainer = document.getElementById('taboo-online-friends-list');
    if (listContainer) {
        listContainer.innerHTML = `<div style="font-size:0.8rem; color:var(--text-muted); text-align:center; padding:16px;">กำลังดึงข้อมูลเพื่อน...</div>`;
    }
    document.getElementById('taboo-friends-modal').style.display = 'flex';
    
    fetch('../../api_social.php?action=list_friends')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const onlineFriends = data.friends.filter(f => f.is_online);
                if (onlineFriends.length === 0) {
                    listContainer.innerHTML = `<div style="font-size:0.8rem; color:var(--text-muted); text-align:center; padding:16px; width: 100%;">ไม่มีเพื่อนที่กำลังออนไลน์ 🟢 ในขณะนี้</div>`;
                } else {
                    listContainer.innerHTML = onlineFriends.map(f => `
                        <div class="taboo-live-card" style="padding: 8px 12px; background: rgba(255,255,255,0.01); border-radius: 12px;">
                            <img src="${f.avatar}" style="width:30px;height:30px;border-radius:50%;object-fit:cover;border:1px solid var(--card-border);">
                            <div style="flex:1;min-width:0;text-align: left; margin-left: 8px;">
                                <div style="font-weight:700;font-size:0.8rem;color:var(--text-main);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${f.real_name ?? f.username}</div>
                            </div>
                            <button onclick="sendTabooInvite(${f.user_id}, this)" class="btn-submit" style="width:auto;margin:0;padding:6px 12px;font-size:0.7rem;border-radius:8px;height:auto;">เชิญเข้าห้อง</button>
                        </div>
                    `).join('');
                }
            } else {
                listContainer.innerHTML = `<div style="font-size:0.8rem; color:var(--danger-red); text-align:center; padding:16px; width: 100%;">เกิดข้อผิดพลาดในการโหลด</div>`;
            }
        }).catch(() => {
            listContainer.innerHTML = `<div style="font-size:0.8rem; color:var(--danger-red); text-align:center; padding:16px; width: 100%;">เกิดข้อผิดพลาดในการโหลด</div>`;
        });
}

function closeFriendsInviteModal() {
    playTick();
    document.getElementById('taboo-friends-modal').style.display = 'none';
}

function sendTabooInvite(friendId, btn) {
    if (!roomCode) return;
    playTick();
    const fd = new FormData();
    fd.append('receiver_id', friendId);
    fd.append('room_code', roomCode);
    fd.append('game_type', 'taboo');
    
    fetch('../../api_social.php?action=send_invite', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                btn.innerHTML = 'ส่งคำเชิญแล้ว';
                btn.disabled = true;
                btn.style.opacity = '0.5';
            } else {
                showCustomAlert('ไม่สามารถส่งคำเชิญได้');
            }
        });
}

// ═══════════════════════════════════════════════════════════════
//  🔎 FONT SCALE SYSTEM FOR GAMEPLAY
// ═══════════════════════════════════════════════════════════════
let storedMultiplier = parseFloat(localStorage.getItem('taboo-font-multiplier') || '1.0');
if (![1.0, 1.6, 2.0].includes(storedMultiplier)) {
    storedMultiplier = 1.0;
}
let tabooFontMultiplier = storedMultiplier;
// Apply setting initially
document.documentElement.style.setProperty('--word-font-multiplier', tabooFontMultiplier.toFixed(1));

function setLobbyFontScale(scale, btn) {
    playTick();
    tabooFontMultiplier = scale;
    localStorage.setItem('taboo-font-multiplier', tabooFontMultiplier.toFixed(1));
    document.documentElement.style.setProperty('--word-font-multiplier', tabooFontMultiplier.toFixed(1));
    
    // Update active class on preset buttons on screen
    if (btn) {
        const container = btn.parentElement;
        if (container) {
            container.querySelectorAll('.btn-font-preset').forEach(b => b.classList.remove('active'));
        }
        btn.classList.add('active');
    }
}

// ═══════════════════════════════════════════════════════════════
//  🚨 CUSTOM ALERT SYSTEM FOR TABOO LOBBY & GAMEPLAY
// ═══════════════════════════════════════════════════════════════
function showCustomAlert(message, type = 'warning') {
    let modal = document.getElementById('custom-alert-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'custom-alert-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 15, 25, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 100000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.22s ease-in-out;
            pointer-events: none;
            padding: 20px;
        `;
        
        const content = document.createElement('div');
        content.id = 'custom-alert-content';
        content.style.cssText = `
            background: rgba(20, 27, 45, 0.9);
            border: 1px solid rgba(255, 0, 127, 0.25);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(255, 0, 127, 0.15);
            border-radius: var(--radius-md, 20px);
            padding: 26px;
            width: 100%;
            max-width: 360px;
            text-align: center;
            transform: scale(0.85);
            transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
        `;
        
        const iconContainer = document.createElement('div');
        iconContainer.style.marginBottom = '12px';
        const icon = document.createElement('ion-icon');
        icon.id = 'custom-alert-icon';
        icon.style.fontSize = '3rem';
        iconContainer.appendChild(icon);
        
        const text = document.createElement('p');
        text.id = 'custom-alert-text';
        text.style.cssText = `
            font-size: 0.92rem;
            color: var(--text-main, #f1f5f9);
            font-weight: 700;
            line-height: 1.55;
            margin-bottom: 22px;
            word-break: break-word;
        `;
        
        const btn = document.createElement('button');
        btn.id = 'custom-alert-close';
        btn.textContent = 'ตกลง';
        btn.style.cssText = `
            border: none;
            padding: 12px 24px;
            border-radius: var(--radius-sm, 14px);
            color: #fff;
            font-weight: 800;
            font-size: 0.82rem;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        `;
        
        btn.onclick = () => hideCustomAlert();
        
        content.appendChild(iconContainer);
        content.appendChild(text);
        content.appendChild(btn);
        modal.appendChild(content);
        document.body.appendChild(modal);
    }
    
    const iconEl = document.getElementById('custom-alert-icon');
    const contentEl = document.getElementById('custom-alert-content');
    const closeBtn = document.getElementById('custom-alert-close');
    
    if (type === 'success') {
        iconEl.setAttribute('name', 'checkmark-circle-outline');
        iconEl.style.color = 'var(--success-green, #30d158)';
        iconEl.style.filter = 'drop-shadow(0 0 8px rgba(48, 209, 88, 0.6))';
        contentEl.style.border = '1px solid rgba(48, 209, 88, 0.25)';
        contentEl.style.boxShadow = '0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(48, 209, 88, 0.15)';
        closeBtn.style.background = 'linear-gradient(135deg, var(--success-green, #30d158), #248a3d)';
        closeBtn.style.boxShadow = '0 4px 12px rgba(48, 209, 88, 0.3)';
    } else {
        iconEl.setAttribute('name', 'alert-circle-outline');
        iconEl.style.color = 'var(--neon-pink, #ff007f)';
        iconEl.style.filter = 'drop-shadow(0 0 8px rgba(255, 0, 127, 0.6))';
        contentEl.style.border = '1px solid rgba(255, 0, 127, 0.25)';
        contentEl.style.boxShadow = '0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(255, 0, 127, 0.15)';
        closeBtn.style.background = 'linear-gradient(135deg, var(--neon-pink, #ff007f), var(--neon-purple, #a100ff))';
        closeBtn.style.boxShadow = '0 4px 12px rgba(255, 0, 127, 0.3)';
    }
    
    document.getElementById('custom-alert-text').textContent = message;
    
    if (type === 'success') {
        playSuccessTone();
    } else {
        playAlertTone();
    }
    
    modal.style.pointerEvents = 'auto';
    modal.style.opacity = '1';
    contentEl.style.transform = 'scale(1)';
}

function hideCustomAlert() {
    playTick();
    const modal = document.getElementById('custom-alert-modal');
    if (modal) {
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        const content = document.getElementById('custom-alert-content');
        if (content) content.style.transform = 'scale(0.85)';
    }
}

function playAlertTone() {
    try {
        initAudio();
        if (audioCtx) {
            const now = audioCtx.currentTime;
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(320, now);
            osc.frequency.exponentialRampToValueAtTime(120, now + 0.18);
            gain.gain.setValueAtTime(0.05, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.18);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(now);
            osc.stop(now + 0.18);
        }
    } catch(e) {}
}

function playSuccessTone() {
    try {
        initAudio();
        if (audioCtx) {
            const now = audioCtx.currentTime;
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(523.25, now); // C5
            osc.frequency.setValueAtTime(659.25, now + 0.08); // E5
            gain.gain.setValueAtTime(0.04, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(now);
            osc.stop(now + 0.22);
        }
    } catch(e) {}
}

// ═══════════════════════════════════════════════════════════════
//  🚀 LOAD HANDLERS
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('taboo-theme');
    if (savedTheme === 'white') document.body.classList.add('theme-white');
    
    switchScreen('taboo-setup-entry');
    
    // Ping activity immediately and every 10 seconds
    fetch('../../api_social.php?action=ping_active');
    setInterval(() => {
        fetch('../../api_social.php?action=ping_active');
    }, 10000);
    
    // Auto-select room if param is set
    if (INITIAL_ROOM_PARAM && INITIAL_ROOM_PARAM.length === 4) {
        const joinBtn = document.querySelector('.btn-join');
        if (joinBtn) {
            // Quick delay to allow UI to settle before joining
            setTimeout(() => {
                joinTabooRoom();
            }, 300);
        }
    }
});
