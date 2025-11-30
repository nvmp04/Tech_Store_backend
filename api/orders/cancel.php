<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/Order.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Authenticate user
$user = AuthMiddleware::requireUser();

// Get input data
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'order_id là bắt buộc']);
    exit;
}

$orderModel = new Order();

// Get order to check status
$order = $orderModel->getOrderById($data['order_id'], $user['id']);

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
    exit;
}

// Only allow cancel if order is pending or confirmed
if (!in_array($order['status'], ['pending', 'confirmed'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Chỉ có thể hủy đơn hàng đang chờ xử lý hoặc đã xác nhận'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Cancel order
$cancelled = $orderModel->cancelOrder($data['order_id'], $user['id']);

if ($cancelled) {
    echo json_encode([
        'success' => true,
        'message' => 'Đã hủy đơn hàng thành công'
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể hủy đơn hàng']);
}