<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

$id_seccion = $_GET['seccion'] ?? 0;

if (!$id_seccion) {
    echo json_encode([]);
    exit;
}

// Obtener estudiantes activos de la sección
$estudiantes = $db->query(
    "SELECT e.id_estudiante, e.cedula, e.nombres, e.apellidos
     FROM estudiantes e
     JOIN inscripciones i ON e.id_estudiante = i.id_estudiante
     WHERE i.id_seccion = :seccion 
       AND i.anio_escolar = '2026-2027'
       AND e.estatus_academico = 'activo'
     ORDER BY e.apellidos, e.nombres",
    ['seccion' => $id_seccion]
)->fetchAll();

header('Content-Type: application/json');
echo json_encode($estudiantes);
?>