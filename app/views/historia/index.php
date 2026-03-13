<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

$id_estudiante = $_GET['id'] ?? 0;
if (!$id_estudiante) {
    header('Location: ../estudiantes/index.php');
    exit;
}

// Obtener datos del estudiante
$stmt_est = $db->prepare(
    "SELECT id_estudiante, cedula, nombres, apellidos 
     FROM estudiantes 
     WHERE id_estudiante = :id"
);
$stmt_est->execute(['id' => $id_estudiante]);
$estudiante = $stmt_est->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    header('Location: ../estudiantes/index.php');
    exit;
}

// Obtener todas las calificaciones del estudiante, ordenadas por período y materia
$stmt_cal = $db->prepare(
    "SELECT c.*, m.nombre_materia, m.codigo_materia, s.nombre_seccion, i.anio_escolar
     FROM calificaciones c
     JOIN materias m ON c.id_materia = m.id_materia
     LEFT JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
     LEFT JOIN secciones s ON i.id_seccion = s.id_seccion
     WHERE c.id_estudiante = :estudiante
     ORDER BY i.anio_escolar DESC, m.nombre_materia, c.tipo_evaluacion"
);
$stmt_cal->execute(['estudiante' => $id_estudiante]);
$calificaciones = $stmt_cal->fetchAll();

// Agrupar por período para mejor visualización
$historial = [];
foreach ($calificaciones as $c) {
    $periodo = $c['anio_escolar'] ?? 'Sin período';
    $historial[$periodo][] = $c;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Historial Académico</title>
    <style>
        /* Estilos similares a los que ya usas */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .navbar {
            background: #1a3e60;
            color: white;
            padding: 15px 30px;
            margin: -20px -20px 20px -20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .navbar a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1200px; margin: 0 auto; }
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h2 {
            color: #1a3e60;
            margin-bottom: 20px;
            border-bottom: 3px solid #1a3e60;
            padding-bottom: 10px;
        }
        .info-estudiante {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .periodo {
            background: #e9ecef;
            padding: 10px 15px;
            margin: 20px 0 10px;
            border-radius: 5px;
            font-weight: bold;
            color: #1a3e60;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #1a3e60;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .btn-volver {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        .btn-volver:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📚 SIGAE - Historial Académico</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>📋 HISTORIAL ACADÉMICO</h2>
            
            <div class="info-estudiante">
                <strong>Estudiante:</strong> <?= htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']) ?><br>
                <strong>Cédula:</strong> <?= htmlspecialchars($estudiante['cedula']) ?>
            </div>
            
            <?php if (empty($historial)): ?>
                <p style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 5px;">
                    📭 No hay calificaciones registradas para este estudiante.
                </p>
            <?php else: ?>
                <?php foreach ($historial as $periodo => $califs): ?>
                    <div class="periodo">📅 Período: <?= htmlspecialchars($periodo) ?></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Materia</th>
                                <th>Tipo</th>
                                <th>Nota</th>
                                <th>Sección</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($califs as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['codigo_materia']) ?> - <?= htmlspecialchars($c['nombre_materia']) ?></td>
                                <td><?= strtoupper($c['tipo_evaluacion']) ?></td>
                                <td style="font-weight: bold; color: #1a3e60;"><?= number_format($c['valor_calificacion'], 2) ?></td>
                                <td><?= htmlspecialchars($c['nombre_seccion'] ?? 'N/A') ?></td>
                                <td><?= date('d/m/Y', strtotime($c['fecha_registro'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="../estudiantes/index.php" class="btn-volver">🔙 VOLVER A ESTUDIANTES</a>
            </div>
        </div>
    </div>
</body>
</html>