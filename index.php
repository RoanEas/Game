<?php 
session_start();
include 'db.php'; 

// อ่านข้อมูลโครงสร้างอวตารจากไฟล์ JSON
$jsonData = file_get_contents('data/avatar_items.json');
$avatarData = json_decode($jsonData, true);

// ================= ระบบบันทึกข้อมูลตัวละครและชื่อสายลับครั้งแรก =================
if (isset($_POST['start_agent'])) {
    if (isset($_SESSION['user_id'])) {
        $real_name = $_POST['real_name'];
        $avatar_img = $_POST['selected_avatar']; 
        $uid = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("UPDATE users SET real_name = ?, avatar_img = ?, is_avatar_created = 1 WHERE id = ?");
        $stmt->bind_param("ssi", $real_name, $avatar_img, $uid);
        $stmt->execute();
        
        $_SESSION['real_name'] = $real_name;
        $_SESSION['avatar_img'] = $avatar_img;
        $_SESSION['avatar_status'] = 1; 
        
        header("Location: index.php");
        exit();
    }
}

// ================= ระบบล็อกอินเข้าสู่ระบบ (เข้าเล่นด่วน & แอดมิน) =================
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'] ?? '';
    $avatar = $_POST['selected_login_avatar'] ?? 'dog.png';
    $now = time();

    if ($username === 'admin') {
        // แอดมินต้องตรวจรหัสผ่าน
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username); 
        $stmt->execute(); 
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['real_name'] = $user['real_name'];
                $_SESSION['avatar_img'] = $user['avatar_img'];
                $_SESSION['avatar_status'] = $user['is_avatar_created']; 
                $_SESSION['score'] = $user['score'];
                
                // อัปเดตเช็คชื่อด้วย
                $update = $conn->prepare("UPDATE users SET last_checkin = ? WHERE id = ?");
                $update->bind_param("ii", $now, $user['id']);
                $update->execute();
                
                header("Location: index.php"); 
                exit();
            }
        }
        echo "<script>alert('รหัสผ่านสำหรับแอดมินไม่ถูกต้อง');</script>";
    } else {
        if (empty($username)) {
            echo "<script>alert('กรุณากรอกชื่อสายลับ');</script>";
        } else {
            // ตรวจสอบว่ามีชื่อนี้ในระบบหรือยัง
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // มีผู้ใช้อยู่แล้ว -> ล็อกอินและอัปเดตอวตาร/เช็คชื่อ
                $user = $result->fetch_assoc();
                
                $update = $conn->prepare("UPDATE users SET avatar_img = ?, last_checkin = ?, is_avatar_created = 1 WHERE id = ?");
                $update->bind_param("sii", $avatar, $now, $user['id']);
                $update->execute();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['real_name'] = $username;
                $_SESSION['avatar_img'] = $avatar;
                $_SESSION['avatar_status'] = 1;
                $_SESSION['score'] = $user['score'];
                
                header("Location: index.php");
                exit();
            } else {
                // ยังไม่มีผู้ใช้ -> สมัครสมาชิกให้อัตโนมัติและเข้าสู่ระบบทันที
                $dummy_email = $username . '_' . uniqid() . '@play.com';
                $dummy_password = password_hash('member_pass', PASSWORD_BCRYPT);
                $role = 'member';
                $score = 0;
                $is_avatar_created = 1;
                
                $insert = $conn->prepare("INSERT INTO users (username, email, password, real_name, avatar_img, is_avatar_created, role, score, last_checkin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->bind_param("sssssissi", $username, $dummy_email, $dummy_password, $username, $avatar, $is_avatar_created, $role, $score, $now);
                
                if ($insert->execute()) {
                    $new_id = $insert->insert_id;
                    
                    $_SESSION['user_id'] = $new_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;
                    $_SESSION['real_name'] = $username;
                    $_SESSION['avatar_img'] = $avatar;
                    $_SESSION['avatar_status'] = 1;
                    $_SESSION['score'] = $score;
                    
                    header("Location: index.php");
                    exit();
                } else {
                    echo "<script>alert('เกิดข้อผิดพลาดในการสร้างบัญชีเข้าเล่นเกม');</script>";
                }
            }
        }
    }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ATBASH & CAESAR GAME 🚀</title>
    <link href="style.css?v=<?=time();?>" rel="stylesheet" type="text/css">
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

