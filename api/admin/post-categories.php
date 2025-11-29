<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/PostCategory.php';

// Only admin can access
$admin = AuthMiddleware::requireAdmin();

$categoryModel = new PostCategory();

switch ($_SERVER['REQUEST_METHOD']) {
    
    case 'GET':
        // GET /api/admin/post-categories.php - Lấy tất cả categories
        
        $categories = $categoryModel->getAllCategories();

        echo json_encode([
            'success' => true,
            'categories' => $categories
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        // POST /api/admin/post-categories.php
        // Body: { name, description, display_order }
        
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'name là bắt buộc']);
            exit;
        }

        try {
            $categoryId = $categoryModel->createCategory(
                $data['name'],
                $data['description'] ?? null,
                $data['display_order'] ?? 0
            );

            $category = $categoryModel->findById($categoryId);

            echo json_encode([
                'success' => true,
                'message' => 'Đã tạo category',
                'category' => $category
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        // PUT /api/admin/post-categories.php?id=xxx
        // Body: { name, description, display_order }
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'category id là bắt buộc']);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data) || !is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        try {
            $updateData = [];
            $allowedFields = ['name', 'description', 'display_order'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Không có dữ liệu để cập nhật']);
                exit;
            }

            $categoryModel->updateCategory($_GET['id'], $updateData);

            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật category'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'DELETE':
        // DELETE /api/admin/post-categories.php?id=xxx
        
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'category id là bắt buộc']);
            exit;
        }

        // Check có bài viết nào dùng category này không
        $postCount = $categoryModel->countPosts($_GET['id']);
        
        if ($postCount > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => "Không thể xóa category này vì có {$postCount} bài viết đang sử dụng"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $categoryModel->delete($_GET['id']);

        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa category'
        ], JSON_UNESCAPED_UNICODE);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}