<?php
// Forzar mostrar TODOS los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ USAR NAMESPACE AL INICIO (DESPUÉS DE <?php)
use App\Core\Database;

echo "<h1 style='color: blue;'>🔍 MODO DEPURACIÓN SIGAE</h1>";
echo "<hr>";

echo "✅ 1. El sistema de errores está ACTIVADO<br><br>";

echo "<h3>🔌 2. VERIFICANDO EXTENSIONES DE PHP</h3>";
echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? '✅ ACTIVADA' : '❌ NO ACTIVADA') . "<br>";
echo "pgsql: " . (extension_loaded('pgsql') ? '✅ ACTIVADA' : '❌ NO ACTIVADA') . "<br><br>";

echo "<h3>📁 3. VERIFICANDO ARCHIVO Database.php</h3>";
$dbFile = 'app/core/Database.php';
if (file_exists($dbFile)) {
    echo "✅ Archivo encontrado: $dbFile<br><br>";
} else {
    echo "❌ Archivo NO encontrado: $dbFile<br><br>";
}

echo "<h3>🔄 4. CARGANDO Database.php</h3>";
try {
    require_once $dbFile;
    echo "✅ Database.php cargado correctamente<br><br>";
    
    echo "<h3>🗄️ 5. CONECTANDO A POSTGRESQL</h3>";
    $db = Database::getInstance();
    echo "✅ ¡CONEXIÓN EXITOSA!<br>";
    
    $stmt = $db->query("SELECT version() as version");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>📦 Versión PostgreSQL:</strong> " . $row['version'] . "</p>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM secciones");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>📋 Secciones:</strong> " . $row['total'] . "</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ ERROR ENCONTRADO:</h2>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
}
?>