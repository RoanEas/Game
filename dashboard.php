<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$uid = $_SESSION['user_id'];

// Handle profile update
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $real_name = trim($_POST['real_name'] ?? '');
    $avatar_img = trim($_POST['avatar_img'] ?? 'dog.png');
    
    if (empty($real_name)) {
        $message = 'กรุณากรอกชื่อแสดงผลของคุณ';
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("UPDATE users SET real_name = ?, avatar_img = ?, is_avatar_created = 1 WHERE id = ?");
        $stmt->bind_param("ssi", $real_name, $avatar_img, $uid);
        if ($stmt->execute()) {
            $_SESSION['real_name'] = $real_name;
            $_SESSION['avatar_img'] = $avatar_img;
            $_SESSION['avatar_status'] = 1;
            $message = 'บันทึกข้อมูลโปรไฟล์ของคุณเรียบร้อยแล้ว!';
            $message_type = 'success';
        } else {
            $message = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
            $message_type = 'error';
        }
    }
}

// Fetch user data
$stmt = $conn->prepare("SELECT username, email, real_name, avatar_img, score, role FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$username = $user['username'] ?? 'unknown';
$email = $user['email'] ?? '';
$real_name = $user['real_name'] ?? '';
$avatar_img = $user['avatar_img'] ?? 'dog.png';
$score = $user['score'] ?? 0;
$role = $user['role'] ?? 'member';

// Load avatars list from JSON
$avatarData = [];
$avatarJsonPath = __DIR__ . '/data/avatar_items.json';
if (file_exists($avatarJsonPath)) {
    $avatarData = json_decode(file_get_contents($avatarJsonPath), true);
}
$avatarsList = $avatarData['avatars'] ?? [];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>โปรไฟล์สายลับ - Cyber Profile</title>
    <link href="profile-style.css" rel="stylesheet" type="text/css">
    <!-- Ionicons for cyberpunk symbols -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>

<div class="profile-container">

    <header class="profile-header">
        <div class="header-badge">ข้อมูลสายลับส่วนบุคคล</div>
        <h1>แก้ไขโปรไฟล์</h1>
        <p>ปรับปรุงชื่อรหัส รูปภาพ หรืออวตารของคุณที่จะแสดงในระบบและห้องแข่ง</p>
    </header>

    <?php if (!empty($message)): ?>
        <div class="message-banner <?php echo $message_type === 'success' ? 'msg-success' : 'msg-error'; ?>">
            <p style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                <ion-icon name="<?php echo $message_type === 'success' ? 'checkmark-circle-outline' : 'alert-circle-outline'; ?>" style="font-size: 1.3rem;"></ion-icon>
                <?php echo htmlspecialchars($message); ?>
            </p>
        </div>
    <?php endif; ?>

    <form method="POST" action="dashboard.php" class="profile-form-grid">
        
        <!-- Left Side: Avatar selector -->
        <div class="profile-col-left">
            <!-- CARD 1: AVATAR SELECTION -->
            <div class="profile-card">
                <div class="card-title">
                    <ion-icon name="happy-outline" style="color: var(--neon-purple);"></ion-icon>
                    <span>รูปลักษณ์ตัวละครอวตาร</span>
                </div>
                
                <div class="avatar-section">
                    <div class="avatar-preview-box" id="avatar-preview">
                        <?php 
                        $preview_url = (strpos($avatar_img, 'http') === 0) ? $avatar_img : 'assets/avatar/' . $avatar_img;
                        // Find matching seed for fallback
                        $fallback_seed = '1';
                        foreach ($avatarsList as $av) {
                            if ($av['file'] === $avatar_img) {
                                $fallback_seed = $av['id'];
                                break;
                            }
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($preview_url); ?>" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=<?php echo $fallback_seed; ?>'" alt="Avatar Preview">
                    </div>
                    <div class="avatar-info-text">
                        คลิกเลือกรูปภาพอวตารด้านล่างเพื่ออัปเดตตัวละครของคุณ
                    </div>
                    
                    <input type="hidden" name="avatar_img" id="avatar_img_val" value="<?php echo htmlspecialchars($avatar_img); ?>">
                    
                    <div class="avatar-grid">
                        <?php foreach ($avatarsList as $index => $avatar): ?>
                            <div class="avatar-item <?php echo ($avatar_img === $avatar['file']) ? 'selected' : ''; ?>"
                                 onclick="selectProfileAvatar('<?php echo htmlspecialchars($avatar['file']); ?>', '<?php echo htmlspecialchars($avatar['img_url']); ?>', this)">
                                <img src="<?php echo htmlspecialchars($avatar['img_url']); ?>" onerror="this.src='https://api.dicebear.com/7.x/bottts/svg?seed=<?php echo $avatar['id']; ?>'" alt="Avatar Option">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Fields, Stats & Save Button -->
        <div class="profile-col-right">
            <!-- CARD 2: REAL NAME / PROFILE NAME -->
            <div class="profile-card">
                <div class="card-title">
                    <ion-icon name="card-outline" style="color: var(--neon-cyan);"></ion-icon>
                    <span>ข้อมูลรหัสผ่านสายลับ</span>
                </div>
                
                <div class="form-field">
                    <label for="real_name">ชื่อแสดงผลของคุณ (Display Name)</label>
                    <input type="text" name="real_name" id="real_name" class="form-input" value="<?php echo htmlspecialchars($real_name); ?>" placeholder="ป้อนชื่อเรียกในห้องแข่ง..." required autocomplete="off">
                </div>
                
                <div class="form-field">
                    <label>ชื่อบัญชีเข้าระบบ (Username)</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($username); ?>" readonly title="ไม่สามารถแก้ไข Username ได้">
                </div>

                <div class="form-field">
                    <label>อีเมลติดต่อ (Email)</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($email); ?>" readonly title="ไม่สามารถแก้ไข Email ได้">
                </div>
            </div>

            <!-- CARD 3: AGENT PROFILE STATS -->
            <div class="profile-card">
                <div class="card-title">
                    <ion-icon name="trophy-outline" style="color: #facc15;"></ion-icon>
                    <span>สถิติปฏิบัติการคอมพิวเตอร์</span>
                </div>
                <div class="stats-container">
                    <div class="stat-group">
                        <span class="stat-label">คะแนนสะสมหลัก (Score)</span>
                        <span class="stat-value"><?php echo intval($score); ?></span>
                    </div>
                    <div class="stat-group" style="align-items: flex-end;">
                        <span class="stat-label">บทบาทของคุณ</span>
                        <span class="role-badge <?php echo ($role === 'admin') ? 'role-admin' : 'role-member'; ?>">
                            <?php echo htmlspecialchars($role); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- SAVE BUTTON -->
            <button type="submit" class="btn-save">
                <ion-icon name="save-outline"></ion-icon>
                <span>บันทึกการแก้ไขข้อมูล</span>
            </button>
        </div>
        
    </form>

    <nav class="floating-tabbar" id="profile-tabbar">
        <a href="index.php" class="tab-item" title="หน้าแรกหลัก">
            <ion-icon name="home-outline"></ion-icon>
            <span class="tab-text">หน้าแรก</span>
        </a>
        <a href="#" class="tab-item active" title="โปรไฟล์แก้ไข">
            <ion-icon name="person-outline"></ion-icon>
            <span class="tab-text">โปรไฟล์</span>
        </a>
        <a href="logout.php" class="tab-item" style="color: var(--neon-pink);" title="ออกจากระบบ">
            <ion-icon name="log-out-outline"></ion-icon>
            <span class="tab-text">ออกระบบ</span>
        </a>
    </nav>

</div>

<script>
function selectProfileAvatar(file, imgUrl, el) {
    // Update hidden field value
    document.getElementById('avatar_img_val').value = file;
    
    // Update active border on options
    document.querySelectorAll('.avatar-item').forEach(item => {
        item.classList.remove('selected');
    });
    el.classList.add('selected');
    
    // Read the actual loaded src of the clicked option to support fallbacks (e.g. if local assets are missing)
    const optionImg = el.querySelector('img');
    const actualImgUrl = optionImg ? optionImg.src : imgUrl;
    
    // Update preview image
    const previewContainer = document.getElementById('avatar-preview');
    if (previewContainer) {
        const previewImg = previewContainer.querySelector('img');
        if (previewImg) {
            previewImg.src = actualImgUrl;
            
            // Extract seed from URL if it's a Dicebear fallback to keep preview in sync
            const seedMatch = actualImgUrl.match(/seed=([^&]+)/);
            if (seedMatch) {
                const seed = seedMatch[1];
                previewImg.onerror = function() {
                    this.src = 'https://api.dicebear.com/7.x/bottts/svg?seed=' + seed;
                };
            } else {
                // Default fallback
                previewImg.onerror = function() {
                    this.src = 'https://api.dicebear.com/7.x/bottts/svg?seed=1';
                };
            }
        }
    }
}

// Haptic touch feedback on tab items
document.querySelectorAll('.tab-item').forEach(item => {
    item.addEventListener('click', () => {
        if (navigator.vibrate) {
            navigator.vibrate(12);
        }
    });
});
</script>
</body>
</html>