<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Incluir funciones de permisos
require_once __DIR__ . '/../../helpers/functions.php';

// Verificar permisos: solo quienes pueden gestionar o registrar calificaciones
requerir_permiso(['gestion_calificaciones', 'registrar_calificaciones', '*']);

// Resto del código
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;
require_once __DIR__ . '/../../helpers/AuditLogger.php';

$db = Database::getInstance();
$audit = new AuditLogger();

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
        // Obtener el id_inscripcion del estudiante para el período actual
        $stmt_insc = $db->prepare(
            "SELECT id_inscripcion FROM inscripciones 
             WHERE id_estudiante = :estudiante AND anio_escolar = :periodo"
        );
        $stmt_insc->execute([
            'estudiante' => $id_estudiante,
            'periodo' => $periodo
        ]);
        $inscripcion = $stmt_insc->fetch(PDO::FETCH_ASSOC);
        
        if (!$inscripcion) {
            throw new Exception("El estudiante no está inscrito en este período");
        }
        
        // Insertar calificación con RETURNING para obtener el ID
        $stmt_insert = $db->prepare(
            "INSERT INTO calificaciones 
            (id_estudiante, id_materia, id_docente, valor_calificacion, tipo_evaluacion, 
             periodo_academico, observaciones, id_usuario_registro, id_inscripcion) 
            VALUES 
            (:estudiante, :materia, :docente, :valor, :tipo, :periodo, :obs, :usuario, :inscripcion)
            RETURNING id_calificacion"
        );
        $stmt_insert->execute([
            'estudiante' => $id_estudiante,
            'materia' => $id_materia,
            'docente' => $id_docente,
            'valor' => $valor,
            'tipo' => $tipo,
            'periodo' => $periodo,
            'obs' => $observaciones,
            'usuario' => $_SESSION['user_id'],
            'inscripcion' => $inscripcion['id_inscripcion']
        ]);
        $nuevo_id = $stmt_insert->fetchColumn();
        
        // Auditoría de inserción
        $datos_nuevos = [
            'id_estudiante' => $id_estudiante,
            'id_materia' => $id_materia,
            'id_docente' => $id_docente,
            'valor' => $valor,
            'tipo' => $tipo,
            'periodo' => $periodo,
            'observaciones' => $observaciones
        ];
        $audit->logInsert('calificaciones', $nuevo_id, $datos_nuevos);
        
        $success = "✅ Calificación registrada exitosamente";
    } catch (Exception $e) {
        $error = "❌ Error al registrar: " . $e->getMessage();
    }
}

// =============================================
// ELIMINAR CALIFICACIÓN
// =============================================
if (isset($_GET['eliminar'])) {
    $id_calificacion = $_GET['eliminar'];
    
    try {
        // Obtener datos antes de eliminar para auditoría
        $stmt_sel = $db->prepare("SELECT * FROM calificaciones WHERE id_calificacion = :id");
        $stmt_sel->execute(['id' => $id_calificacion]);
        $calificacion = $stmt_sel->fetch(PDO::FETCH_ASSOC);
        
        if ($calificacion) {
            // Eliminar la calificación
            $stmt_del = $db->prepare("DELETE FROM calificaciones WHERE id_calificacion = :id");
            $stmt_del->execute(['id' => $id_calificacion]);
            
            // Auditoría de eliminación
            $audit->logDelete('calificaciones', $id_calificacion, $calificacion);
            
            header('Location: index.php?success=eliminado&ver_seccion=' . ($_GET['ver_seccion'] ?? '') . '&periodo=' . ($_GET['periodo'] ?? ''));
            exit;
        } else {
            header('Location: index.php?error=noexiste');
            exit;
        }
    } catch (Exception $e) {
        $error = "❌ Error al eliminar: " . $e->getMessage();
    }
}

// =============================================
// OBTENER DATOS PARA LOS SELECTS
// =============================================
$stmt_secciones = $db->query("SELECT * FROM secciones WHERE estatus = 'activa' ORDER BY nombre_seccion");
$secciones = $stmt_secciones->fetchAll();

$stmt_materias = $db->query("SELECT * FROM materias WHERE estatus = 'activa' ORDER BY nombre_materia");
$materias = $stmt_materias->fetchAll();

$stmt_docentes = $db->query("SELECT * FROM docentes ORDER BY apellidos, nombres");
$docentes = $stmt_docentes->fetchAll();

// =============================================
// OBTENER CALIFICACIONES POR SECCIÓN
// =============================================
$calificaciones = [];
$seccion_seleccionada = $_GET['ver_seccion'] ?? null;
$periodo_seleccionado = $_GET['periodo'] ?? '2026-2027';

