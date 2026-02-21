<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

$tipo = $_GET['tipo'] ?? 'materias';
$seccion = $_GET['seccion'] ?? 0;
$periodo = $_GET['periodo'] ?? '2026-2027';
$periodo1 = $_GET['periodo1'] ?? '2026-2027';
$periodo2 = $_GET['periodo2'] ?? '2025-2026';

// Nombre del archivo
$filename = "reporte_{$tipo}_" . date('Ymd_His') . ".csv";

// Headers para descargar CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Abrir salida
$output = fopen('php://output', 'w');

// UTF-8 BOM para Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($tipo == 'materias') {
    // Reporte de promedios por materia
    fputcsv($output, ['CÓDIGO', 'MATERIA', 'TOTAL NOTAS', 'PROMEDIO', 'NOTA MÍNIMA', 'NOTA MÁXIMA']);
    
    $data = $db->query(
        "SELECT 
            m.codigo_materia,
            m.nombre_materia,
            COUNT(c.id_calificacion) AS total_notas,
            ROUND(AVG(c.valor_calificacion), 2) AS promedio,
            ROUND(MIN(c.valor_calificacion), 2) AS minima,
            ROUND(MAX(c.valor_calificacion), 2) AS maxima
         FROM materias m
         JOIN calificaciones c ON m.id_materia = c.id_materia
         JOIN estudiantes e ON c.id_estudiante = e.id_estudiante
         WHERE e.id_seccion = :seccion AND c.periodo_academico = :periodo
         GROUP BY m.id_materia, m.codigo_materia, m.nombre_materia
         ORDER BY promedio DESC",
        ['seccion' => $seccion, 'periodo' => $periodo]
    )->fetchAll();
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
} elseif ($tipo == 'comparativa') {
    // Reporte comparativo entre períodos
    fputcsv($output, ['PERÍODO', 'TOTAL ESTUDIANTES', 'TOTAL CALIFICACIONES', 'PROMEDIO GENERAL']);
    
    $data = $db->query(
        "SELECT 
            c.periodo_academico,
            COUNT(DISTINCT e.id_estudiante) AS estudiantes,
            COUNT(c.id_calificacion) AS calificaciones,
            ROUND(AVG(c.valor_calificacion), 2) AS promedio
         FROM estudiantes e
         JOIN calificaciones c ON e.id_estudiante = c.id_estudiante
         WHERE e.id_seccion = :seccion 
           AND c.periodo_academico IN (:periodo1, :periodo2)
         GROUP BY c.periodo_academico
         ORDER BY c.periodo_academico DESC",
        ['seccion' => $seccion, 'periodo1' => $periodo1, 'periodo2' => $periodo2]
    )->fetchAll();
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
?>