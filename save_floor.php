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

if (!isset($data['floorKey']) || !isset($data['label']) || !isset($data['image']) || !isset($data['rooms'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields (floorKey, label, image, rooms).']);
    exit;
}

$floorKey = strtolower(trim($data['floorKey']));
$label = trim($data['label']);
$image = trim($data['image']);
$rooms = $data['rooms'];
$mainPath = isset($data['mainPath']) ? $data['mainPath'] : [];

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

// Ensure the floors array exists
if (!isset($currentData['floors'])) {
    $currentData['floors'] = [];
}

// Clean up numeric strings where possible for floats and ints
foreach ($rooms as &$room) {
    if (isset($room['top'])) $room['top'] = (float)$room['top'];
    if (isset($room['left'])) $room['left'] = (float)$room['left'];
    if (isset($room['width'])) $room['width'] = (float)$room['width'];
    if (isset($room['height'])) $room['height'] = (float)$room['height'];
    if (isset($room['capacity']) && $room['capacity'] !== null && $room['capacity'] !== '') {
        $room['capacity'] = (int)$room['capacity'];
    } else {
        $room['capacity'] = null;
    }
}
unset($room);

// Update or create the floor entry
$currentData['floors'][$floorKey] = [
    'label' => $label,
    'image' => $image,
    'rooms' => $rooms,
    'mainPath' => $mainPath
];

$jsonData = json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (file_put_contents($dataFile, $jsonData) !== false) {
    echo json_encode(['success' => true, 'message' => "Floor '{$label}' saved successfully."]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data to file. Check file permissions.']);
}
