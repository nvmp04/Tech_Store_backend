<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/Order.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Authenticate user
$user = AuthMiddleware::requireUser();

// Get order ID from query string
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'order_id là bắt buộc']);
    exit;
}

$orderModel = new Order();

// Get order (verify ownership)
$order = $orderModel->getOrderById($orderId, $user['id']);

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
    exit;
}

// Get order items
$items = $orderModel->getOrderItems($orderId);

echo json_encode([
    'success' => true,
    'order' => array_merge($order, ['items' => $items])
], JSON_UNESCAPED_UNICODE);