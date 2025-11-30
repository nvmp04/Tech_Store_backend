<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../services/AuthService.php';
require_once __DIR__ . '/../../models/User.php';

// Chỉ admin mới được truy cập
$admin = AuthMiddleware::requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$authService = new AuthService();
$userModel = new User();

switch ($method) {
    case 'GET':
        // GET /api/admin/users.php - Lấy danh sách users
        // GET /api/admin/users.php?id=xxx - Lấy chi tiết 1 user
        // GET /api/admin/users.php?role=user - Lọc theo role
        
        if (isset($_GET['id'])) {
            $userId = $_GET['id'];
            $user = $userModel->getUserProfile($userId);
            
            if ($user) {
                echo json_encode([
                    'success' => true,
                    'user' => $user
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'User không tồn tại'
                ]);
            }
        } else {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $role = $_GET['role'] ?? null;
            
            if ($role) {
                $users = $userModel->getUsersByRole($role, $limit, $offset);
                $total = $userModel->count('role = ?', [$role]);
            } else {
                $users = $userModel->getAllUsers($limit, $offset);
                $total = $userModel->count();
            }
            
            echo json_encode([
                'success' => true,
                'data' => $users,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;

    case 'PUT':
        // PUT /api/admin/users.php - Cập nhật role user
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['user_id']) || !isset($data['role'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Thiếu user_id hoặc role'
            ]);
            break;
        }
        
        $result = $authService->updateUserRole(
            $admin['id'],
            $data['user_id'],
            $data['role']
        );
        
        if (!$result['success']) {
            http_response_code(400);
        }
        
        echo json_encode($result);
        break;

    case 'DELETE':
        // DELETE /api/admin/users.php?id=xxx - Xóa user
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Thiếu user_id'
            ]);
            break;
        }
        
        $userId = $_GET['id'];
        
        // Không cho phép xóa chính mình
        if ($userId === $admin['id']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể xóa tài khoản của chính bạn'
            ]);
            break;
        }
        
        $deleted = $userModel->delete($userId);
        
        if ($deleted) {
            echo json_encode([
                'success' => true,
                'message' => 'Xóa user thành công'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User không tồn tại'
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method không được hỗ trợ'
        ]);
        break;
}