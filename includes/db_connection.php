<?php
/**
 * Simplified Database Connection - Uses config/database.php
 */
require_once '../config/database.php';

class Database {
    private $conn;
    
    public function getConnection() {
        if ($this->conn === null) {
            $this->conn = DatabaseConfig::getConnection();
        }
        return $this->conn;
    }
}
?>