<?php if (isset($_SESSION['user_id']) && $_SESSION['avatar_status'] == 0): ?>
<div class="game-overlay">
    <div class="agent-card">
        <div class="main-preview-box">
            <img id="current-agent-view" src="assets/avatar/dog.png" alt="Preview">
        </div>
        <form action="index.php" method="POST">
            <input type="hidden" name="selected_avatar" id="selected_avatar" value="dog.png">
            <div class="avatar-grid">
                <?php if(isset($avatarData['avatars'])): ?>
                    <?php foreach($avatarData['avatars'] as $index => $avatar): ?>
                        <div class="avatar-option <?php echo ($index === 0) ? 'selected' : ''; ?>" 
                             onclick="selectAgent('<?php echo $avatar['file']; ?>', '<?php echo $avatar['img_url']; ?>', this)">
                            <img src="<?php echo $avatar['img_url']; ?>" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=<?php echo $avatar['id']; ?>'">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <input type="text" name="real_name" class="agent-input" placeholder="ใส่ชื่อ นามสกุลสายลับ" required>
            <button type="submit" name="start_agent" class="btn-submit">เริ่มต้นภารกิจ</button>
        </form>
    </div>
</div>
<?php endif; ?>


<!-- 🛸 TOP HEADER -->
<header class="top-header">
    <a href="index.php" class="brand-logo">
        <ion-icon name="rocket"></ion-icon>
        MISSION CONTROL
    </a>

    <?php if(isset($_SESSION['user_id'])): ?>
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
    <?php else: ?>
        <div class="user-profile-pill">
            <ion-icon name="person-circle-outline" style="font-size:24px; color:var(--text-muted);"></ion-icon>
            <span class="visitor-badge">VISITOR</span>
        </div>
    <?php endif; ?>
</header>


