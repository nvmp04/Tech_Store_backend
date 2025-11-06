<?php
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/BaseModel.php';

class CartService {
    private $cartModel;

    public function __construct() {
        $this->cartModel = new Cart();
    }

    // Lấy giỏ hàng
    public function getCart($userId) {
        try {
            $items = $this->cartModel->getCartByUserId($userId);
            
            $totalAmount = 0;
            $totalItems = 0;
            $selectedCount = 0;
            $selectedAmount = 0;

            foreach ($items as &$item) {
                $item['images'] = json_decode($item['images'], true);
                $item['subtotal'] = $item['price'] * $item['quantity'];
                $totalItems += $item['quantity'];
                
                if ($item['selected']) {
                    $selectedCount += $item['quantity'];
                    $selectedAmount += $item['subtotal'];
                }
                
                $totalAmount += $item['subtotal'];
            }

            return [
                'success' => true,
                'data' => [
                    'items' => $items,
                    'summary' => [
                        'total_items' => $totalItems,
                        'total_amount' => $totalAmount,
                        'selected_count' => $selectedCount,
                        'selected_amount' => $selectedAmount
                    ]
                ]
            ];

        } catch (Exception $e) {
            error_log("Get cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi lấy giỏ hàng'];
        }
    }

    // Thêm vào giỏ
    public function addToCart($userId, $productId, $quantity = 1) {
        try {
            if ($quantity <= 0) {
                return ['success' => false, 'message' => 'Số lượng không hợp lệ'];
            }

            $result = $this->cartModel->addToCart($userId, $productId, $quantity);
            
            if ($result) {
                return ['success' => true, 'message' => 'Đã thêm vào giỏ hàng'];
            } else {
                return ['success' => false, 'message' => 'Không thể thêm vào giỏ hàng'];
            }

        } catch (Exception $e) {
            error_log("Add to cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi thêm vào giỏ hàng'];
        }
    }

    // Cập nhật số lượng
    public function updateQuantity($userId, $productId, $quantity) {
        try {
            if ($quantity < 0) {
                return ['success' => false, 'message' => 'Số lượng không hợp lệ'];
            }

            $result = $this->cartModel->updateQuantity($userId, $productId, $quantity);
            
            if ($result) {
                return ['success' => true, 'message' => 'Đã cập nhật số lượng'];
            } else {
                return ['success' => false, 'message' => 'Không thể cập nhật số lượng'];
            }

        } catch (Exception $e) {
            error_log("Update quantity error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật số lượng'];
        }
    }

    // Toggle selected
    public function toggleSelected($userId, $productId) {
        try {
            $result = $this->cartModel->toggleSelected($userId, $productId);
            
            if ($result) {
                return ['success' => true, 'message' => 'Đã cập nhật trạng thái'];
            } else {
                return ['success' => false, 'message' => 'Không thể cập nhật trạng thái'];
            }

        } catch (Exception $e) {
            error_log("Toggle selected error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra'];
        }
    }

    // Set selected cho item
    public function setSelected($userId, $productId, $selected) {
        try {
            $result = $this->cartModel->setSelected($userId, $productId, $selected);
            
            if ($result) {
                return ['success' => true, 'message' => 'Đã cập nhật trạng thái'];
            } else {
                return ['success' => false, 'message' => 'Không thể cập nhật trạng thái'];
            }

        } catch (Exception $e) {
            error_log("Set selected error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra'];
        }
    }

    // Xóa khỏi giỏ
    public function removeFromCart($userId, $productId) {
        try {
            $result = $this->cartModel->removeFromCart($userId, $productId);
            
            if ($result) {
                return ['success' => true, 'message' => 'Đã xóa khỏi giỏ hàng'];
            } else {
                return ['success' => false, 'message' => 'Không thể xóa khỏi giỏ hàng'];
            }

        } catch (Exception $e) {
            error_log("Remove from cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi xóa'];
        }
    }

    // Xóa toàn bộ giỏ
    public function clearCart($userId) {
        try {
            $result = $this->cartModel->clearCart($userId);
            
            if ($result) {
                return ['success' => true, 'message' => 'Đã xóa toàn bộ giỏ hàng'];
            } else {
                return ['success' => false, 'message' => 'Không thể xóa giỏ hàng'];
            }

        } catch (Exception $e) {
            error_log("Clear cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi xóa giỏ hàng'];
        }
    }
}