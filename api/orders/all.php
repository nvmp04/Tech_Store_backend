<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/Order.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

AuthMiddleware::requireUser();
$orderModel = new Order();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;
$status = isset($_GET['status']) ? $_GET['status'] : null;

$orders = $orderModel->getAllOrders($limit, $offset, $status);
$total = $orderModel->countAllOrders($status);
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
?>
