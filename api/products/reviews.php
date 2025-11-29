<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/ProductReview.php';

$reviewModel = new ProductReview();

switch ($_SERVER['REQUEST_METHOD']) {
    
    case 'GET':
        // GET /api/products/reviews.php?product_id=123
        // GET /api/products/reviews.php?product_id=123&verified=1 (chỉ lấy reviews)
        
        if (empty($_GET['product_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'product_id là bắt buộc']);
            exit;
        }

        $productId = (int)$_GET['product_id'];
        $verified = isset($_GET['verified']) ? (int)$_GET['verified'] : null;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        // Lấy reviews/comments
        $reviews = $reviewModel->getProductReviews($productId, $limit, $offset, $verified);
        $total = $reviewModel->countProductReviews($productId, $verified);
        
        // Lấy rating stats với distribution
        $stats = $reviewModel->getAverageRating($productId);
        $distribution = $reviewModel->getRatingDistribution($productId);

        echo json_encode([
            'success' => true,
            'reviews' => $reviews,
            'stats' => [
                'average_rating' => round($stats['avg_rating'] ?? 0, 1),
                'review_count' => $stats['review_count'] ?? 0,
                'total_comments' => $total,
                'distribution' => $distribution
            ],
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_items' => $total,
                'items_per_page' => $limit
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        // POST /api/products/reviews.php
        // Body: { product_id, content, rating (optional) }
        
        $user = AuthMiddleware::requireUser();
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['product_id']) || empty($data['content'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'product_id và content là bắt buộc']);
            exit;
        }

        $productId = (int)$data['product_id'];
        $content = trim($data['content']);
        $rating = isset($data['rating']) ? (int)$data['rating'] : null;

        // Validate rating nếu có
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Rating phải từ 1-5']);
            exit;
        }

        try {
            // Check user đã mua sản phẩm chưa
            $hasPurchased = $reviewModel->hasUserPurchased($user['id'], $productId);
            $orderId = null;

            if ($hasPurchased) {
                // User đã mua → tạo review (verified=1)
                if (!$rating) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Review sản phẩm cần có rating']);
                    exit;
                }

                // Lấy order_id để gắn vào review
                $orderId = $reviewModel->getLatestPurchaseOrder($user['id'], $productId);
            } else {
                // User chưa mua → tạo comment thường (verified=0)
                // Rating là optional
            }

            $reviewId = $reviewModel->createReview(
                $productId,
                $user['id'],
                $content,
                $rating,
                $orderId
            );

            echo json_encode([
                'success' => true,
                'message' => $hasPurchased ? 'Đánh giá của bạn đã được gửi' : 'Bình luận của bạn đã được gửi',
                'review_id' => $reviewId,
                'verified' => $hasPurchased
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
    $user = AuthMiddleware::requireUser();
    
    if (empty($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'review id là bắt buộc']);
        exit;
    }
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['content'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'content là bắt buộc']);
        exit;
    }
    
    $reviewId = $_GET['id'];
    $content = trim($data['content']);
    $rating = isset($data['rating']) ? (int)$data['rating'] : null;
    
    // Validate rating nếu có
    if ($rating !== null && ($rating < 1 || $rating > 5)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rating phải từ 1-5']);
        exit;
    }
    
    try {
        // Lấy thông tin review trước
        $review = $reviewModel->getReviewById($reviewId);
        
        if (!$review) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy đánh giá này'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Check ownership
        if ($review['user_id'] != $user['id']) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Bạn không có quyền chỉnh sửa đánh giá này'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Check verified status
        if ($review['verified'] == 0) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Chỉ có thể chỉnh sửa đánh giá của sản phẩm đã mua. Bình luận thường không thể chỉnh sửa.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Check thời gian (30 ngày)
        $createdAt = strtotime($review['created_at']);
        $daysPassed = floor((time() - $createdAt) / (60 * 60 * 24));
        
        if ($daysPassed > 30) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => "Đã quá thời hạn chỉnh sửa (30 ngày). Đánh giá của bạn đã đăng được {$daysPassed} ngày.",
                'days_passed' => $daysPassed
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Rating bắt buộc cho verified review
        if ($rating === null) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Đánh giá sản phẩm phải có rating từ 1-5 sao'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Update review
        $reviewModel->updateUserReview($reviewId, $user['id'], $content, $rating);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã cập nhật đánh giá của bạn',
            'days_remaining' => 30 - $daysPassed
        ], JSON_UNESCAPED_UNICODE);
        
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;
    
    case 'DELETE':
        // DELETE /api/products/reviews.php?id={review_id}
        // User xóa review của mình (trong 7 ngày)
        
        $user = AuthMiddleware::requireUser();
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'review id là bắt buộc']);
            exit;
        }
        
        try {
            // $stmt = $reviewModel->getConnection()->prepare("
            //     SELECT * FROM product_reviews 
            //     WHERE id = ? 
            //     AND user_id = ? 
            //     AND verified = 1
            //     AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            // ");
             $stmt = $reviewModel->getConnection()->prepare("
                SELECT * FROM product_reviews 
                WHERE id = ? 
                AND user_id = ? 
         
                AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$_GET['id'], $user['id']]);
            $review = $stmt->fetch();
            
            if (!$review) {
                http_response_code(403);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Bạn chỉ có thể xóa review trong vòng 7 ngày kể từ khi đăng'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $reviewModel->delete($_GET['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa đánh giá của bạn'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}