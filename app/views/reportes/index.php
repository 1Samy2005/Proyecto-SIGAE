<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

// =============================================
// OBTENER SECCIONES Y PERÍODOS
// =============================================
$secciones = $db->query("SELECT * FROM secciones WHERE estatus = 'activa' ORDER BY nombre_seccion")->fetchAll();

$periodos = $db->query(
    "SELECT DISTINCT anio_escolar FROM inscripciones ORDER BY anio_escolar DESC"
)->fetchAll();

// Si no hay períodos en inscripciones, usar los de calificaciones como respaldo
if (empty($periodos)) {
    $periodos = $db->query(
        "SELECT DISTINCT periodo_academico FROM calificaciones ORDER BY periodo_academico DESC"
    )->fetchAll();
}

// =============================================
// FILTROS SELECCIONADOS
// =============================================
$seccion_seleccionada = $_GET['seccion'] ?? null;
$periodo1 = $_GET['periodo1'] ?? ($periodos[0]['anio_escolar'] ?? $periodos[0]['periodo_academico'] ?? '2026-2027');
$periodo2 = $_GET['periodo2'] ?? ($periodos[1]['anio_escolar'] ?? $periodos[1]['periodo_academico'] ?? '2025-2026');

// =============================================
// 1. ESTADÍSTICAS GENERALES DE LA SECCIÓN (CORREGIDO)
// =============================================
$estadisticas_seccion = null;
if ($seccion_seleccionada) {
    $stmt = $db->prepare(
        "SELECT 
            COUNT(DISTINCT i.id_estudiante) AS total_estudiantes,
            COUNT(c.id_calificacion) AS total_calificaciones,
            ROUND(AVG(c.valor_calificacion), 2) AS promedio_seccion,
            ROUND(MIN(c.valor_calificacion), 2) AS nota_minima,
            ROUND(MAX(c.valor_calificacion), 2) AS nota_maxima
         FROM calificaciones c
         JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
         WHERE i.id_seccion = :seccion AND i.anio_escolar = :periodo"
    );
    $stmt->execute(['seccion' => $seccion_seleccionada, 'periodo' => $periodo1]);
    $estadisticas_seccion = $stmt->fetch(PDO::FETCH_ASSOC);
}

// =============================================
// 2. PROMEDIOS POR MATERIA (CORREGIDO)
// =============================================
$promedios_materias = [];
if ($seccion_seleccionada) {
    $stmt = $db->prepare(
        "SELECT 
            m.id_materia,
            m.codigo_materia,
            m.nombre_materia,
            COUNT(c.id_calificacion) AS total_notas,
            ROUND(AVG(c.valor_calificacion), 2) AS promedio,
            ROUND(MIN(c.valor_calificacion), 2) AS minima,
            ROUND(MAX(c.valor_calificacion), 2) AS maxima
         FROM calificaciones c
         JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
         JOIN materias m ON c.id_materia = m.id_materia
         WHERE i.id_seccion = :seccion AND i.anio_escolar = :periodo
         GROUP BY m.id_materia, m.codigo_materia, m.nombre_materia
         ORDER BY promedio DESC"
    );
    $stmt->execute(['seccion' => $seccion_seleccionada, 'periodo' => $periodo1]);
    $promedios_materias = $stmt->fetchAll();
}

// =============================================
// 3. COMPARATIVA ENTRE PERÍODOS (CORREGIDO)
// =============================================
$comparativa_periodos = [];
if ($seccion_seleccionada) {
    $stmt = $db->prepare(
        "SELECT 
            i.anio_escolar AS periodo_academico,
            COUNT(DISTINCT i.id_estudiante) AS estudiantes,
            COUNT(c.id_calificacion) AS calificaciones,
            ROUND(AVG(c.valor_calificacion), 2) AS promedio
         FROM calificaciones c
         JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
         WHERE i.id_seccion = :seccion 
           AND i.anio_escolar IN (:periodo1, :periodo2)
         GROUP BY i.anio_escolar
         ORDER BY i.anio_escolar DESC"
    );
    $stmt->execute([
        'seccion' => $seccion_seleccionada,
        'periodo1' => $periodo1,
        'periodo2' => $periodo2
    ]);
    $comparativa_periodos = $stmt->fetchAll();
}