<!-- 🎮 MAIN CONTENT AREA -->
<main class="main-container">

    <!-- TAB 1: GAMES -->
    <div id="tab-home" class="tab-content active">
        <div class="arcade-grid">
            
            <a href="games/senior_roulette/index.php" class="game-card card-blue" <?php if(!isset($_SESSION['user_id'])) echo 'onclick="event.preventDefault(); switchTab(\'tab-login\', document.querySelector(\'[data-tab=\\\'tab-login\\\']\'));"'; ?>>
                <ion-icon name="shuffle" class="game-icon"></ion-icon>
                <div class="game-title">สุ่มภารกิจจับคู่</div>
                <div class="game-desc">สับกองไพ่ทายปริศนาใบหน้ารุ่นพี่ ปวส.</div>
                <div class="play-pill">PLAY</div>
            </a>

            <a href="games/senior_roulette/game_music.php" class="game-card card-green" <?php if(!isset($_SESSION['user_id'])) echo 'onclick="event.preventDefault(); switchTab(\'tab-login\', document.querySelector(\'[data-tab=\\\'tab-login\\\']\'));"'; ?>>
                <ion-icon name="musical-notes" class="game-icon"></ion-icon>
                <div class="game-title">สมรภูมิทายเพลง</div>
                <div class="game-desc">ฟังเสียงท่อนฮุกออโต้จำกัดเวลาทายชื่อเพลง</div>
                <div class="play-pill">PLAY</div>
            </a>

            <a href="games/hardware_quiz/index.php" class="game-card card-orange" <?php if(!isset($_SESSION['user_id'])) echo 'onclick="event.preventDefault(); switchTab(\'tab-login\', document.querySelector(\'[data-tab=\\\'tab-login\\\']\'));"'; ?>>
                <ion-icon name="hardware-chip" class="game-icon"></ion-icon>
                <div class="game-title">ทายภาพอุปกรณ์</div>
                <div class="game-desc">วิเคราะห์ภาพฮาร์ดแวร์ ทดสอบความไว</div>
                <div class="play-pill">PLAY</div>
            </a>

            <a href="games/gacha_v2.php" class="game-card card-pink" <?php if(!isset($_SESSION['user_id'])) echo 'onclick="event.preventDefault(); switchTab(\'tab-login\', document.querySelector(\'[data-tab=\\\'tab-login\\\']\'));"'; ?>>
                <ion-icon name="dice" class="game-icon"></ion-icon>
                <div class="game-title">กาชาคัดออก</div>
                <div class="game-desc">ตู้สไลด์สายพานสุ่มไฟกระพริบ 3 ใบสุดท้าย</div>
                <div class="play-pill">PLAY</div>
            </a>

            <!-- Heads Up Cyber -->
            <a href="games/head_guess/index.php" class="game-card card-purple" <?php if(!isset($_SESSION['user_id'])) echo 'onclick="event.preventDefault(); switchTab(\'tab-login\', document.querySelector(\'[data-tab=\\\'tab-login\\\']\'));"'; ?>>
                <ion-icon name="phone-portrait" class="game-icon"></ion-icon>
                <div class="game-title">ทายคำบนหัว</div>
                <div class="game-desc">ถือโทรศัพท์ทาบหน้าผาก ใบ้คำสุดมันส์กับเพื่อนๆ</div>
                <div class="play-pill">PLAY</div>
            </a>

            <!-- Taboo Game -->
            <a href="games/taboo/index.php" class="game-card card-orange" <?php if(!isset($_SESSION['user_id'])) echo 'onclick="event.preventDefault(); switchTab(\'tab-login\', document.querySelector(\'[data-tab=\\\'tab-login\\\']\'));"'; ?>>
                <ion-icon name="ban" class="game-icon"></ion-icon>
                <div class="game-title">ปาร์ตี้คำต้องห้าม</div>
                <div class="game-desc">ห้ามพูดคำสะกดจิตแบ่งทีม 3v3 / 6v6 หรือผู้คุมจับผิด</div>
                <div class="play-pill">PLAY</div>
            </a>

            <!-- Spotify Cyber Music Player -->
            <a href="games/music_player/index.php" class="game-card card-green" <?php if(!isset($_SESSION['user_id'])) echo 'onclick="event.preventDefault(); switchTab(\'tab-login\', document.querySelector(\'[data-tab=\\\'tab-login\\\']\'));"'; ?>>
                <ion-icon name="musical-note" class="game-icon" style="color: #1db954;"></ion-icon>
                <div class="game-title">ฟังเพลงไซเบอร์</div>
                <div class="game-desc">สตรีมเพลง Youtube แบบ Spotify ค้นหาและเก็บเพลงที่ชอบ</div>
                <div class="play-pill" style="background-color: #1db954; color:#000;">STREAM</div>
            </a>

            <!-- Lightning Quiz (ปริศนาสายฟ้า) -->
            <a href="games/blitz_quiz/index.php" class="game-card card-cyan" <?php if(!isset($_SESSION['user_id'])) echo 'onclick="event.preventDefault(); switchTab(\'tab-login\', document.querySelector(\'[data-tab=\\\'tab-login\\\']\'));"'; ?>>
                <ion-icon name="flash" class="game-icon" style="color: var(--neon-cyan);"></ion-icon>
                <div class="game-title">ปริศนาสายฟ้า</div>
                <div class="game-desc">ตอบคำถามเก็บคะแนนสะสมไต่บันไดความสูง 10 ขั้น</div>
                <div class="play-pill" style="background-color: var(--neon-cyan); color:#000;">PLAY</div>
            </a>

        </div>
    </div>


    <!-- TAB 2: LEADERBOARD -->
    <div id="tab-leaderboard" class="tab-content">
        <div class="auth-panel">
            <h2>🏆 LEADERBOARD</h2>
            <table class="leaderboard-table">
                <thead><tr><th>RANK</th><th>AGENT</th><th>SCORE</th></tr></thead>
                <tbody>
                    <?php
                    $rank_sql = "SELECT username, real_name, score, avatar_img FROM users WHERE is_avatar_created = 1 ORDER BY score DESC LIMIT 5";
                    $rank_res = $conn->query($rank_sql); $rank = 1;
                    if ($rank_res && $rank_res->num_rows > 0) {
                        while($user_row = $rank_res->fetch_assoc()) {
                            ?>
                            <tr>
                                <td>#<?php echo $rank; ?></td>
                                <td>
                                    <?php
                                    $row_av = $user_row['avatar_img'] ?? 'dog.png';
                                    $av_mapping = ['dog.png' => '0', 'cat.png' => '1', 'bear.png' => '2', 'boy.png' => '3', 'girl.png' => '4'];
                                    $row_seed = $av_mapping[$row_av] ?? '1';
                                    ?>
                                    <img src="assets/avatar/<?php echo $row_av; ?>" class="rank-avatar" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=<?php echo $row_seed; ?>'">
                                    <span style="margin-left:8px; font-weight:500; color:var(--text-main);"><?php echo htmlspecialchars($user_row['real_name']); ?></span>
                                </td>
                                <td><?php echo number_format($user_row['score']); ?> PTS</td>
                            </tr>
                    <?php $rank++; } } else { echo '<tr><td colspan="3" style="text-align:center;">No data available</td></tr>'; } ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- TAB: ATTENDANCE (เช็คชื่อ) -->
    <div id="tab-attendance" class="tab-content">
        <div class="auth-panel" style="max-width: 600px; margin: 0 auto;">
            <h2 style="text-align: center; font-family: 'Chakra Petch', sans-serif; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 8px;">
                <ion-icon name="checkbox-outline" style="color: var(--neon-cyan);"></ion-icon>
                <span>รายชื่อสายลับที่เช็คชื่อวันนี้</span>
            </h2>
            <p style="text-align: center; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 24px; font-family: 'Chakra Petch', sans-serif;">
                อัปเดตความเคลื่อนไหวของเพื่อนๆ แบบเรียลไทม์ (Real-time Attendance)
            </p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div style="text-align: center; margin-bottom: 24px;">
                    <button onclick="triggerManualCheckin()" class="btn-submit" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: auto; padding: 12px 24px; background: linear-gradient(135deg, var(--neon-cyan), #0891b2); color: #000; font-weight: 800; font-size: 1rem; border-radius: 12px; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(6,182,212,0.3); font-family: 'Chakra Petch', sans-serif; transition: all 0.2s ease;">
                        <ion-icon name="finger-print-outline" style="font-size: 1.3rem;"></ion-icon>
                        <span>กดเช็คชื่อรายงานตัว 📍</span>
                    </button>
                </div>
            <?php endif; ?>

            <div id="attendance-list-wrapper" class="social-list-wrapper" style="max-height: 450px; overflow-y: auto;">
                <div class="empty-state-text">กำลังโหลดข้อมูลรายชื่อ...</div>
            </div>
        </div>
    </div>

    <!-- TAB 3: LOGIN / SIGN UP -->
    <div id="tab-login" class="tab-content">
        <div class="auth-panel" style="max-width: 500px; margin: 0 auto;">
            <h2 style="text-align: center; margin-bottom: 20px; font-family: 'Chakra Petch', sans-serif;">🚀 เข้าสู่ระบบ / เข้าเล่นเกม</h2>
            <form action="index.php" method="POST" id="login-form">
                <input type="hidden" name="selected_login_avatar" id="selected_login_avatar" value="dog.png">
                
                <div class="input-group">
                    <label style="display: block; margin-bottom: 12px; text-align: center; color: var(--neon-cyan); font-weight: bold; font-family: 'Chakra Petch', sans-serif;">เลือกตัวละครของคุณ (Select Character)</label>
                    <div class="avatar-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 20px; justify-items: center;">
                        <?php if(isset($avatarData['avatars'])): ?>
                            <?php foreach($avatarData['avatars'] as $index => $avatar): ?>
                                <div class="avatar-option <?php echo ($index === 0) ? 'selected' : ''; ?>" 
                                     style="width: 56px; height: 56px; border-radius: 50%; overflow: hidden; border: 3px solid transparent; cursor: pointer; transition: all 0.2s ease; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center;"
                                     onclick="selectLoginAvatar('<?php echo $avatar['file']; ?>', this)">
                                    <img src="<?php echo $avatar['img_url']; ?>" style="width: 80%; height: 80%; object-fit: contain;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=<?php echo $avatar['id']; ?>'">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="input-group">
                    <label>ชื่อสายลับ (รหัสนักศึกษา หรือ ชื่อเล่น)</label>
                    <input type="text" name="username" id="login-username" required style="width: 100%;" placeholder="พิมพ์ชื่อเพื่อเริ่มเล่น..." oninput="checkAdminUser(this.value)" autocomplete="off">
                </div>

                <!-- Password group (Hidden by default, shown for admin) -->
                <div class="input-group" id="password-group" style="display: none; transition: all 0.3s ease; opacity: 0; max-height: 0; overflow: hidden; margin-top: 15px;">
                    <label>รหัสผ่านผู้ดูแลระบบ (Admin Password)</label>
                    <input type="password" name="password" id="login-password" style="width: 100%;" placeholder="กรอกรหัสผ่านเพื่อเข้าใช้งาน...">
                </div>

                <button type="submit" name="login" class="btn-submit" style="margin-top: 25px; width: 100%;">เริ่มปฏิบัติภารกิจ 🚀</button>
            </form>
        </div>
    </div>

    <!-- TAB 5: SOCIAL / FRIENDS -->
    <?php if(isset($_SESSION['user_id'])): ?>
    <div id="tab-social" class="tab-content">
        <div class="social-panel-container">
            <!-- Header -->
            <div class="social-header">
                <ion-icon name="people" class="social-main-icon"></ion-icon>
                <h2>SOCIAL NETWORK</h2>
                <p>เชื่อมต่อกับสายลับคนอื่นๆ เพื่อเชิญเข้าเล่นบอร์ดปาร์ตี้</p>
            </div>

            <!-- Sub Tabber -->
            <div class="social-sub-tabber">
                <button class="sub-tab-btn active" data-subtab="friends" onclick="switchSocialSubTab('friends')">
                    <ion-icon name="people-outline"></ion-icon>
                    <span>เพื่อน (<span id="friends-count">0</span>)</span>
                </button>
                <button class="sub-tab-btn" data-subtab="requests" onclick="switchSocialSubTab('requests')">
                    <ion-icon name="mail-unread-outline"></ion-icon>
                    <span>คำขอ (<span id="requests-count">0</span>)</span>
                    <span id="requests-badge" class="badge-dot" style="display: none;"></span>
                </button>
                <button class="sub-tab-btn" data-subtab="search" onclick="switchSocialSubTab('search')">
                    <ion-icon name="search-outline"></ion-icon>
                    <span>ค้นหา</span>
                </button>
            </div>

            <!-- Sub Tab Content: Friends List -->
            <div id="subtab-content-friends" class="social-sub-content">
                <div class="friends-list-header">
                    <span class="status-legend"><span class="dot-online"></span> ออนไลน์ (<span id="online-count">0</span>)</span>
                </div>
                <div id="social-friends-list" class="social-list-wrapper">
                    <!-- JS renders this -->
                </div>
            </div>

            <!-- Sub Tab Content: Requests List -->
            <div id="subtab-content-requests" class="social-sub-content" style="display: none;">
                <div id="social-requests-list" class="social-list-wrapper">
                    <!-- JS renders this -->
                </div>
            </div>

            <!-- Sub Tab Content: Search Users -->
            <div id="subtab-content-search" class="social-sub-content" style="display: none;">
                <div class="search-box-wrapper">
                    <div class="search-input-field">
                        <ion-icon name="search-outline" class="search-icon-inside"></ion-icon>
                        <input type="text" id="social-search-query" placeholder="ค้นหาชื่อผู้เล่นหรือรหัสประจำตัว..." oninput="onSearchInput(this)">
                    </div>
                </div>
                <div id="social-search-results" class="social-list-wrapper">
                    <!-- JS renders this -->
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</main>


<!-- 📱 BOTTOM FLOATING DOCK -->
<div class="bottom-dock-wrapper">
    <nav class="bottom-dock" id="bottom-dock">
        <div class="dock-slider" id="dock-slider"></div>
        
        <button class="dock-btn active" data-tab="tab-home" onclick="switchTab('tab-home', this)">
            <ion-icon name="home"></ion-icon>
            <span>HOME</span>
        </button>

        <button class="dock-btn" data-tab="tab-leaderboard" onclick="switchTab('tab-leaderboard', this)">
            <ion-icon name="trophy"></ion-icon>
            <span>RANK</span>
        </button>

        <button class="dock-btn" data-tab="tab-attendance" onclick="switchTab('tab-attendance', this)">
            <ion-icon name="checkbox-outline"></ion-icon>
            <span>CHECK-IN</span>
        </button>

        <?php if(isset($_SESSION['user_id'])): ?>
            <button class="dock-btn" data-tab="tab-social" onclick="switchTab('tab-social', this)">
                <ion-icon name="people-outline"></ion-icon>
                <span>SOCIAL</span>
            </button>
        <?php endif; ?>

        <?php if(!isset($_SESSION['user_id'])): ?>
            <button class="dock-btn" data-tab="tab-login" onclick="switchTab('tab-login', this)">
                <ion-icon name="log-in-outline"></ion-icon>
                <span>LOGIN</span>
            </button>
        <?php endif; ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="admin.php" class="dock-btn" style="text-decoration: none; color: #facc15;">
                <ion-icon name="settings"></ion-icon>
                <span>ADMIN</span>
            </a>
        <?php endif; ?>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="index.php?logout=1" class="dock-btn" style="text-decoration: none; color: var(--neon-pink);">
                <ion-icon name="log-out-outline"></ion-icon>
                <span>LOGOUT</span>
            </a>
        <?php endif; ?>
    </nav>
</div>


<script>
// ═══════════════════════════════════════════════════════════════
//  LOGIN AVATAR SELECTION & ADMIN PASSWORD TRIGGER
// ═══════════════════════════════════════════════════════════════
function selectLoginAvatar(fileName, element) {
    document.getElementById('selected_login_avatar').value = fileName;
    document.querySelectorAll('#login-form .avatar-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
}

function checkAdminUser(value) {
    const passwordGroup = document.getElementById('password-group');
    const passwordInput = document.getElementById('login-password');
    if (value.trim().toLowerCase() === 'admin') {
        passwordGroup.style.display = 'block';
        setTimeout(() => {
            passwordGroup.style.opacity = '1';
            passwordGroup.style.maxHeight = '100px';
        }, 10);
        passwordInput.required = true;
    } else {
        passwordGroup.style.opacity = '0';
        passwordGroup.style.maxHeight = '0';
        passwordInput.required = false;
        passwordInput.value = '';
        setTimeout(() => {
            passwordGroup.style.display = 'none';
        }, 300);
    }
}

// ═══════════════════════════════════════════════════════════════
//  AVATAR SELECTION (REGISTRATION COMPLETE / FIRST RUN)
// ═══════════════════════════════════════════════════════════════
function selectAgent(fileName, imgUrl, element) {
    document.getElementById('selected_avatar').value = fileName;
    const viewImg = document.getElementById('current-agent-view');
    viewImg.src = imgUrl;
    viewImg.onerror = function() { this.src = element.querySelector('img').src; };
    
    document.querySelectorAll('.avatar-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
}

// ═══════════════════════════════════════════════════════════════
//  ANIMATED DOCK SLIDER
// ═══════════════════════════════════════════════════════════════
function moveSlider(button) {
    const slider = document.getElementById('dock-slider');
    if (!slider || !button) return;
    
    const dock = document.getElementById('bottom-dock');
    const dockRect = dock.getBoundingClientRect();
    const btnRect = button.getBoundingClientRect();
    
    const offsetLeft = btnRect.left - dockRect.left;
    
    slider.style.width = btnRect.width + 'px';
    slider.style.transform = `translateX(${offsetLeft - 12}px)`; // -12px due to dock padding
}

function switchTab(tabId, button) {
    document.querySelectorAll('.tab-content').forEach(c => {
        c.classList.remove('active');
        c.style.animation = 'none'; // reset animation
    });
    
    document.querySelectorAll('.dock-btn').forEach(b => b.classList.remove('active'));
    
    const target = document.getElementById(tabId);
    target.classList.add('active');
    
    // Force reflow
    target.offsetHeight;
    target.style.animation = 'fadeIn 0.4s ease forwards';
    
    if (button) {
        button.classList.add('active');
        moveSlider(button);
    }
}

// Init slider position
window.addEventListener('load', () => {
    const activeBtn = document.querySelector('.dock-btn.active');
    if (activeBtn) {
        setTimeout(() => moveSlider(activeBtn), 150);
    }
});

window.addEventListener('resize', () => {
    const activeBtn = document.querySelector('.dock-btn.active');
    if (activeBtn) moveSlider(activeBtn);
});

// ═══════════════════════════════════════════════════════════════
//  🛸 SOCIAL FRIEND AND INVITATION SYSTEM
// ═══════════════════════════════════════════════════════════════
const IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
let activeInviteId = null;

function pingUserActivity() {
    if (!IS_LOGGED_IN) return;
    fetch('api_social.php?action=ping_active');
}

function checkLiveInvitations() {
    if (!IS_LOGGED_IN) return;
    fetch('api_social.php?action=check_invites')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success' && data.invitation) {
                const invite = data.invitation;
                if (activeInviteId === invite.id) return; // Already showing this invite
                activeInviteId = invite.id;
                const GAME_NAMES = {
                    'taboo': 'ปาร์ตี้คำต้องห้าม (Taboo)',
                    'head_guess': 'ทายคำบนหัว (Heads Up)',
                    'senior_roulette': 'สุ่มภารกิจจับคู่ (Senior Roulette)',
                    'hardware_quiz': 'ทายภาพอุปกรณ์ (Hardware Quiz)',
                    'gacha': 'กาชาคัดออก (Gacha)'
                };
                const gameName = GAME_NAMES[invite.game_type] || invite.game_type;
                document.getElementById('global-invite-text').innerHTML = `คุณสายลับ <b>${invite.sender_name}</b> ได้เชิญคุณเข้าเล่นเกม <b>${gameName}</b> ห้อง <b>${invite.room_code}</b>`;
                document.getElementById('global-invite-modal').style.display = 'flex';
                
                document.getElementById('btn-accept-invite').onclick = () => {
                    const fd = new FormData();
                    fd.append('invite_id', invite.id);
                    fd.append('status', 'accepted');
                    fetch('api_social.php?action=update_invite', { method: 'POST', body: fd }).then(() => {
                        window.location.href = `games/${invite.game_type}/index.php?room=${invite.room_code}`;
                    });
                };
                
                document.getElementById('btn-decline-invite').onclick = () => {
                    const fd = new FormData();
                    fd.append('invite_id', invite.id);
                    fd.append('status', 'declined');
                    fetch('api_social.php?action=update_invite', { method: 'POST', body: fd }).then(() => {
                        document.getElementById('global-invite-modal').style.display = 'none';
                        activeInviteId = null;
                    });
                };
            }
        });
}

