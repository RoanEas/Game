<?php 
session_start();
include 'db.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('⛔ เฉพาะแอดมินเท่านั้น'); window.location.href='index.php';</script>";
    exit();
}

// ⚡ ระบบเพิ่มคำถาม ปริศนาสายฟ้า
if (isset($_POST['add_blitz_question'])) {
    $q_text = trim($_POST['question_text']);
    $choices_raw = $_POST['choices'] ?? [];
    
    // Clean empty choices
    $choices = [];
    foreach ($choices_raw as $c) {
        $c_clean = trim($c);
        if (!empty($c_clean)) {
            $choices[] = $c_clean;
        }
    }
    
    if (!empty($q_text) && count($choices) >= 2) {
        $choices_json = json_encode($choices, JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("INSERT INTO blitz_questions (question_text, choices) VALUES (?, ?)");
        $stmt->bind_param("ss", $q_text, $choices_json);
        $stmt->execute();
    }
    header("Location: admin.php#blitz-section");
    exit();
}

// ⚡ ระบบลบคำถาม ปริศนาสายฟ้า
if (isset($_GET['delete_blitz_question'])) {
    $q_id = intval($_GET['delete_blitz_question']);
    $stmt = $conn->prepare("DELETE FROM blitz_questions WHERE id = ?");
    $stmt->bind_param("i", $q_id);
    $stmt->execute();
    header("Location: admin.php#blitz-section");
    exit();
}

// 📈 เพิ่มระบบอัปเดตคะแนนผู้เล่นใหม่
if (isset($_POST['update_score'])) {
    $user_id = $_POST['user_id'];
    $new_score = $_POST['score'];
    
    $stmt = $conn->prepare("UPDATE users SET score = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_score, $user_id);
    $stmt->execute();
    header("Location: admin.php");
    exit();
}

// (ระบบเดิม: เพิ่มเกม / ลบเกม / สลับสถานะ)
if (isset($_POST['add_game'])) {
    $name = $_POST['game_name']; $desc = $_POST['description']; $url = $_POST['game_url']; $img = $_POST['image_url'];
    $stmt = $conn->prepare("INSERT INTO games (game_name, description, game_url, image_url) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $desc, $url, $img); $stmt->execute();
    header("Location: admin.php"); exit();
}
if (isset($_GET['delete'])) {
    $id = $_GET['delete']; $stmt = $conn->prepare("DELETE FROM games WHERE id = ?"); $stmt->bind_param("i", $id); $stmt->execute();
    header("Location: admin.php"); exit();
}
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status']; $current_status = $_GET['current']; $new_status = ($current_status == 'active') ? 'maintenance' : 'active';
    $stmt = $conn->prepare("UPDATE games SET status = ? WHERE id = ?"); $stmt->bind_param("si", $new_status, $id); $stmt->execute();
    header("Location: admin.php"); exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Center - 🛸</title>
    <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>

<nav class="nav-minimal">
    <div class="nav-logo"><a href="admin.php">⚙️ CONTROL CENTER</a></div>
    <div><a href="index.php" class="nav-link" style="background: #10b981;">ดูหน้าเว็บ (View Site)</a></div>
</nav>

<div class="container" style="max-width:1300px;">
    
    <div class="admin-layout" style="margin-bottom: 50px;">
        <div class="form-box">
            <h2>➕ เพิ่มภารกิจใหม่</h2>
            <form action="admin.php" method="POST">
                <div class="input-group"><label>ชื่อเกม</label><input type="text" name="game_name" required></div>
                <div class="input-group"><label>รายละเอียด</label><textarea name="description" rows="2" required></textarea></div>
                <div class="input-group"><label>ลิงก์เกม</label><input type="text" name="game_url" required></div>
                <div class="input-group"><label>ลิงก์รูปภาพปก</label><input type="url" name="image_url" value="https://images.unsplash.com/photo-1614728263952-84ea256f9679"></div>
                <button type="submit" name="add_game" class="btn-submit">บันทึกเกม</button>
            </form>
        </div>

        <div>
            <table class="minimal-table">
                <thead><tr><th>รูปปก</th><th>ชื่อเกม</th><th>สถานะ</th><th style="text-align:right;">การจัดการ</th></tr></thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM games ORDER BY id DESC"; $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars($row['image_url']); ?>" style="width:60px; height:40px; object-fit:cover; border-radius:5px;"></td>
                                <td><strong style="color:#fff;"><?php echo htmlspecialchars($row['game_name']); ?></strong></td>
                                <td><?php echo ($row['status'] == 'active') ? '<span class="badge-active">● ออนไลน์</span>' : '<span class="badge-maintenance">● ปิดปรับปรุง</span>'; ?></td>
                                <td style="text-align:right;"><a href="admin.php?toggle_status=<?php echo $row['id']; ?>&current=<?php echo $row['status']; ?>" class="btn-action">สลับสถานะ</a><a href="admin.php?delete=<?php echo $row['id']; ?>" class="btn-action" style="color:#f87171;" onclick="return confirm('ลบเกมนี้?')">ลบ</a></td>
                            </tr>
                        <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>

    <hr style="border-color: rgba(255,255,255,0.1); margin: 40px 0;">

    <div class="form-box" style="max-width: 100%;">
        <h2 style="color: #facc15;">🏆 ระบบอัปเดตคะแนน & จัดอันดับที่ 1 ของผู้เล่น</h2>
        <table class="minimal-table" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ชื่อผู้ใช้งาน</th>
                    <th>อีเมลนักศึกษา</th>
                    <th width="250">คะแนนปัจจุบัน</th>
                    <th style="text-align: right;">ดำเนินการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // ดึงข้อมูลผู้ใช้ทุกคนที่เป็น member (เรียงจากคะแนนสูงสุดลงมา)
                $user_sql = "SELECT * FROM users WHERE role = 'member' ORDER BY score DESC";
                $user_res = $conn->query($user_sql);
                $u_rank = 1;

                if ($user_res && $user_res->num_rows > 0) {
                    while($u_row = $user_res->fetch_assoc()) {
                        ?>
                        <tr>
                            <td>#<?php echo $u_row['id']; ?></td>
                            <td>
                                <strong style="color: #fff;">
                                    <?php echo ($u_rank == 1) ? '👑 ' : ''; ?>
                                    <?php echo htmlspecialchars($u_row['username']); ?>
                                </strong>
                                <?php echo ($u_rank == 1) ? ' <span style="color:#facc15; font-size:0.8rem;">(ที่ 1 ของชมรม)</span>' : ''; ?>
                            </td>
                            <td style="color: #94a3b8;"><?php echo htmlspecialchars($u_row['email']); ?></td>
                            
                            <form action="admin.php" method="POST">
                                <td>
                                    <input type="number" name="score" value="<?php echo $u_row['score']; ?>" 
                                           style="width:120px; padding:6px; background:#0f051d; border:1px solid #4c1d95; border-radius:5px; color:#4ade80; font-weight:bold; text-align:center;">
                                    <input type="hidden" name="user_id" value="<?php echo $u_row['id']; ?>">
                                </td>
                                <td style="text-align: right;">
                                    <button type="submit" name="update_score" class="btn-action" style="background:#22c55e; color:#fff; border:none;">
                                        💾 อัปเดตคะแนน
                                    </button>
                                </td>
                            </form>
                        </tr>
                        <?php
                        $u_rank++;
                    }
                } else {
                    echo '<tr><td colspan="5" style="text-align:center; color:#94a3b8;">ยังไม่มีนักศึกษาสมัครสมาชิกเข้ามาในระบบ</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <hr style="border-color: rgba(255,255,255,0.1); margin: 40px 0;">

    <div class="admin-layout" id="blitz-section" style="margin-bottom: 50px;">
        <div class="form-box">
            <h2 style="color: #06b6d4;">⚡ เพิ่มคำถาม ปริศนาสายฟ้า</h2>
            <form action="admin.php" method="POST" id="blitz-question-form">
                <div class="input-group">
                    <label>ข้อความคำถาม (Question Text)</label>
                    <textarea name="question_text" rows="3" placeholder="ป้อนคำถามของคุณ..." required></textarea>
                </div>
                
                <div class="input-group">
                    <label style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <span>ตัวเลือกคำตอบ (Choices)</span>
                        <button type="button" onclick="addChoiceInput()" style="background:#06b6d4; color:#000; border:none; padding:4px 10px; border-radius:6px; font-weight:bold; cursor:pointer; font-size:0.8rem;">+ เพิ่มชอยส์</button>
                    </label>
                    <div id="choices-container" style="display:flex; flex-direction:column; gap:8px;">
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="text" name="choices[]" placeholder="ตัวเลือกที่ 1..." required style="flex:1;">
                        </div>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="text" name="choices[]" placeholder="ตัวเลือกที่ 2..." required style="flex:1;">
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="add_blitz_question" class="btn-submit" style="background:#06b6d4; color:#000; border:none; font-weight:bold;">บันทึกคำถาม</button>
            </form>
        </div>

        <div>
            <h2>📝 รายการคำถามทั้งหมด</h2>
            <div style="max-height: 500px; overflow-y: auto;">
                <table class="minimal-table">
                    <thead><tr><th>ID</th><th>คำถาม</th><th>ชอยส์คำตอบ</th><th style="text-align:right;">การจัดการ</th></tr></thead>
                    <tbody>
                        <?php
                        $blitz_q_sql = "SELECT * FROM blitz_questions ORDER BY id DESC";
                        $blitz_q_res = $conn->query($blitz_q_sql);
                        if ($blitz_q_res && $blitz_q_res->num_rows > 0) {
                            while($bq = $blitz_q_res->fetch_assoc()) {
                                $bq_choices = json_decode($bq['choices'], true) ?? [];
                                ?>
                                <tr>
                                    <td>#<?php echo $bq['id']; ?></td>
                                    <td><strong style="color:#fff;"><?php echo htmlspecialchars($bq['question_text']); ?></strong></td>
                                    <td>
                                        <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                            <?php foreach ($bq_choices as $choice): ?>
                                                <span style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px; font-size:0.75rem; color:#cbd5e1;"><?php echo htmlspecialchars($choice); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="admin.php?delete_blitz_question=<?php echo $bq['id']; ?>" class="btn-action" style="color:#f87171;" onclick="return confirm('ลบคำถามนี้?')">ลบ</a>
                                    </td>
                                </tr>
                            <?php } } else { ?>
                                <tr><td colspan="4" style="text-align:center; color:#94a3b8;">ยังไม่มีคำถามในระบบ</td></tr>
                            <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
function addChoiceInput() {
    const container = document.getElementById('choices-container');
    const childCount = container.children.length;
    
    const div = document.createElement('div');
    div.style.display = 'flex';
    div.style.gap = '8px';
    div.style.alignItems = 'center';
    
    div.innerHTML = `
        <input type="text" name="choices[]" placeholder="ตัวเลือกที่ ${childCount + 1}..." required style="flex:1;">
        <button type="button" onclick="this.parentElement.remove()" style="background:#ef4444; color:#fff; border:none; padding:8px 12px; border-radius:8px; font-weight:bold; cursor:pointer;">X</button>
    `;
    container.appendChild(div);
}
</script>

</body>
</html>