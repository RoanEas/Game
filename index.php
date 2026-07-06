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

// ================= ระบบล็อกอินเข้าสู่ระบบ =================
if (isset($_POST['login'])) {
    $username = $_POST['username']; $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); $stmt->execute(); $result = $stmt->get_result();
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
            header("Location: index.php"); exit();
        }
    }
    echo "<script>alert('ข้อมูลเข้าสู่ระบบไม่ถูกต้อง');</script>";
}

// ================= ระบบสมัครสมาชิก =================
if (isset($_POST['register'])) {
    $username = $_POST['username']; $email = $_POST['email']; $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);
    if($stmt->execute()) { echo "<script>alert('สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบเพื่อเลือกตัวละครสายลับ');</script>"; }
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
            <img src="assets/avatar/<?php echo htmlspecialchars($_SESSION['avatar_img'] ?? 'dog.png'); ?>" 
                 onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'" class="user-avatar" alt="Avatar">
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
                                    <img src="assets/avatar/<?php echo $user_row['avatar_img']; ?>" class="rank-avatar" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=1'">
                                    <span style="margin-left:8px; font-weight:500; color:var(--text-main);"><?php echo htmlspecialchars($user_row['real_name']); ?></span>
                                </td>
                                <td><?php echo number_format($user_row['score']); ?> PTS</td>
                            </tr>
                    <?php $rank++; } } else { echo '<tr><td colspan="3" style="text-align:center;">No data available</td></tr>'; } ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- TAB 3: LOGIN -->
    <div id="tab-login" class="tab-content">
        <div class="auth-panel">
            <h2>SIGN IN TO PLAY</h2>
            <form action="index.php" method="POST">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn-submit">LOGIN</button>
            </form>
        </div>
    </div>


    <!-- TAB 4: REGISTER -->
    <div id="tab-register" class="tab-content">
        <div class="auth-panel">
            <h2>CREATE AGENT PROFILE</h2>
            <form action="index.php" method="POST">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="register" class="btn-submit pink">REGISTER</button>
            </form>
        </div>
    </div>

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

        <?php if(!isset($_SESSION['user_id'])): ?>
            <button class="dock-btn" data-tab="tab-login" onclick="switchTab('tab-login', this)">
                <ion-icon name="key"></ion-icon>
                <span>LOGIN</span>
            </button>
            <button class="dock-btn" data-tab="tab-register" onclick="switchTab('tab-register', this)">
                <ion-icon name="person-add"></ion-icon>
                <span>JOIN</span>
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
//  AVATAR SELECTION
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
</script>
</body>
</html>