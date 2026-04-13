<?php

class Connect
{
    protected $conn;
    private static $instance = null;

    protected function __construct()
    {
        $this->getConnection();
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
        // Check if connection is still valid
        if ($this->conn instanceof mysqli && $this->conn->ping()) {
            return $this->conn;
        }
        // Reconnect if needed
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $user = getenv('DB_USER') ?: 'app';
        $pass = getenv('DB_PASS') ?: 'app';
        $name = getenv('DB_NAME') ?: 'app';
        $port = (int) (getenv('DB_PORT') ?: 3308);
        $this->conn = new mysqli($host, $user, $pass, $name, $port);
        $this->conn->set_charset('utf8mb4');
        $this->conn->query("SET time_zone = '+00:00'");
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
}
