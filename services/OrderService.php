<?php
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';

class OrderService {
    private $orderModel;
    private $productModel;
    private $cartModel;

    public function __construct() {
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->cartModel = new Cart();
    }

    /**
     * Validate thông tin đặt hàng
     */
    private function validateOrderData($data) {
        $required = ['full_name', 'email', 'phone', 'province', 'district', 'ward', 'address_detail'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['valid' => false, 'message' => "Trường {$field} là bắt buộc"];
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email không hợp lệ'];
        }

        if (!preg_match('/^[0-9]{10,11}$/', $data['phone'])) {
            return ['valid' => false, 'message' => 'Số điện thoại không hợp lệ'];
        }

        return ['valid' => true];
    }

    /**
     * MUA NGAY - Buy Now (1 sản phẩm)
     */
    public function buyNow($userId, $productId, $quantity, $orderInfo) {
        try {
            $validation = $this->validateOrderData($orderInfo);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            $product = $this->productModel->getProduct($productId);
            if (!$product) {
                return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
            }

            if ($product['in_stock'] <= 0) {
                return ['success' => false, 'message' => 'Sản phẩm đã hết hàng'];
            }

            if ($quantity <= 0) {
                return ['success' => false, 'message' => 'Số lượng không hợp lệ'];
            }

            $totalAmount = $product['price'] * $quantity;

            $this->orderModel->beginTransaction();

            try {
                // Tạo đơn hàng
                $orderData = [
                    'user_id' => $userId,
                    'full_name' => $orderInfo['full_name'],
                    'email' => $orderInfo['email'],
                    'phone' => $orderInfo['phone'],
                    'province' => $orderInfo['province'],
                    'district' => $orderInfo['district'],
                    'ward' => $orderInfo['ward'],
                    'address_detail' => $orderInfo['address_detail'],
                        'total_amount' => $totalAmount,
                        'rate' => $orderInfo['rate'] ?? 0
                ];

                $orderId = $this->orderModel->createOrder($orderData);

                // Thêm order items
                $orderItems = [[
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'quantity' => $quantity,
                    'price' => $product['price'],
                    'subtotal' => $product['price'] * $quantity
                ]];

                $this->orderModel->addOrderItems($orderId, $orderItems);

                $this->orderModel->commit();

                return [
                    'success' => true,
                    'message' => 'Đặt hàng thành công',
                    'order_id' => $orderId
                ];

            } catch (Exception $e) {
                $this->orderModel->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Buy now error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Lỗi chi tiết: " . $e->getMessage()
            ];
        }
    }

    /**
     * CHECKOUT TỪ GIỎ HÀNG - Checkout Selected Items
     */
    public function checkoutSelectedItems($userId, $orderInfo, $cartItemIds = null) {
        try {
            $validation = $this->validateOrderData($orderInfo);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            $cart = $this->cartModel->getOrCreateCart($userId);

            // Lấy items được chọn
            if ($cartItemIds !== null && !empty($cartItemIds)) {
                $allCartItems = $this->cartModel->getCartItems($cart['id']);
                $selectedItems = array_filter($allCartItems, function($item) use ($cartItemIds) {
                    return in_array($item['id'], $cartItemIds);
                });
            } else {
                $selectedItems = $this->cartModel->getSelectedItems($cart['id']);
            }

            if (empty($selectedItems)) {
                return ['success' => false, 'message' => 'Vui lòng chọn sản phẩm để đặt hàng'];
            }

            // Kiểm tra tồn kho
            foreach ($selectedItems as $item) {
                if ($item['in_stock'] <= 0) {
                    return [
                        'success' => false,
                        'message' => "Sản phẩm '{$item['name']}' đã hết hàng"
                    ];
                }
            }

            $totalAmount = array_sum(array_map(function($item) {
                return $item['price'] * $item['quantity'];
            }, $selectedItems));

            $this->orderModel->beginTransaction();

            try {
                // Tạo đơn hàng
                $orderData = [
                    'user_id' => $userId,
                    'full_name' => $orderInfo['full_name'],
                    'email' => $orderInfo['email'],
                    'phone' => $orderInfo['phone'],
                    'province' => $orderInfo['province'],
                    'district' => $orderInfo['district'],
                    'ward' => $orderInfo['ward'],
                    'address_detail' => $orderInfo['address_detail'],
                        'total_amount' => $totalAmount,
                        'rate' => $orderInfo['rate'] ?? 0
                ];

                $orderId = $this->orderModel->createOrder($orderData);

                // Thêm order items
                $orderItems = array_map(function($item) {
                    return [
                        'product_id' => $item['product_id'],
                        'product_name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $item['price'] * $item['quantity']
                    ];
                }, $selectedItems);

                $this->orderModel->addOrderItems($orderId, $orderItems);

                // Xóa các item đã checkout khỏi giỏ
                $itemIds = array_column($selectedItems, 'id');
                $this->cartModel->removeSelectedItems($cart['id'], $itemIds);

                // Cập nhật trạng thái cart nếu trống
                $remainingCount = $this->cartModel->getCartItemCount($cart['id']);
                if ($remainingCount === 0) {
                    $this->cartModel->updateCartStatus($cart['id'], 'checked_out');
                }

                $this->orderModel->commit();

                return [
                    'success' => true,
                    'message' => 'Đặt hàng thành công',
                    'order_id' => $orderId
                ];

            } catch (Exception $e) {
                $this->orderModel->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Checkout selected items error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Lỗi chi tiết: " . $e->getMessage()
            ];
        }
    }
}
