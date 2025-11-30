<?php
require_once __DIR__ . '/BaseModel.php';

class Cart extends BaseModel {
    protected $table = 'carts';

    /**
     * Lấy hoặc tạo giỏ hàng cho user
     */
    public function getOrCreateCart($userId) {
        // Tìm cart active của user
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} 
            WHERE user_id = ? AND status = 'active'
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();
        
        if (!$cart) {
            // Tạo cart mới với UUID
            $cartId = $this->generateUUID();
            $stmt = $this->conn->prepare("
                INSERT INTO {$this->table} (id, user_id, status)
                VALUES (?, ?, 'active')
            ");
            $stmt->execute([$cartId, $userId]);
            return $this->findById($cartId);
        }
        
        return $cart;
    }

    /**
     * Lấy tất cả items trong giỏ hàng kèm thông tin sản phẩm
     */
    public function getCartItems($cartId) {
        $stmt = $this->conn->prepare("
            SELECT 
                ci.id,
                ci.cart_id,
                ci.product_id,
                ci.quantity,
                ci.price,
                ci.is_selected,
                ci.quantity * ci.price as subtotal,
                p.name,
                p.cpu,
                p.ram,
                p.storage,
                p.images,
                p.in_stock
            FROM cart_items ci
            INNER JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = ?
            ORDER BY ci.created_at DESC
        ");
        
        $stmt->execute([$cartId]);
        return $stmt->fetchAll();
    }

    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function addItem($cartId, $productId, $quantity, $price) {
        try {
            // Check if item already exists
            $stmt = $this->conn->prepare("
                SELECT id, quantity FROM cart_items 
                WHERE cart_id = ? AND product_id = ?
            ");
            $stmt->execute([$cartId, $productId]);
            $existingItem = $stmt->fetch();

            if ($existingItem) {
                // Update quantity
                $newQuantity = $existingItem['quantity'] + $quantity;
                $stmt = $this->conn->prepare("
                    UPDATE cart_items 
                    SET quantity = ?, price = ?, is_selected = 1, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$newQuantity, $price, $existingItem['id']]);
                return $existingItem['id'];
            } else {
                // Insert new item với UUID
                $itemId = $this->generateUUID();
                $stmt = $this->conn->prepare("
                    INSERT INTO cart_items (id, cart_id, product_id, quantity, price, is_selected)
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$itemId, $cartId, $productId, $quantity, $price]);
                return $itemId;
            }
        } catch (Exception $e) {
            error_log("Add cart item error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate UUID v4
     */
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ
     */
    public function updateItemQuantity($cartItemId, $quantity) {
        if ($quantity <= 0) {
            return $this->$cartModel->removeItem($data['item_id']);
        }

        $stmt = $this->conn->prepare("
            UPDATE cart_items 
            SET quantity = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$quantity, $cartItemId]);
    }

    /**
     * Đánh dấu chọn/bỏ chọn item trong giỏ
     */
    public function toggleItemSelection($cartItemId, $isSelected) {
        $stmt = $this->conn->prepare("
            UPDATE cart_items 
            SET is_selected = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$isSelected ? 1 : 0, $cartItemId]);
    }

    /**
     * Chọn tất cả items trong giỏ
     */
    public function selectAllItems($cartId) {
        $stmt = $this->conn->prepare("
            UPDATE cart_items 
            SET is_selected = 1, updated_at = CURRENT_TIMESTAMP
            WHERE cart_id = ?
        ");
        return $stmt->execute([$cartId]);
    }

    /**
     * Bỏ chọn tất cả items trong giỏ
     */
    public function unselectAllItems($cartId) {
        $stmt = $this->conn->prepare("
            UPDATE cart_items 
            SET is_selected = 0, updated_at = CURRENT_TIMESTAMP
            WHERE cart_id = ?
        ");
        return $stmt->execute([$cartId]);
    }

    /** 
     * Xóa sản phẩm khỏi giỏ hàng
     */
    public function removeItem($item_id) {
        $stmt = $this->conn->prepare("
            DELETE FROM cart_items 
            WHERE id = ?
        ");
        return $stmt->execute([$item_id]);
    }

    /**
     * Xóa tất cả items trong giỏ hàng
     */
    public function clearCart($cartId) {
        $stmt = $this->conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        return $stmt->execute([$cartId]);
    }

    /**
     * Tính tổng giá trị giỏ hàng (chỉ items được chọn)
     */
    public function getCartTotal($cartId, $selectedOnly = false) {
        $sql = "SELECT SUM(quantity * price) as total FROM cart_items WHERE cart_id = ?";
        
        if ($selectedOnly) {
            $sql .= " AND is_selected = 1";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cartId]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    /**
     * Đếm số lượng items trong giỏ
     */
    public function getCartItemCount($cartId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM cart_items
            WHERE cart_id = ?
        ");
        $stmt->execute([$cartId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Đếm số items được chọn
     */
    public function getSelectedItemCount($cartId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM cart_items
            WHERE cart_id = ? AND is_selected = 1
        ");
        $stmt->execute([$cartId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Lấy thông tin cart item by ID
     */
    public function getCartItemById($cartItemId) {
        $stmt = $this->conn->prepare("
            SELECT ci.*, c.user_id
            FROM cart_items ci
            INNER JOIN carts c ON ci.cart_id = c.id
            WHERE ci.id = ?
        ");
        $stmt->execute([$cartItemId]);
        return $stmt->fetch();
    }

    /**
     * Lấy items được chọn trong giỏ
     */
    public function getSelectedItems($cartId) {
        $stmt = $this->conn->prepare("
            SELECT 
                ci.id,
                ci.cart_id,
                ci.product_id,
                ci.quantity,
                ci.price,
                ci.quantity * ci.price as subtotal,
                p.name,
                p.images,
                p.in_stock
            FROM cart_items ci
            INNER JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = ? AND ci.is_selected = 1
            ORDER BY ci.created_at DESC
        ");
        
        $stmt->execute([$cartId]);
        return $stmt->fetchAll();
    }

    /**
     * Xóa items đã chọn khỏi giỏ hàng
     */
    public function removeSelectedItems($cartId, $itemIds = null) {
        if ($itemIds !== null && !empty($itemIds)) {
            // Xóa theo IDs cụ thể
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $stmt = $this->conn->prepare("
                DELETE FROM cart_items 
                WHERE cart_id = ? AND id IN ($placeholders)
            ");
            
            $params = array_merge([$cartId], $itemIds);
            return $stmt->execute($params);
        } else {
            // Xóa tất cả items được chọn
            $stmt = $this->conn->prepare("
                DELETE FROM cart_items 
                WHERE cart_id = ? AND is_selected = 1
            ");
            return $stmt->execute([$cartId]);
        }
    }

    /**
     * Cập nhật trạng thái giỏ hàng
     */
    public function updateCartStatus($cartId, $status) {
        $validStatuses = ['active', 'checked_out', 'abandoned'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        return $stmt->execute([$status, $cartId]);
    }
}