<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Restrict access to administrators only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: play.php");
    exit();
}


$projectDir = '/gmaemini'; 
$jsonPath = dirname(__DIR__, 2) . '/data/bingo_items.json';

// Initialize default items if JSON file doesn't exist
if (!file_exists($jsonPath)) {
    $defaultItems = [
        ["id" => 1, "name" => "หน่วยความจำ", "image" => "https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=300&q=80", "clue" => "ชิปอิเล็กทรอนิกส์ใช้เก็บคำสั่งหรือข้อมูลชั่วคราวระหว่างคอมทำงาน"],
        ["id" => 2, "name" => "อีดิท", "image" => "https://images.unsplash.com/photo-1516259762381-22954d7d3ad2?auto=format&fit=crop&w=300&q=80", "clue" => "กระบวนการปรับแต่ง แก้ไข คัดเขียนสิ่งเดิมให้ดีขึ้นหรือเหมาะสมขึ้น"],
        ["id" => 3, "name" => "ข้อมูล", "image" => "https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=300&q=80", "clue" => "ข้อเท็จจริง ตัวเลข หรือภาพดิบๆ ที่ยังไม่ได้ผ่านการประมวลผล"],
        ["id" => 4, "name" => "เครื่องอ่านบาร์โค้ด", "image" => "https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?auto=format&fit=crop&w=300&q=80", "clue" => "อุปกรณ์รับข้อมูลประเภทแสง ใช้ยิงกวาดอ่านเส้นรหัสแท่งสีดำบนหีบห่อสินค้า"],
        ["id" => 5, "name" => "อินเทอร์เน็ต", "image" => "https://images.unsplash.com/photo-1544197150-b99a580bb7a8?auto=format&fit=crop&w=300&q=80", "clue" => "เครือข่ายคอมพิวเตอร์ขนาดใหญ่ที่เชื่อมต่อคอมพิวเตอร์และเซิร์ฟเวอร์ทั่วโลก"],
        
        ["id" => 6, "name" => "แรม", "image" => "https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=300&q=80", "clue" => "หน่วยความจำชั่วคราวความเร็วสูง ที่ข้อมูลจะลบหายไปทันทีที่ไฟฟ้าดับหรือปิดเครื่อง"],
        ["id" => 7, "name" => "คีย์บอร์ด", "image" => "https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=300&q=80", "clue" => "อุปกรณ์นำเข้าหลักรูปแบบปุ่มกดตัวอักษร ตัวเลข และเครื่องหมายต่าง ๆ"],
        ["id" => 8, "name" => "หูฟัง", "image" => "https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=300&q=80", "clue" => "อุปกรณ์แสดงผลเสียงขนาดเล็กใช้สวมครอบหัวหรือสวมในหูเพื่อฟังส่วนตัว"],
        ["id" => 9, "name" => "แผ่นเสียง", "image" => "https://images.unsplash.com/photo-1539625319135-8d6b9e86fcf7?auto=format&fit=crop&w=300&q=80", "clue" => "แผ่นอนาล็อกกลมแบนมีร่องวงกลมสำหรับบันทึกเสียงโบราณ ใช้กับเครื่องเล่นจานหมุน"],
        ["id" => 10, "name" => "สื่อจัดเก็บข้อมูล", "image" => "https://images.unsplash.com/photo-1597852074816-d933c4d2b988?auto=format&fit=crop&w=300&q=80", "clue" => "คำเรียกกลุ่มอุปกรณ์ทั้งหมดที่ใช้เก็บบันทึกข้อมูลดิจิทัลถาวร"],
        
        ["id" => 11, "name" => "พาวเวอร์ซัพพลาย", "image" => "https://images.unsplash.com/photo-1591488320449-011701bb6704?auto=format&fit=crop&w=300&q=80", "clue" => "อุปกรณ์แปลงกระแสไฟฟ้าบ้านเป็นไฟฟ้ากระแสตรงเพื่อเลี้ยงอุปกรณ์คอมพิวเตอร์"],
        ["id" => 12, "name" => "พาสเวิร์ด", "image" => "https://images.unsplash.com/photo-1510511459019-5dda7724fd87?auto=format&fit=crop&w=300&q=80", "clue" => "รหัสลับส่วนตัวที่เป็นกุญแจหลักชั้นแรกสำหรับระบบความปลอดภัยในการเข้าระบบ"],
        ["id" => 13, "name" => "สแกนเนอร์", "image" => "https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?auto=format&fit=crop&w=300&q=80", "clue" => "อุปกรณ์ใช้แสงอ่านกวาดแปลงภาพวาดหรือเอกสารกระดาษเป็นไฟล์ดิจิทัล"],
        ["id" => 14, "name" => "เอเร่อ", "image" => "https://images.unsplash.com/photo-1594322436404-5a0526db4d13?auto=format&fit=crop&w=300&q=80", "clue" => "หน้าต่างแจ้งเตือนข้อผิดพลาดขัดข้องของระบบปฏิบัติการหรือโปรแกรมคอมพิวเตอร์"],
        
        ["id" => 15, "name" => "ดาวน์โหลด", "image" => "https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=300&q=80", "clue" => "กระบวนการดึงหรือคัดลอกไฟล์ข้อมูลจากระบบอินเทอร์เน็ตเข้ามาเก็บในคอม"],
        ["id" => 16, "name" => "ซีพียู", "image" => "https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=300&q=80", "clue" => "ชิปหน่วยประมวลผลกลาง ทำหน้าที่คิดและคำนวณ เปรียบเหมือนสมองคอมพิวเตอร์"],
        ["id" => 17, "name" => "เคส", "image" => "https://images.unsplash.com/photo-1587202372775-e229f172b9d7?auto=format&fit=crop&w=300&q=80", "clue" => "โครงกล่องภายนอกใช้บรรจุ ยึดจับ และป้องกันอุปกรณ์อิเล็กทรอนิกส์ทั้งหมดของคอม"],
        ["id" => 18, "name" => "ฮาร์ดดิสก์", "image" => "https://images.unsplash.com/photo-1531403009284-440f080d1e12?auto=format&fit=crop&w=300&q=80", "clue" => "อุปกรณ์เก็บข้อมูลหลักที่มีจานจานโลหะแม่เหล็กหมุนความเร็วสูงภายใน"],
        ["id" => 19, "name" => "ซอฟต์แวร์", "image" => "https://images.unsplash.com/photo-1605379399642-870262d3d051?auto=format&fit=crop&w=300&q=80", "clue" => "กลุ่มโปรแกรมหรือชุดคำสั่งที่เขียนขึ้นเพื่อสั่งให้คอมพิวเตอร์ทำงานตามต้องการ"],
        
        ["id" => 20, "name" => "การ์ดแสดงผล", "image" => "https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&w=300&q=80", "clue" => "ชิปหรือการ์ดเร่งความเร็วประมวลผลภาพ 3D และส่งสัญญาณภาพออกจอคอม"],
        ["id" => 21, "name" => "ซีดี", "image" => "https://images.unsplash.com/photo-1528642474498-1af0c17fd8c3?auto=format&fit=crop&w=300&q=80", "clue" => "แผ่นออปติคัลดิสก์ใช้แสงเลเซอร์อ่านเขียนข้อมูลดิจิทัล เหมาะสำหรับเพลงและซอฟต์แวร์"],
        ["id" => 22, "name" => "ไมโครโฟน", "image" => "https://images.unsplash.com/photo-1590602847861-f357a9332bbc?auto=format&fit=crop&w=300&q=80", "clue" => "อุปกรณ์รับสัญญาณเสียงคลื่นอนาล็อกและแปลงเป็นสัญญาณไฟฟ้าดิจิทัลบันทึกในคอม"],
        ["id" => 23, "name" => "ยูสเซอร์เนม", "image" => "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80", "clue" => "ชื่อระบุตัวตนของผู้ใช้ที่ใช้ควบคู่กับรหัสผ่านเพื่อล็อกอินเข้าระบบ"],
        ["id" => 24, "name" => "จอภาพ", "image" => "https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=300&q=80", "clue" => "อุปกรณ์ส่งออกหลักประเภทแสดงผลลัพธ์ข้อมูลภาพ กราฟิก และข้อความให้สายตามองเห็น"]
    ];
    
    $dataDir = dirname(__DIR__, 2) . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    file_put_contents($jsonPath, json_encode(["items" => $defaultItems, "question_mode" => false, "is_revealed" => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$itemsJson = file_get_contents($jsonPath);
$itemsData = json_decode($itemsJson, true);
if (!is_array($itemsData)) {
    $itemsData = ["items" => [], "question_mode" => false, "is_revealed" => true];
}
$needsWrite = false;
if (!isset($itemsData['round_id'])) {
    $itemsData['round_id'] = uniqid('r_', true);
    $needsWrite = true;
}
if (!isset($itemsData['question_mode'])) {
    $itemsData['question_mode'] = false;
    $needsWrite = true;
}
if (!isset($itemsData['is_revealed'])) {
    $itemsData['is_revealed'] = true;
    $needsWrite = true;
}

// Define the 5 decoy items
$decoys = [
    ["id" => 25, "name" => "เครื่องปริ้นเตอร์ 3 มิติ", "image" => "https://images.unsplash.com/photo-1615811361523-6bd03d7748e7?auto=format&fit=crop&w=300&q=80", "decoy" => true, "clue" => "อุปกรณ์พิมพ์วัตถุขึ้นมาเป็นรูปทรงสามมิติที่จับต้องได้จากการฉีดเส้นพลาสติก"],
    ["id" => 26, "name" => "แว่น VR", "image" => "https://images.unsplash.com/photo-1593508512255-86ab42a8e620?auto=format&fit=crop&w=300&q=80", "decoy" => true, "clue" => "อุปกรณ์สวมศีรษะจำลองภาพแวดล้อมเสมือนจริง 3 มิติ แบบ 360 องศา"],
    ["id" => 27, "name" => "พล็อตเตอร์", "image" => "https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=300&q=80", "decoy" => true, "clue" => "เครื่องพิมพ์ขนาดใหญ่พิเศษใช้ปากกาขีดเขียนเส้นวาดแบบแปลนสถาปัตยกรรม"],
    ["id" => 28, "name" => "การ์ดเสียง", "image" => "https://images.unsplash.com/photo-1580584126903-c17d41830450?auto=format&fit=crop&w=300&q=80", "decoy" => true, "clue" => "บอร์ดวงจรขยายประมวลผลสัญญาณเสียงเพื่อต่อลำโพงและเครื่องดนตรีระดับมืออาชีพ"],
    ["id" => 29, "name" => "เครื่องสำรองไฟ (UPS)", "image" => "https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=300&q=80", "decoy" => true, "clue" => "เครื่องมีแบตเตอรี่ภายในจ่ายไฟฉุกเฉินเพื่อให้คอมไม่ดับและปิดเครื่องได้ทันเมื่อไฟดับ"]
];

// Verify clues exist on old items (migration)
$clueMap = [
    1 => "ชิปอิเล็กทรอนิกส์ใช้เก็บคำสั่งหรือข้อมูลชั่วคราวระหว่างคอมทำงาน",
    2 => "กระบวนการปรับแต่ง แก้ไข คัดเขียนสิ่งเดิมให้ดีขึ้นหรือเหมาะสมขึ้น",
    3 => "ข้อเท็จจริง ตัวเลข หรือภาพดิบๆ ที่ยังไม่ได้ผ่านการประมวลผล",
    4 => "อุปกรณ์รับข้อมูลประเภทแสง ใช้ยิงกวาดอ่านเส้นรหัสแท่งสีดำบนหีบห่อสินค้า",
    5 => "เครือข่ายคอมพิวเตอร์ขนาดใหญ่ที่เชื่อมต่อคอมพิวเตอร์และเซิร์ฟเวอร์ทั่วโลก",
    6 => "หน่วยความจำชั่วคราวความเร็วสูง ที่ข้อมูลจะลบหายไปทันทีที่ไฟฟ้าดับหรือปิดเครื่อง",
    7 => "อุปกรณ์นำเข้าหลักรูปแบบปุ่มกดตัวอักษร ตัวเลข และเครื่องหมายต่าง ๆ",
    8 => "อุปกรณ์แสดงผลเสียงขนาดเล็กใช้สวมครอบหัวหรือสวมในหูเพื่อฟังส่วนตัว",
    9 => "แผ่นอนาล็อกกลมแบนมีร่องวงกลมสำหรับบันทึกเสียงโบราณ ใช้กับเครื่องเล่นจานหมุน",
    10 => "คำเรียกกลุ่มอุปกรณ์ทั้งหมดที่ใช้เก็บันทึกข้อมูลดิจิทัลถาวร",
    11 => "อุปกรณ์แปลงกระแสไฟฟ้าบ้านเป็นไฟฟ้ากระแสตรงเพื่อเลี้ยงอุปกรณ์คอมพิวเตอร์",
    12 => "รหัสลับส่วนตัวที่เป็นกุญแจหลักชั้นแรกสำหรับระบบความปลอดภัยในการเข้าระบบ",
    13 => "อุปกรณ์ใช้แสงอ่านกวาดแปลงภาพวาดหรือเอกสารกระดาษเป็นไฟล์ดิจิทัล",
    14 => "หน้าต่างแจ้งเตือนข้อผิดพลาดขัดข้องของระบบปฏิบัติการหรือโปรแกรมคอมพิวเตอร์",
    15 => "กระบวนการดึงหรือคัดลอกไฟล์ข้อมูลจากระบบอินเทอร์เน็ตเข้ามาเก็บในคอม",
    16 => "ชิปหน่วยประมวลผลกลาง ทำหน้าที่คิดและคำนวณ เปรียบเหมือนสมองคอมพิวเตอร์",
    17 => "โครงกล่องภายนอกใช้บรรจุ ยึดจับ และป้องกันอุปกรณ์อิเล็กทรอนิกส์ทั้งหมดของคอม",
    18 => "อุปกรณ์เก็บข้อมูลหลักที่มีจานจานโลหะแม่เหล็กหมุนความเร็วสูงภายใน",
    19 => "กลุ่มโปรแกรมหรือชุดคำสั่งที่เขียนขึ้นเพื่อสั่งให้คอมพิวเตอร์ทำงานตามต้องการ",
    20 => "ชิปหรือการ์ดเร่งความเร็วประมวลผลภาพ 3D และส่งสัญญาณภาพออกจอคอม",
    21 => "แผ่นออปติคัลดิสก์ใช้แสงเลเซอร์อ่านเขียนข้อมูลดิจิทัล เหมาะสำหรับเพลงและซอฟต์แวร์",
    22 => "อุปกรณ์รับสัญญาณเสียงคลื่นอนาล็อกและแปลงเป็นสัญญาณไฟฟ้าดิจิทัลบันทึกในคอม",
    23 => "ชื่อระบุตัวตนของผู้ใช้ที่ใช้ควบคู่กับรหัสผ่านเพื่อล็อกอินเข้าระบบ",
    24 => "อุปกรณ์ส่งออกหลักประเภทแสดงผลลัพธ์ข้อมูลภาพ กราฟิก และข้อความให้สายตามองเห็น",
    25 => "อุปกรณ์พิมพ์วัตถุขึ้นมาเป็นรูปทรงสามมิติที่จับต้องได้จากการฉีดเส้นพลาสติก",
    26 => "อุปกรณ์สวมศีรษะจำลองภาพแวดล้อมเสมือนจริง 3 มิติ แบบ 360 องศา",
    27 => "เครื่องพิมพ์ขนาดใหญ่พิเศษใช้ปากกาขีดเขียนเส้นวาดแบบแปลนสถาปัตยกรรม",
    28 => "บอร์ดวงจรขยายประมวลผลสัญญาณเสียงเพื่อต่อลำโพงและเครื่องดนตรีระดับมืออาชีพ",
    29 => "เครื่องมีแบตเตอรี่ภายในจ่ายไฟฉุกเฉินเพื่อให้คอมไม่ดับและปิดเครื่องได้ทันเมื่อไฟดับ"
];

foreach ($itemsData['items'] as &$it) {
    if (!isset($it['clue']) && isset($clueMap[$it['id']])) {
        $it['clue'] = $clueMap[$it['id']];
        $needsWrite = true;
    }
}
unset($it);
$existingIds = array_column($itemsData['items'], 'id');
foreach ($decoys as $decoy) {
    if (!in_array($decoy['id'], $existingIds)) {
        $itemsData['items'][] = $decoy;
        $needsWrite = true;
    }
}

if (!isset($itemsData['draw_sequence']) || empty($itemsData['draw_sequence'])) {
    $itemIds = [];
    foreach (($itemsData['items'] ?? []) as $item) {
        if (empty($item['decoy'])) {
            $itemIds[] = intval($item['id']);
        }
    }
    shuffle($itemIds);
    $itemsData['draw_sequence'] = $itemIds;
    $needsWrite = true;
}
if ($needsWrite) {
    file_put_contents($jsonPath, json_encode($itemsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
$itemsJson = json_encode($itemsData);
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>เครื่องสุ่มจับสลากบิงโก 🎮</title>
    <link href="quiz-style.css?v=<?=time();?>" rel="stylesheet" type="text/css">
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
<script>
    (function() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            document.body.classList.add('light-mode');
        }
    })();
</script>

<canvas id="canvas-particles"></canvas>
<canvas id="canvas-fireworks"></canvas>
<div class="modal-backdrop" id="backdrop" onclick="closeHistorySheet()"></div>

<!-- FLY-IN ZOOM REVEAL OVERLAY (พุ่งเข้าหน้าแบบเด่นๆ) -->
<div class="flash-effect" id="flash-effect-screen"></div>
<div class="zoom-reveal-overlay" id="zoom-overlay" onclick="closeZoomReveal()">
    <div class="zoom-reveal-card">
        <div class="zoom-reveal-sub">🎯 สุ่มจับได้อุปกรณ์</div>
        <div class="zoom-reveal-image-wrapper">
            <img id="zoom-image" src="" alt="Zoom Image">
        </div>
        <div class="zoom-reveal-name" id="zoom-name"></div>
        <div style="font-size:0.85rem; color:var(--text-muted); font-weight:400; margin-top:10px;">คลิกที่ใดก็ได้เพื่อปิดหน้าต่างนี้</div>
    </div>
</div>

<!-- BINGO BOARD GRID STATUS OVERLAY (ตารางตรวจสอบบิงโก) -->
<div class="board-overlay" id="board-overlay" onclick="closeBingoBoard()">
    <div class="board-card" onclick="event.stopPropagation()">
        <div class="sheet-header" style="margin-bottom: 0;">
            <div class="sheet-title">ตารางตรวจสอบบิงโก (5x5)</div>
            <button class="circle-btn" onclick="closeBingoBoard()" style="width:32px; height:32px; font-size:0.9rem;">
                <ion-icon name="close-outline"></ion-icon>
            </button>
        </div>
        <div style="font-size:0.75rem; color:var(--text-muted); text-align:left; margin-bottom:8px; display:flex; align-items:center; gap:4px;">
            <ion-icon name="information-circle-outline" style="font-size:1rem; color:var(--accent);"></ion-icon> ไอเทมสว่าง = ออกรางวัลแล้ว, ไอเทมมืด = ยังไม่ออกรางวัล
        </div>
        <div class="board-grid" id="board-grid-container"></div>
    </div>
</div>


<div class="minimal-app admin-app">

    <div class="admin-grid-layout">
        <!-- LEFT COLUMN: NAVIGATION / INFO / HUD -->
        <div class="admin-left-col" style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- HUD Header -->
            <header class="hud-header" style="flex-direction: column; align-items: stretch; gap: 15px; padding: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <h1 class="hud-title" style="font-size: 1.25rem;">เครื่องสุ่มจับบิงโก</h1>
                    <div class="hud-actions">
                        <button class="circle-btn" onclick="window.location.href='../../index.php'" title="กลับหน้าแรกหลัก">
                            <ion-icon name="home-outline"></ion-icon>
                        </button>
                        <button class="circle-btn" id="btn-theme" onclick="toggleTheme()" title="เปลี่ยนธีม มืด/สว่าง">
                            <ion-icon name="moon-outline" id="theme-icon"></ion-icon>
                        </button>
                        <button class="circle-btn" id="btn-sound" onclick="toggleMusic()" title="เพลงประกอบกล่องดนตรี">
                            <ion-icon name="musical-notes-outline" id="sound-icon"></ion-icon>
                        </button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 8px;">
                    <button class="btn-secondary" onclick="openBingoBoard()" style="padding: 10px; font-size: 0.8rem;" title="ดูตารางตรวจสอบบิงโก">
                        <ion-icon name="grid-outline" style="font-size: 1.1rem;"></ion-icon> ตารางตรวจสอบ
                    </button>
                    <button class="btn-secondary" onclick="resetBingoGame()" style="padding: 10px; font-size: 0.8rem; border-color: rgba(239, 68, 68, 0.2); color: #ef4444;" title="รีเซ็ตสุ่มใหม่">
                        <ion-icon name="refresh-outline" style="font-size: 1.1rem;"></ion-icon> รีเซ็ตเกมใหม่
                    </button>
                </div>
            </header>

            <!-- Sidebar Tabbar -->
            <div class="sidebar-menu">
                <div class="menu-item active">
                    <ion-icon name="sparkles" style="font-size: 1.2rem;"></ion-icon>
                    <span>เครื่องสุ่มจับ</span>
                </div>
                <a href="play.php" class="menu-item">
                    <ion-icon name="grid-outline" style="font-size: 1.2rem;"></ion-icon>
                    <span>กระดานเล่นบิงโก</span>
                </a>
                <a href="../../dashboard.php" class="menu-item">
                    <ion-icon name="home-outline" style="font-size: 1.2rem;"></ion-icon>
                    <span>หน้าควบคุมหลัก</span>
                </a>
            </div>

            <!-- BINGO WINNERS SCOREBOARD -->
            <div class="main-card" style="padding: 20px; align-items: stretch; text-align: left; gap: 12px; box-shadow: var(--card-shadow); width: 100%;">
                <div style="font-size:0.9rem; font-weight:600; color:#fbbf24; display:flex; align-items:center; gap:6px;">
                    <ion-icon name="trophy-outline" style="font-size:1.25rem;"></ion-icon> 
                    <span>ผู้ชนะบิงโกในรอบนี้ (<span id="winner-count">0</span>)</span>
                </div>
                <div id="winners-list-container" style="display:flex; flex-direction:column; gap:8px; max-height:240px; overflow-y:auto; font-size:0.85rem; color:var(--text-muted);">
                    <div style="text-align:center; padding:10px; font-style:italic;">ยังไม่มีผู้บิงโกในรอบนี้</div>
                </div>
            </div>
            
        </div>

        <!-- RIGHT COLUMN: THE RANDOMIZER FOCUS -->
        <div class="admin-right-col">
            
            <?php if ($isAdmin): ?>
                <div class="admin-badge" style="margin-bottom: 15px;">👨‍💻 แอดมิน: แก้ไขรูป/ชื่อได้ในปุ่ม "เปลี่ยนรูปภาพ/ชื่อ" ด้านล่าง</div>
            <?php endif; ?>

            <!-- MAIN MINIMAL CARD -->
            <main class="main-card" style="width: 100%;">
                
                <span class="status-pill" id="caller-status">พร้อมเริ่มจับรางวัล</span>

                <div class="item-display-pod" id="display-pod">
                    <ion-icon name="cube-outline" class="placeholder-icon" id="display-placeholder"></ion-icon>
                    <img id="display-image" src="" alt="Drawn Item" style="display: none;">
                </div>

                <div class="item-name" id="display-name" style="font-size: 1.8rem; margin: 10px 0;">กดสุ่มจับเพื่อลุ้นกันเลย!</div>

                <!-- QUIZ MODE TOGGLE -->
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 12px; font-size: 0.9rem; font-weight: bold; background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); padding: 8px 16px; border-radius: 12px;">
                    <input type="checkbox" id="chk-quiz-mode" onchange="onQuizModeToggle(this)" style="width:16px;height:16px;cursor:pointer;">
                    <label for="chk-quiz-mode" style="cursor:pointer; color: var(--text-main); display:flex; align-items:center; gap:4px;">
                        <ion-icon name="help-circle-outline" style="color:var(--accent); font-size:1.1rem;"></ion-icon>
                        💡 โหมดคำถามใบ้ (Quiz Mode)
                    </label>
                </div>

                <!-- MAIN BUTTONS ROW -->
                <div style="display: flex; gap: 8px; width: 100%; margin-top: 10px;">
                    <button class="btn-draw" id="btn-draw" onclick="drawNextItem()" style="font-size: 1.15rem; padding: 18px 28px; flex: 1;">
                        <ion-icon name="sparkles-outline" style="font-size: 24px;"></ion-icon>
                        🎲 สุ่มจับอุปกรณ์ถัดไป
                    </button>
                    <button class="btn-secondary" id="btn-reveal" onclick="revealCurrentAnswer()" style="flex: 0 0 auto; display: none; padding: 0 20px; font-weight: 800; border-color: var(--accent); color: var(--accent); border-radius: 14px; font-size: 0.9rem; align-items: center; gap: 4px; margin: 0;" title="เฉลยคำตอบให้ผู้เล่นเห็น">
                        <ion-icon name="eye-outline" style="font-size:1.2rem;"></ion-icon> เฉลยรางวัล
                    </button>
                </div>

                <!-- SUB ACTIONS -->
                <div class="sub-actions">
                    <button class="btn-secondary" id="btn-auto" onclick="toggleAutoPlay()">
                        <ion-icon name="play-outline"></ion-icon> สุ่มรันอัตโนมัติ
                    </button>
                    <button class="btn-secondary" onclick="openHistorySheet()">
                        <ion-icon name="image-outline"></ion-icon> เปลี่ยนรูปภาพ/ชื่อ (<span id="progress-text">0/24</span>)
                    </button>
                </div>

            </main>
            
        </div>
    </div>

    <!-- FLOATING TABBAR (Mobile fallback) -->
    <div class="floating-tabbar">
        <div class="tab-item active">เครื่องสุ่มจับ</div>
        <a href="play.php" class="tab-item">กระดานเล่นบิงโก</a>
        <a href="../../dashboard.php" class="tab-item">หน้าควบคุมหลัก</a>
    </div>

</div>

<!-- BOTTOM SHEET DRAWER FOR EDITING & INVENTORY -->
<div class="bottom-sheet" id="history-sheet">
    <div class="sheet-header">
        <div class="sheet-title" id="sheet-title-text">⚙️ จัดการรูปภาพและชื่ออุปกรณ์</div>
        <button class="circle-btn" onclick="closeHistorySheet()" style="width:32px; height:32px; font-size:0.9rem;">
            <ion-icon name="close-outline"></ion-icon>
        </button>
    </div>

    <div class="sheet-scroll" id="sheet-list-container"></div>
</div>


<!-- SEQUENCE COMPLETE OVERLAY -->
<div class="complete-overlay" id="complete-screen">
    <div class="complete-modal">
        <div class="complete-title">สุ่มจับรางวัลครบแล้ว! 🎉</div>
        <div class="complete-subtitle">ได้ทำการจับสลากอุปกรณ์คอมพิวเตอร์ครบถ้วนทั้ง 24 ชนิดเรียบร้อยแล้ว ขอให้ทุกคนสนุกกับการบิงโก!</div>
        <button class="btn-draw" onclick="closeCompleteModal()" style="box-shadow:none;">
            ตกลง / เล่นต่อ
        </button>
    </div>
</div>

<?php if ($isAdmin): ?>
<input type="file" id="image-upload-input" style="display:none" accept="image/*">
<?php endif; ?>

<script>
const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
const RAW = <?php echo $itemsJson; ?>;
const allItems = [...(RAW.items || [])];

// State variables
let itemsPool = []; // Shuffled remaining pool
let drawnItems = []; // Array of items drawn in order
let activeUploadIdx = null;
let isShuffling = false;
let autoPlayInterval = null;
let zoomTimeout = null;

function getImageUrl(path) {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }
    return '../../' + path;
}


// Audio Engine - Cozy Music Box Synthesizer
let audioCtx = null;
let bgmTimer = null;
let bgmPlaying = false;
let bgmBeat = 0;

function initAudio() {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === 'suspended') audioCtx.resume();
}

function playTone(freq, type = 'sine', dur = 0.1, vol = 0.08) {
    try {
        initAudio();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        
        osc.type = type;
        osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
        
        gain.gain.setValueAtTime(vol, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + dur);
        
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        
        osc.start();
        osc.stop(audioCtx.currentTime + dur);
    } catch(e) {}
}

// Cozy Chimes SFX
function sndTick() { 
    playTone(800 + Math.random() * 600, 'sine', 0.05, 0.02); 
}
function sndReveal() { 
    // Double chime chord
    playTone(523.25, 'sine', 0.2, 0.09); // C5
    setTimeout(() => playTone(659.25, 'sine', 0.25, 0.07), 60); // E5
    setTimeout(() => playTone(783.99, 'sine', 0.35, 0.05), 120); // G5
}
function sndReset() { 
    playTone(600, 'sine', 0.08, 0.04);
    setTimeout(() => playTone(500, 'sine', 0.08, 0.04), 40);
    setTimeout(() => playTone(400, 'sine', 0.12, 0.04), 80);
}
function sndFinish() {
    let notes = [523.25, 587.33, 659.25, 783.99, 880.00, 1046.50];
    notes.forEach((freq, i) => {
        setTimeout(() => playTone(freq, 'sine', 0.4, 0.05), i * 80);
    });
}

// Cozy BGM Generator
const musicBoxMelody = [
    523.25, 0, 659.25, 0, 783.99, 880.00, 0, 783.99,
    587.33, 0, 698.46, 0, 880.00, 987.77, 0, 880.00,
    659.25, 0, 783.99, 0, 987.77, 1046.50, 0, 987.77,
    523.25, 0, 659.25, 0, 783.99, 880.00, 0, 1046.50
];

function startMusicBoxSynth() {
    if (bgmTimer) clearInterval(bgmTimer);
    bgmTimer = setInterval(() => {
        if (!bgmPlaying || !audioCtx) return;
        
        let note = musicBoxMelody[bgmBeat % musicBoxMelody.length];
        
        if (note > 0) {
            playTone(note, 'sine', 0.35, 0.03);
            if (bgmBeat % 4 === 0) {
                let bassNote = note / 4;
                playTone(bassNote, 'sine', 0.5, 0.05);
            }
        }
        
        bgmBeat++;
    }, 280);
}

function toggleMusic() {
    initAudio();
    const btn = document.getElementById('btn-sound');
    const icon = document.getElementById('sound-icon');
    
    if (bgmPlaying) {
        bgmPlaying = false;
        icon.setAttribute('name', 'volume-mute-outline');
        btn.classList.remove('active');
    } else {
        bgmPlaying = true;
        icon.setAttribute('name', 'volume-high-outline');
        btn.classList.add('active');
        startMusicBoxSynth();
    }
}

// Theme Management (Light / Dark Mode)
function updateThemeIcon() {
    const themeIcon = document.getElementById('theme-icon');
    const btnTheme = document.getElementById('btn-theme');
    if (document.body.classList.contains('light-mode')) {
        if (themeIcon) themeIcon.setAttribute('name', 'sun-outline');
        if (btnTheme) btnTheme.classList.add('active');
    } else {
        if (themeIcon) themeIcon.setAttribute('name', 'moon-outline');
        if (btnTheme) btnTheme.classList.remove('active');
    }
}

function toggleTheme() {
    initAudio();
    const body = document.body;
    if (body.classList.contains('light-mode')) {
        body.classList.remove('light-mode');
        localStorage.setItem('theme', 'dark');
        playTone(400, 'sine', 0.08, 0.04);
    } else {
        body.classList.add('light-mode');
        localStorage.setItem('theme', 'light');
        playTone(600, 'sine', 0.08, 0.04);
    }
    updateThemeIcon();
}

// Admin Item Editor
function editItem(e, idx, field) {
    if (!IS_ADMIN) return;
    if (e) e.stopPropagation();
    
    const item = allItems[idx];
    if (field === 'image') {
        activeUploadIdx = idx;
        document.getElementById('image-upload-input').click();
    } else if (field === 'name') {
        const newName = prompt(`แก้ไขชื่อของอุปกรณ์ (ปัจจุบัน: ${item.name})`, item.name);
        if (newName !== null && newName.trim() !== '') {
            const oldName = item.name;
            item.name = newName.trim();
            
            // Sync state
            const poolIdx = itemsPool.findIndex(x => x.id === item.id);
            if (poolIdx !== -1) itemsPool[poolIdx].name = item.name;
            
            const drawnIdx = drawnItems.findIndex(x => x.id === item.id);
            if (drawnIdx !== -1) drawnItems[drawnIdx].name = item.name;
            
            const mainNameEl = document.getElementById('display-name');
            if (mainNameEl.textContent === oldName) {
                mainNameEl.textContent = item.name;
            }
            
            saveItemsAPI();
        }
    }
}

async function saveItemsAPI() {
    await fetch('api_save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'save_items', items: allItems })
    });
    
    renderSheetList();
}

if (IS_ADMIN) {
    const imageUploadInput = document.getElementById('image-upload-input');
    if (imageUploadInput) {
        imageUploadInput.addEventListener('change', async (e) => {
            if (!e.target.files.length || activeUploadIdx === null) return;
            
            const file = e.target.files[0];
            const formData = new FormData();
            formData.append('image', file);
            
            document.getElementById('caller-status').textContent = "กำลังอัปโหลด...";
            
            try {
                const res = await fetch('api_upload.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.status === 'success') {
                    const item = allItems[activeUploadIdx];
                    item.image = data.filepath;
                    
                    // Sync state
                    const poolIdx = itemsPool.findIndex(x => x.id === item.id);
                    if (poolIdx !== -1) itemsPool[poolIdx].image = item.image;
                    
                    const drawnIdx = drawnItems.findIndex(x => x.id === item.id);
                    if (drawnIdx !== -1) drawnItems[drawnIdx].image = item.image;
                    
                    const mainImageEl = document.getElementById('display-image');
                    if (mainImageEl.src.includes(data.filepath) || drawnItems[drawnItems.length - 1]?.id === item.id) {
                        mainImageEl.src = getImageUrl(item.image) + '?v=' + Date.now();
                    }

                    
                    document.getElementById('caller-status').textContent = "อัปโหลดเรียบร้อย";
                    saveItemsAPI();
                } else {
                    alert("อัปโหลดไม่สำเร็จ: " + data.message);
                }
            } catch(err) {
                alert("เชื่อมต่อเซิร์ฟเวอร์ล้มเหลว: " + err);
            }
            
            imageUploadInput.value = '';
            activeUploadIdx = null;
        });
    }
}

// Bottom sheet control
function openHistorySheet() {
    document.getElementById('history-sheet').classList.add('active');
    document.getElementById('backdrop').classList.add('active');
    renderSheetList();
}

function closeHistorySheet() {
    document.getElementById('history-sheet').classList.remove('active');
    document.getElementById('backdrop').classList.remove('active');
}

// Render the flat 1-24 edit list inside the sheet
function renderSheetList() {
    const listContainer = document.getElementById('sheet-list-container');
    const progressText = document.getElementById('progress-text');
    progressText.textContent = `${drawnItems.length}/24`;
    
    listContainer.innerHTML = '';
    
    allItems.forEach((item, index) => {
        const row = document.createElement('div');
        row.className = 'item-row';
        
        // Check if already drawn
        const isDrawn = drawnItems.some(x => x.id === item.id);
        const badgeClass = isDrawn ? 'badge-drawn' : 'badge-pending';
        const badgeText = isDrawn ? 'จับแล้ว' : 'รอจับ';
        
        let actionButtons = '';
        if (IS_ADMIN) {
            actionButtons = `
                <div class="row-actions">
                    <button class="action-btn" onclick="editItem(event, ${index}, 'image')">🖼️ รูป</button>
                    <button class="action-btn" onclick="editItem(event, ${index}, 'name')">✏️ ชื่อ</button>
                </div>
            `;
        }

        row.innerHTML = `
            <img class="item-thumb" src="${getImageUrl(item.image)}" alt="">
            <div class="item-info">
                <div style="display:flex; align-items:center; gap:6px;">
                    <span class="item-num">#${index + 1}</span>
                    <span class="row-name">${item.name}</span>
                </div>
                <span class="row-badge ${badgeClass}">${badgeText}</span>
            </div>
            ${actionButtons}
        `;
        listContainer.appendChild(row);
    });
}


// Initializer
function initializePool() {
    const seq = RAW.draw_sequence || [];
    const drawnIds = RAW.drawn_ids || [];
    
    // Map sequence IDs to item objects
    let seqItems = seq.map(id => allItems.find(x => x.id === id)).filter(Boolean);
    
    // If sequence is not set (legacy or fallback), shuffle locally
    if (seqItems.length === 0) {
        seqItems = [...allItems];
        for (let i = seqItems.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [seqItems[i], seqItems[j]] = [seqItems[j], seqItems[i]];
        }
    }
    
    // drawnItems are the ones in seqItems that are already in drawnIds
    drawnItems = seqItems.filter(item => drawnIds.includes(item.id));
    
    // itemsPool are the ones in seqItems that are NOT yet in drawnIds, in reverse order (since we pop() from pool)
    const remaining = seqItems.filter(item => !drawnIds.includes(item.id));
    itemsPool = remaining.reverse();

    // Initialize Quiz Mode Checkbox and Reveal Button State
    const chkQuiz = document.getElementById('chk-quiz-mode');
    if (chkQuiz) {
        chkQuiz.checked = RAW.question_mode || false;
    }
    updateRevealButtonState();
}

function onQuizModeToggle(chk) {
    fetch('api_save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'toggle_quiz_mode', enabled: chk.checked })
    }).then(r => r.json()).then(data => {
        if (data.status === 'success') {
            RAW.question_mode = chk.checked;
            updateRevealButtonState();
        }
    });
}

