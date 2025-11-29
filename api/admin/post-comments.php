<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/PostComment.php';

// Only admin can access
$admin = AuthMiddleware::requireAdmin();

$commentModel = new PostComment();

switch ($_SERVER['REQUEST_METHOD']) {
    
    case 'GET':
        // GET /api/admin/post-comments.php - Danh sách comments
        // Params: ?status=pending&post_id=xxx&search=keyword&page=1&limit=20
        
        $filters = [];

        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }

        if (!empty($_GET['post_id'])) {
            $filters['post_id'] = $_GET['post_id'];
        }

        if (!empty($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        $comments = $commentModel->getAllComments($limit, $offset, $filters);
        $total = $commentModel->countAllComments($filters);

        // Get pending count
        $pendingCount = $commentModel->count('status = ?', [PostComment::STATUS_PENDING]);

        echo json_encode([
            'success' => true,
            'comments' => $comments,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'items_per_page' => $limit
            ],
            'pending_count' => $pendingCount,
            'filters' => $filters
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'PUT':
        // PUT /api/admin/post-comments.php?id=xxx
        // Body: { status: 'approved'|'spam' } hoặc { action: 'approve'|'spam' }
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'comment id là bắt buộc']);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        try {
            $commentId = $_GET['id'];

            // Handle action shortcuts
            if (isset($data['action'])) {
                switch ($data['action']) {
                    case 'approve':
                        $data['status'] = PostComment::STATUS_APPROVED;
                        break;
                    case 'spam':
                        $data['status'] = PostComment::STATUS_SPAM;
                        break;
                    case 'pending':
                        $data['status'] = PostComment::STATUS_PENDING;
                        break;
                }
            }

            if (isset($data['status'])) {
                $commentModel->updateStatus($commentId, $data['status']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã cập nhật trạng thái comment'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Thiếu status hoặc action']);
            }

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        // DELETE /api/admin/post-comments.php?id=xxx
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'comment id là bắt buộc']);
            exit;
        }

        $comment = $commentModel->findById($_GET['id']);
        if (!$comment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Comment không tồn tại']);
            exit;
        }

        $commentModel->delete($_GET['id']);

        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa comment'
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}