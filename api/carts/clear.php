<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/Cart.php';

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
$cartModel = new Cart();

// Get cart
$cart = $cartModel->getOrCreateCart($user['id']);

// Clear cart
$cleared = $cartModel->clearCart($cart['id']);

if ($cleared) {
    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa toàn bộ giỏ hàng'
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể xóa giỏ hàng']);
}