function revealCurrentAnswer() {
    fetch('api_save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'reveal_item' })
    }).then(r => r.json()).then(data => {
        if (data.status === 'success') {
            RAW.is_revealed = true;
            updateRevealButtonState();
            
            // Instantly render sheet list and update status
            renderSheetList();
            const latestItem = drawnItems[drawnItems.length - 1];
            if (latestItem) {
                document.getElementById('caller-status').textContent = `เฉลยเรียบร้อย (ชิ้นที่ ${drawnItems.length} / 24)`;
            }
        }
    });
}

function updateRevealButtonState() {
    const btnReveal = document.getElementById('btn-reveal');
    if (!btnReveal) return;
    
    if (RAW.question_mode && drawnItems.length > 0 && !RAW.is_revealed) {
        btnReveal.style.display = 'inline-flex';
    } else {
        btnReveal.style.display = 'none';
    }
}

// Close the fly-in zoom overlay and return to standard view
function closeZoomReveal() {
    if (zoomTimeout) clearTimeout(zoomTimeout);
    
    const zoomOverlay = document.getElementById('zoom-overlay');
    const flash = document.getElementById('flash-effect-screen');
    
    zoomOverlay.classList.remove('active');
    flash.classList.remove('flash-active');
    
    // Settles the image down in the display pod
    const displayImage = document.getElementById('display-image');
    const displayName = document.getElementById('display-name');
    const displayPod = document.getElementById('display-pod');
    const statusPill = document.getElementById('caller-status');
    const latestItem = drawnItems[drawnItems.length - 1];
    
    if (latestItem) {
        displayImage.src = getImageUrl(latestItem.image);
        displayName.innerHTML = `${latestItem.name}<br><span style="font-size:0.85rem;color:var(--text-muted);font-weight:400;font-style:italic;">คำใบ้: ${latestItem.clue || 'ไม่มีคำใบ้'}</span>`;
        statusPill.textContent = `สุ่มได้ชิ้นที่ ${drawnItems.length} / 24`;
        displayPod.classList.add('reveal-bounce');
        
        // Sync lists progress
        document.getElementById('progress-text').textContent = `${drawnItems.length}/24`;
    }

    // Re-enable draw button if there are items left
    if (itemsPool.length > 0) {
        document.getElementById('btn-draw').disabled = false;
    } else {
        // All items drawn
        document.getElementById('btn-draw').disabled = true;
        statusPill.textContent = "จับรางวัลเสร็จสมบูรณ์";
        displayName.textContent = "สุ่มจับครบ 24 รายการ!";
        
        setTimeout(() => {
            document.getElementById('complete-screen').classList.add('active');
            sndFinish();
            startFireworks();
        }, 600);
    }
}

