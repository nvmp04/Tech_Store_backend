<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../helpers/JWTHelper.php';
require_once __DIR__ . '/../services/EmailService.php';

class AuthService {
    private $userModel;
    private $cartModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->cartModel = new Cart();
    }

    /**
     * Hash password với bcrypt
     */
    private function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password
     */
    private function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Generate verification token
     */
    private function generateVerificationToken() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validate email format
     */
    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate password strength
     */
    private function validatePassword($password) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return false;
        }
        
        // Kiểm tra có ít nhất 1 chữ hoa, 1 chữ thường, 1 số
        if (!preg_match('/[A-Z]/', $password) || 
            !preg_match('/[a-z]/', $password) || 
            !preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        return true;
    }

    /**
     * ĐĂNG KÝ USER MỚI
     */
    public function register($email, $password, $fullName = null, $role = User::ROLE_USER) {
        try {
            // Validate email
            if (!$this->validateEmail($email)) {
                return ['success' => false, 'message' => 'Email không hợp lệ'];
            }

            // Validate password
            if (!$this->validatePassword($password)) {
                return [
                    'success' => false, 
                    'message' => 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số'
                ];
            }

            // Check email exists
            if ($this->userModel->emailExists($email)) {
                return ['success' => false, 'message' => 'Email đã được sử dụng'];
            }

            // Validate role
            if (!in_array($role, [User::ROLE_GUEST, User::ROLE_USER, User::ROLE_ADMIN])) {
                $role = User::ROLE_USER;
            }

            // Create user
            $passwordHash = $this->hashPassword($password);

            // THAY ĐỔI: Truyền $role vào createUser
            $userId = $this->userModel->createUser(
                $email,
                $passwordHash,
                $fullName,
                $role
            );

            return [
                'success' => true,
                'message' => 'Đăng ký thành công.',
                'user_id' => $userId
            ];

        } catch (Exception $e) {
            error_log("Register error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi đăng ký'];
        }
    }

    /**
     * XÁC THỰC EMAIL
     */
    public function verifyEmail($token) {
        try {
            $verified = $this->userModel->verifyEmail($token);
            
            if ($verified) {
                return ['success' => true, 'message' => 'Email đã được xác thực thành công'];
            } else {
                return ['success' => false, 'message' => 'Token không hợp lệ hoặc đã được sử dụng'];
            }

        } catch (Exception $e) {
            error_log("Verify email error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi xác thực email'];
        }
    }

    /**
     * ĐĂNG NHẬP
     */
    public function login($email, $password) {
        try {
            // Get user
            $user = $this->userModel->findByEmail($email);

            if (!$user) {
                return ['success' => false, 'message' => 'Email hoặc mật khẩu không đúng'];
            }

            // Verify password
            if (!$this->verifyPassword($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Email hoặc mật khẩu không đúng'];
            }

            // Update last login
            $this->userModel->updateLastLogin($user['id']);

            // Get or create cart for user
            $cart = $this->cartModel->getOrCreateCart($user['id']);

            // Generate JWT with role
            $payload = [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ];

            $token = JWTHelper::encode($payload);

            return [
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role'],
                    'email_verified' => $user['email_verified']
                ],
                'cart_id' => $cart['id']
            ];

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi đăng nhập'];
        }
    }

    /**
     * VALIDATE JWT TOKEN
     */
    public function validateToken($token) {
        return JWTHelper::validateToken($token);
    }

    /**
     * GET USER FROM TOKEN
     */
    public function getUserFromToken($token) {
        try {
            $result = $this->validateToken($token);
            
            if (!$result['valid']) {
                return null;
            }

            $userId = $result['payload']['user_id'];
            // Đảm bảo rằng getUserProfile cũng trả về 'role'
            return $this->userModel->getUserProfile($userId);

        } catch (Exception $e) {
            error_log("Get user from token error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ĐỔI MẬT KHẨU
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            $user = $this->userModel->findById($userId);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User không tồn tại'];
            }

            // Verify old password
            if (!$this->verifyPassword($oldPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Mật khẩu cũ không đúng'];
            }

            // Validate new password
            if (!$this->validatePassword($newPassword)) {
                return [
                    'success' => false, 
                    'message' => 'Mật khẩu mới phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số'
                ];
            }

            // Update password
            $newPasswordHash = $this->hashPassword($newPassword);
            $this->userModel->updatePassword($userId, $newPasswordHash);

            return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];

        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi đổi mật khẩu'];
        }
    }

    /**
     * CẬP NHẬT ROLE (Admin only)
     */
    public function updateUserRole($adminId, $targetUserId, $newRole) {
        try {
            // Check admin permission
            if (!$this->userModel->isAdmin($adminId)) {
                return ['success' => false, 'message' => 'Không có quyền thực hiện thao tác này'];
            }

            // Validate role
            if (!in_array($newRole, [User::ROLE_GUEST, User::ROLE_USER, User::ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Role không hợp lệ'];
            }

            // Check target user exists
            $targetUser = $this->userModel->findById($targetUserId);
            if (!$targetUser) {
                return ['success' => false, 'message' => 'User không tồn tại'];
            }

            // Update role
            $this->userModel->updateRole($targetUserId, $newRole);

            return [
                'success' => true, 
                'message' => 'Cập nhật role thành công',
                'user' => [
                    'id' => $targetUserId,
                    'email' => $targetUser['email'],
                    'role' => $newRole
                ]
            ];

        } catch (Exception $e) {
            error_log("Update role error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật role'];
        }
    }
}