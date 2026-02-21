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
// OBTENER SECCIONES Y PERÍODOS PARA FILTROS
// =============================================
$secciones = $db->query("SELECT * FROM secciones WHERE estatus = 'activa' ORDER BY nombre_seccion")->fetchAll();

$periodos = $db->query(
    "SELECT DISTINCT periodo_academico FROM calificaciones ORDER BY periodo_academico DESC"
)->fetchAll();

// =============================================
// FILTROS SELECCIONADOS
// =============================================
$seccion_seleccionada = $_GET['seccion'] ?? null;
$periodo_seleccionado = $_GET['periodo'] ?? ($periodos[0]['periodo_academico'] ?? '2026-2027');

// =============================================
// OBTENER CUADRO DE MÉRITO
// =============================================
$ranking = [];
$promedio_general_seccion = 0;

if ($seccion_seleccionada) {
    // Consulta para obtener promedios por estudiante
    $ranking = $db->query(
        "SELECT 
            e.id_estudiante,
            e.cedula,
            e.nombres,
            e.apellidos,
            COUNT(c.id_calificacion) AS total_materias,
            ROUND(AVG(c.valor_calificacion), 2) AS promedio,
            RANK() OVER (ORDER BY AVG(c.valor_calificacion) DESC) AS posicion
         FROM estudiantes e
         JOIN calificaciones c ON e.id_estudiante = c.id_estudiante
         WHERE e.id_seccion = :seccion 
           AND c.periodo_academico = :periodo
           AND e.estatus_academico = 'activo'
         GROUP BY e.id_estudiante
         ORDER BY promedio DESC",
        ['seccion' => $seccion_seleccionada, 'periodo' => $periodo_seleccionado]
    )->fetchAll();
    
    // Calcular promedio general de la sección
    if (!empty($ranking)) {
        $suma_promedios = array_sum(array_column($ranking, 'promedio'));
        $promedio_general_seccion = round($suma_promedios / count($ranking), 2);
    }
}

