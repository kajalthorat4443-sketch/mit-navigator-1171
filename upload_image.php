<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Disable caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error occurred.']);
    exit;
}

$file = $_FILES['image'];
$fileName = basename($file['name']);
$fileSize = $file['size'];
$fileTmp = $file['tmp_name'];
$fileType = mime_content_type($fileTmp);

// Allowed mime types
$allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml'];

if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, and SVG are allowed.']);
    exit;
}

// Ensure the filename is safe
$safeFileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);

$uploadDir = __DIR__ . '/../assets/images/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . $safeFileName;

if (move_uploaded_file($fileTmp, $destination)) {
    // Return relative path for frontend use
    echo json_encode([
        'success' => true,
        'path' => 'assets/images/' . $safeFileName
    ]);
} 

else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file.']);
}
