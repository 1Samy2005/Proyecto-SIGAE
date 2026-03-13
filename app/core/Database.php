<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $host = 'aws-1-us-east-2.pooler.supabase.com';
        $port = '5432';
        $dbname = 'postgres';
        $user = 'postgres.gykochlunbxxuvdxlnuw';
        $pass = 'KzEAcBJD6FX5IIf5';

        try {
            $this->connection = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
}