<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/Post.php';

// Only admin can access
$admin = AuthMiddleware::requireAdmin();

$postModel = new Post();

switch ($_SERVER['REQUEST_METHOD']) {
    
    case 'GET':
        // GET /api/admin/posts.php?id=xxx - Chi tiết bài viết để edit
        if (isset($_GET['id'])) {
            $post = $postModel->findById($_GET['id']);
            
            if (!$post) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Bài viết không tồn tại']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'post' => $post
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // GET /api/admin/posts.php - Danh sách tất cả bài viết (bao gồm draft)
        // Params: ?status=draft&category_id=1&search=keyword&page=1&limit=20
        
        $filters = [];

        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }

        if (!empty($_GET['category_id'])) {
            $filters['category_id'] = (int)$_GET['category_id'];
        }

        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        if (!empty($_GET['include_deleted'])) {
            $filters['include_deleted'] = true;
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        $posts = $postModel->getAllPosts($limit, $offset, $filters);
        $total = $postModel->countAllPosts($filters);

        echo json_encode([
            'success' => true,
            'posts' => $posts,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'items_per_page' => $limit
            ],
            'filters' => $filters
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        // POST /api/admin/posts.php - Tạo bài viết mới
        // Body: { title, content, excerpt, category_id, thumbnail, status, is_featured }
        
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['title']) || empty($data['content'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'title và content là bắt buộc']);
            exit;
        }

        try {
            $postId = $postModel->createPost(
                $admin['id'],
                $data['title'],
                $data['content'],
                $data['excerpt'] ?? null,
                $data['category_id'] ?? null,
                $data['thumbnail'] ?? null,
                $data['status'] ?? Post::STATUS_DRAFT,
                $data['is_featured'] ?? 0
            );

            $post = $postModel->findById($postId);

            echo json_encode([
                'success' => true,
                'message' => 'Đã tạo bài viết',
                'post' => $post
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        // PUT /api/admin/posts.php?id=xxx
        // Body: { title, content, excerpt, category_id, thumbnail, status, is_featured }
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'post id là bắt buộc']);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data) || !is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Check post exists
        $post = $postModel->findById($_GET['id']);
        if (!$post) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Bài viết không tồn tại']);
            exit;
        }

        // Restore action
        if (isset($data['action']) && $data['action'] === 'restore') {
            $postModel->restore($_GET['id']);
            echo json_encode([
                'success' => true,
                'message' => 'Đã khôi phục bài viết'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            // Prepare update data
            $updateData = [];
            $allowedFields = ['title', 'content', 'excerpt', 'category_id', 'thumbnail', 'status', 'is_featured'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Không có dữ liệu để cập nhật']);
                exit;
            }

            $postModel->updatePost($_GET['id'], $updateData);

            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật bài viết'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        // DELETE /api/admin/posts.php?id=xxx - Soft delete
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'post id là bắt buộc']);
            exit;
        }

        $post = $postModel->findById($_GET['id']);
        if (!$post) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Bài viết không tồn tại']);
            exit;
        }

        $postModel->softDelete($_GET['id']);

        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa bài viết (có thể khôi phục)'
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}