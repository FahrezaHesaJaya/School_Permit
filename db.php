<?php

class Database {
    private $servername = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "izin_sekolah";
    private $conn;

    // Singleton instance
    private static $instance = null;

    // Private constructor to prevent instantiation
    private function __construct() {
        $this->connect();
    }

    // Method to connect to the database
    private function connect() {
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    // Public static method to get the Singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Method to get the connection
    public function getConnection() {
        return $this->conn;
    }

    // Destructor to close the connection
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
