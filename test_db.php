<?php
require_once 'app/core/Database.php';
use App\Core\Database;

try {
    $db = Database::getInstance();
    echo "✅ Conexión exitosa a PostgreSQL";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>