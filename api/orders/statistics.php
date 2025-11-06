
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

// Get statistics
$statistics = $orderModel->getOrderStatistics($user['id']);

// Format data
$formattedStats = [
    'total_orders' => 0,
    'total_amount' => 0,
    'by_status' => []
];

foreach ($statistics as $stat) {
    $formattedStats['total_orders'] += $stat['count'];
    $formattedStats['total_amount'] += $stat['total_amount'];
    $formattedStats['by_status'][$stat['status']] = [
        'count' => $stat['count'],
        'total_amount' => $stat['total_amount']
    ];
}

echo json_encode([
    'success' => true,
    'statistics' => $formattedStats
], JSON_UNESCAPED_UNICODE);