<?php
require_once __DIR__ . '/env.php'; // Đảm bảo load .env trước
class Database {
    private ?PDO $conn = null;

    public function connect(): PDO {
        if ($this->conn) return $this->conn;

        // Kiểm tra biến môi trường có tồn tại không
        $requiredKeys = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD'];
        foreach ($requiredKeys as $key) {
            if (!isset($_ENV[$key])) {
                die("❌ Missing ENV variable: {$key}. Did you load .env?");
            }
        }

        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_NAME']
            );

            $this->conn = new PDO(
                $dsn,
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }

        return $this->conn;
    }
}
