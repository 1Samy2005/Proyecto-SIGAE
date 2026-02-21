<?php
require_once 'C:/xampp/htdocs/SIGAE/app/controllers/TwoFactorController.php';
echo "1. Controlador incluido<br>";

try {
    $controller = new TwoFactorController();
    echo "2. Instancia creada<br>";
    echo "3. TODO OK";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
