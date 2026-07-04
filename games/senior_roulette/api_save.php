<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Bad Request"]);
    exit;
}

$projectDir = '/minigmae'; 
$themePath = $_SERVER['DOCUMENT_ROOT'] . $projectDir . '/data/senior_theme.json';
$seniorsPath = $_SERVER['DOCUMENT_ROOT'] . $projectDir . '/data/seniors_list.json';

if ($input['action'] === 'save_theme') {
    if (isset($input['theme']) && is_array($input['theme'])) {
        $themeData = ["theme" => $input['theme']];
        file_put_contents($themePath, json_encode($themeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(["status" => "success", "message" => "Theme saved successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid theme data"]);
    }
} 
elseif ($input['action'] === 'save_seniors') {
    if (isset($input['seniors']) && is_array($input['seniors'])) {
        $seniorsData = ["seniors" => $input['seniors']];
        file_put_contents($seniorsPath, json_encode($seniorsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(["status" => "success", "message" => "Seniors saved successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid seniors data"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Unknown action"]);
}