// Draw Item with Fly-in Zoom Effect
function drawNextItem() {
    if (isShuffling || itemsPool.length === 0) return;
    
    initAudio();
    isShuffling = true;
    document.getElementById('btn-draw').disabled = true;
    
    const statusPill = document.getElementById('caller-status');
    const displayImage = document.getElementById('display-image');
    const displayPlaceholder = document.getElementById('display-placeholder');
    const displayName = document.getElementById('display-name');
    const displayPod = document.getElementById('display-pod');
    
    statusPill.textContent = "กำลังสุ่มจับรางวัล...";
    displayPlaceholder.style.display = 'none';
    displayImage.style.display = 'block';
    displayPod.classList.remove('reveal-bounce');
    
    // Rolling suspense tick animation
    let count = 0;
    let spinInterval = setInterval(() => {
        const randItem = allItems[Math.floor(Math.random() * allItems.length)];
        displayImage.src = getImageUrl(randItem.image);
        displayName.textContent = randItem.name;
        sndTick();
        count++;
        
        if (count > 12) {
            clearInterval(spinInterval);
            
            // Extract next item
            const drawnItem = itemsPool.pop();
            drawnItems.push(drawnItem);
            
            RAW.is_revealed = RAW.question_mode ? false : true;
            updateRevealButtonState();
            
            // Sync drawn item ID to the server to prevent client cheating
            fetch('api_save.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'draw_item', id: drawnItem.id })
            });
            
            // 🚨 FLY-IN REVEAL ACTION (พุ่งเข้าหน้าแบบเด่นๆ)
            const zoomOverlay = document.getElementById('zoom-overlay');
            const zoomImage = document.getElementById('zoom-image');
            const zoomName = document.getElementById('zoom-name');
            const flash = document.getElementById('flash-effect-screen');

            
            zoomImage.src = getImageUrl(drawnItem.image);
            zoomName.innerHTML = `${drawnItem.name}<br><span style="font-size:1.05rem;color:#facc15;font-weight:bold;display:block;margin-top:10px;line-height:1.4;">คำใบ้: ${drawnItem.clue || 'ไม่มีคำใบ้'}</span>`;

            
            // Trigger overlay and screen flash
            zoomOverlay.classList.add('active');
            flash.classList.add('flash-active');
            
            sndReveal();
            isShuffling = false;
            
            // Auto close zoom after 2.8 seconds if not clicked
            zoomTimeout = setTimeout(() => {
                closeZoomReveal();
            }, 2800);
        }
    }, 100);
}

