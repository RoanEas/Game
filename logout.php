<?php
// 1. เริ่มต้นการเรียกใช้งาน Session เพื่อให้ระบบรู้จักว่าใครกำลังจะล็อกเอาต์
session_start();

// 2. ล้างค่าทุกตัวแปรที่อยู่ใน Session ของผู้ใช้ออกทั้งหมด
$_SESSION = array();

// 3. ทำลายคุ้กกี้ของ Session (ถ้ามี) เพื่อไม่ให้หลงเหลือเศษขยะในเบราว์เซอร์
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. สั่งทำลายเซสชันบนเซิร์ฟเวอร์อย่างถาวร
session_destroy();

// 5. ส่งผู้ใช้กลับไปยังหน้าแรก (หน้าล็อกอินหลัก) ทันที
header("Location: index.php");
exit();
?>