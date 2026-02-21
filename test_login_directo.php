<?php
session_start();
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';

use App\Core\Database;

echo "<h1 style='color: blue;'>🔍 PRUEBA DIRECTA DE LOGIN</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    
    // 1. Verificar que el usuario existe
    echo "<h3>1. Verificando usuario 'admin'...</h3>";
    $stmt = $db->query("SELECT * FROM usuarios WHERE nombre_usuario = 'admin'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("<p style='color: red;'>❌ Usuario 'admin' NO existe en la BD</p>");
    }
    
    echo "<p style='color: green;'>✅ Usuario 'admin' ENCONTRADO</p>";
    echo "<p><strong>ID:</strong> " . $user['id_usuario'] . "</p>";
    echo "<p><strong>Email:</strong> " . $user['email'] . "</p>";
    echo "<p><strong>Hash en BD:</strong> " . $user['password_hash'] . "</p>";
    
    // 2. Probar la contraseña 'Admin2026!'
    echo "<h3>2. Probando contraseña 'Admin2026!'...</h3>";
    $password_prueba = 'Admin2026!';
    $verificacion = password_verify($password_prueba, $user['password_hash']);
    
    echo "<p><strong>Contraseña probada:</strong> $password_prueba</p>";
    echo "<p><strong>Resultado de verificación:</strong> " . 
         ($verificacion ? '<span style="color: green;">✅ CORRECTA</span>' : '<span style="color: red;">❌ INCORRECTA</span>') . 
         "</p>";
    
    // 3. Si es incorrecta, regenerar el hash
    if (!$verificacion) {
        echo "<h3>3. Regenerando hash...</h3>";
        echo "<p style='color: orange;'>⚠️ La contraseña no coincide. Regenerando hash...</p>";
        
        $nuevo_hash = password_hash('Admin2026!', PASSWORD_DEFAULT);
        
        $update = $db->query(
            "UPDATE usuarios SET password_hash = :hash WHERE nombre_usuario = 'admin'",
            ['hash' => $nuevo_hash]
        );
        
        echo "<p style='color: green;'>✅ Hash actualizado correctamente</p>";
        echo "<p><strong>Nuevo hash:</strong> $nuevo_hash</p>";
        
        // Verificar el nuevo hash
        $stmt = $db->query("SELECT password_hash FROM usuarios WHERE nombre_usuario = 'admin'");
        $nuevo = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>Hash en BD ahora:</strong> " . $nuevo['password_hash'] . "</p>";
        
        // Probar nuevamente
        $verificacion2 = password_verify('Admin2026!', $nuevo['password_hash']);
        echo "<p><strong>Verificación con nuevo hash:</strong> " . 
             ($verificacion2 ? '<span style="color: green;">✅ CORRECTA</span>' : '<span style="color: red;">❌ INCORRECTA</span>') . 
             "</p>";
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ PRUEBA COMPLETADA</h2>";
    echo "<p>Ahora intenta iniciar sesión en <a href='/SIGAE/app/views/auth/login.php'>el login</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR</h2>";
    echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
}
?>