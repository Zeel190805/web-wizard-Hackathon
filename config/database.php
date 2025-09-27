<?php
// Database configuration for Student Portal
class Database {
    private $host = 'localhost';
    private $dbname = 'student_portal';
    private $username = 'root';
    private $password = '';
    private $pdo;
    
    public function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}

// Global database instance
$database = new Database();
$pdo = $database->getConnection();
?>
