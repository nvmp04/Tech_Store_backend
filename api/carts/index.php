<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/Cart.php';
require_once __DIR__ . '/../../models/Product.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Authenticate user
$user = AuthMiddleware::requireUser();
$cartModel = new Cart();
$productModel = new Product();

// Get or create cart
$cart = $cartModel->getOrCreateCart($user['id']);

switch ($_SERVER['REQUEST_METHOD']) {
    
    case 'GET':
        // Lấy giỏ hàng
        $items = $cartModel->getCartItems($cart['id']);
        $total = $cartModel->getCartTotal($cart['id'], false); // Tổng tất cả items
        $selectedTotal = $cartModel->getCartTotal($cart['id'], true); // Tổng items được chọn
        $itemCount = $cartModel->getCartItemCount($cart['id']);
        $selectedCount = $cartModel->getSelectedItemCount($cart['id']);

        echo json_encode([
            'success' => true,
            'cart' => [
                'id' => $cart['id'],
                'status' => $cart['status'],
                'items' => $items,
                'total' => $total,
                'selected_total' => $selectedTotal,
                'item_count' => $itemCount,
                'selected_count' => $selectedCount
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        // Thêm sản phẩm vào giỏ
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['product_id']) || empty($data['quantity'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'product_id và quantity là bắt buộc'
            ]);
            exit;
        }

        // Validate product
        $product = $productModel->getProduct($data['product_id']);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
            exit;
        }

        if ($product['in_stock'] <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng']);
            exit;
        }

        $quantity = (int)$data['quantity'];
        if ($quantity <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Số lượng không hợp lệ']);
            exit;
        }

        // Add to cart
        $itemId = $cartModel->addItem($cart['id'], $product['id'], $quantity, $product['price']);

        if ($itemId) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã thêm vào giỏ hàng',
                'item_id' => $itemId
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Không thể thêm vào giỏ hàng']);
        }
        break;

    case 'PUT':
        // Cập nhật số lượng
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['item_id']) || !isset($data['quantity'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'item_id và quantity là bắt buộc'
            ]);
            exit;
        }

        // Verify ownership
        $cartItem = $cartModel->getCartItemById($data['item_id']);
        if (!$cartItem || $cartItem['user_id'] !== $user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Không có quyền']);
            exit;
        }

        $quantity = (int)$data['quantity'];
        $updated = $cartModel->updateItemQuantity($data['item_id'], $quantity);

        if ($updated) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật giỏ hàng'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật']);
        }
        break;

    case 'PATCH':
        // Đánh dấu chọn/bỏ chọn item
        $data = json_decode(file_get_contents("php://input"), true);

        // Check action type
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'toggle_selection':
                    // Toggle single item
                    if (empty($data['item_id']) || !isset($data['is_selected'])) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'message' => 'item_id và is_selected là bắt buộc'
                        ]);
                        exit;
                    }

                    // Verify ownership
                    $cartItem = $cartModel->getCartItemById($data['item_id']);
                    if (!$cartItem || $cartItem['user_id'] !== $user['id']) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Không có quyền']);
                        exit;
                    }

                    $updated = $cartModel->toggleItemSelection($data['item_id'], $data['is_selected']);
                    
                    if ($updated) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Đã cập nhật'
                        ], JSON_UNESCAPED_UNICODE);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật']);
                    }
                    break;

                case 'select_all':
                    // Select all items
                    $updated = $cartModel->selectAllItems($cart['id']);
                    
                    if ($updated) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Đã chọn tất cả'
                        ], JSON_UNESCAPED_UNICODE);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật']);
                    }
                    break;

                case 'unselect_all':
                    // Unselect all items
                    $updated = $cartModel->unselectAllItems($cart['id']);
                    
                    if ($updated) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Đã bỏ chọn tất cả'
                        ], JSON_UNESCAPED_UNICODE);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật']);
                    }
                    break;

                case 'update_status':
                    // Update cart status
                    if (empty($data['status'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'status là bắt buộc']);
                        exit;
                    }

                    $updated = $cartModel->updateCartStatus($cart['id'], $data['status']);
                    
                    if ($updated) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Đã cập nhật trạng thái giỏ hàng'
                        ], JSON_UNESCAPED_UNICODE);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
                    }
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
                    break;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'action là bắt buộc']);
        }
        break;

    case 'DELETE':
        // Xóa sản phẩm khỏi giỏ
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['item_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'item_id là bắt buộc']);
            exit;
        }

        // Verify ownership
        $cartItem = $cartModel->getCartItemById($data['item_id']);
        if (!$cartItem || $cartItem['user_id'] !== $user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Không có quyền']);
            exit;
        }

        $deleted = $cartModel->removeItem($data['item_id']);

        if ($deleted) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa khỏi giỏ hàng'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Không thể xóa']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}