function switchSocialSubTab(subtabId) {
    document.querySelectorAll('.sub-tab-btn').forEach(btn => {
        if (btn.getAttribute('data-subtab') === subtabId) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    document.querySelectorAll('.social-sub-content').forEach(content => {
        if (content.id === `subtab-content-${subtabId}`) {
            content.style.display = 'block';
        } else {
            content.style.display = 'none';
        }
    });

    if (subtabId === 'search') {
        setTimeout(() => {
            const searchInput = document.getElementById('social-search-query');
            if (searchInput) searchInput.focus();
        }, 100);
    }
}

let searchTimeout = null;
function onSearchInput(input) {
    clearTimeout(searchTimeout);
    const q = input.value.trim();
    if (!q) {
        document.getElementById('social-search-results').innerHTML = '';
        return;
    }
    searchTimeout = setTimeout(() => {
        searchSocialUsers();
    }, 300);
}

function loadSocialLists() {
    if (!IS_LOGGED_IN) return;
    fetch('api_social.php?action=list_friends')
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'success') return;
            
            // Update Counts and Badges
            const friendsCountEl = document.getElementById('friends-count');
            const onlineCountEl = document.getElementById('online-count');
            const requestsCountEl = document.getElementById('requests-count');
            const requestsBadgeEl = document.getElementById('requests-badge');

            if (friendsCountEl) friendsCountEl.textContent = data.friends.length;
            if (onlineCountEl) onlineCountEl.textContent = data.friends.filter(f => f.is_online).length;
            if (requestsCountEl) requestsCountEl.textContent = data.requests.length;
            
            if (requestsBadgeEl) {
                if (data.requests.length > 0) {
                    requestsBadgeEl.style.display = 'inline-block';
                } else {
                    requestsBadgeEl.style.display = 'none';
                }
            }

            // Render Friends List
            const friendsList = document.getElementById('social-friends-list');
            if (friendsList) {
                if (data.friends.length === 0) {
                    friendsList.innerHTML = `<div class="empty-state-text">ยังไม่มีรายชื่อเพื่อนในระบบ<br>ไปที่แท็บ "ค้นหา" เพื่อแอดเพื่อนใหม่</div>`;
                } else {
                    friendsList.innerHTML = data.friends.map(f => `
                        <div class="social-user-row ${f.is_online ? 'online-status' : ''}">
                            <div class="avatar-wrapper">
                                <img src="${f.avatar}" class="row-avatar" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                                <span class="status-indicator-badge"></span>
                            </div>
                            <div class="row-user-details">
                                <div class="row-user-name">${f.real_name ?? f.username}</div>
                                <div class="row-user-username">@${f.username}</div>
                                <div class="row-status-text ${f.is_online ? 'online' : 'offline'}">
                                    ${f.is_online ? '🟢 ออนไลน์' : '🔴 ออฟไลน์'}
                                </div>
                            </div>
                            <div class="row-actions">
                                <button onclick="removeFriend(${f.friendship_id})" class="btn-action-round danger" title="ลบเพื่อน">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </button>
                            </div>
                        </div>
                    `).join('');
                }
            }
            
            // Render Incoming Requests
            const reqList = document.getElementById('social-requests-list');
            if (reqList) {
                if (data.requests.length === 0) {
                    reqList.innerHTML = `<div class="empty-state-text">ไม่มีคำขอแอดเพื่อนในขณะนี้</div>`;
                } else {
                    reqList.innerHTML = data.requests.map(r => `
                        <div class="social-user-row">
                            <img src="${r.avatar}" class="row-avatar" style="width: 42px; height: 42px; border-radius: 50%;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                            <div class="row-user-details" style="margin-left: 8px;">
                                <div class="row-user-name">${r.real_name ?? r.username}</div>
                                <div class="row-user-username">@${r.username}</div>
                            </div>
                            <div class="row-actions">
                                <button onclick="declineFriendRequest(${r.friendship_id})" class="btn-action-round danger" title="ปฏิเสธ">
                                    <ion-icon name="close-outline"></ion-icon>
                                </button>
                                <button onclick="acceptFriendRequest(${r.friendship_id})" class="btn-action-round success" title="รับแอด">
                                    <ion-icon name="checkmark-outline"></ion-icon>
                                </button>
                            </div>
                        </div>
                    `).join('');
                }
            }
        });
}

