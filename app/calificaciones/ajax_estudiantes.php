<?php
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

$id_seccion = $_GET['seccion'] ?? 0;

if ($id_seccion) {
    $estudiantes = $db->query(
        "SELECT id_estudiante, cedula, nombres, apellidos 
         FROM estudiantes 
         WHERE id_seccion = :seccion AND estatus_academico = 'activo'
         ORDER BY apellidos, nombres",
        ['seccion' => $id_seccion]
    )->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($estudiantes);
}
?>