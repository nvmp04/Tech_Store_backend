<?php
require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';

    const ROLE_GUEST = 'guest';
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';

    // Tìm User theo email
    public function findByEmail($email) {
        return $this->findBy('email', $email);
    }

    // Tìm user theo verification token
    public function findByVerificationToken($token) {
        return $this->findBy('verification_token', $token);
    }

    // Tạo user mới
    public function createUser($email, $passwordHash, $fullName = null, $role = self::ROLE_USER) {
        $data = [
            'email' => $email,
            'password_hash' => $passwordHash,
            'full_name' => $fullName,
            'role' => $role
        ];
        return $this->create($data);
    }
    
    // Verify email
    public function verifyEmail($token) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET email_verified = 1, verification_token = NULL 
            WHERE verification_token = ? AND email_verified = 0
        ");
        
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    }

    // Update password
    public function updatePassword($userId, $newPasswordHash) {
         return $this->update($userId, ['password_hash' => $newPasswordHash]);
    }

    // Update user role (chỉ admin mới được gọi)
    public function updateRole($userId, $newRole) {
        if (!in_array($newRole, [self::ROLE_GUEST, self::ROLE_USER, self::ROLE_ADMIN])) {
            throw new InvalidArgumentException("Invalid role");
        }
        return $this->update($userId, ['role' => $newRole]);
    }

    // Check if email exists
    public function emailExists($email) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    // Get verified users
    public function getVerifiedUsers($limit = null, $offset = 0) {
        $sql = "SELECT id, email, full_name, role, created_at FROM {$this->table} WHERE email_verified = 1";
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    // Get all users (admin only)
    public function getAllUsers($limit = null, $offset = 0) {
        $sql = "SELECT id, email, full_name, role, created_at, updated_at FROM {$this->table}";
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    // Get users by role
    public function getUsersByRole($role, $limit = null, $offset = 0) {
        $sql = "SELECT id, email, full_name, role, created_at FROM {$this->table} WHERE role = ?";
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$role, (int)$limit, (int)$offset]);
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$role]);
        }
        return $stmt->fetchAll();
    }

    // Update last login
    public function updateLastLogin($userId) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table} 
            SET updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }

    // Get user profile (without sensitive data)
    public function getUserProfile($userId) {
        $stmt = $this->conn->prepare("
            SELECT id, email, full_name, role, email_verified, created_at, updated_at
            FROM {$this->table} 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    // Check if user is admin
    public function isAdmin($userId) {
        $user = $this->findById($userId);
        return $user && $user['role'] === self::ROLE_ADMIN;
    }

    // Check if user has role
    public function hasRole($userId, $role) {
        $user = $this->findById($userId);
        return $user && $user['role'] === $role;
    }
}