function searchSocialUsers() {
    const q = document.getElementById('social-search-query').value.trim();
    if (!q) return;
    fetch('api_social.php?action=search_users&query=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            const results = document.getElementById('social-search-results');
            if (!results) return;
            if (data.status === 'success') {
                if (data.users.length === 0) {
                    results.innerHTML = `<div class="empty-state-text">ไม่พบรายชื่อสายลับ หรือเป็นเพื่อนกันอยู่แล้ว</div>`;
                } else {
                    results.innerHTML = data.users.map(u => `
                        <div class="social-user-row">
                            <img src="${u.avatar}" class="row-avatar" style="width: 42px; height: 42px; border-radius: 50%;" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                            <div class="row-user-details" style="margin-left: 8px;">
                                <div class="row-user-name">${u.real_name ?? u.username}</div>
                                <div class="row-user-username">@${u.username}</div>
                            </div>
                            <div class="row-actions">
                                <button onclick="sendFriendRequest(${u.id}, this)" class="btn-add-friend">
                                    <ion-icon name="person-add-outline"></ion-icon>
                                    + แอดเพื่อน
                                </button>
                            </div>
                        </div>
                    `).join('');
                }
            }
        });
}

function sendFriendRequest(friendId, btn) {
    const fd = new FormData();
    fd.append('friend_id', friendId);
    fetch('api_social.php?action=add_friend', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                btn.innerHTML = '<ion-icon name="checkmark-outline"></ion-icon> ส่งคำขอแล้ว';
                btn.disabled = true;
                btn.style.opacity = '0.5';
                loadSocialLists();
            } else {
                alert(data.message);
            }
        });
}

