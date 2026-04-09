<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload.']);
    exit;
}

if (!isset($data['floorKey'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field: floorKey.']);
    exit;
}

$floorKey = strtolower(trim($data['floorKey']));

$dataFile = __DIR__ . '/../data/rooms.json';

if (!file_exists($dataFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Data file not found']);
    exit;
}

$currentData = json_decode(file_get_contents($dataFile), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Existing data file contains invalid JSON']);
    exit;
}

if (!isset($currentData['floors'][$floorKey])) {
    http_response_code(404);
    echo json_encode(['error' => "Floor '{$floorKey}' not found."]);
    exit;
}

// Remove the floor
unset($currentData['floors'][$floorKey]);

$jsonData = json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (file_put_contents($dataFile, $jsonData) !== false) {
    echo json_encode(['success' => true, 'message' => "Floor '{$floorKey}' deleted successfully."]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data. Check file permissions.']);
}
