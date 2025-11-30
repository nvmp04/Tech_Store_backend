<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Kiểm tra file upload
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['image'];

// Các loại file cho phép
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Kiểm tra loại file
if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, WebP are allowed']);
    exit();
}

// Kiểm tra phần mở rộng
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($fileExtension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file extension']);
    exit();
}

// Kiểm tra kích thước file (tối đa 5MB)
$maxFileSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxFileSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File size exceeds 5MB limit']);
    exit();
}

// Tạo thư mục nếu chưa tồn tại
$uploadDir = __DIR__ . '/../uploads/products/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Tạo tên file unique
$fileName = uniqid('img_', true) . '.' . $fileExtension;
$filePath = $uploadDir . $fileName;

// Di chuyển file
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit();
}

// Trả về URL của ảnh đã upload
$imageUrl = 'http://localhost/BE_Tech_Store/uploads/products/' . $fileName;

http_response_code(200);
echo json_encode([
    'success' => true,
    'url' => $imageUrl,
    'fileName' => $fileName
]);