function acceptFriendRequest(reqId) {
    const fd = new FormData();
    fd.append('request_id', reqId);
    fetch('api_social.php?action=accept_friend', { method: 'POST', body: fd }).then(() => loadSocialLists());
}

function declineFriendRequest(reqId) {
    const fd = new FormData();
    fd.append('request_id', reqId);
    fetch('api_social.php?action=decline_friend', { method: 'POST', body: fd }).then(() => loadSocialLists());
}

function removeFriend(friendshipId) {
    if (!confirm('ยืนยันที่จะลบเพื่อนคนนี้ออกจากรายชื่อ?')) return;
    const fd = new FormData();
    fd.append('request_id', friendshipId);
    fetch('api_social.php?action=decline_friend', { method: 'POST', body: fd }).then(() => loadSocialLists());
}

// ═══════════════════════════════════════════════════════════════
//  📍 REAL-TIME ATTENDANCE CHECK-IN SYSTEM
// ═══════════════════════════════════════════════════════════════
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
            
            // Render the list of checked-in players
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

// Initial fetch and set interval
document.addEventListener('DOMContentLoaded', () => {
    loadAttendanceList();
    setInterval(loadAttendanceList, 3000); // query every 3 seconds
});

// Start timers and checks
if (IS_LOGGED_IN) {
    pingUserActivity();
    checkLiveInvitations();
    loadSocialLists();
    
    setInterval(pingUserActivity, 10000); // ping activity every 10s
    setInterval(checkLiveInvitations, 2500); // check invites every 2.5s
    setInterval(loadSocialLists, 10000); // update friends list online status every 10s
}

