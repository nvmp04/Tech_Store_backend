<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/PostComment.php';

$postModel = new Post();
$commentModel = new PostComment();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// GET /api/posts.php?slug=xxx - Chi tiết bài viết
if (isset($_GET['slug'])) {
    $slug = $_GET['slug'];
    $post = $postModel->getBySlug($slug);

    if (!$post) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Bài viết không tồn tại'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Tăng view count
    $postModel->incrementViewCount($post['id']);
    $post['view_count']++;

    // Lấy comments
    $comments = $commentModel->getPostComments($post['id']);
    $commentCount = $commentModel->countPostComments($post['id']);

    // Lấy bài viết liên quan
    $relatedPosts = [];
    if ($post['category_id']) {
        $relatedPosts = $postModel->getRelatedPosts($post['id'], $post['category_id'], 5);
    }

    echo json_encode([
        'success' => true,
        'post' => $post,
        'comments' => $comments,
        'comment_count' => $commentCount,
        'related_posts' => $relatedPosts
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// GET /api/posts.php - Danh sách bài viết (có filter & search)
// Params: ?category=slug&search=keyword&featured=1&page=1&limit=10

$filters = [];

if (!empty($_GET['category'])) {
    $filters['category'] = $_GET['category'];
}

if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

if (isset($_GET['featured'])) {
    $filters['featured'] = (int)$_GET['featured'];
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

$posts = $postModel->getPublishedPosts($limit, $offset, $filters);
$total = $postModel->countPublishedPosts($filters);

echo json_encode([
    'success' => true,
    'posts' => $posts,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => ceil($total / $limit),
        'total_items' => $total,
        'items_per_page' => $limit,
        'has_next' => $page < ceil($total / $limit),
        'has_prev' => $page > 1
    ],
    'filters' => $filters
], JSON_UNESCAPED_UNICODE);