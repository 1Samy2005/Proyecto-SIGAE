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
// CRUD - AGREGAR DOCENTE
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $cedula = $_POST['cedula'];
    $nombres = strtoupper($_POST['nombres']);
    $apellidos = strtoupper($_POST['apellidos']);
    $especialidad = $_POST['especialidad'];
    $titulo = $_POST['titulo'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    
    $stmt = $db->prepare(
        "INSERT INTO docentes (cedula, nombres, apellidos, especialidad, titulo_academico, telefono, email, estatus) 
         VALUES (:cedula, :nombres, :apellidos, :especialidad, :titulo, :telefono, :email, 'activo')"
    );
    $stmt->execute([
        'cedula' => $cedula,
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'especialidad' => $especialidad,
        'titulo' => $titulo,
        'telefono' => $telefono,
        'email' => $email
    ]);
    header('Location: index.php?success=1');
    exit;
}

// =============================================
// CRUD - EDITAR DOCENTE
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $cedula = $_POST['cedula'];
    $nombres = strtoupper($_POST['nombres']);
    $apellidos = strtoupper($_POST['apellidos']);
    $especialidad = $_POST['especialidad'];
    $titulo = $_POST['titulo'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $estatus = $_POST['estatus'];
    
    $stmt = $db->prepare(
        "UPDATE docentes SET 
            cedula = :cedula, 
            nombres = :nombres, 
            apellidos = :apellidos, 
            especialidad = :especialidad, 
            titulo_academico = :titulo, 
            telefono = :telefono, 
            email = :email,
            estatus = :estatus
         WHERE id_docente = :id"
    );
    $stmt->execute([
        'id' => $id,
        'cedula' => $cedula,
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'especialidad' => $especialidad,
        'titulo' => $titulo,
        'telefono' => $telefono,
        'email' => $email,
        'estatus' => $estatus
    ]);
    header('Location: index.php?success=2');
    exit;
}

// =============================================
// CRUD - ELIMINAR DOCENTE
// =============================================
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $db->prepare("DELETE FROM docentes WHERE id_docente = :id");
    $stmt->execute(['id' => $id]);
    header('Location: index.php?success=3');
    exit;
}

// =============================================
// CRUD - ASIGNAR MATERIA/SECCIÓN
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar'])) {
    $id_docente = $_POST['id_docente'];
    $id_materia = $_POST['id_materia'];
    $id_seccion = $_POST['id_seccion'];
    $periodo = $_POST['periodo'];
    
    $stmt = $db->prepare(
        "INSERT INTO asignaciones (id_docente, id_materia, id_seccion, periodo_academico) 
         VALUES (:docente, :materia, :seccion, :periodo)
         ON CONFLICT (id_docente, id_materia, id_seccion, periodo_academico) DO NOTHING"
    );
    $stmt->execute([
        'docente' => $id_docente,
        'materia' => $id_materia,
        'seccion' => $id_seccion,
        'periodo' => $periodo
    ]);
    header('Location: index.php?success=4');
    exit;
}

// =============================================
// CRUD - ELIMINAR ASIGNACIÓN
// =============================================
if (isset($_GET['eliminar_asignacion'])) {
    $id_asignacion = $_GET['eliminar_asignacion'];
    $stmt = $db->prepare("DELETE FROM asignaciones WHERE id_asignacion = :id");
    $stmt->execute(['id' => $id_asignacion]);
    header('Location: index.php?success=5&ver_asignaciones=' . ($_GET['ver_asignaciones'] ?? ''));
    exit;
}

// =============================================
// OBTENER DATOS
// =============================================
$docentes = $db->query("SELECT * FROM docentes ORDER BY id_docente DESC")->fetchAll();

$materias = $db->query("SELECT * FROM materias WHERE estatus = 'activa' ORDER BY nombre_materia")->fetchAll();
$secciones = $db->query("SELECT * FROM secciones WHERE estatus = 'activa' ORDER BY nombre_seccion")->fetchAll();

// Obtener docente para editar
$editar_docente = null;
if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM docentes WHERE id_docente = :id");
    $stmt->execute(['id' => $_GET['editar']]);
    $editar_docente = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener asignaciones por docente
$asignaciones_por_docente = [];
if (isset($_GET['ver_asignaciones'])) {
    $id_docente = $_GET['ver_asignaciones'];
    $stmt = $db->prepare(
        "SELECT a.*, m.nombre_materia, s.nombre_seccion 
         FROM asignaciones a
         JOIN materias m ON a.id_materia = m.id_materia
         JOIN secciones s ON a.id_seccion = s.id_seccion
         WHERE a.id_docente = :docente
         ORDER BY a.periodo_academico DESC"
    );
    $stmt->execute(['docente' => $id_docente]);
    $asignaciones_por_docente = $stmt->fetchAll();
}

