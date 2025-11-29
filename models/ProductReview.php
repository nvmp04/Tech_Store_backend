<?php
require_once __DIR__ . '/BaseModel.php';

class ProductReview extends BaseModel {
    protected $table = 'product_reviews';

    const STATUS_APPROVED = 'approved';
    const STATUS_HIDDEN = 'hidden';
    const STATUS_SPAM = 'spam';

    /**
     * Tạo review/comment mới
     */
    public function createReview($productId, $userId, $content, $rating = null, $orderId = null) {
        // Check nếu là review (có orderId) thì phải có rating
        $verified = $orderId ? 1 : 0;
        
        if ($verified && !$rating) {
            throw new InvalidArgumentException("Review phải có rating");
        }

        // Check user đã review sản phẩm này chưa (chỉ với verified=1)
        if ($verified && $this->hasUserReviewed($userId, $productId)) {
            throw new Exception("Bạn đã đánh giá sản phẩm này rồi");
        }

        $data = [
            'product_id' => $productId,
            'user_id' => $userId,
            'order_id' => $orderId,
            'content' => $content,
            'rating' => $rating,
            'verified' => $verified,
            'status' => self::STATUS_APPROVED
        ];

        return $this->create($data);
    }

    /**
     * Check user đã review sản phẩm chưa
     */
    public function hasUserReviewed($userId, $productId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE user_id = ? AND product_id = ? AND verified = 1 AND status = ?
        ");
        $stmt->execute([$userId, $productId, self::STATUS_APPROVED]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Lấy tất cả reviews/comments của sản phẩm
     */
    public function getProductReviews($productId, $limit = null, $offset = 0, $verified = null) {
        $sql = "
            SELECT 
                r.*,
                u.full_name,
                u.email
            FROM {$this->table} r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ? AND r.status = ?
        ";

        $params = [$productId, self::STATUS_APPROVED];

        if ($verified !== null) {
            $sql .= " AND r.verified = ?";
            $params[] = $verified;
        }

        $sql .= " ORDER BY r.verified DESC, r.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm reviews của sản phẩm
     */
    public function countProductReviews($productId, $verified = null) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE product_id = ? AND status = ?";
        $params = [$productId, self::STATUS_APPROVED];

        if ($verified !== null) {
            $sql .= " AND verified = ?";
            $params[] = $verified;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Tính rating trung bình của sản phẩm
     */
    public function getAverageRating($productId) {
        $stmt = $this->conn->prepare("
            SELECT 
                AVG(rating) as avg_rating,
                COUNT(*) as review_count
            FROM {$this->table}
            WHERE product_id = ? AND verified = 1 AND rating IS NOT NULL AND status = ?
        ");
        $stmt->execute([$productId, self::STATUS_APPROVED]);
        return $stmt->fetch();
    }

    /**
     * Admin reply vào review/comment
     */
    public function addAdminResponse($reviewId, $response) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET admin_response = ?, admin_response_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$response, $reviewId]);
    }

    /**
     * Cập nhật status (admin ẩn/spam)
     */
    public function updateStatus($reviewId, $status) {
        if (!in_array($status, [self::STATUS_APPROVED, self::STATUS_HIDDEN, self::STATUS_SPAM])) {
            throw new InvalidArgumentException("Invalid status");
        }
        return $this->update($reviewId, ['status' => $status]);
    }

    /**
     * Lấy tất cả reviews (admin) - có filter
     */
    public function getAllReviews($limit = null, $offset = 0, $filters = []) {
        $sql = "
            SELECT 
                r.*,
                u.full_name,
                u.email,
                p.name as product_name
            FROM {$this->table} r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN products p ON r.product_id = p.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['product_id'])) {
            $sql .= " AND r.product_id = ?";
            $params[] = $filters['product_id'];
        }

        if (!empty($filters['verified'])) {
            $sql .= " AND r.verified = ?";
            $params[] = $filters['verified'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND r.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY r.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Check user đã mua sản phẩm chưa (để verify review)
     */
    public function hasUserPurchased($userId, $productId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ? 
            AND oi.product_id = ?
            AND o.status IN ('delivered', 'confirmed')
        ");
        $stmt->execute([$userId, $productId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Lấy order_id gần nhất mà user đã mua sản phẩm
     */
    public function getLatestPurchaseOrder($userId, $productId) {
        $stmt = $this->conn->prepare("
            SELECT o.id
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ? 
            AND oi.product_id = ?
            AND o.status IN ('delivered', 'confirmed')
            ORDER BY o.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId, $productId]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }

    /**
     * Lấy review của user cho sản phẩm
     */
    public function getUserReview($userId, $productId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} 
            WHERE user_id = ? AND product_id = ? AND verified = 1
        ");
        $stmt->execute([$userId, $productId]);
        return $stmt->fetch();
    }

    /**
     * Update review của user (chỉ content và rating)
     */
    public function updateUserReview($reviewId, $userId, $content, $rating = null) {
        // Verify ownership
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} 
            WHERE id = ? AND user_id = ? AND verified = 1
        ");
        $stmt->execute([$reviewId, $userId]);
        $review = $stmt->fetch();
        
        if (!$review) {
            throw new Exception("Không tìm thấy review của bạn");
        }
        
        // Validate rating nếu có
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new InvalidArgumentException("Rating phải từ 1-5");
        }
        
        $data = ['content' => $content];
        if ($rating !== null) {
            $data['rating'] = $rating;
        }
        
        return $this->update($reviewId, $data);
    }

    /**
     * Check if user can edit review (trong 30 ngày)
     */
    /**
     * Check if user can edit review với thông tin chi tiết
     * @return array ['can_edit' => bool, 'reason' => string, 'days_passed' => int]
     */
    public function canEditReview($reviewId, $userId) {
        $stmt = $this->conn->prepare("
            SELECT 
                *,
                DATEDIFF(NOW(), created_at) as days_passed
            FROM {$this->table} 
            WHERE id = ?
        ");
        $stmt->execute([$reviewId]);
        $review = $stmt->fetch();
        
        if (!$review) {
            return [
                'can_edit' => false,
                'reason' => 'Không tìm thấy đánh giá này',
                'days_passed' => null
            ];
        }
        
        if ($review['user_id'] != $userId) {
            return [
                'can_edit' => false,
                'reason' => 'Bạn không có quyền chỉnh sửa đánh giá này',
                'days_passed' => $review['days_passed']
            ];
        }
        
        if ($review['verified'] == 0) {
            return [
                'can_edit' => false,
                'reason' => 'Chỉ có thể chỉnh sửa đánh giá của sản phẩm đã mua',
                'days_passed' => $review['days_passed']
            ];
        }
        
        if ($review['days_passed'] > 30) {
            return [
                'can_edit' => false,
                'reason' => "Đã quá thời hạn chỉnh sửa (30 ngày). Đánh giá của bạn đã đăng được {$review['days_passed']} ngày",
                'days_passed' => $review['days_passed']
            ];
        }
        
        return [
            'can_edit' => true,
            'reason' => null,
            'days_passed' => $review['days_passed'],
            'days_remaining' => 30 - $review['days_passed']
        ];
    }

    /**
     * Lấy thống kê rating chi tiết (distribution)
     */
    public function getRatingDistribution($productId) {
        $stmt = $this->conn->prepare("
            SELECT 
                rating,
                COUNT(*) as count
            FROM {$this->table}
            WHERE product_id = ? 
            AND verified = 1 
            AND rating IS NOT NULL 
            AND status = ?
            GROUP BY rating
            ORDER BY rating DESC
        ");
        $stmt->execute([$productId, self::STATUS_APPROVED]);
        
        $distribution = [
            5 => ['count' => 0, 'percentage' => 0],
            4 => ['count' => 0, 'percentage' => 0],
            3 => ['count' => 0, 'percentage' => 0],
            2 => ['count' => 0, 'percentage' => 0],
            1 => ['count' => 0, 'percentage' => 0],
        ];
        
        $total = 0;
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[$row['rating']] = (int)$row['count'];
            $total += (int)$row['count'];
        }
        
        // Calculate percentage
        foreach ($results as $rating => $count) {
            $distribution[$rating] = [
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0
            ];
        }
        
        return $distribution;
    }

    public function getReviewById($reviewId) {
    $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ?");
    $stmt->execute([$reviewId]);
    return $stmt->fetch();
}
}