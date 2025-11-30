<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/Order.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Authenticate user
$user = AuthMiddleware::requireAdmin();

// TODO: Add admin role check
// if ($user['role'] !== 'admin') {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
//     exit;
// }

$orderModel = new Order();

switch ($_SERVER['REQUEST_METHOD']) {
    
    case 'GET':
        // Lấy danh sách tất cả đơn hàng
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;
        $status = isset($_GET['status']) ? $_GET['status'] : null;

        // Get specific order by ID
        if (isset($_GET['id'])) {
            $order = $orderModel->getOrderById($_GET['id']);
            
            if (!$order) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
                exit;
            }

            $items = $orderModel->getOrderItems($_GET['id']);
            
            echo json_encode([
                'success' => true,
                'order' => array_merge($order, ['items' => $items])
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Get all orders with pagination
        $orders = $orderModel->getAllOrders($limit, $offset, $status);
        $total = $orderModel->countAllOrders($status);
        $totalPages = ceil($total / $limit);

        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
                'items_per_page' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'PUT':
        // Cập nhật trạng thái đơn hàng
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['order_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'order_id là bắt buộc']);
            exit;
        }

        $updated = false;

        // Update order status
        if (isset($data['status'])) {
            $updated = $orderModel->updateOrderStatus($data['order_id'], $data['status']);
        }

        // Update payment status
        if (isset($data['payment_status'])) {
            $updated = $orderModel->updatePaymentStatus($data['order_id'], $data['payment_status']) || $updated;
        }

        if ($updated) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật đơn hàng'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật đơn hàng']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}