// =============================================
// PERMISOS SEGÚN ROL
// =============================================
$puede_gestionar = in_array($_SESSION['rol'], ['administrador', 'control_estudio']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Gestión de Docentes</title>
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
        .navbar h1 { font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .navbar a {
            color: #1a3e60;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.2s;
            font-weight: 500;
        }
        .navbar a:hover { background: #1a3e60; color: white; }
        .container { max-width: 1400px; margin: 0 auto; }
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        h2 {
            color: #1a3e60;
            margin-bottom: 20px;
            border-bottom: 3px solid #1a3e60;
            padding-bottom: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        input, select, button {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            width: 100%;
        }
        input:focus, select:focus {
            border-color: #667eea;
            outline: none;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover { transform: translateY(-2px); }
        
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
        tr:hover { background: #f8f9fa; }
        .badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-inactive { background: #dc3545; }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
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
        .asignaciones-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>👨‍🏫 SIGAE - Gestión de Docentes</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?> (<?= $_SESSION['rol'] ?>)</span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">
                <?php
                $msgs = [
                    1 => '✅ Docente agregado correctamente',
                    2 => '✅ Docente actualizado correctamente',
                    3 => '✅ Docente eliminado correctamente',
                    4 => '✅ Asignación realizada correctamente',
                    5 => '✅ Asignación eliminada correctamente'
                ];
                echo $msgs[$_GET['success']] ?? 'Operación exitosa';
                ?>
            </div>
        <?php endif; ?>
        
        <!-- ========================================= -->
        <!-- FORMULARIO AGREGAR/EDITAR DOCENTE -->
        <!-- ========================================= -->
        <div class="card">
            <h2><?= $editar_docente ? '✏️ EDITAR DOCENTE' : '➕ AGREGAR NUEVO DOCENTE' ?></h2>
            <form method="POST">
                <?php if ($editar_docente): ?>
                    <input type="hidden" name="id" value="<?= $editar_docente['id_docente'] ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <input type="text" name="cedula" placeholder="📌 Cédula" 
                           value="<?= htmlspecialchars($editar_docente['cedula'] ?? '') ?>" required>
                    <input type="text" name="nombres" placeholder="👤 Nombres" 
                           value="<?= htmlspecialchars($editar_docente['nombres'] ?? '') ?>" required>
                    <input type="text" name="apellidos" placeholder="👤 Apellidos" 
                           value="<?= htmlspecialchars($editar_docente['apellidos'] ?? '') ?>" required>
                    <input type="text" name="especialidad" placeholder="📚 Especialidad" 
                           value="<?= htmlspecialchars($editar_docente['especialidad'] ?? '') ?>" required>
                    <input type="text" name="titulo" placeholder="🎓 Título Académico" 
                           value="<?= htmlspecialchars($editar_docente['titulo_academico'] ?? '') ?>" required>
                    <input type="text" name="telefono" placeholder="📞 Teléfono" 
                           value="<?= htmlspecialchars($editar_docente['telefono'] ?? '') ?>">
                    <input type="email" name="email" placeholder="📧 Email" 
                           value="<?= htmlspecialchars($editar_docente['email'] ?? '') ?>">
                    <?php if ($editar_docente): ?>
                        <select name="estatus">
                            <option value="activo" <?= $editar_docente['estatus'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= $editar_docente['estatus'] == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 15px;">
                    <?php if ($editar_docente): ?>
                        <button type="submit" name="editar">✏️ ACTUALIZAR DOCENTE</button>
                        <a href="index.php"><button type="button" style="background: #6c757d;">❌ CANCELAR</button></a>
                    <?php else: ?>
                        <button type="submit" name="agregar">➕ GUARDAR DOCENTE</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- ========================================= -->
        <!-- LISTA DE DOCENTES -->
        <!-- ========================================= -->
        <div class="card">
            <h2>📋 LISTA DE DOCENTES</h2>
            <table>
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Especialidad</th>
                        <th>Título</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($docentes)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 30px;">📭 No hay docentes registrados</td></tr>
                    <?php else: ?>
                        <?php foreach ($docentes as $d): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['cedula']) ?></strong></td>
                            <td><?= htmlspecialchars($d['nombres']) ?></td>
                            <td><?= htmlspecialchars($d['apellidos']) ?></td>
                            <td><?= htmlspecialchars($d['especialidad']) ?></td>
                            <td><?= htmlspecialchars($d['titulo_academico']) ?></td>
                            <td>
                                <?= htmlspecialchars($d['telefono']) ?><br>
                                <small><?= htmlspecialchars($d['email']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $d['estatus'] == 'inactivo' ? 'badge-inactive' : '' ?>">
                                    <?= strtoupper($d['estatus']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($puede_gestionar): ?>
                                    <!-- Editar -->
                                    <a href="?editar=<?= $d['id_docente'] ?>" class="btn-icon" title="Editar docente">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <!-- Asignar materias -->
                                    <a href="?ver_asignaciones=<?= $d['id_docente'] ?>" class="btn-icon" title="Asignar materias">
                                        <i class="fas fa-tasks"></i>
                                    </a>
                                    <!-- Eliminar (con tooltip Modificar docente) -->
                                    <a href="?eliminar=<?= $d['id_docente'] ?>" class="btn-icon" 
                                       title="Modificar docente" 
                                       onclick="return confirm('⚠️ ¿ELIMINAR DOCENTE?\n\n<?= htmlspecialchars($d['nombres'] . ' ' . $d['apellidos']) ?>\n\nEsta acción no se puede deshacer.')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- ========================================= -->
        <!-- FORMULARIO DE ASIGNACIÓN DE MATERIAS/SECCIONES -->
        <!-- ========================================= -->
        <?php if (isset($_GET['ver_asignaciones']) && $puede_gestionar): 
            $id_docente = $_GET['ver_asignaciones'];
            $docente = $db->prepare("SELECT * FROM docentes WHERE id_docente = :id");
            $docente->execute(['id' => $id_docente]);
            $docente = $docente->fetch(PDO::FETCH_ASSOC);
            if ($docente):
        ?>
        <div class="card">
            <h2>📌 ASIGNAR MATERIAS Y SECCIONES - <?= htmlspecialchars($docente['nombres'] . ' ' . $docente['apellidos']) ?></h2>
            
            <form method="POST" style="margin-bottom: 30px;">
                <input type="hidden" name="id_docente" value="<?= $id_docente ?>">
                
                <div class="form-grid">
                    <select name="id_materia" required>
                        <option value="">🔽 SELECCIONE MATERIA</option>
                        <?php foreach ($materias as $m): ?>
                            <option value="<?= $m['id_materia'] ?>"><?= $m['codigo_materia'] ?> - <?= $m['nombre_materia'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="id_seccion" required>
                        <option value="">🔽 SELECCIONE SECCIÓN</option>
                        <?php foreach ($secciones as $s): ?>
                            <option value="<?= $s['id_seccion'] ?>"><?= $s['nombre_seccion'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="text" name="periodo" placeholder="📅 Período (ej: 2026-2027)" 
                           value="2026-2027" required>
                    
                    <button type="submit" name="asignar" style="background: #17a2b8;">➕ ASIGNAR</button>
                    <a href="index.php" style="text-decoration: none;">
                        <button type="button" style="background: #6c757d;">❌ CERRAR</button>
                    </a>
                </div>
            </form>
            
            <?php if (!empty($asignaciones_por_docente)): ?>
                <h3>📋 ASIGNACIONES ACTUALES</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Sección</th>
                            <th>Período</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asignaciones_por_docente as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['nombre_materia']) ?></td>
                            <td><?= htmlspecialchars($a['nombre_seccion']) ?></td>
                            <td><?= htmlspecialchars($a['periodo_academico']) ?></td>
                            <td>
                                <a href="?eliminar_asignacion=<?= $a['id_asignacion'] ?>&ver_asignaciones=<?= $id_docente ?>" 
                                   class="btn-icon" 
                                   title="Eliminar asignación"
                                   onclick="return confirm('¿QUITAR ASIGNACIÓN?')">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                    📭 No hay asignaciones para este docente
                </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ============================================= -->
    <!-- TOOLTIPS PERSONALIZADOS CON JAVASCRIPT -->
    <!-- ============================================= -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const iconos = document.querySelectorAll('.btn-icon');
            iconos.forEach(icono => {
                icono.addEventListener('mouseenter', function(e) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip-personalizado';
                    tooltip.textContent = this.getAttribute('title');
                    document.body.appendChild(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
                    
                    this.addEventListener('mouseleave', function() {
                        tooltip.remove();
                    }, { once: true });
                });
            });
        });
    </script>
</body>
</html>