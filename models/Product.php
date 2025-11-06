<?php
require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel {
    protected $table = 'products';

    /**
     * Lấy sản phẩm theo ID với validation
     */
    public function getProduct($productId) {
        return $this->findById($productId);
    }

    /**
     * Kiểm tra sản phẩm còn hàng
     */
    public function isInStock($productId) {
        $product = $this->findById($productId);
        return $product && $product['in_stock'] == 1;
    }

    /**
     * Lấy nhiều sản phẩm theo IDs
     */
    public function getProductsByIds($productIds) {
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE id IN ($placeholders)
        ");
        
        $stmt->execute($productIds);
        return $stmt->fetchAll();
    }
}