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
// REGISTRAR CALIFICACIONES
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {
    $id_estudiante = $_POST['id_estudiante'];
    $id_materia = $_POST['id_materia'];
    $id_docente = $_POST['id_docente'];
    $valor = $_POST['valor'];
    $tipo = $_POST['tipo'];
    $periodo = $_POST['periodo'];
    $observaciones = $_POST['observaciones'] ?? '';
    
    try {
        $db->query(
            "INSERT INTO calificaciones 
            (id_estudiante, id_materia, id_docente, valor_calificacion, tipo_evaluacion, periodo_academico, observaciones, id_usuario_registro) 
            VALUES 
            (:estudiante, :materia, :docente, :valor, :tipo, :periodo, :obs, :usuario)",
            [
                'estudiante' => $id_estudiante,
                'materia' => $id_materia,
                'docente' => $id_docente,
                'valor' => $valor,
                'tipo' => $tipo,
                'periodo' => $periodo,
                'obs' => $observaciones,
                'usuario' => $_SESSION['user_id']
            ]
        );
        $success = "✅ Calificación registrada exitosamente";
    } catch (Exception $e) {
        $error = "❌ Error al registrar: " . $e->getMessage();
    }
}

// =============================================
// ELIMINAR CALIFICACIÓN
// =============================================
if (isset($_GET['eliminar'])) {
    $db->query("DELETE FROM calificaciones WHERE id_calificacion = :id", ['id' => $_GET['eliminar']]);
    header('Location: index.php?success=eliminado');
    exit;
}

// =============================================
// OBTENER DATOS PARA LOS SELECTS
// =============================================
$secciones = $db->query("SELECT * FROM secciones WHERE estatus = 'activa' ORDER BY nombre_seccion")->fetchAll();
$materias = $db->query("SELECT * FROM materias WHERE estatus = 'activa' ORDER BY nombre_materia")->fetchAll();
$docentes = $db->query("SELECT * FROM docentes WHERE estatus = 'activo' ORDER BY apellidos, nombres")->fetchAll();

// =============================================
// OBTENER ESTUDIANTES POR SECCIÓN (AJAX)
// =============================================
$estudiantes_por_seccion = [];
if (isset($_GET['seccion'])) {
    $id_seccion = $_GET['seccion'];
    $estudiantes_por_seccion = $db->query(
        "SELECT id_estudiante, cedula, nombres, apellidos 
         FROM estudiantes 
         WHERE id_seccion = :seccion AND estatus_academico = 'activo'
         ORDER BY apellidos, nombres",
        ['seccion' => $id_seccion]
    )->fetchAll();
}

// =============================================
// OBTENER CALIFICACIONES POR SECCIÓN
// =============================================
$calificaciones = [];
$seccion_seleccionada = $_GET['ver_seccion'] ?? null;
$periodo_seleccionado = $_GET['periodo'] ?? '2026-2027';

