<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/PostComment.php';

$commentModel = new PostComment();

switch ($_SERVER['REQUEST_METHOD']) {
    
    case 'GET':
        // GET /api/post-comments.php?post_id=xxx
        
        if (empty($_GET['post_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'post_id là bắt buộc']);
            exit;
        }

        $postId = $_GET['post_id'];
        $comments = $commentModel->getPostComments($postId);
        $count = $commentModel->countPostComments($postId);

        echo json_encode([
            'success' => true,
            'comments' => $comments,
            'total_count' => $count
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        // POST /api/post-comments.php
        // Body: { post_id, content, parent_id (optional), author_name (guest), author_email (guest) }
        
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['post_id']) || empty($data['content'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'post_id và content là bắt buộc']);
            exit;
        }

        // Optional auth - user hoặc guest
        $user = AuthMiddleware::optionalAuth();
        
        $postId = $data['post_id'];
        $content = trim($data['content']);
        $parentId = $data['parent_id'] ?? null;

        try {
            if ($user) {
                // User đã login
                $commentId = $commentModel->createComment(
                    $postId,
                    $content,
                    $user['id'],
                    null,
                    null,
                    $parentId
                );
                $status = 'approved';
                $message = 'Bình luận của bạn đã được đăng';
            } else {
                // Guest comment
                if (empty($data['author_name']) || empty($data['author_email'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Guest comment cần có tên và email']);
                    exit;
                }

                // Validate email
                if (!filter_var($data['author_email'], FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
                    exit;
                }

                $commentId = $commentModel->createComment(
                    $postId,
                    $content,
                    null,
                    $data['author_name'],
                    $data['author_email'],
                    $parentId
                );
                $status = 'pending';
                $message = 'Bình luận của bạn đang chờ duyệt';
            }

            echo json_encode([
                'success' => true,
                'message' => $message,
                'comment_id' => $commentId,
                'status' => $status
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        // PUT /api/post-comments.php?id=xxx
        // Body: { content }
        // User edit comment của mình (trong 24h)
        
        $user = AuthMiddleware::requireUser();
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'comment id là bắt buộc']);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['content'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'content là bắt buộc']);
            exit;
        }

        try {
            $commentModel->editOwnComment($_GET['id'], $user['id'], trim($data['content']));
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật bình luận'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        // DELETE /api/post-comments.php?id=xxx
        // User xóa comment của mình (trong 24h)
        
        $user = AuthMiddleware::requireUser();
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'comment id là bắt buộc']);
            exit;
        }

        try {
            $commentModel->deleteOwnComment($_GET['id'], $user['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa bình luận'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}