<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ระบบเช็คชื่อรายงานตัว 📍</title>
    <link href="style.css?v=<?=time();?>" rel="stylesheet" type="text/css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;700&family=Outfit:wght@400;600;800&family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>

<!-- Background Geometric Elements -->
<div class="bg-shapes">
    <div class="shape circle"></div>
    <div class="shape triangle"></div>
    <div class="shape square"></div>
    <div class="shape cross"></div>
</div>

<!-- 🛸 TOP HEADER -->
<header class="top-header">
    <a href="index.php" class="brand-logo">
        <ion-icon name="rocket"></ion-icon>
        MISSION CONTROL
    </a>

    <a href="dashboard.php" class="user-profile-pill" style="text-decoration: none;">
        <?php
        $session_av = $_SESSION['avatar_img'] ?? 'dog.png';
        $av_mapping = ['dog.png' => '0', 'cat.png' => '1', 'bear.png' => '2', 'boy.png' => '3', 'girl.png' => '4'];
        $fallback_seed = $av_mapping[$session_av] ?? '1';
        ?>
        <img src="assets/avatar/<?php echo htmlspecialchars($session_av); ?>" 
             onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=<?php echo $fallback_seed; ?>'" class="user-avatar" alt="Avatar">
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['real_name'] ?? $_SESSION['username']); ?></span>
            <span class="user-score"><?php echo number_format($_SESSION['score'] ?? 0); ?> PTS</span>
        </div>
    </a>
</header>

