<?php
require_once 'C:/xampp/htdocs/SIGAE/app/controllers/TwoFactorController.php';
echo "1. Controlador incluido correctamente<br>";

try {
    $controller = new TwoFactorController();
    echo "2. Instancia creada correctamente<br>";
    
    // No llamamos a configurar(), solo probamos la carga
    echo "3. Todo OK";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
