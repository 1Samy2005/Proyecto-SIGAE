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

$inscripcion_id = $_GET['inscripcion'] ?? 0;

if (!$inscripcion_id) {
    echo json_encode(['error' => 'ID de inscripción no válido']);
    exit;
}

// Obtener calificaciones
$calificaciones = $db->query(
    "SELECT 
        m.codigo_materia,
        m.nombre_materia,
        c.valor_calificacion,
        c.tipo_evaluacion,
        p.nombre_periodo
     FROM calificaciones c
     JOIN materias m ON c.id_materia = m.id_materia
     LEFT JOIN periodos_academicos p ON c.id_periodo = p.id_periodo
     WHERE c.id_inscripcion = :inscripcion
     ORDER BY p.fecha_inicio, m.nombre_materia",
    ['inscripcion' => $inscripcion_id]
)->fetchAll();

header('Content-Type: application/json');
echo json_encode($calificaciones);