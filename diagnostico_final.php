<?php
echo "<h1>Diagnóstico 2FA - Versión 2</h1>";

$ruta = 'C:/xampp/htdocs/SIGAE/app/controllers/TwoFactorController.php';
echo "<p><strong>Paso 1:</strong> Verificando existencia del controlador...</p>";

if (file_exists($ruta)) {
    echo "<p style='color:green;'>✅ ¡Archivo encontrado!</p>";
    echo "<p><strong>Paso 2:</strong> Intentando cargar el controlador...</p>";
    
    // Intentar cargar el archivo
    require_once $ruta;
    echo "<p style='color:green;'>✅ ¡Controlador cargado correctamente!</p>";
    
    echo "<p><strong>Paso 3:</strong> Intentando crear la instancia...</p>";
    try {
        $controller = new TwoFactorController();
        echo "<p style='color:green;'>✅ ¡Instancia creada con éxito!</p>";
        echo "<p><strong>El sistema 2FA está listo para configurarse.</strong></p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Error al crear la instancia: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color:red;'>❌ ERROR: El archivo del controlador NO EXISTE en <code>$ruta</code></p>";
}
?>