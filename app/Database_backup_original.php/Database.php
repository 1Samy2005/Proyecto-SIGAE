<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    
    private static $instance = null;
    private $conn;
    
    // CONFIGURACIÓN DE LA BASE DE DATOS
    private $host = 'localhost';
    private $port = '5432';
    private $dbname = 'sigaedb';      // ✅ CAMBIA A 'sigae_db' SI USAS ESA
    private $user = 'postgres';
    private $password = '123456';     // ✅ CAMBIA A TU CONTRASEÑA REAL
    private $charset = 'UTF8';
    
    private $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s",
                $this->host,
                $this->port,
                $this->dbname,
                $this->user,
                $this->password
            );
            
            $this->conn = new PDO($dsn, $this->user, $this->password, $this->options);
            $this->conn->exec("SET NAMES '{$this->charset}'");
            
        } catch (PDOException $e) {
            error_log("[Database] Error de conexión: " . $e->getMessage());
            die("<h2 style='color: #721c24; background: #f8d7da; padding: 15px; border-radius: 5px;'>"
                . "❌ Error de conexión a la base de datos. Verifique su archivo Database.php"
                . "</h2>");
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("[Database] Error en consulta: " . $e->getMessage());
            error_log("[Database] SQL: " . $sql);
            error_log("[Database] Params: " . json_encode($params));
            throw $e;
        }
    }
    
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollBack();
    }
    
    public function lastInsertId($name = null) {
        return $this->conn->lastInsertId($name);
    }
    
    private function __clone() {}
    
    public function __wakeup() {}  // ✅ CORREGIDO: visibilidad PUBLIC
}