// ═══════════════════════════════════════════════════════════════
//  📱 MOBILE-SPECIFIC FEATURES & OPTIMIZATIONS
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    // 1. Dynamic mobile viewport height fix (prevents browser bar cutting bottom)
    function setMobileVh() {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    setMobileVh();
    window.addEventListener('resize', setMobileVh);
    window.addEventListener('orientationchange', () => {
        setTimeout(setMobileVh, 200);
    });

    // 2. Swipe Navigation to Switch Tabs on Mobile
    const mainContainer = document.querySelector('.main-container');
    if (!mainContainer) return;

    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;

    mainContainer.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    mainContainer.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipeGesture();
    }, { passive: true });

    function handleSwipeGesture() {
        const deltaX = touchEndX - touchStartX;
        const deltaY = touchEndY - touchStartY;

        // Swipe horizontal threshold: min 75px, vertical max 40px
        if (Math.abs(deltaX) > 75 && Math.abs(deltaY) < 40) {
            // Find all dock buttons in order
            const dockButtons = Array.from(document.querySelectorAll('#bottom-dock .dock-btn'));
            if (dockButtons.length === 0) return;

            // Find current active button index
            const activeIndex = dockButtons.findIndex(btn => btn.classList.contains('active'));
            if (activeIndex === -1) return;

            let nextIndex = activeIndex;

            if (deltaX < 0) {
                // Swiped Left -> Switch to Next Tab (Right)
                if (activeIndex < dockButtons.length - 1) {
                    nextIndex = activeIndex + 1;
                }
            } else {
                // Swiped Right -> Switch to Previous Tab (Left)
                if (activeIndex > 0) {
                    nextIndex = activeIndex - 1;
                }
            }

            if (nextIndex !== activeIndex) {
                const targetBtn = dockButtons[nextIndex];
                const tabId = targetBtn.getAttribute('data-tab');
                if (tabId) {
                    // Trigger tab switch
                    switchTab(tabId, targetBtn);
                    
                    // Simple haptic feedback if supported (vibration on mobile)
                    if (navigator.vibrate) {
                        navigator.vibrate(12);
                    }
                }
            }
        }
    }
});
</script>

