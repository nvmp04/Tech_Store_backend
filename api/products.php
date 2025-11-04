<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Max-Age: 86400');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

require_once __DIR__ . '/../config/Database.php';   
require_once __DIR__ . '/../services/ProductsService.php';

function pdo(): PDO {
    static $pdo = null; 
    if ($pdo) return $pdo; 

    $db = new Database(); 
    $pdo = $db->connect(); 
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Thiết lập chế độ fetch
    return $pdo;
}

function out($data, int $status = 200) {
    http_response_code($status); 
    echo json_encode($data, JSON_UNESCAPED_UNICODE); 
    exit; 
}

$svc = new ProductsService(pdo());

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Lấy tất cả sản phẩm
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $id = (int)$_GET['id'];
            $row = $svc->getOne($id);
            if (!$row) {
                out(['success' => false, 'message' => 'Không tìm thấy sản phẩm'], 404);
            }
            out(['success' => true, 'data' => $row]);
        } else {
            out(['success' => true, 'data' => $svc->getAll()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Kiểm tra tạo sản phẩm mới
        $body = json_decode(file_get_contents('php://input'), true);
        $result = $svc->create($body);

        if (!empty($result['success']) && $result['success']) {
            out($result, 201);
        } else {
            out($result, 400);
        }

    } else {
        out(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (Throwable $e) {
    out(['success' => false, 'message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
}
