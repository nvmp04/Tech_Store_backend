<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../models/User.php';

class AuthMiddleware {
    
    /**
     * Xác thực user từ JWT token
     * Trả về user data nếu hợp lệ, exit nếu không hợp lệ
     */
    public static function authenticate() {
        // Get Authorization header
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        // Extract token
        if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'Token không được cung cấp'
            ]);
            exit;
        }

        $token = $matches[1];

        // Validate token
        $authService = new AuthService();
        $user = $authService->getUserFromToken($token);

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ]);
            exit;
        }

        return $user;
    }
    
    /**
     * Optional authentication - không bắt buộc phải có token
     * Trả về user data nếu có token hợp lệ, null nếu không có token
     */
    public static function optionalAuth() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        $authService = new AuthService();
        return $authService->getUserFromToken($token);
    }

    /**
     * Kiểm tra quyền admin
     * Trả về user data nếu là admin, exit nếu không phải admin
     */
    public static function requireAdmin() {
        $user = self::authenticate();

        if ($user['role'] !== User::ROLE_ADMIN) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Không có quyền truy cập. Chỉ admin mới có thể thực hiện thao tác này.'
            ]);
            exit;
        }

        return $user;
    }

    /**
     * Kiểm tra quyền user (user hoặc admin)
     * Trả về user data nếu có quyền, exit nếu là guest
     */
    public static function requireUser() {
        $user = self::authenticate();

        if ($user['role'] === User::ROLE_GUEST) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Tài khoản của bạn chưa được kích hoạt đầy đủ'
            ]);
            exit;
        }

        return $user;
    }

    /**
     * Kiểm tra role cụ thể
     */
    public static function requireRole($requiredRole) {
        $user = self::authenticate();

        if ($user['role'] !== $requiredRole) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Không có quyền truy cập'
            ]);
            exit;
        }

        return $user;
    }

    /**
     * Kiểm tra một trong các role
     */
    public static function requireAnyRole($allowedRoles = []) {
        $user = self::authenticate();

        if (!in_array($user['role'], $allowedRoles)) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Không có quyền truy cập'
            ]);
            exit;
        }

        return $user;
    }

    /**
     * Kiểm tra quyền truy cập resource của chính user
     * Hoặc là admin
     */
    public static function requireOwnerOrAdmin($resourceUserId) {
        $user = self::authenticate();

        if ($user['role'] !== User::ROLE_ADMIN && $user['id'] !== $resourceUserId) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Không có quyền truy cập tài nguyên này'
            ]);
            exit;
        }

        return $user;
    }
}