<?php
require_once __DIR__ . '/BaseModel.php';

class Post extends BaseModel {
    protected $table = 'posts';

    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';

    /**
     * Tạo slug từ title
     */
    private function generateSlug($title) {
        // Convert Vietnamese to ASCII
        $search = ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ','è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ','ì','í','ị','ỉ','ĩ','ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ','ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ','ỳ','ý','ỵ','ỷ','ỹ','đ','À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ','È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ','Ì','Í','Ị','Ỉ','Ĩ','Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ','Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ','Ỳ','Ý','Ỵ','Ỷ','Ỹ','Đ'];
        $replace = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','E','E','E','E','E','E','E','E','E','E','E','I','I','I','I','I','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','U','U','U','U','U','U','U','U','U','U','U','Y','Y','Y','Y','Y','D'];
        
        $slug = str_replace($search, $replace, $title);
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure unique
        $originalSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Check slug exists
     */
    private function slugExists($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = ? AND deleted_at IS NULL";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Tạo bài viết mới
     */
    public function createPost($authorId, $title, $content, $excerpt = null, $categoryId = null, $thumbnail = null, $status = self::STATUS_DRAFT, $isFeatured = 0) {
        $slug = $this->generateSlug($title);
        
        $data = [
            'author_id' => $authorId,
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'excerpt' => $excerpt,
            'category_id' => $categoryId,
            'thumbnail' => $thumbnail,
            'status' => $status,
            'is_featured' => $isFeatured ? 1 : 0,
            'published_at' => $status === self::STATUS_PUBLISHED ? date('Y-m-d H:i:s') : null
        ];

        return $this->create($data);
    }

    /**
     * Cập nhật bài viết
     */
    public function updatePost($postId, $data) {
        // Nếu có title mới → tạo slug mới
        if (isset($data['title'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        // Nếu đổi sang published → set published_at
        if (isset($data['status']) && $data['status'] === self::STATUS_PUBLISHED) {
            $post = $this->findById($postId);
            if (!$post['published_at']) {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
        }

        return $this->update($postId, $data);
    }

    /**
     * Soft delete
     */
    public function softDelete($postId) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$postId]);
    }

    /**
     * Restore soft deleted post
     */
    public function restore($postId) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET deleted_at = NULL WHERE id = ?");
        return $stmt->execute([$postId]);
    }

    /**
     * Lấy bài viết theo slug (public)
     */
    public function getBySlug($slug) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.*,
                u.full_name as author_name,
                c.name as category_name,
                c.slug as category_slug
            FROM {$this->table} p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN post_categories c ON p.category_id = c.id
            WHERE p.slug = ? 
            AND p.status = ? 
            AND p.deleted_at IS NULL
        ");
        $stmt->execute([$slug, self::STATUS_PUBLISHED]);
        return $stmt->fetch();
    }

    /**
     * Tăng view count
     */
    public function incrementViewCount($postId) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?");
        return $stmt->execute([$postId]);
    }

    /**
     * Lấy danh sách bài viết (public) - có filter & search
     */
    public function getPublishedPosts($limit = 10, $offset = 0, $filters = []) {
        $sql = "
            SELECT 
                p.*,
                u.full_name as author_name,
                c.name as category_name,
                c.slug as category_slug
            FROM {$this->table} p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN post_categories c ON p.category_id = c.id
            WHERE p.status = ? AND p.deleted_at IS NULL
        ";

        $params = [self::STATUS_PUBLISHED];

        // Filter by category
        if (!empty($filters['category'])) {
            $sql .= " AND c.slug = ?";
            $params[] = $filters['category'];
        }

        // Filter featured
        if (isset($filters['featured'])) {
            $sql .= " AND p.is_featured = ?";
            $params[] = $filters['featured'] ? 1 : 0;
        }

        // Search
        if (!empty($filters['search'])) {
            $sql .= " AND (p.title LIKE ? OR p.excerpt LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY p.published_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm bài viết published
     */
    public function countPublishedPosts($filters = []) {
        $sql = "
            SELECT COUNT(*) as total
            FROM {$this->table} p
            LEFT JOIN post_categories c ON p.category_id = c.id
            WHERE p.status = ? AND p.deleted_at IS NULL
        ";

        $params = [self::STATUS_PUBLISHED];

        if (!empty($filters['category'])) {
            $sql .= " AND c.slug = ?";
            $params[] = $filters['category'];
        }

        if (isset($filters['featured'])) {
            $sql .= " AND p.is_featured = ?";
            $params[] = $filters['featured'] ? 1 : 0;
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.title LIKE ? OR p.excerpt LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Lấy tất cả bài viết (admin) - bao gồm draft & deleted
     */
    public function getAllPosts($limit = 20, $offset = 0, $filters = []) {
        $sql = "
            SELECT 
                p.*,
                u.full_name as author_name,
                c.name as category_name
            FROM {$this->table} p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN post_categories c ON p.category_id = c.id
            WHERE 1=1
        ";

        $params = [];

        // Include deleted?
        if (empty($filters['include_deleted'])) {
            $sql .= " AND p.deleted_at IS NULL";
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.title LIKE ? OR p.excerpt LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Đếm tất cả bài viết (admin)
     */
    public function countAllPosts($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $params = [];

        if (empty($filters['include_deleted'])) {
            $sql .= " AND deleted_at IS NULL";
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR excerpt LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    /**
     * Lấy bài viết liên quan (cùng category)
     */
    public function getRelatedPosts($postId, $categoryId, $limit = 5) {
        $stmt = $this->conn->prepare("
            SELECT id, title, slug, excerpt, thumbnail, published_at
            FROM {$this->table}
            WHERE category_id = ? 
            AND id != ? 
            AND status = ? 
            AND deleted_at IS NULL
            ORDER BY published_at DESC
            LIMIT ?
        ");
        $stmt->execute([$categoryId, $postId, self::STATUS_PUBLISHED, $limit]);
        return $stmt->fetchAll();
    }
}