// Auto-run engine
function toggleAutoPlay() {
    if (autoPlayInterval) {
        clearInterval(autoPlayInterval);
        autoPlayInterval = null;
        document.getElementById('btn-auto').classList.remove('active');
    } else {
        if (itemsPool.length === 0) return;
        document.getElementById('btn-auto').classList.add('active');
        
        autoPlayInterval = setInterval(() => {
            // Close zoom if open, then draw
            if (document.getElementById('zoom-overlay').classList.contains('active')) {
                closeZoomReveal();
            } else if (!isShuffling && itemsPool.length > 0) {
                drawNextItem();
            }
        }, 4000); // 4 seconds interval to give time for zoom animation
    }
}

// Reset Game
async function resetBingoGame() {
    const userConfirmed = await showCustomConfirm();
    if (!userConfirmed) return;

    if (autoPlayInterval) {
        clearInterval(autoPlayInterval);
        autoPlayInterval = null;
        document.getElementById('btn-auto').classList.remove('active');
    }
    
    // Request server to generate a new round ID
    fetch('api_save.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'new_round' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // Update RAW state dynamically
            RAW.round_id = data.round_id;
            RAW.draw_sequence = data.draw_sequence;
            RAW.drawn_ids = data.drawn_ids;
            RAW.is_revealed = true;
            
            sndReset();
            initializePool();
            stopFireworks();
            
            // UI elements reset
            document.getElementById('btn-draw').disabled = false;
            document.getElementById('caller-status').textContent = "พร้อมเริ่มจับรางวัล";
            document.getElementById('display-placeholder').style.display = 'block';
            document.getElementById('display-image').style.display = 'none';
            document.getElementById('display-image').src = '';
            document.getElementById('display-name').textContent = "กดสุ่มจับเพื่อลุ้นกันเลย!";
            document.getElementById('display-pod').classList.remove('reveal-bounce');
            document.getElementById('progress-text').textContent = "0/24";
            
            document.getElementById('zoom-overlay').classList.remove('active');
            document.getElementById('flash-effect-screen').classList.remove('flash-active');
            
            closeHistorySheet();
            closeCompleteModal();
            closeBingoBoard();
            
            // Re-render editor sheet
            renderSheetList();
        }
    });
}



