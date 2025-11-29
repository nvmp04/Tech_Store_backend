<?php
require_once __DIR__ . '/BaseModel.php';

class PostComment extends BaseModel {
    protected $table = 'post_comments';

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_SPAM = 'spam';

    /**
     * Tạo comment mới
     */
    public function createComment($postId, $content, $userId = null, $authorName = null, $authorEmail = null, $parentId = null) {
        // Nếu có userId → lấy thông tin từ user
        if ($userId) {
            $userModel = new User();
            $user = $userModel->findById($userId);
            
            if ($user) {
                $authorName = $user['full_name'] ?: $user['email'];
                $authorEmail = $user['email'];
                // User đã login → approved luôn
                $status = self::STATUS_APPROVED;
            } else {
                throw new Exception("User không tồn tại");
            }
        } else {
            // Guest comment → cần duyệt
            if (!$authorName || !$authorEmail) {
                throw new InvalidArgumentException("Guest comment cần có tên và email");
            }
            $status = self::STATUS_PENDING;
        }

        $data = [
            'post_id' => $postId,
            'parent_id' => $parentId,
            'user_id' => $userId,
            'author_name' => $authorName,
            'author_email' => $authorEmail,
            'content' => $content,
            'status' => $status
        ];

        return $this->create($data);
    }

    /**
     * Lấy comments của bài viết (public - chỉ approved)
     */
    public function getPostComments($postId) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.*,
                u.email as user_email,
                u.role as user_role
            FROM {$this->table} c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ? AND c.status = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$postId, self::STATUS_APPROVED]);
        $comments = $stmt->fetchAll();

        // Build tree structure
        return $this->buildCommentTree($comments);
    }

    /**
     * Build nested comment tree
     */
    private function buildCommentTree($comments, $parentId = null) {
        $branch = [];

        foreach ($comments as $comment) {
            if ($comment['parent_id'] === $parentId) {
                $children = $this->buildCommentTree($comments, $comment['id']);
                if ($children) {
                    $comment['replies'] = $children;
                }
                $branch[] = $comment;
            }
        }

        return $branch;
    }

    /**
     * Đếm comments của bài viết (approved)
     */
    public function countPostComments($postId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total 
            FROM {$this->table} 
            WHERE post_id = ? AND status = ?
        ");
        $stmt->execute([$postId, self::STATUS_APPROVED]);
        return $stmt->fetch()['total'];
    }

    /**
     * Lấy tất cả comments (admin) - có filter
     */
    public function getAllComments($limit = null, $offset = 0, $filters = []) {
        $sql = "
            SELECT 
                c.*,
                p.title as post_title,
                p.slug as post_slug,
                u.email as user_email
            FROM {$this->table} c
            LEFT JOIN posts p ON c.post_id = p.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['post_id'])) {
            $sql .= " AND c.post_id = ?";
            $params[] = $filters['post_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.content LIKE ? OR c.author_name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY c.created_at DESC";

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
     * Đếm tất cả comments (admin)
     */
    public function countAllComments($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['post_id'])) {
            $sql .= " AND post_id = ?";
            $params[] = $filters['post_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (content LIKE ? OR author_name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Cập nhật status (admin duyệt/spam)
     */
    public function updateStatus($commentId, $status) {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_SPAM])) {
            throw new InvalidArgumentException("Invalid status");
        }
        return $this->update($commentId, ['status' => $status]);
    }

    /**
     * Lấy pending comments (cần duyệt)
     */
    public function getPendingComments($limit = 20) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.*,
                p.title as post_title,
                p.slug as post_slug
            FROM {$this->table} c
            LEFT JOIN posts p ON c.post_id = p.id
            WHERE c.status = ?
            ORDER BY c.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([self::STATUS_PENDING, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * User xóa comment của mình (trong 24h)
     */
    public function deleteOwnComment($commentId, $userId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} 
            WHERE id = ? AND user_id = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$commentId, $userId]);
        $comment = $stmt->fetch();

        if (!$comment) {
            throw new Exception("Không thể xóa comment này");
        }

        return $this->delete($commentId);
    }

    /**
     * User edit comment của mình (trong 24h)
     */
    public function editOwnComment($commentId, $userId, $newContent) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} 
            WHERE id = ? AND user_id = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$commentId, $userId]);
        $comment = $stmt->fetch();

        if (!$comment) {
            throw new Exception("Không thể chỉnh sửa comment này");
        }

        return $this->update($commentId, ['content' => $newContent]);
    }
}