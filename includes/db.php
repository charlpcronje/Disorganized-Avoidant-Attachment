<?php
// /includes/db.php
// Database connection handler

require_once 'config.php';
require_once 'functions.php';

class Database {
    private static $instance = null;
    private $conn;
    private $logger;
    
    // Private constructor - singleton pattern
    private function __construct() {
        $this->logger = new Logger();
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                $this->logger->error("Database connection failed: " . $this->conn->connect_error);
                throw new Exception("Database connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            $this->logger->error("Database connection error: " . $e->getMessage());
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    // Get database instance (singleton pattern)
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Get the connection
    public function getConnection() {
        return $this->conn;
    }
    
    // Prepare statement with query
    public function prepare($sql) {
        try {
            return $this->conn->prepare($sql);
        } catch (Exception $e) {
            $this->logger->error("Query preparation failed: " . $e->getMessage());
            return false;
        }
    }
    
    // Execute query
    public function query($sql) {
        try {
            $result = $this->conn->query($sql);
            if (!$result) {
                $this->logger->error("Query failed: " . $this->conn->error . " (SQL: $sql)");
            }
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Query execution failed: " . $e->getMessage() . " (SQL: $sql)");
            return false;
        }
    }
    
    // Get last inserted ID
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    // Get error
    public function error() {
        return $this->conn->error;
    }
    
    // Count affected rows
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
    
    // Escape string
    public function escapeString($string) {
        return $this->conn->real_escape_string($string);
    }
    
    // Close connection (destruct)
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}