// =============================================
// OBTENER DESTACADOS POR MATERIA
// =============================================
$destacados_materia = [];
if ($seccion_seleccionada) {
    $destacados_materia = $db->query(
        "SELECT DISTINCT ON (m.id_materia)
            m.id_materia,
            m.codigo_materia,
            m.nombre_materia,
            e.id_estudiante,
            e.apellidos || ', ' || e.nombres AS estudiante,
            c.valor_calificacion AS nota_maxima
         FROM calificaciones c
         JOIN estudiantes e ON c.id_estudiante = e.id_estudiante
         JOIN materias m ON c.id_materia = m.id_materia
         WHERE e.id_seccion = :seccion 
           AND c.periodo_academico = :periodo
         ORDER BY m.id_materia, c.valor_calificacion DESC",
        ['seccion' => $seccion_seleccionada, 'periodo' => $periodo_seleccionado]
    )->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Cuadros de Mérito</title>
    <style>
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
        
        /* TARJETA DE FILTROS */
        .filtros-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        .filtros-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
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
        
        /* TARJETA DE CUADRO DE HONOR */
        .cuadro-honor {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .header-cuadro {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f0f0;
        }
        .header-cuadro h2 {
            color: #1a3e60;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .header-cuadro p {
            color: #666;
            font-size: 18px;
        }
        .promedio-seccion {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            display: inline-block;
            margin-top: 10px;
            font-weight: bold;
        }
        
        /* PODIO */
        .podium {
            display: grid;
            grid-template-columns: 1fr 1.2fr 1fr;
            gap: 20px;
            align-items: end;
            margin-bottom: 40px;
            padding: 20px;
        }
        .puesto {
            text-align: center;
            padding: 20px;
            border-radius: 15px;
            transition: all 0.3s;
        }
        .puesto:hover {
            transform: translateY(-10px);
        }
        .puesto-1 {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #333;
            order: 2;
            padding: 30px 20px;
        }
        .puesto-2 {
            background: linear-gradient(135deg, #C0C0C0, #A0A0A0);
            color: white;
            order: 1;
        }
        .puesto-3 {
            background: linear-gradient(135deg, #CD7F32, #8B4513);
            color: white;
            order: 3;
        }
        .medalla {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .puesto .nombre {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .puesto .promedio {
            font-size: 24px;
            font-weight: bold;
        }
        
        /* TABLA DE RANKING */
        .ranking-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .ranking-table th {
            background: #1a3e60;
            color: white;
            font-weight: 600;
            padding: 15px 12px;
            text-align: left;
        }
        .ranking-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        .ranking-table tr:hover {
            background: #f8f9fa;
        }
        .posicion {
            font-size: 18px;
            font-weight: bold;
            width: 80px;
            text-align: center;
        }
        .posicion-1 { color: #FFD700; }
        .posicion-2 { color: #C0C0C0; }
        .posicion-3 { color: #CD7F32; }
        
        .badge-destacado {
            background: #ffc107;
            color: #333;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* TARJETAS DE DESTACADOS */
        .destacados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .destacado-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .destacado-card h4 {
            font-size: 18px;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            padding-bottom: 10px;
        }
        .destacado-card .estudiante {
            font-size: 16px;
            font-weight: bold;
        }
        .destacado-card .nota {
            font-size: 24px;
            font-weight: bold;
            margin-top: 10px;
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
        <h1>🏆 SIGAE - Cuadros de Mérito</h1>
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
                        <label>📅 PERÍODO</label>
                        <select name="periodo">
                            <?php foreach ($periodos as $p): ?>
                                <option value="<?= $p['periodo_academico'] ?>" <?= ($periodo_seleccionado == $p['periodo_academico']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['periodo_academico']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>&nbsp;</label>
                        <button type="submit">🔍 GENERAR CUADRO</button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($seccion_seleccionada): ?>
            <?php if (empty($ranking)): ?>
                <div class="cuadro-honor">
                    <div class="alert">
                        🏆 No hay calificaciones registradas para esta sección y período
                    </div>
                </div>
            <?php else: ?>
                
                <!-- CUADRO DE HONOR PRINCIPAL -->
                <div class="cuadro-honor">
                    <div class="header-cuadro">
                        <h2>🏆 CUADRO DE HONOR 🏆</h2>
                        <p>SECCIÓN: <?= htmlspecialchars($seccion_seleccionada) ?> | PERÍODO: <?= htmlspecialchars($periodo_seleccionado) ?></p>
                        <div class="promedio-seccion">
                            📊 PROMEDIO GENERAL DE LA SECCIÓN: <?= number_format($promedio_general_seccion, 2) ?> pts
                        </div>
                    </div>
                    
                    <!-- PODIO (TOP 3) -->
                    <?php if (count($ranking) >= 3): ?>
                    <div class="podium">
                        <!-- 2° LUGAR -->
                        <div class="puesto puesto-2">
                            <div class="medalla">🥈</div>
                            <div class="nombre"><?= htmlspecialchars($ranking[1]['apellidos'] . ', ' . $ranking[1]['nombres']) ?></div>
                            <div class="promedio"><?= number_format($ranking[1]['promedio'], 2) ?></div>
                        </div>
                        
                        <!-- 1° LUGAR -->
                        <div class="puesto puesto-1">
                            <div class="medalla">🥇</div>
                            <div class="nombre"><?= htmlspecialchars($ranking[0]['apellidos'] . ', ' . $ranking[0]['nombres']) ?></div>
                            <div class="promedio"><?= number_format($ranking[0]['promedio'], 2) ?></div>
                        </div>
                        
                        <!-- 3° LUGAR -->
                        <div class="puesto puesto-3">
                            <div class="medalla">🥉</div>
                            <div class="nombre"><?= htmlspecialchars($ranking[2]['apellidos'] . ', ' . $ranking[2]['nombres']) ?></div>
                            <div class="promedio"><?= number_format($ranking[2]['promedio'], 2) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- TABLA COMPLETA DE RANKING -->
                    <h3 style="color: #1a3e60; margin: 30px 0 20px 0;">📋 RANKING COMPLETO</h3>
                    <table class="ranking-table">
                        <thead>
                            <tr>
                                <th>POSICIÓN</th>
                                <th>CÉDULA</th>
                                <th>ESTUDIANTE</th>
                                <th>TOTAL MATERIAS</th>
                                <th>PROMEDIO</th>
                                <th>DESTACADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranking as $r): ?>
                            <tr>
                                <td class="posicion">
                                    <?php if ($r['posicion'] == 1): ?>
                                        <span style="color: #FFD700;">🥇 1°</span>
                                    <?php elseif ($r['posicion'] == 2): ?>
                                        <span style="color: #C0C0C0;">🥈 2°</span>
                                    <?php elseif ($r['posicion'] == 3): ?>
                                        <span style="color: #CD7F32;">🥉 3°</span>
                                    <?php else: ?>
                                        <?= $r['posicion'] ?>°
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['cedula']) ?></td>
                                <td><strong><?= htmlspecialchars($r['apellidos'] . ', ' . $r['nombres']) ?></strong></td>
                                <td style="text-align: center;"><?= $r['total_materias'] ?></td>
                                <td style="font-weight: bold; color: #1a3e60; font-size: 16px;">
                                    <?= number_format($r['promedio'], 2) ?>
                                </td>
                                <td>
                                    <?php if ($r['promedio'] >= 18): ?>
                                        <span class="badge-destacado">🌟 EXCELENCIA</span>
                                    <?php elseif ($r['promedio'] >= 16): ?>
                                        <span class="badge-destacado" style="background: #28a745; color: white;">📚 DESTACADO</span>
                                    <?php elseif ($r['promedio'] >= 14): ?>
                                        <span class="badge-destacado" style="background: #17a2b8; color: white;">📈 BUENO</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- DESTACADOS POR MATERIA -->
                <?php if (!empty($destacados_materia)): ?>
                <div class="cuadro-honor">
                    <h3 style="color: #1a3e60; margin-bottom: 20px;">🏅 DESTACADOS POR MATERIA</h3>
                    <div class="destacados-grid">
                        <?php foreach ($destacados_materia as $dm): ?>
                        <div class="destacado-card">
                            <h4><?= htmlspecialchars($dm['codigo_materia']) ?> - <?= htmlspecialchars($dm['nombre_materia']) ?></h4>
                            <div class="estudiante"><?= htmlspecialchars($dm['estudiante']) ?></div>
                            <div class="nota">🎯 <?= number_format($dm['nota_maxima'], 2) ?> pts</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>