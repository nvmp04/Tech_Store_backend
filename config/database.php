<?php
    class Database {
    private $host = "localhost";
    private $db_name = "tech_store";
    private $username = "root";
    private $port = 3306;
    private $password = "";
    private $conn;

    public function connect(): PDO{
        $this->conn = null;

        try{
            $this->conn = new PDO(
                dsn: "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4",
                username: $this->username,
                password: $this->password,
                options: [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        }
        catch(PDOException $e){
            // error_log("Database Connection Error: " . $e->getMessage());    
            die("Database Connection Error: " . $e->getMessage());
            // throw new Exception("Database connection error");
        }
        return $this->conn;
    }

}
?>