function closeCompleteModal() {
    document.getElementById('complete-screen').classList.remove('active');
}

// BINGO BOARD CONTROLLER
function openBingoBoard() {
    initAudio();
    const container = document.getElementById('board-grid-container');
    container.innerHTML = '';
    
    for (let i = 0; i < 25; i++) {
        const cell = document.createElement('div');
        
        if (i === 12) {
            // Center Free Space
            cell.className = 'board-cell cell-free';
            cell.innerHTML = `
                <ion-icon name="star"></ion-icon>
                <span class="cell-free-text">FREE</span>
            `;
        } else {
            // Map items (0-11 maps to 0-11, 12 is FREE, 13-24 maps to 12-23)
            const itemIdx = (i < 12) ? i : i - 1;
            const item = allItems[itemIdx];
            
            if (item) {
                const isDrawn = drawnItems.some(x => x.id === item.id);
                cell.className = 'board-cell';
                if (isDrawn) cell.classList.add('cell-drawn');
                
                cell.innerHTML = `
                    <img src="${getImageUrl(item.image)}" alt="">
                    <span class="cell-check-icon"><ion-icon name="checkmark-outline"></ion-icon></span>
                    <span class="cell-name-label">${item.name}</span>
                `;
            }
        }
        container.appendChild(cell);
    }
    
    document.getElementById('board-overlay').classList.add('active');
    document.getElementById('backdrop').classList.add('active');
}

