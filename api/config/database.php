<?php
class Database {
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Always use MySQL configuration for local deployment
            $host = 'localhost';
            $port = '3306';
            $dbname = 'agile';
            $username = 'root';
            $password = '';
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

            $this->conn = new PDO($dsn, $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // Don't output connection error to response, log it instead
            error_log("Database connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>