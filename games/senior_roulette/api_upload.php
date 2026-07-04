<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No file uploaded"]);
    exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "Upload error code: " . $file['error']]);
    exit;
}

// Check allowed MIME types
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(["status" => "error", "message" => "Invalid file type. Only JPG, PNG, GIF, WEBP allowed."]);
    exit;
}

// Generate safe unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (!$ext) $ext = 'jpg';
$filename = 'senior_' . time() . '_' . rand(1000,9999) . '.' . strtolower($ext);

// The root of minigmae is 2 levels up
$targetDir = dirname(__DIR__, 2) . '/assets/seniors/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$targetPath = $targetDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Return relative path from minigmae root
    $relativePath = 'assets/seniors/' . $filename;
    echo json_encode(["status" => "success", "filepath" => $relativePath]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to move uploaded file"]);
}
