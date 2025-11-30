<?php
require_once __DIR__ . '/BaseModel.php';

class OrderItem extends BaseModel {
    protected $table = 'order_items';

    /**
     * Thêm sản phẩm vào đơn hàng
     */
    public function addOrderItem($orderId, $productId, $productName, $quantity, $price) {
        return $this->create([
            'order_id' => $orderId,
            'product_id' => $productId,
            'product_name' => $productName,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $quantity * $price
        ]);
    }

    /**
     * Thêm nhiều sản phẩm vào đơn hàng (bulk insert)
     */
    public function addOrderItems($orderId, $items) {
        if (empty($items)) {
            return false;
        }

        $values = [];
        $params = [];

        foreach ($items as $item) {
            $values[] = "(?, ?, ?, ?, ?, ?)";
            $params[] = $orderId;
            $params[] = $item['product_id'];
            $params[] = $item['product_name'];
            $params[] = $item['quantity'];
            $params[] = $item['price'];
            $params[] = $item['quantity'] * $item['price'];
        }

        $sql = "INSERT INTO {$this->table} 
                (order_id, product_id, product_name, quantity, price, subtotal) 
                VALUES " . implode(', ', $values);

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Lấy sản phẩm trong đơn hàng
     */
    public function getItemsByOrderId($orderId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} 
            WHERE order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
}