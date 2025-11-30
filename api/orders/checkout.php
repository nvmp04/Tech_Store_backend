<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../services/OrderService.php';

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

// Validate required fields
$required = ['full_name', 'email', 'phone', 'province', 'district', 'ward', 'address_detail'];

foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => "Trường {$field} là bắt buộc"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Prepare order info
$orderInfo = [
    'full_name' => $data['full_name'],
    'email' => $data['email'],
    'phone' => $data['phone'],
    'province' => $data['province'],
    'district' => $data['district'],
    'ward' => $data['ward'],
    'address_detail' => $data['address_detail'],
    'note' => $data['note'] ?? null
];

$orderService = new OrderService();

// Check if specific items are selected
if (isset($data['cart_item_ids']) && is_array($data['cart_item_ids']) && !empty($data['cart_item_ids'])) {
    // Checkout selected items by IDs
    $result = $orderService->checkoutSelectedItems($user['id'], $orderInfo, $data['cart_item_ids']);
} else {
    // Checkout items marked as is_selected = 1
    $result = $orderService->checkoutSelectedItems($user['id'], $orderInfo);
}

http_response_code($result['success'] ? 200 : 400);
echo json_encode($result, JSON_UNESCAPED_UNICODE);