// =============================================
// 4. DISTRIBUCIÓN DE NOTAS (RANGOS) (CORREGIDO)
// =============================================
$distribucion_notas = null;
if ($seccion_seleccionada) {
    $stmt = $db->prepare(
        "SELECT 
            SUM(CASE WHEN c.valor_calificacion >= 18 THEN 1 ELSE 0 END) AS excelente,
            SUM(CASE WHEN c.valor_calificacion >= 16 AND c.valor_calificacion < 18 THEN 1 ELSE 0 END) AS destacado,
            SUM(CASE WHEN c.valor_calificacion >= 14 AND c.valor_calificacion < 16 THEN 1 ELSE 0 END) AS bueno,
            SUM(CASE WHEN c.valor_calificacion >= 10 AND c.valor_calificacion < 14 THEN 1 ELSE 0 END) AS aprobado,
            SUM(CASE WHEN c.valor_calificacion < 10 THEN 1 ELSE 0 END) AS reprobado
         FROM calificaciones c
         JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
         WHERE i.id_seccion = :seccion AND i.anio_escolar = :periodo"
    );
    $stmt->execute(['seccion' => $seccion_seleccionada, 'periodo' => $periodo1]);
    $distribucion_notas = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Reportes y Estadísticas</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        /* (mantén los mismos estilos que ya tenías) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255,255,255,0.95);
            color: #1a3e60;
            padding: 15px 30px;
            margin: -20px -20px 20px -20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .navbar h1 { 
            font-size: 24px; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar a {
            color: #1a3e60;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.2s;
            font-weight: 500;
        }
        .navbar a:hover { 
            background: #1a3e60; 
            color: white;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* FILTROS */
        .filtros-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .filtros-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }
        .filtro-grupo {
            display: flex;
            flex-direction: column;
        }
        .filtro-grupo label {
            font-size: 12px;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        select, button {
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            width: 100%;
            transition: all 0.2s;
            background: white;
        }
        select:focus, button:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            letter-spacing: 1px;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        /* TARJETAS DE ESTADÍSTICAS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #1a3e60;
            margin-top: 10px;
        }
        
        /* TARJETAS DE REPORTES */
        .report-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .report-header h2 {
            color: #1a3e60;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-export {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-excel {
            background: #28a745;
            color: white;
        }
        .btn-excel:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-pdf {
            background: #dc3545;
            color: white;
        }
        .btn-pdf:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        /* TABLAS */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
        tr:hover {
            background: #f8f9fa;
        }
        
        /* GRÁFICOS */
        .chart-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .chart-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            height: 300px;
        }
        canvas {
            width: 100% !important;
            height: 260px !important;
        }
        
        .alert {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 15px;
            color: #666;
            font-size: 18px;
            border: 2px dashed #ccc;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📊 SIGAE - Reportes y Estadísticas</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <!-- FILTROS -->
        <div class="filtros-card">
            <form method="GET" action="">
                <div class="filtros-grid">
                    <div class="filtro-grupo">
                        <label>📌 SECCIÓN</label>
                        <select name="seccion" required>
                            <option value="">-- SELECCIONE SECCIÓN --</option>
                            <?php foreach ($secciones as $s): ?>
                                <option value="<?= $s['id_seccion'] ?>" <?= ($seccion_seleccionada == $s['id_seccion']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nombre_seccion']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>📅 PERÍODO PRINCIPAL</label>
                        <select name="periodo1">
                            <?php foreach ($periodos as $p): 
                                $valor = $p['anio_escolar'] ?? $p['periodo_academico']; ?>
                                <option value="<?= $valor ?>" <?= ($periodo1 == $valor) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($valor) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>📅 PERÍODO COMPARATIVO</label>
                        <select name="periodo2">
                            <?php foreach ($periodos as $p):
                                $valor = $p['anio_escolar'] ?? $p['periodo_academico']; ?>
                                <option value="<?= $valor ?>" <?= ($periodo2 == $valor) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($valor) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>&nbsp;</label>
                        <button type="submit">📊 GENERAR REPORTES</button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($seccion_seleccionada): ?>
            <?php if (!$estadisticas_seccion || $estadisticas_seccion['total_estudiantes'] == 0): ?>
                <div class="report-card">
                    <div class="alert">
                        📭 No hay datos disponibles para la sección y período seleccionados
                    </div>
                </div>
            <?php else: ?>
                
                <!-- ESTADÍSTICAS GENERALES -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-label">Total Estudiantes</div>
                        <div class="stat-value"><?= $estadisticas_seccion['total_estudiantes'] ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📝</div>
                        <div class="stat-label">Calificaciones</div>
                        <div class="stat-value"><?= $estadisticas_seccion['total_calificaciones'] ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-label">Promedio General</div>
                        <div class="stat-value"><?= number_format($estadisticas_seccion['promedio_seccion'], 2) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📈</div>
                        <div class="stat-label">Nota Máxima</div>
                        <div class="stat-value"><?= number_format($estadisticas_seccion['nota_maxima'], 2) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📉</div>
                        <div class="stat-label">Nota Mínima</div>
                        <div class="stat-value"><?= number_format($estadisticas_seccion['nota_minima'], 2) ?></div>
                    </div>
                </div>
                
                <!-- PROMEDIOS POR MATERIA -->
                <div class="report-card">
                    <div class="report-header">
                        <h2>📚 PROMEDIOS POR MATERIA - <?= htmlspecialchars($periodo1) ?></h2>
                        <div class="export-buttons">
                            <a href="exportar_excel.php?seccion=<?= $seccion_seleccionada ?>&periodo=<?= $periodo1 ?>&tipo=materias" 
                               class="btn-export btn-excel">📥 EXCEL</a>
                            <a href="exportar_pdf.php?seccion=<?= $seccion_seleccionada ?>&periodo=<?= $periodo1 ?>&tipo=materias" 
                               class="btn-export btn-pdf">📄 PDF</a>
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>CÓDIGO</th>
                                    <th>MATERIA</th>
                                    <th>TOTAL NOTAS</th>
                                    <th>PROMEDIO</th>
                                    <th>MÍNIMA</th>
                                    <th>MÁXIMA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($promedios_materias as $m): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($m['codigo_materia']) ?></strong></td>
                                    <td><?= htmlspecialchars($m['nombre_materia']) ?></td>
                                    <td style="text-align: center;"><?= $m['total_notas'] ?></td>
                                    <td style="font-weight: bold; color: #1a3e60;"><?= number_format($m['promedio'], 2) ?></td>
                                    <td><?= number_format($m['minima'], 2) ?></td>
                                    <td><?= number_format($m['maxima'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- COMPARATIVA ENTRE PERÍODOS -->
                <?php if (count($comparativa_periodos) >= 2): ?>
                <div class="report-card">
                    <div class="report-header">
                        <h2>🔄 COMPARATIVA ENTRE PERÍODOS</h2>
                        <div class="export-buttons">
                            <a href="exportar_excel.php?seccion=<?= $seccion_seleccionada ?>&periodo1=<?= $periodo1 ?>&periodo2=<?= $periodo2 ?>&tipo=comparativa" 
                               class="btn-export btn-excel">📥 EXCEL</a>
                            <a href="exportar_pdf.php?seccion=<?= $seccion_seleccionada ?>&periodo1=<?= $periodo1 ?>&periodo2=<?= $periodo2 ?>&tipo=comparativa" 
                               class="btn-export btn-pdf">📄 PDF</a>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <?php foreach ($comparativa_periodos as $cp): ?>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                            <h3 style="color: #1a3e60; margin-bottom: 15px;">📅 <?= htmlspecialchars($cp['periodo_academico']) ?></h3>
                            <p><strong>Estudiantes:</strong> <?= $cp['estudiantes'] ?></p>
                            <p><strong>Calificaciones:</strong> <?= $cp['calificaciones'] ?></p>
                            <p style="font-size: 24px; font-weight: bold; color: #667eea; margin-top: 10px;">
                                <?= number_format($cp['promedio'], 2) ?> pts
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- GRÁFICOS -->
                <div class="report-card">
                    <div class="report-header">
                        <h2>📊 VISUALIZACIÓN DE DATOS</h2>
                    </div>
                    
                    <div class="chart-container">
                        <!-- GRÁFICO DE PROMEDIOS POR MATERIA -->
                        <div class="chart-box">
                            <canvas id="chartMaterias"></canvas>
                        </div>
                        
                        <!-- GRÁFICO DE DISTRIBUCIÓN DE NOTAS -->
                        <div class="chart-box">
                            <canvas id="chartDistribucion"></canvas>
                        </div>
                    </div>
                </div>
                
                <script>
                    // Gráfico de promedios por materia
                    const ctxMaterias = document.getElementById('chartMaterias').getContext('2d');
                    new Chart(ctxMaterias, {
                        type: 'bar',
                        data: {
                            labels: [<?php foreach ($promedios_materias as $m): ?>'<?= $m['codigo_materia'] ?>', <?php endforeach; ?>],
                            datasets: [{
                                label: 'Promedio por Materia',
                                data: [<?php foreach ($promedios_materias as $m): ?><?= $m['promedio'] ?>, <?php endforeach; ?>],
                                backgroundColor: 'rgba(102, 126, 234, 0.7)',
                                borderColor: 'rgba(102, 126, 234, 1)',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 20
                                }
                            }
                        }
                    });
                    
                    // Gráfico de distribución de notas
                    <?php if ($distribucion_notas): ?>
                    const ctxDist = document.getElementById('chartDistribucion').getContext('2d');
                    new Chart(ctxDist, {
                        type: 'doughnut',
                        data: {
                            labels: ['Excelente (18-20)', 'Destacado (16-17.99)', 'Bueno (14-15.99)', 'Aprobado (10-13.99)', 'Reprobado (0-9.99)'],
                            datasets: [{
                                data: [
                                    <?= $distribucion_notas['excelente'] ?? 0 ?>,
                                    <?= $distribucion_notas['destacado'] ?? 0 ?>,
                                    <?= $distribucion_notas['bueno'] ?? 0 ?>,
                                    <?= $distribucion_notas['aprobado'] ?? 0 ?>,
                                    <?= $distribucion_notas['reprobado'] ?? 0 ?>
                                ],
                                backgroundColor: [
                                    'rgba(40, 167, 69, 0.8)',
                                    'rgba(23, 162, 184, 0.8)',
                                    'rgba(255, 193, 7, 0.8)',
                                    'rgba(253, 126, 20, 0.8)',
                                    'rgba(220, 53, 69, 0.8)'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                    <?php endif; ?>
                </script>
                
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>