function closeBingoBoard() {
    document.getElementById('board-overlay').classList.remove('active');
    document.getElementById('backdrop').classList.remove('active');
}


// 🎆 GENTLE DUST PARTICLES ENGINE (HTML5 CANVAS)
const partCanvas = document.getElementById('canvas-particles');
const partCtx = partCanvas.getContext('2d');
let particlesList = [];

function resizePartCanvas() {
    partCanvas.width = window.innerWidth;
    partCanvas.height = window.innerHeight;
}
window.addEventListener('resize', resizePartCanvas);
resizePartCanvas();

class DustParticle {
    constructor() {
        this.x = Math.random() * partCanvas.width;
        this.y = Math.random() * partCanvas.height;
        this.vx = (Math.random() - 0.5) * 0.2;
        this.vy = (Math.random() - 0.5) * 0.2;
        this.radius = Math.random() * 1.5 + 0.5;
        this.alpha = Math.random() * 0.3 + 0.1;
    }
    update() {
        this.x += this.vx;
        this.y += this.vy;
        
        if (this.x < 0) this.x = partCanvas.width;
        if (this.x > partCanvas.width) this.x = 0;
        if (this.y < 0) this.y = partCanvas.height;
        if (this.y > partCanvas.height) this.y = 0;
    }
    draw() {
        partCtx.beginPath();
        partCtx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
        const isLight = document.body.classList.contains('light-mode');
        partCtx.fillStyle = isLight 
            ? `rgba(0, 0, 0, ${this.alpha * 0.35})` 
            : `rgba(255, 255, 255, ${this.alpha})`;
        partCtx.fill();
    }
}

