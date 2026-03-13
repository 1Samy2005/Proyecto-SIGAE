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
$periodo = $_GET['periodo'] ?? '2026-2027';

if (!$id_estudiante) {
    header('Location: index.php');
    exit;
}

// Obtener datos del estudiante
$stmt_est = $db->prepare(
    "SELECT e.*, s.nombre_seccion 
     FROM estudiantes e
     LEFT JOIN inscripciones i ON e.id_estudiante = i.id_estudiante AND i.anio_escolar = :periodo
     LEFT JOIN secciones s ON i.id_seccion = s.id_seccion
     WHERE e.id_estudiante = :id"
);
$stmt_est->execute(['id' => $id_estudiante, 'periodo' => $periodo]);
$estudiante = $stmt_est->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    header('Location: index.php');
    exit;
}

// Obtener calificaciones del estudiante en el período
$stmt_cal = $db->prepare(
    "SELECT c.*, m.nombre_materia, m.codigo_materia
     FROM calificaciones c
     JOIN materias m ON c.id_materia = m.id_materia
     WHERE c.id_estudiante = :estudiante AND c.periodo_academico = :periodo
     ORDER BY m.nombre_materia, c.tipo_evaluacion"
);
$stmt_cal->execute(['estudiante' => $id_estudiante, 'periodo' => $periodo]);
$calificaciones = $stmt_cal->fetchAll();

// Calcular promedio general
$stmt_prom = $db->prepare(
    "SELECT ROUND(AVG(valor_calificacion), 2) as promedio
     FROM calificaciones
     WHERE id_estudiante = :estudiante AND periodo_academico = :periodo"
);
$stmt_prom->execute(['estudiante' => $id_estudiante, 'periodo' => $periodo]);
$promedio = $stmt_prom->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Boleta de Calificaciones</title>
    <style>
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
        .container { max-width: 1000px; margin: 0 auto; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #1a3e60;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo img {
            width: 80px;
            height: auto;
        }
        .logo h1 {
            color: #1a3e60;
            font-size: 24px;
            margin: 0;
        }
        .logo p {
            color: #666;
            font-size: 14px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-item label {
            font-weight: bold;
            color: #555;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-item span {
            font-size: 18px;
            color: #1a3e60;
            font-weight: bold;
            display: block;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        .promedio {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .btn-print, .btn-back {
            background: #1a3e60;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-print:hover, .btn-back:hover { background: #2c5a7a; }
        .btn-back { background: #6c757d; margin-left: 10px; }
        .btn-back:hover { background: #5a6268; }
        @media print {
            .navbar, .btn-print, .btn-back { display: none; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📄 SIGAE - Boleta de Calificaciones</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <!-- Cabecera con logo -->
            <div class="header">
                <div class="logo">
                    <img src="../../../public/assets/img/logo-escuela.png" alt="Logo U.E.N. José Agustín Marquiegui">
                    <div>
                        <h1>U.E.N. JOSÉ AGUSTÍN MARQUIEGÜI</h1>
                        <p>Sistema de Gestión Académica - SIGAE</p>
                    </div>
                </div>
                <div>
                    <p><strong>Boleta de Calificaciones</strong></p>
                    <p>Período: <?= htmlspecialchars($periodo) ?></p>
                </div>
            </div>
            
            <h2>🎓 DATOS DEL ESTUDIANTE</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>ESTUDIANTE</label>
                    <span><?= htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']) ?></span>
                </div>
                <div class="info-item">
                    <label>CÉDULA</label>
                    <span><?= htmlspecialchars($estudiante['cedula']) ?></span>
                </div>
                <div class="info-item">
                    <label>SECCIÓN</label>
                    <span><?= htmlspecialchars($estudiante['nombre_seccion'] ?? 'No asignada') ?></span>
                </div>
                <div class="info-item">
                    <label>PERÍODO</label>
                    <span><?= htmlspecialchars($periodo) ?></span>
                </div>
            </div>
            
            <?php if (empty($calificaciones)): ?>
                <p style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 5px;">
                    📭 No hay calificaciones registradas para este período
                </p>
            <?php else: ?>
                <h2>📊 CALIFICACIONES</h2>
                <table>
                    <thead>
                        <tr>
                            <th>MATERIA</th>
                            <th>TIPO</th>
                            <th>NOTA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calificaciones as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['codigo_materia']) ?> - <?= htmlspecialchars($c['nombre_materia']) ?></td>
                            <td><?= strtoupper($c['tipo_evaluacion']) ?></td>
                            <td style="font-weight: bold; color: #1a3e60;"><?= number_format($c['valor_calificacion'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="promedio">
                    PROMEDIO GENERAL: <?= number_format($promedio, 2) ?>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center;">
                <button class="btn-print" onclick="window.print()">🖨️ IMPRIMIR BOLETA</button>
                <a href="index.php" class="btn-back">🔙 VOLVER</a>
            </div>
        </div>
    </div>
</body>
</html>