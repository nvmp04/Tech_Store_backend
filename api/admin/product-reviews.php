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
require_once __DIR__ . '/../../models/ProductReview.php';

// Only admin can access
$admin = AuthMiddleware::requireAdmin();

$reviewModel = new ProductReview();

switch ($_SERVER['REQUEST_METHOD']) {
    
    case 'GET':
        // GET /api/admin/product-reviews.php - Danh sách tất cả reviews/comments
        // Params: ?product_id=xxx&verified=1&status=approved&page=1&limit=20
        
        $filters = [];

        if (!empty($_GET['product_id'])) {
            $filters['product_id'] = (int)$_GET['product_id'];
        }

        if (isset($_GET['verified'])) {
            $filters['verified'] = (int)$_GET['verified'];
        }

        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        $reviews = $reviewModel->getAllReviews($limit, $offset, $filters);
        $total = $reviewModel->count();

        // Get stats
        $hiddenCount = $reviewModel->count('status = ?', [ProductReview::STATUS_HIDDEN]);
        $spamCount = $reviewModel->count('status = ?', [ProductReview::STATUS_SPAM]);

        echo json_encode([
            'success' => true,
            'reviews' => $reviews,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'items_per_page' => $limit
            ],
            'stats' => [
                'hidden_count' => $hiddenCount,
                'spam_count' => $spamCount
            ],
            'filters' => $filters
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'PUT':
        // PUT /api/admin/product-reviews.php?id=xxx
        // Body: { status: 'approved'|'hidden'|'spam' } hoặc { admin_response: 'text' }
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'review id là bắt buộc']);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        try {
            $reviewId = $_GET['id'];
            $updated = false;

            // Update status
            if (isset($data['status'])) {
                $reviewModel->updateStatus($reviewId, $data['status']);
                $updated = true;
            }

            // Add admin response
            if (isset($data['admin_response'])) {
                $response = trim($data['admin_response']);
                if ($response === '') {
                    // Remove admin response
                    $reviewModel->update($reviewId, [
                        'admin_response' => null,
                        'admin_response_at' => null
                    ]);
                } else {
                    $reviewModel->addAdminResponse($reviewId, $response);
                }
                $updated = true;
            }

            if ($updated) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Đã cập nhật review'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Không có dữ liệu để cập nhật']);
            }

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        // DELETE /api/admin/product-reviews.php?id=xxx
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'review id là bắt buộc']);
            exit;
        }

        $review = $reviewModel->findById($_GET['id']);
        if (!$review) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Review không tồn tại']);
            exit;
        }

        $reviewModel->delete($_GET['id']);

        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa review/comment'
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}