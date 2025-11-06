<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AuthService.php';

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

// Get input data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (empty($data['email']) || empty($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email và password là bắt buộc']);
    exit;
}

// Register user
$authService = new AuthService();
$result = $authService->register(
    $data['email'],
    $data['password'],
    $data['full_name'] ?? null
);

http_response_code($result['success'] ? 201 : 400);
echo json_encode($result, JSON_UNESCAPED_UNICODE);