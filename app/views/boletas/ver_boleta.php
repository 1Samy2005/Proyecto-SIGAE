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

// Datos del estudiante
$estudiante = $db->query(
    "SELECT e.*, s.nombre_seccion 
     FROM estudiantes e
     LEFT JOIN secciones s ON e.id_seccion = s.id_seccion
     WHERE e.id_estudiante = :id",
    ['id' => $id_estudiante]
)->fetch();

if (!$estudiante) {
    header('Location: index.php');
    exit;
}

// Calificaciones del estudiante
$calificaciones = $db->query(
    "SELECT c.*, m.nombre_materia, m.codigo_materia, d.nombres as nombres_docente, d.apellidos as apellidos_docente
     FROM calificaciones c
     JOIN materias m ON c.id_materia = m.id_materia
     JOIN docentes d ON c.id_docente = d.id_docente
     WHERE c.id_estudiante = :id AND c.periodo_academico = :periodo
     ORDER BY m.nombre_materia",
    ['id' => $id_estudiante, 'periodo' => $periodo]
)->fetchAll();

// Calcular promedio
$suma = 0;
$total = count($calificaciones);
foreach ($calificaciones as $c) {
    $suma += $c['valor_calificacion'];
}
$promedio = $total > 0 ? round($suma / $total, 2) : 0;

// Determinar estatus
$estatus = $promedio >= 10 ? 'APROBADO' : 'REPROBADO';
$estatus_color = $promedio >= 10 ? '#28a745' : '#dc3545';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta de Notas - <?= htmlspecialchars($estudiante['apellidos'] . ' ' . $estudiante['nombres']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .boleta {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: #1a3e60;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header h2 {
            font-size: 20px;
            font-weight: normal;
            opacity: 0.9;
        }
        .info-section {
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-value {
            font-size: 18px;
            font-weight: bold;
            color: #1a3e60;
            margin-top: 5px;
        }
        .table-container {
            padding: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #1a3e60;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .nota {
            font-weight: bold;
            font-size: 16px;
            color: #1a3e60;
        }
        .footer {
            padding: 30px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .promedio {
            font-size: 24px;
            font-weight: bold;
            color: #1a3e60;
        }
        .estatus {
            padding: 10px 20px;
            border-radius: 25px;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #1a3e60;
            color: white;
        }
        .btn-primary:hover {
            background: #2c5a7a;
            transform: translateY(-2px);
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        @media print {
            body { background: white; }
            .no-print { display: none; }
            .boleta { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="boleta">
      <!-- HEADER CON LOGO DEL COLEGIO -->
<div class="header" style="display: flex; align-items: center; justify-content: center; gap: 20px;">
    <img src="http://localhost/SIGAE/public/assets/img/logo-escuela.png" 
         style="width: 80px; height: auto;" 
         alt="Logo U.E.N. José Agustín Marquiegui">
    <div>
        <h1 style="font-size: 28px; margin-bottom: 10px;">U.E.N. JOSÉ AGUSTÍN MARQUIEGÜI</h1>
        <h2 style="font-size: 20px; font-weight: normal; opacity: 0.9;">SISTEMA DE GESTIÓN ACADÉMICA - SIGAE</h2>
        <h2 style="font-size: 20px; font-weight: normal; opacity: 0.9;">BOLETA DE NOTAS OFICIAL</h2>
    </div>
</div>
</div>v>
        
        <!-- INFORMACIÓN DEL ESTUDIANTE -->
        <div class="info-section">
            <div class="info-item">
                <span class="info-label">ESTUDIANTE</span>
                <span class="info-value"><?= htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">CÉDULA</span>
                <span class="info-value"><?= htmlspecialchars($estudiante['cedula']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">SECCIÓN</span>
                <span class="info-value"><?= htmlspecialchars($estudiante['nombre_seccion']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">PERÍODO ACADÉMICO</span>
                <span class="info-value"><?= htmlspecialchars($periodo) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">FECHA DE EMISIÓN</span>
                <span class="info-value"><?= date('d/m/Y') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">TOTAL ASIGNATURAS</span>
                <span class="info-value"><?= $total ?></span>
            </div>
        </div>
        
        <!-- TABLA DE CALIFICACIONES -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO</th>
                        <th>MATERIA</th>
                        <th>DOCENTE</th>
                        <th>TIPO</th>
                        <th style="text-align: center;">NOTA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($calificaciones)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px; color: #666;">
                                📭 No hay calificaciones registradas para este período
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($calificaciones as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['codigo_materia']) ?></strong></td>
                            <td><?= htmlspecialchars($c['nombre_materia']) ?></td>
                            <td><?= htmlspecialchars($c['apellidos_docente']) ?>, <?= htmlspecialchars($c['nombres_docente']) ?></td>
                            <td><span style="background: #17a2b8; color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px;"><?= strtoupper($c['tipo_evaluacion']) ?></span></td>
                            <td style="text-align: center; font-weight: bold; font-size: 18px; color: #1a3e60;"><?= number_format($c['valor_calificacion'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- PIE DE PÁGINA CON PROMEDIO Y ESTATUS -->
        <div class="footer">
            <div>
                <span class="info-label">PROMEDIO GENERAL</span>
                <div class="promedio"><?= number_format($promedio, 2) ?> pts</div>
            </div>
            <div>
                <span class="info-label">ESTATUS ACADÉMICO</span>
                <div class="estatus" style="background: <?= $estatus_color ?>;">
                    <?= $estatus ?>
                </div>
            </div>
        </div>
        
        <!-- ACCIONES (IMPRIMIR/REGRESAR) - NO VISIBLE EN IMPRESIÓN -->
        <div class="actions no-print" style="padding: 20px;">
            <button onclick="window.print()" class="btn btn-success">
                🖨️ IMPRIMIR BOLETA
            </button>
            <a href="index.php?seccion=<?= $estudiante['id_seccion'] ?>&periodo=<?= $periodo ?>" class="btn btn-secondary">
                ⬅️ REGRESAR
            </a>
        </div>
    </div>
    
    <script>
        // Auto-imprimir? (opcional, descomentar si se desea)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>