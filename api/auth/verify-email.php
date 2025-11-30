<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../services/AuthService.php';

// Get token from query string
$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token không hợp lệ']);
    exit;
}

// Verify email
$authService = new AuthService();
$result = $authService->verifyEmail($token);

http_response_code($result['success'] ? 200 : 400);
echo json_encode($result, JSON_UNESCAPED_UNICODE);