function initDustNodes() {
    particlesList = [];
    let count = Math.min(45, Math.floor((partCanvas.width * partCanvas.height) / 25000));
    for (let i = 0; i < count; i++) {
        particlesList.push(new DustParticle());
    }
}

function animateDustNodes() {
    requestAnimationFrame(animateDustNodes);
    partCtx.clearRect(0, 0, partCanvas.width, partCanvas.height);
    
    particlesList.forEach(particle => {
        particle.update();
        particle.draw();
    });
}
initDustNodes();
animateDustNodes();

// 🎆 FIREWORKS CANVAS ENGINE
const canvas = document.getElementById('canvas-fireworks');
const ctx = canvas.getContext('2d');
let fireworks = [];
let particles = [];
let animationFrameId = null;

function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
window.addEventListener('resize', resizeCanvas);

class Firework {
    constructor(sx, sy, tx, ty) {
        this.x = sx;
        this.y = sy;
        this.sx = sx;
        this.sy = sy;
        this.tx = tx;
        this.ty = ty;
        this.distanceToTarget = Math.sqrt(Math.pow(tx - sx, 2) + Math.pow(ty - sy, 2));
        this.distanceTraveled = 0;
        this.coordinates = [];
        this.coordinateCount = 3;
        while(this.coordinateCount--) {
            this.coordinates.push([this.x, this.y]);
        }
        this.angle = Math.atan2(ty - sy, tx - sx);
        this.speed = 2.5;
        this.acceleration = 1.05;
        this.brightness = Math.random() * 20 + 50;
        this.targetRadius = 1;
    }
    update(index) {
        this.coordinates.pop();
        this.coordinates.unshift([this.x, this.y]);
        
        if (this.targetRadius < 8) {
            this.targetRadius += 0.3;
        } else {
            this.targetRadius = 1;
        }
        
        this.speed *= this.acceleration;
        
        let vx = Math.cos(this.angle) * this.speed;
        let vy = Math.sin(this.angle) * this.speed;
        this.distanceTraveled = Math.sqrt(Math.pow(this.x + vx - this.sx, 2) + Math.pow(this.y + vy - this.sy, 2));
        
        if (this.distanceTraveled >= this.distanceToTarget) {
            createParticles(this.tx, this.ty);
            fireworks.splice(index, 1);
        } else {
            this.x += vx;
            this.y += vy;
        }
    }
    draw() {
        ctx.beginPath();
        ctx.moveTo(this.coordinates[this.coordinates.length - 1][0], this.coordinates[this.coordinates.length - 1][1]);
        ctx.lineTo(this.x, this.y);
        ctx.strokeStyle = `hsl(${Math.random() * 360}, 100%, ${this.brightness}%)`;
        ctx.stroke();
    }
}

