<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/User.php';

// Yêu cầu user hoặc admin (không cho guest)
$user = AuthMiddleware::requireUser();
$method = $_SERVER['REQUEST_METHOD'];
$userModel = new User();

switch ($method) {
    case 'GET':
        // GET /api/user/profile.php
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
        break;

    case 'PUT':
        // PUT /api/user/profile.php - Cập nhật profile
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Chỉ cho phép cập nhật full_name
        $allowedFields = ['full_name'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Không có dữ liệu để cập nhật'
            ]);
            break;
        }
        
        try {
            $updated = $userModel->update($user['id'], $updateData);
            
            if ($updated) {
                $updatedUser = $userModel->getUserProfile($user['id']);
                echo json_encode([
                    'success' => true,
                    'message' => 'Cập nhật profile thành công',
                    'user' => $updatedUser
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cập nhật thất bại'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
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
