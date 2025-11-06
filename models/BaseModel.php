<?php
require_once __DIR__ . '/../config/database.php';

abstract class BaseModel {
    protected $conn;
    protected $table;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollBack() {
        return $this->conn->rollBack();
    }

    // Find record by ID
    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute(([$id]));
        return $stmt->fetch();
    }

    // Find records by specific field
    public function findBy($field, $value){
        if(!preg_match('/^[a-zA-Z0-9_]+$/', $field)){
            throw new InvalidArgumentException("Invalid field name");
        }

        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} where {$field} = ?");
        $stmt->execute([$value]);
        return $stmt->fetch();
    }

    // Find all records with optional pagination
    public function findAll($limit = null, $offset = null){
        $sql = "SELECT * FROM {$this->table}";
        
        if($limit !== null && $offset !== null){
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([(int)$limit, (int)$offset]);
        } else {
            $stmt = $this->conn->query($sql);
        }
        
        return $stmt->fetchAll();
    }
    
    // Create new record
    public function create($data){
        $fields = array_keys($data);
        $placeholders = array_fill(0,count($fields), '?');
    
        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->conn->lastInsertId();
    }

    // Update record by ID
    public function update($id, $data){
        $fields = [];
        foreach ($data as $field => $value){
            $fields[] = "{$field} = ?";
        }

        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($values);
    }

    // Delete record by ID
    public function delete($id){
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Count total records
    public function count($where = null, $params = []){
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";

        if($where){
            $sql .= " WHERE " . $where;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch()['total'];
    }
}