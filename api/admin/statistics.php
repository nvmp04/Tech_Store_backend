<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/User.php';

$admin = AuthMiddleware::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userModel = new User();

$stats = [
    'total_users' => $userModel->count(),
    'total_admins' => $userModel->count('role = ?', ['admin']),
    'total_regular_users' => $userModel->count('role = ?', ['user']),
    'total_guests' => $userModel->count('role = ?', ['guest']),
];

echo json_encode([
    'success' => true,
    'statistics' => $stats
]);