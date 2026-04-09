<?php
/**
 * Institute Map — Rooms API
 * Returns room/floor data as JSON
 * Usage:
 *   GET /api/rooms.php              → all floors
 *   GET /api/rooms.php?floor=ground → single floor
 *   GET /api/rooms.php?search=lab   → search across all floors
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$dataFile = __DIR__ . '/../data/rooms.json';

if (!file_exists($dataFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Data file not found']);
    exit;
}

$data = json_decode(file_get_contents($dataFile), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

$floor  = isset($_GET['floor'])  ? trim($_GET['floor'])  : null;
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : null;

// Return single floor
if ($floor && isset($data['floors'][$floor])) {
    echo json_encode([
        'building' => $data['building'],
        'floor'    => $floor,
        'data'     => $data['floors'][$floor]
    ]);
    exit;
}

// Search across all floors
if ($search && strlen($search) >= 2) {
    $results = [];
    foreach ($data['floors'] as $floorKey => $floorData) {
        foreach ($floorData['rooms'] as $room) {
            if (
                stripos($room['id'],   $search) !== false ||
                stripos($room['name'], $search) !== false ||
                stripos($room['type'], $search) !== false
            ) {
                $results[] = array_merge($room, [
                    'floor'       => $floorKey,
                    'floor_label' => $floorData['label']
                ]);
            }
        }
    }
    echo json_encode([
        'query'   => $search,
        'count'   => count($results),
        'results' => $results
    ]);
    exit;
}

// Return all floors (default)
echo json_encode([
    'building'    => $data['building'],
    'institution' => $data['institution'],
    'floors'      => $data['floors']
]);
