<?php
require_once __DIR__ . '/BaseModel.php';

class Order extends BaseModel {
    protected $table = 'orders';

    /**
     * Tạo đơn hàng mới
     */
    public function createOrder($data) {
        // Generate UUID trước
        $orderId = $this->generateUUID();
        
        $stmt = $this->conn->prepare("
            INSERT INTO orders (
                id, user_id, full_name, email, phone, province, district, ward,
                address_detail, total_amount, quantity, note
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $orderId,
            $data['user_id'],
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $data['province'],
            $data['district'],
            $data['ward'],
            $data['address_detail'],
            $data['total_amount'],
            $data['quantity'] ?? 0,
            $data['note'] ?? null
        ]);

        return $orderId;
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
     * Thêm items vào đơn hàng
     */
    public function addOrderItems($orderId, $items) {
        $stmt = $this->conn->prepare("
            INSERT INTO order_items (id, order_id, product_id, product_name, quantity, price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $itemId = $this->generateUUID();
            $subtotal = $item['quantity'] * $item['price'];
            $stmt->execute([
                $itemId,
                $orderId,
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['price'],
                $subtotal
            ]);
        }

        return true;
    }

    /**
     * Lấy chi tiết đơn hàng
     */
    public function getOrderById($orderId, $userId = null) {
        $sql = "
            SELECT 
                o.*,
                COUNT(oi.id) as total_items
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.id = ?
        ";

        if ($userId) {
            $sql .= " AND o.user_id = ?";
        }

        $sql .= " GROUP BY o.id";

        $stmt = $this->conn->prepare($sql);
        
        if ($userId) {
            $stmt->execute([$orderId, $userId]);
        } else {
            $stmt->execute([$orderId]);
        }

        return $stmt->fetch();
    }

    /**
     * Lấy items của đơn hàng
     */
    public function getOrderItems($orderId) {
        $stmt = $this->conn->prepare("
            SELECT 
                oi.*,
                p.images
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.created_at ASC
        ");
        
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách đơn hàng của user với pagination
     */
    public function getUserOrders($userId, $limit = 10, $offset = 0, $status = null) {
        $sql = "
            SELECT 
                o.*,
                COUNT(oi.id) as total_items
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
        ";

        $params = [$userId];

        if ($status) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }

        $sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Đếm tổng số đơn hàng của user
     */
    public function countUserOrders($userId, $status = null) {
        $sql = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
        $params = [$userId];

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result['total'] ?? 0;
    }

    /**
     * Lấy tất cả đơn hàng (Admin) với pagination
     */
    public function getAllOrders($limit = 10, $offset = 0, $status = null) {
        $sql = "
            SELECT 
                o.*,
                u.email as user_email,
                COUNT(oi.id) as total_items
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
        ";

        $params = [];

        if ($status) {
            $sql .= " WHERE o.status = ?";
            $params[] = $status;
        }

        $sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Đếm tổng số đơn hàng (Admin)
     */
    public function countAllOrders($status = null) {
        $sql = "SELECT COUNT(*) as total FROM orders";
        $params = [];

        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result['total'] ?? 0;
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateOrderStatus($orderId, $status) {
        $validStatuses = ['pending', 'confirmed', 'shipping', 'delivered', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE orders 
            SET status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        return $stmt->execute([$status, $orderId]);
    }

    /**
     * Cập nhật trạng thái thanh toán
     */
    public function updatePaymentStatus($orderId, $paymentStatus) {
        $validStatuses = ['unpaid', 'paid', 'refunded'];
        
        if (!in_array($paymentStatus, $validStatuses)) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE orders 
            SET payment_status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        return $stmt->execute([$paymentStatus, $orderId]);
    }

    /**
     * Hủy đơn hàng
     */
    public function cancelOrder($orderId, $userId = null) {
        $sql = "UPDATE orders SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $params = [$orderId];

        if ($userId) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Thống kê đơn hàng theo trạng thái
     */
    public function getOrderStatistics($userId = null) {
        $sql = "
            SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as total_amount
            FROM orders
        ";

        $params = [];

        if ($userId) {
            $sql .= " WHERE user_id = ?";
            $params[] = $userId;
        }

        $sql .= " GROUP BY status";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}