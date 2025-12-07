<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db = new Database();
$pdo = $db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare('SELECT id, url, created_at FROM carousel ORDER BY created_at ASC');
        $stmt->execute();
        $carousel = $stmt->fetchAll();

        echo json_encode(['success' => true, 'carousel' => $carousel], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Kiểm tra file upload
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        $errorMsg = 'No file uploaded or upload error';
        if (isset($_FILES['image'])) {
            $errorMsg .= ' (error code: ' . $_FILES['image']['error'] . ')';
        }
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }

    $file = $_FILES['image'];

    // Các loại file cho phép
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // Kiểm tra loại file
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, WebP are allowed']);
        exit;
    }

    // Kiểm tra phần mở rộng
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file extension']);
        exit;
    }

    // Kiểm tra kích thước file (tối đa 5MB)
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxFileSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
        exit;
    }

    // Tạo thư mục upload carousel nếu chưa có
    $uploadDir = __DIR__ . '/../uploads/carousel/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Tạo tên file unique
    $fileName = uniqid('carousel_', true) . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;

    // Lưu file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        exit;
    }

    // URL công khai của ảnh
    $imageUrl = 'http://localhost/BE_Tech_Store/uploads/carousel/' . $fileName;

    try {
        $stmt = $pdo->prepare('INSERT INTO carousel (url) VALUES (?)');
        $stmt->execute([$imageUrl]);

        // For UUID primary key, get the last inserted ID or generate new one
        $getIdStmt = $pdo->prepare('SELECT id FROM carousel WHERE url = ? ORDER BY created_at DESC LIMIT 1');
        $getIdStmt->execute([$imageUrl]);
        $result = $getIdStmt->fetch();
        $carouselId = $result ? $result['id'] : null;

        echo json_encode(['success' => true, 'id' => $carouselId, 'url' => $imageUrl, 'message' => 'Carousel item created']);
        exit;
    } catch (Exception $e) {
        // If DB insert fails, delete the uploaded file
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Check for duplicate URL error
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'URL already exists']);
            exit;
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    $carouselId = isset($input['id']) ? trim($input['id']) : null;

    if (!$carouselId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM carousel WHERE id = ? LIMIT 1');
        $stmt->execute([$carouselId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Carousel item not found']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM carousel WHERE id = ?');
        $stmt->execute([$carouselId]);

        echo json_encode(['success' => true, 'id' => $carouselId, 'message' => 'Carousel item deleted']);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
exit;

?>
