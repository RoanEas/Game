<?php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Bad Request"]);
    exit;
}

$action = $input['action'];

// Access Control
if ($action === 'claim_bingo') {
    // Normal player needs to be logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Unauthorized"]);
        exit;
    }
} else {
    // Admin only actions
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Unauthorized"]);
        exit;
    }
}

$jsonPath = dirname(__DIR__, 2) . '/data/bingo_items.json';

// Read existing data
$existingData = [];
if (file_exists($jsonPath)) {
    $existingData = json_decode(file_get_contents($jsonPath), true);
}
if (!is_array($existingData)) {
    $existingData = [];
}
if (!isset($existingData['items'])) $existingData['items'] = [];
if (!isset($existingData['drawn_ids'])) $existingData['drawn_ids'] = [];
if (!isset($existingData['winners'])) $existingData['winners'] = [];
if (!isset($existingData['round_id'])) $existingData['round_id'] = uniqid('r_', true);

if ($action === 'save_items') {
    if (isset($input['items']) && is_array($input['items'])) {
        $existingData['items'] = $input['items'];
        file_put_contents($jsonPath, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(["status" => "success", "message" => "Items saved successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid items data"]);
    }
} elseif ($action === 'new_round') {
    $existingData['round_id'] = uniqid('r_', true);
    $existingData['drawn_ids'] = [];
    $existingData['winners'] = [];
    
    // Generate pre-determined draw sequence
    $itemIds = [];
    foreach (($existingData['items'] ?? []) as $item) {
        $itemIds[] = intval($item['id']);
    }
    shuffle($itemIds);
    $existingData['draw_sequence'] = $itemIds;
    
    // Reset player assignments
    $existingData['player_assignments'] = [];
    
    file_put_contents($jsonPath, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode([
        "status" => "success",
        "round_id" => $existingData['round_id'],
        "draw_sequence" => $existingData['draw_sequence'],
        "drawn_ids" => $existingData['drawn_ids']
    ]);
} elseif ($action === 'draw_item') {
    $itemId = intval($input['id']);
    if (!in_array($itemId, $existingData['drawn_ids'])) {
        $existingData['drawn_ids'][] = $itemId;
    }
    file_put_contents($jsonPath, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(["status" => "success", "drawn_ids" => $existingData['drawn_ids']]);
} elseif ($action === 'claim_bingo') {
    $username = $_SESSION['username'] ?? 'Anonymous';
    
    // Check if already in winners list to prevent duplicate entries
    $alreadyWon = false;
    foreach ($existingData['winners'] as $winner) {
        if ($winner['username'] === $username) {
            $alreadyWon = true;
            break;
        }
    }
    
    if (!$alreadyWon) {
        date_default_timezone_set('Asia/Bangkok');
        $existingData['winners'][] = [
            "username" => $username,
            "time" => date('H:i:s')
        ];
        file_put_contents($jsonPath, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    echo json_encode(["status" => "success", "winners" => $existingData['winners']]);
} else {
    echo json_encode(["status" => "error", "message" => "Unknown action"]);
}
