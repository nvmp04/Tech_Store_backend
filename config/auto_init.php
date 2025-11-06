<?php
/**
 * Auto initialize admin account
 * File này sẽ tự động chạy khi được include
 * Chỉ tạo admin nếu chưa tồn tại
 */

function autoInitAdmin() {
    try {
        require_once __DIR__ . '/database.php';
        
        $db = new Database();
        $conn = $db->connect();
        
        // Kiểm tra admin đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
        $stmt->execute(['admin@techstore.com']);
        
        // Nếu đã có admin thì không làm gì
        if ($stmt->rowCount() > 0) {
            return;
        }
        
        // Tạo password hash
        $passwordHash = password_hash('Admin@123', PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Insert admin account
        $stmt = $conn->prepare("
            INSERT INTO users (id, email, password_hash, full_name, role) 
            VALUES (UUID(), ?, ?, ?, 'admin')
            ON DUPLICATE KEY UPDATE 
                password_hash = VALUES(password_hash),
                role = 'admin'
        ");
        
        $stmt->execute([
            'admin@techstore.com',
            $passwordHash,
            'System Admin'
        ]);
        
        error_log("Admin account auto-initialized: admin@techstore.com / Admin@123");
        
    } catch (Exception $e) {
        error_log(" Auto init admin failed: " . $e->getMessage());
    }
}

// Tự động chạy khi file được include
autoInitAdmin();