if ($seccion_seleccionada) {
    $calificaciones = $db->query(
        "SELECT c.*, 
                e.cedula as cedula_estudiante, e.nombres as nombres_estudiante, e.apellidos as apellidos_estudiante,
                m.nombre_materia, m.codigo_materia,
                d.nombres as nombres_docente, d.apellidos as apellidos_docente
         FROM calificaciones c
         JOIN estudiantes e ON c.id_estudiante = e.id_estudiante
         JOIN materias m ON c.id_materia = m.id_materia
         JOIN docentes d ON c.id_docente = d.id_docente
         WHERE e.id_seccion = :seccion AND c.periodo_academico = :periodo
         ORDER BY e.apellidos, e.nombres, m.nombre_materia",
        ['seccion' => $seccion_seleccionada, 'periodo' => $periodo_seleccionado]
    )->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Calificaciones</title>
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
        .navbar h1 { font-size: 24px; }
        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .navbar a:hover { background: rgba(255,255,255,0.2); }
        .container { max-width: 1400px; margin: 0 auto; }
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
            font-size: 22px;
            border-bottom: 3px solid #1a3e60;
            padding-bottom: 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-size: 12px;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        input, select, button, textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 100%;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #1a3e60;
            outline: none;
            box-shadow: 0 0 0 3px rgba(26,62,96,0.1);
        }
        button {
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }
        button:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover { background: #c82333; }
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        .btn-warning:hover { background: #e0a800; }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover { background: #138496; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        th {
            background: #1a3e60;
            color: white;
            font-weight: 600;
            padding: 15px 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        tr:hover { background: #f5f5f5; }
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        .badge-info { background: #17a2b8; color: white; }
        .nota {
            font-size: 18px;
            font-weight: bold;
            color: #1a3e60;
        }
        .filtros {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📊 SIGAE - Gestión de Calificaciones</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] == 'eliminado'): ?>
            <div class="alert alert-success">✅ Calificación eliminada correctamente</div>
        <?php endif; ?>
        
        <!-- ========================================= -->
        <!-- REGISTRO DE CALIFICACIONES -->
        <!-- ========================================= -->
        <div class="card">
            <h2>➕ REGISTRAR NUEVA CALIFICACIÓN</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>📌 SECCIÓN</label>
                        <select id="seccion" name="seccion" required onchange="cargarEstudiantes(this.value)">
                            <option value="">-- SELECCIONE SECCIÓN --</option>
                            <?php foreach ($secciones as $s): ?>
                                <option value="<?= $s['id_seccion'] ?>"><?= $s['nombre_seccion'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>👨‍🎓 ESTUDIANTE</label>
                        <select id="estudiante" name="id_estudiante" required>
                            <option value="">-- PRIMERO SELECCIONE SECCIÓN --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📚 MATERIA</label>
                        <select name="id_materia" required>
                            <option value="">-- SELECCIONE MATERIA --</option>
                            <?php foreach ($materias as $m): ?>
                                <option value="<?= $m['id_materia'] ?>"><?= $m['codigo_materia'] ?> - <?= $m['nombre_materia'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>👨‍🏫 DOCENTE</label>
                        <select name="id_docente" required>
                            <option value="">-- SELECCIONE DOCENTE --</option>
                            <?php foreach ($docentes as $d): ?>
                                <option value="<?= $d['id_docente'] ?>"><?= $d['apellidos'] ?>, <?= $d['nombres'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📝 TIPO DE EVALUACIÓN</label>
                        <select name="tipo" required>
                            <option value="parcial">📌 PARCIAL</option>
                            <option value="final">🎯 FINAL</option>
                            <option value="recuperacion">🔄 RECUPERACIÓN</option>
                            <option value="proyecto">📋 PROYECTO</option>
                            <option value="taller">🔧 TALLER</option>
                            <option value="laboratorio">🧪 LABORATORIO</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📅 PERÍODO</label>
                        <input type="text" name="periodo" value="2026-2027" required>
                    </div>
                    
                    <div class="form-group">
                        <label>🎯 NOTA (0-20)</label>
                        <input type="number" name="valor" min="0" max="20" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>📝 OBSERVACIONES</label>
                        <input type="text" name="observaciones" placeholder="Opcional">
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="registrar" style="width: auto; padding: 12px 30px;">
                        ✅ REGISTRAR CALIFICACIÓN
                    </button>
                </div>
            </form>
        </div>
        
        <!-- ========================================= -->
        <!-- FILTRO DE CALIFICACIONES POR SECCIÓN -->
        <!-- ========================================= -->
        <div class="card">
            <h2>🔍 CONSULTAR CALIFICACIONES</h2>
            <div class="filtros">
                <select id="filtro_seccion" onchange="window.location.href='?ver_seccion='+this.value+'&periodo='+document.getElementById('filtro_periodo').value">
                    <option value="">-- SELECCIONE SECCIÓN --</option>
                    <?php foreach ($secciones as $s): ?>
                        <option value="<?= $s['id_seccion'] ?>" <?= ($seccion_seleccionada == $s['id_seccion']) ? 'selected' : '' ?>>
                            <?= $s['nombre_seccion'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select id="filtro_periodo" onchange="window.location.href='?ver_seccion=<?= $seccion_seleccionada ?>&periodo='+this.value">
                    <option value="2026-2027" <?= ($periodo_seleccionado == '2026-2027') ? 'selected' : '' ?>>2026-2027</option>
                    <option value="2025-2026" <?= ($periodo_seleccionado == '2025-2026') ? 'selected' : '' ?>>2025-2026</option>
                    <option value="2024-2025" <?= ($periodo_seleccionado == '2024-2025') ? 'selected' : '' ?>>2024-2025</option>
                </select>
                
                <?php if ($seccion_seleccionada): ?>
                    <a href="?exportar=<?= $seccion_seleccionada ?>&periodo=<?= $periodo_seleccionado ?>" class="btn-info" style="text-decoration: none; padding: 12px; text-align: center;">
                        📥 EXPORTAR
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if ($seccion_seleccionada): ?>
                <h3 style="margin-top: 20px; color: #1a3e60;">
                    📋 CALIFICACIONES - SECCIÓN <?= htmlspecialchars($seccion_seleccionada) ?> - PERÍODO <?= $periodo_seleccionado ?>
                </h3>
                
                <?php if (empty($calificaciones)): ?>
                    <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; margin-top: 20px;">
                        <p style="font-size: 18px; color: #666;">📭 No hay calificaciones registradas para esta sección y período</p>
                    </div>
                <?php else: ?>
                    <div class="table-container" style="margin-top: 20px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>CÉDULA</th>
                                    <th>ESTUDIANTE</th>
                                    <th>MATERIA</th>
                                    <th>DOCENTE</th>
                                    <th>TIPO</th>
                                    <th>NOTA</th>
                                    <th>FECHA</th>
                                    <th>ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($calificaciones as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['cedula_estudiante']) ?></td>
                                    <td><?= htmlspecialchars($c['apellidos_estudiante'] . ', ' . $c['nombres_estudiante']) ?></td>
                                    <td><?= htmlspecialchars($c['codigo_materia']) ?><br><small><?= htmlspecialchars($c['nombre_materia']) ?></small></td>
                                    <td><?= htmlspecialchars($c['apellidos_docente']) ?>, <?= htmlspecialchars($c['nombres_docente']) ?></td>
                                    <td><span class="badge badge-info"><?= strtoupper($c['tipo_evaluacion']) ?></span></td>
                                    <td><span class="nota"><?= number_format($c['valor_calificacion'], 2) ?></span></td>
                                    <td><?= date('d/m/Y', strtotime($c['fecha_registro'])) ?></td>
                                    <td>
                                        <a href="?eliminar=<?= $c['id_calificacion'] ?>&ver_seccion=<?= $seccion_seleccionada ?>&periodo=<?= $periodo_seleccionado ?>" 
                                           class="btn-danger" 
                                           style="padding: 6px 12px; text-decoration: none; border-radius: 4px; color: white;"
                                           onclick="return confirm('¿ELIMINAR ESTA CALIFICACIÓN?')">🗑️ ELIMINAR</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Cargar estudiantes por sección vía AJAX
        function cargarEstudiantes(idSeccion) {
            const selectEstudiante = document.getElementById('estudiante');
            
            if (!idSeccion) {
                selectEstudiante.innerHTML = '<option value="">-- PRIMERO SELECCIONE SECCIÓN --</option>';
                return;
            }
            
            fetch('ajax_estudiantes.php?seccion=' + idSeccion)
                .then(response => response.json())
                .then(data => {
                    selectEstudiante.innerHTML = '<option value="">-- SELECCIONE ESTUDIANTE --</option>';
                    data.forEach(e => {
                        const option = document.createElement('option');
                        option.value = e.id_estudiante;
                        option.textContent = `${e.apellidos}, ${e.nombres} (${e.cedula})`;
                        selectEstudiante.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    selectEstudiante.innerHTML = '<option value="">-- ERROR AL CARGAR ESTUDIANTES --</option>';
                });
        }
    </script>
</body>
</html>