<main class="main-container" style="padding-top: 100px; padding-bottom: 100px;">
    <div class="auth-panel" style="max-width: 650px; margin: 0 auto; padding: 30px;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="index.php" class="btn-action" style="display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 0.95rem; background: rgba(255,255,255,0.05); padding: 8px 16px; border-radius: 8px;">
                <ion-icon name="arrow-back-outline"></ion-icon>
                <span>กลับหน้าหลัก</span>
            </a>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <button onclick="confirmReset()" class="btn-action" style="display: inline-flex; align-items: center; gap: 6px; color: var(--neon-red); cursor: pointer; font-family: 'Chakra Petch', sans-serif; font-size: 0.95rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); padding: 8px 16px; border-radius: 8px; font-weight: bold; border: none; transition: all 0.2s;">
                    <ion-icon name="trash-outline"></ion-icon>
                    <span>รีเซ็ตการเช็คชื่อทั้งหมด 🔄</span>
                </button>
            <?php endif; ?>
        </div>

        <h2 style="text-align: center; font-family: 'Chakra Petch', sans-serif; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 8px; color: #fff;">
            <ion-icon name="finger-print-outline" style="color: var(--neon-cyan); font-size: 2rem;"></ion-icon>
            <span>รายงานตัวเข้าร่วมเล่นเกม 📍</span>
        </h2>
        <p style="text-align: center; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 28px; font-family: 'Chakra Petch', sans-serif;">
            รายชื่อสายลับที่ลงทะเบียนร่วมเล่นในวันนี้ (Real-time Attendance)
        </p>

        <div style="text-align: center; margin-bottom: 30px;">
            <button onclick="triggerManualCheckin()" class="btn-submit" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: auto; padding: 14px 28px; background: linear-gradient(135deg, var(--neon-cyan), #0891b2); color: #000; font-weight: 800; font-size: 1.1rem; border-radius: 14px; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(6,182,212,0.3); font-family: 'Chakra Petch', sans-serif; transition: all 0.2s ease;">
                <ion-icon name="checkbox-outline" style="font-size: 1.4rem;"></ion-icon>
                <span>กดเช็คชื่อรายงานตัวสายลับ 📍</span>
            </button>
        </div>

        <div id="attendance-list-wrapper" class="social-list-wrapper" style="max-height: 480px; overflow-y: auto; padding-right: 5px;">
            <div class="empty-state-text">กำลังโหลดข้อมูลรายชื่อ...</div>
        </div>
    </div>
</main>

<!-- 📱 BOTTOM FLOATING DOCK -->
<div class="bottom-dock-wrapper">
    <nav class="bottom-dock" id="bottom-dock">
        <a href="index.php" class="dock-btn" style="text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <ion-icon name="home-outline"></ion-icon>
            <span>HOME</span>
        </a>
        <a href="index.php#tab-leaderboard" class="dock-btn" style="text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <ion-icon name="trophy-outline"></ion-icon>
            <span>RANK</span>
        </a>
        <a href="attendance.php" class="dock-btn active" style="text-decoration: none; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <ion-icon name="checkbox"></ion-icon>
            <span>CHECK-IN</span>
        </a>
    </nav>
</div>

<script>
function loadAttendanceList() {
    fetch('api_attendance.php')
        .then(r => r.json())
        .then(data => {
            const wrapper = document.getElementById('attendance-list-wrapper');
            if (!wrapper) return;
            if (data.status !== 'success') {
                wrapper.innerHTML = `<div class="empty-state-text" style="color: var(--neon-pink);">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>`;
                return;
            }
            if (data.players.length === 0) {
                wrapper.innerHTML = `<div class="empty-state-text">ยังไม่มีผู้ลงทะเบียนร่วมเล่นในวันนี้</div>`;
                return;
            }
            
            wrapper.innerHTML = data.players.map(p => {
                const avSeed = p.avatar_file === 'dog.png' ? '0' : (p.avatar_file === 'cat.png' ? '1' : (p.avatar_file === 'bear.png' ? '2' : (p.avatar_file === 'boy.png' ? '3' : '4')));
                return `
                    <div class="social-user-row" style="margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.05); padding: 12px; border-radius: 16px; background: rgba(255,255,255,0.02); display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="avatar-wrapper" style="position: relative; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); border-radius: 50%; overflow: hidden; border: 2px solid ${p.is_online ? 'var(--neon-green)' : 'rgba(255,255,255,0.1)'};">
                                <img src="assets/avatar/${p.avatar_file}" class="row-avatar" style="width: 80%; height: 80%; object-fit: contain;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=${avSeed}'">
                            </div>
                            <div class="row-user-details" style="display: flex; flex-direction: column;">
                                <div class="row-user-name" style="font-weight: bold; color: #fff; font-size: 1rem; font-family: 'Chakra Petch', sans-serif;">${p.real_name}</div>
                                <div class="row-user-username" style="font-size: 0.8rem; color: var(--text-muted);">@${p.username}</div>
                            </div>
                        </div>
                        <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end;">
                            <div style="font-size: 0.8rem; color: var(--neon-cyan); font-weight: bold;">📍 เช็คชื่อ ${p.checkin_time}</div>
                            <div style="font-size: 0.8rem; font-weight: bold; color: ${p.is_online ? 'var(--neon-green)' : '#94a3b8'}; margin-top: 4px;">
                                ${p.is_online ? '● ONLINE' : '○ OFFLINE'}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        });
}

function triggerManualCheckin() {
    fetch('api_attendance.php?action=checkin')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                if (navigator.vibrate) navigator.vibrate(30);
                alert('เช็คชื่อรายงานตัวเข้าเล่นสำเร็จ! 📍');
                loadAttendanceList();
            } else {
                alert('ไม่สามารถเช็คชื่อได้: ' + data.message);
            }
        });
}

function confirmReset() {
    if (confirm('ต้องการรีเซ็ตเช็คชื่อของทุกคนกลับเป็นยังไม่ได้รายงานตัวหรือไม่? ข้อมูลประวัติเช็คชื่อทั้งหมดวันนี้จะถูกเคลียร์')) {
        fetch('api_attendance.php?action=reset')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('รีเซ็ตรายการเช็คชื่อสำเร็จแล้ว! 🔄');
                    loadAttendanceList();
                } else {
                    alert('รีเซ็ตไม่สำเร็จ: ' + data.message);
                }
            });
    }
}

// Load list on start
loadAttendanceList();
// Auto update every 5 seconds
setInterval(loadAttendanceList, 5000);
</script>
</body>
</html>
