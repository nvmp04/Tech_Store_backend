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
$orderModel = new Order();

// Get pagination params
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

// Get filter params
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Get orders
$orders = $orderModel->getUserOrders($user['id'], $limit, $offset, $status);
$total = $orderModel->countUserOrders($user['id'], $status);

// Calculate pagination info
$totalPages = ceil($total / $limit);

echo json_encode([
    'success' => true,
    'orders' => $orders,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_items' => $total,
        'items_per_page' => $limit,
        'has_next' => $page < $totalPages,
        'has_prev' => $page > 1
    ]
], JSON_UNESCAPED_UNICODE);