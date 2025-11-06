<?php

class Database {
    private $conn;

    public function connect() {
        $host =  'db';
        $user =  'appuser';
        $password = 'apppassword';
        $dbname = 'myapp_db';
        $this->conn = new mysqli($host, $user, $password, $dbname);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        return $this->conn;
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>