if ($seccion_seleccionada) {
    $stmt_cal = $db->prepare(
        "SELECT 
            c.id_calificacion,
            c.valor_calificacion,
            c.tipo_evaluacion,
            c.periodo_academico,
            c.fecha_registro,
            e.id_estudiante,
            e.cedula as cedula_estudiante,
            e.nombres as nombres_estudiante,
            e.apellidos as apellidos_estudiante,
            m.codigo_materia,
            m.nombre_materia,
            d.nombres as nombres_docente,
            d.apellidos as apellidos_docente,
            s.nombre_seccion
         FROM calificaciones c
         JOIN estudiantes e ON c.id_estudiante = e.id_estudiante
         JOIN materias m ON c.id_materia = m.id_materia
         JOIN docentes d ON c.id_docente = d.id_docente
         JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
         JOIN secciones s ON i.id_seccion = s.id_seccion
         WHERE i.id_seccion = :seccion 
           AND i.anio_escolar = :periodo
         ORDER BY e.apellidos, e.nombres, m.nombre_materia"
    );
    $stmt_cal->execute([
        'seccion' => $seccion_seleccionada,
        'periodo' => $periodo_seleccionado
    ]);
    $calificaciones = $stmt_cal->fetchAll();
}

// =============================================
// PERMISOS SEGÚN ROL
// =============================================
$puede_registrar = in_array($_SESSION['rol'], ['administrador', 'control_estudio', 'docente']);
$puede_eliminar = in_array($_SESSION['rol'], ['administrador', 'control_estudio']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Calificaciones</title>
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .navbar h1 { font-size: 24px; }
        .navbar a {
            color: #1a3e60;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 5px;
        }
        .navbar a:hover { background: #1a3e60; color: white; }
        .container { max-width: 1400px; margin: 0 auto; }
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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
        .btn-icon {
            text-decoration: none;
            margin: 0 8px;
            color: #1a3e60;
            font-size: 20px;
            transition: color 0.2s, transform 0.2s;
            display: inline-block;
        }
        .btn-icon:hover {
            color: #667eea;
            transform: scale(1.1);
        }
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
        .tooltip-personalizado {
            position: absolute;
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            pointer-events: none;
            z-index: 1000;
            opacity: 0.9;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📊 SIGAE - Gestión de Calificaciones</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?> (<?= $_SESSION['rol'] ?>)</span>
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
        <!-- REGISTRO DE CALIFICACIONES (Solo para admin, control y docente) -->
        <!-- ========================================= -->
        <?php if ($puede_registrar): ?>
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
        <?php endif; ?>
        
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
                    <a href="?exportar=<?= $seccion_seleccionada ?>&periodo=<?= $periodo_seleccionado ?>" class="btn-info" style="background: #17a2b8; color: white; text-decoration: none; padding: 12px; text-align: center; border-radius: 5px;">
                        📥 EXPORTAR
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if ($seccion_seleccionada): ?>
                <h3 style="margin-top: 20px; color: #1a3e60;">
                    📋 CALIFICACIONES - PERÍODO <?= $periodo_seleccionado ?>
                </h3>
                
                <?php if (empty($calificaciones)): ?>
                    <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; margin-top: 20px;">
                        <p style="font-size: 18px; color: #666;">📭 No hay calificaciones registradas para esta sección y período</p>
                    </div>
                <?php else: ?>
                    <div class="table-container" style="margin-top: 20px; overflow-x: auto;">
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
                                        <?php if ($puede_eliminar): ?>
                                            <a href="?eliminar=<?= $c['id_calificacion'] ?>&ver_seccion=<?= $seccion_seleccionada ?>&periodo=<?= $periodo_seleccionado ?>" 
                                               class="btn-icon eliminar-icon" 
                                               title="Modificar calificación"
                                               onclick="return confirm('¿ELIMINAR ESTA CALIFICACIÓN?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
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
            
            selectEstudiante.innerHTML = '<option value="">Cargando estudiantes...</option>';
            
            fetch('ajax_estudiantes.php?seccion=' + idSeccion)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    selectEstudiante.innerHTML = '';
                    
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = '-- SELECCIONE ESTUDIANTE --';
                    selectEstudiante.appendChild(defaultOption);
                    
                    data.forEach(est => {
                        const option = document.createElement('option');
                        option.value = est.id_estudiante;
                        option.textContent = `${est.apellidos}, ${est.nombres} (${est.cedula})`;
                        selectEstudiante.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    selectEstudiante.innerHTML = '<option value="">-- ERROR AL CARGAR ESTUDIANTES --</option>';
                });
        }

        // =============================================
        // TOOLTIPS PERSONALIZADOS CON JAVASCRIPT
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const iconos = document.querySelectorAll('.btn-icon');
            iconos.forEach(icono => {
                icono.addEventListener('mouseenter', function(e) {
                    // Crear tooltip
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip-personalizado';
                    tooltip.textContent = this.getAttribute('title');
                    document.body.appendChild(tooltip);
                    
                    // Posicionar tooltip
                    const rect = this.getBoundingClientRect();
                    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
                    
                    // Remover tooltip al salir
                    this.addEventListener('mouseleave', function() {
                        tooltip.remove();
                    }, { once: true });
                });
            });
        });
    </script>
</body>
</html>