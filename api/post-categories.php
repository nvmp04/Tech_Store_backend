<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/PostCategory.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$categoryModel = new PostCategory();

// GET /api/post-categories.php - Lấy tất cả categories (có post_count)
$categories = $categoryModel->getCategoriesWithPosts();

echo json_encode([
    'success' => true,
    'categories' => $categories
], JSON_UNESCAPED_UNICODE);