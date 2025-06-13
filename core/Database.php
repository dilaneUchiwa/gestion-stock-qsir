<?php

class Database {
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct() {
        $this->config = require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
        $dsn = "{$this->config['driver']}:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};user={$this->config['username']};password={$this->config['password']}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $options);

            $this->pdo->exec('SET search_path TO ' . $this->config['schema']);

        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log error or handle as needed
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function select($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll();
    }

    public function insert($sql, $params = []) {
        $this->executeQuery($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function update($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }

    public function delete($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }

    // Prevent cloning and unserialization
    private function __clone() { }
    public function __wakeup() { }
}
?>