<!-- 📨 LIVE INVITATION OVERLAY MODAL -->
<div id="global-invite-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center; padding: 16px;">
    <div style="background: rgba(13, 20, 35, 0.95); border: 2px solid var(--neon-purple); border-radius: 24px; padding: 30px; max-width: 400px; width: 100%; text-align: center; box-shadow: 0 20px 50px rgba(161,0,255,0.35);">
        <div style="font-size: 3.5rem; margin-bottom: 12px; filter: drop-shadow(0 0 10px rgba(161,0,255,0.4));">📨</div>
        <h3 style="font-family: 'Chakra Petch', sans-serif; font-size: 1.5rem; font-weight: 900; margin-bottom: 12px; color: #fff; letter-spacing: 1px;">จดหมายเชิญเข้าเล่นเกม</h3>
        <p id="global-invite-text" style="font-size: 0.95rem; color: #cbd5e1; line-height: 1.6; margin-bottom: 24px;"></p>
        <div style="display: flex; gap: 12px; width: 100%;">
            <button id="btn-decline-invite" class="btn-submit pink" style="margin: 0; flex: 1; padding: 12px; font-size: 0.9rem; font-weight: 800; border-radius: 12px;">ปฏิเสธ</button>
            <button id="btn-accept-invite" class="btn-submit" style="margin: 0; flex: 1; padding: 12px; font-size: 0.9rem; font-weight: 800; border-radius: 12px; background: linear-gradient(135deg, var(--neon-purple), #7e00ff); box-shadow: inset 0 2px 0 rgba(255,255,255,0.3), inset 0 -2px 0 rgba(0,0,0,0.4);">ยอมรับ</button>
        </div>
    </div>
</div>

</body>
</html>