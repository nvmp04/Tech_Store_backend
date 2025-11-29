<?php
require_once __DIR__ . '/BaseModel.php';

class PostCategory extends BaseModel {
    protected $table = 'post_categories';

    /**
     * Generate slug from name
     */
    private function generateSlug($name) {
        $search = ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ','è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ','ì','í','ị','ỉ','ĩ','ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ','ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ','ỳ','ý','ỵ','ỷ','ỹ','đ','À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ','È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ','Ì','Í','Ị','Ỉ','Ĩ','Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ','Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ','Ỳ','Ý','Ỵ','Ỷ','Ỹ','Đ'];
        $replace = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','E','E','E','E','E','E','E','E','E','E','E','I','I','I','I','I','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','U','U','U','U','U','U','U','U','U','U','U','Y','Y','Y','Y','Y','D'];
        
        $slug = str_replace($search, $replace, $name);
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
     * Check if slug exists
     */
    private function slugExists($slug, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = ?";
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
     * Tạo category mới
     */
    public function createCategory($name, $description = null, $displayOrder = 0) {
        $slug = $this->generateSlug($name);
        
        $data = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'display_order' => $displayOrder
        ];

        return $this->create($data);
    }

    /**
     * Cập nhật category
     */
    public function updateCategory($categoryId, $data) {
        // Nếu có name mới → tạo slug mới
        if (isset($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        return $this->update($categoryId, $data);
    }

    /**
     * Lấy category theo slug
     */
    public function getBySlug($slug) {
        return $this->findBy('slug', $slug);
    }

    /**
     * Lấy tất cả categories (có thứ tự)
     */
    public function getAllCategories() {
        $stmt = $this->conn->prepare("
            SELECT 
                c.*,
                COUNT(p.id) as post_count
            FROM {$this->table} c
            LEFT JOIN posts p ON c.id = p.category_id 
                AND p.status = 'published' 
                AND p.deleted_at IS NULL
            GROUP BY c.id
            ORDER BY c.display_order ASC, c.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Lấy categories có bài viết
     */
    public function getCategoriesWithPosts() {
        $stmt = $this->conn->prepare("
            SELECT 
                c.*,
                COUNT(p.id) as post_count
            FROM {$this->table} c
            INNER JOIN posts p ON c.id = p.category_id 
                AND p.status = 'published' 
                AND p.deleted_at IS NULL
            GROUP BY c.id
            HAVING post_count > 0
            ORDER BY c.display_order ASC, c.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Đếm bài viết trong category
     */
    public function countPosts($categoryId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total
            FROM posts
            WHERE category_id = ? 
            AND status = 'published'
            AND deleted_at IS NULL
        ");
        $stmt->execute([$categoryId]);
        return $stmt->fetch()['total'];
    }

    /**
     * Cập nhật display order
     */
    public function updateDisplayOrder($categoryId, $order) {
        return $this->update($categoryId, ['display_order' => (int)$order]);
    }
}