class Particle {
    constructor(x, y) {
        this.x = x;
        this.y = y;
        this.coordinates = [];
        this.coordinateCount = 5;
        while(this.coordinateCount--) {
            this.coordinates.push([this.x, this.y]);
        }
        this.angle = Math.random() * Math.PI * 2;
        this.speed = Math.random() * 8 + 1;
        this.friction = 0.95;
        this.gravity = 1;
        this.hue = Math.random() * 360;
        this.brightness = Math.random() * 20 + 60;
        this.alpha = 1;
        this.decay = Math.random() * 0.015 + 0.015;
    }
    update(index) {
        this.coordinates.pop();
        this.coordinates.unshift([this.x, this.y]);
        this.speed *= this.friction;
        this.x += Math.cos(this.angle) * this.speed;
        this.y += Math.sin(this.angle) * this.speed + this.gravity;
        this.alpha -= this.decay;
        
        if (this.alpha <= this.decay) {
            particles.splice(index, 1);
        }
    }
    draw() {
        ctx.beginPath();
        ctx.moveTo(this.coordinates[this.coordinates.length - 1][0], this.coordinates[this.coordinates.length - 1][1]);
        ctx.lineTo(this.x, this.y);
        ctx.strokeStyle = `hsla(${this.hue}, 100%, ${this.brightness}%, ${this.alpha})`;
        ctx.stroke();
    }
}

function createParticles(x, y) {
    let particleCount = 50;
    while(particleCount--) {
        particles.push(new Particle(x, y));
    }
}

function startFireworks() {
    canvas.classList.add('active');
    resizeCanvas();
    fireworks = [];
    particles = [];
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    animateFireworks();
}

function stopFireworks() {
    canvas.classList.remove('active');
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

let timerTick = 0;
function animateFireworks() {
    animationFrameId = requestAnimationFrame(animateFireworks);
    
    ctx.globalCompositeOperation = 'destination-out';
    ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.globalCompositeOperation = 'lighter';
    
    let i = fireworks.length;
    while(i--) {
        fireworks[i].draw();
        fireworks[i].update(i);
    }
    
    let j = particles.length;
    while(j--) {
        particles[j].draw();
        particles[j].update(j);
    }
    
    if (timerTick >= 20) {
        let startX = canvas.width / 2 + (Math.random() * 200 - 100);
        let startY = canvas.height;
        let targetX = Math.random() * canvas.width;
        let targetY = Math.random() * (canvas.height / 2);
        fireworks.push(new Firework(startX, startY, targetX, targetY));
        timerTick = 0;
    } else {
        timerTick++;
    }
}

// Start Setup
updateThemeIcon();
initializePool();
renderSheetList();

// Real-Time Winners List Poller
let lastWinnersCount = 0;
async function syncWinnersAndState() {
    try {
        const res = await fetch('../../data/bingo_items.json?v=' + Date.now());
        const data = await res.json();
        
        const winners = data.winners || [];
        const winnersList = document.getElementById('winners-list-container');
        document.getElementById('winner-count').textContent = winners.length;
        
        if (winners.length === 0) {
            winnersList.innerHTML = `<div style="text-align:center; padding:10px; font-style:italic;">ยังไม่มีผู้บิงโกในรอบนี้</div>`;
            lastWinnersCount = 0;
        } else {
            // Play a cute chime sound if a new winner is registered
            if (winners.length > lastWinnersCount) {
                // Play double chime
                playTone(880, 'sine', 0.15, 0.05);
                setTimeout(() => playTone(1320, 'sine', 0.25, 0.05), 80);
                lastWinnersCount = winners.length;
            }
            
            winnersList.innerHTML = '';
            winners.forEach((winner, idx) => {
                const div = document.createElement('div');
                div.className = 'item-row';
                div.style.padding = '8px 12px';
                div.style.background = 'rgba(251, 189, 0, 0.05)';
                div.style.borderColor = 'rgba(251, 189, 0, 0.15)';
                div.style.borderRadius = '10px';
                div.style.gap = '10px';
                div.innerHTML = `
                    <span style="font-weight:700; color:#fbbf24; font-size:0.8rem;">🏆 ที่ ${idx + 1}</span>
                    <span style="color:#fff; font-weight:600; font-size:0.85rem;">${winner.username}</span>
                    <span style="margin-left:auto; font-size:0.7rem; color:var(--text-muted); font-family:monospace;">${winner.time} น.</span>
                `;
                winnersList.appendChild(div);
            });
        }
    } catch(e) {}
}

// Start polling
setInterval(syncWinnersAndState, 2000);
syncWinnersAndState();

function showCustomConfirm() {
    return new Promise((resolve) => {
        const modal = document.getElementById('custom-confirm-modal');
        const okBtn = document.getElementById('confirm-ok-btn');
        const cancelBtn = document.getElementById('confirm-cancel-btn');
        
        modal.style.display = 'flex';
        
        function handleOk() {
            cleanup();
            resolve(true);
        }
        
        function handleCancel() {
            cleanup();
            resolve(false);
        }
        
        function cleanup() {
            okBtn.removeEventListener('click', handleOk);
            cancelBtn.removeEventListener('click', handleCancel);
            modal.style.display = 'none';
        }
        
        okBtn.addEventListener('click', handleOk);
        cancelBtn.addEventListener('click', handleCancel);
    });
}
</script>

<!-- 🛸 CUSTOM CONFIRM MODAL -->
<div id="custom-confirm-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); backdrop-filter: blur(8px); z-index: 99999; justify-content: center; align-items: center; padding: 16px;">
    <div style="background: #111422; border: 1px solid #1f2538; width: 100%; max-width: 400px; padding: 24px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); text-align: center; font-family: 'Prompt', sans-serif;">
        <h3 style="font-family: 'Chakra Petch', sans-serif; font-size: 1.25rem; color: #fff; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; gap: 6px;">
            <ion-icon name="alert-circle-outline" style="color: #ff453a; font-size: 1.6rem;"></ion-icon>
            เริ่มเกมรอบใหม่
        </h3>
        <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 24px; line-height: 1.4;">
            คุณต้องการเริ่มเกมรอบใหม่ใช่หรือไม่?<br>
            <span style="color: #ff453a; font-weight: 700;">(กระดานและผลการสุ่มของทุกคนจะเริ่มใหม่ทั้งหมด)</span>
        </p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button id="confirm-cancel-btn" class="btn-secondary" style="padding: 10px 20px; font-size: 0.85rem; border-radius: 10px; margin: 0; flex: 1; border: 1px solid var(--card-border); background: rgba(255,255,255,0.03); color: #fff; cursor: pointer;">ยกเลิก</button>
            <button id="confirm-ok-btn" class="btn-primary" style="padding: 10px 20px; font-size: 0.85rem; border-radius: 10px; margin: 0; flex: 1; background: #ff453a; border-color: #ff453a; color: #fff; cursor: pointer;">ตกลง เริ่มใหม่</button>
        </div>
    </div>
</div>

</body>
</html>