<?php

class Connect
{
    private $conn;

    public function __construct()
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
        return isset($this->conn);
    }
}
