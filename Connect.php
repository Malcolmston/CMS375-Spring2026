<?php

class Connect
{
    protected $conn;
    private static $instance = null;

    protected function __construct()
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $user = getenv('DB_USER') ?: 'app';
        $pass = getenv('DB_PASS') ?: 'app';
        $name = getenv('DB_NAME') ?: 'app';
        $port = (int) (getenv('DB_PORT') ?: 3308);


        $this->conn = new mysqli($host, $user, $pass, $name, $port);

        if ($this->conn->connect_error) {
            throw new Exception('DB connection failed: ' . $this->conn->connect_error);
        }
        $this->conn->set_charset('utf8mb4');

        $this->conn->query("SET time_zone = '+00:00'");
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): mysqli
    {
        return $this->conn;
    }

    /**
     * Check if the database connection is established
     *
     * @return bool True if connected, false otherwise
     */
    public function isConnected(): bool
    {
        return isset($this->conn) && $this->conn instanceof mysqli;
    }

    public function __destruct()
    {
        // Skip if no connection or not a valid mysqli object
        if (!isset($this->conn) || !($this->conn instanceof mysqli)) {
            return;
        }
        try {
            @$this->conn->close();
        } catch (Error $e) {
            // Already closed, ignore
        }
    }
}
