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
    
    $db->query(
        "INSERT INTO docentes (cedula, nombres, apellidos, especialidad, titulo_academico, telefono, email, estatus) 
         VALUES (:cedula, :nombres, :apellidos, :especialidad, :titulo, :telefono, :email, 'activo')",
        [
            'cedula' => $cedula,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'especialidad' => $especialidad,
            'titulo' => $titulo,
            'telefono' => $telefono,
            'email' => $email
        ]
    );
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
    
    $db->query(
        "UPDATE docentes SET 
            cedula = :cedula, 
            nombres = :nombres, 
            apellidos = :apellidos, 
            especialidad = :especialidad, 
            titulo_academico = :titulo, 
            telefono = :telefono, 
            email = :email,
            estatus = :estatus
         WHERE id_docente = :id",
        [
            'id' => $id,
            'cedula' => $cedula,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'especialidad' => $especialidad,
            'titulo' => $titulo,
            'telefono' => $telefono,
            'email' => $email,
            'estatus' => $estatus
        ]
    );
    header('Location: index.php?success=2');
    exit;
}

// =============================================
// CRUD - ELIMINAR DOCENTE
// =============================================
if (isset($_GET['eliminar'])) {
    $db->query("DELETE FROM docentes WHERE id_docente = :id", ['id' => $_GET['eliminar']]);
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
    
    $db->query(
        "INSERT INTO asignaciones (id_docente, id_materia, id_seccion, periodo_academico) 
         VALUES (:docente, :materia, :seccion, :periodo)
         ON CONFLICT (id_docente, id_materia, id_seccion, periodo_academico) DO NOTHING",
        [
            'docente' => $id_docente,
            'materia' => $id_materia,
            'seccion' => $id_seccion,
            'periodo' => $periodo
        ]
    );
    header('Location: index.php?success=4');
    exit;
}

// =============================================
// CRUD - ELIMINAR ASIGNACIÓN
// =============================================
if (isset($_GET['eliminar_asignacion'])) {
    $db->query("DELETE FROM asignaciones WHERE id_asignacion = :id", ['id' => $_GET['eliminar_asignacion']]);
    header('Location: index.php?success=5');
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
    $stmt = $db->query("SELECT * FROM docentes WHERE id_docente = :id", ['id' => $_GET['editar']]);
    $editar_docente = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener asignaciones por docente
$asignaciones_por_docente = [];
if (isset($_GET['ver_asignaciones'])) {
    $id_docente = $_GET['ver_asignaciones'];
    $asignaciones_por_docente = $db->query(
        "SELECT a.*, m.nombre_materia, s.nombre_seccion 
         FROM asignaciones a
         JOIN materias m ON a.id_materia = m.id_materia
         JOIN secciones s ON a.id_seccion = s.id_seccion
         WHERE a.id_docente = :docente
         ORDER BY a.periodo_academico DESC",
        ['docente' => $id_docente]
    )->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Gestión de Docentes</title>
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
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
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
        .btn-cancel {
            background: #6c757d;
        }
        .btn-cancel:hover { background: #5a6268; }
        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 6px 14px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
            font-weight: bold;
        }
        .btn-edit:hover { background: #e0a800; }
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 6px 14px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            font-weight: bold;
        }
        .btn-delete:hover { background: #c82333; }
        .btn-assign {
            background: #17a2b8;
            color: white;
            padding: 6px 14px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            font-weight: bold;
        }
        .btn-assign:hover { background: #138496; }
        .btn-view {
            background: #6c757d;
            color: white;
            padding: 6px 14px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }
        .btn-view:hover { background: #5a6268; }
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
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-inactive {
            background: #dc3545;
        }
        .stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
        }
        .total {
            background: #1a3e60;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
        }
        .asignaciones-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>👨‍🏫 SIGAE - Gestión de Docentes</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
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
                           value="<?= $editar_docente['cedula'] ?? '' ?>" required>
                    
                    <input type="text" name="nombres" placeholder="👤 Nombres" 
                           value="<?= $editar_docente['nombres'] ?? '' ?>" required>
                    
                    <input type="text" name="apellidos" placeholder="👤 Apellidos" 
                           value="<?= $editar_docente['apellidos'] ?? '' ?>" required>
                    
                    <input type="text" name="especialidad" placeholder="📚 Especialidad" 
                           value="<?= $editar_docente['especialidad'] ?? '' ?>" required>
                    
                    <input type="text" name="titulo" placeholder="🎓 Título Académico" 
                           value="<?= $editar_docente['titulo_academico'] ?? '' ?>" required>
                    
                    <input type="text" name="telefono" placeholder="📞 Teléfono" 
                           value="<?= $editar_docente['telefono'] ?? '' ?>">
                    
                    <input type="email" name="email" placeholder="📧 Email" 
                           value="<?= $editar_docente['email'] ?? '' ?>">
                    
                    <?php if ($editar_docente): ?>
                        <select name="estatus">
                            <option value="activo" <?= $editar_docente['estatus'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= $editar_docente['estatus'] == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <?php if ($editar_docente): ?>
                        <button type="submit" name="editar" style="width: auto; padding: 12px 30px;">✏️ ACTUALIZAR DOCENTE</button>
                        <a href="index.php" style="text-decoration: none;">
                            <button type="button" class="btn-cancel" style="width: auto; padding: 12px 30px;">❌ CANCELAR</button>
                        </a>
                    <?php else: ?>
                        <button type="submit" name="agregar" style="width: auto; padding: 12px 30px;">➕ GUARDAR DOCENTE</button>
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
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px;">
                                🚫 No hay docentes registrados
                            </td>
                        </tr>
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
                                <a href="?editar=<?= $d['id_docente'] ?>" class="btn-edit">✏️ EDITAR</a>
                                <a href="?ver_asignaciones=<?= $d['id_docente'] ?>" class="btn-assign">📌 ASIGNAR</a>
                                <a href="?eliminar=<?= $d['id_docente'] ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('⚠️ ¿ELIMINAR DOCENTE?\n\n<?= $d['nombres'] ?> <?= $d['apellidos'] ?>\n\nEsta acción no se puede deshacer.')">🗑️ ELIMINAR</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="stats">
                <span style="font-size: 16px; color: #1a3e60;">📊 Total docentes activos:</span>
                <span class="total"><?= count($docentes) ?></span>
            </div>
        </div>
        
        <!-- ========================================= -->
        <!-- FORMULARIO DE ASIGNACIÓN DE MATERIAS/SECCIONES -->
        <!-- ========================================= -->
        <?php if (isset($_GET['ver_asignaciones'])): 
            $id_docente = $_GET['ver_asignaciones'];
            $docente = $db->query("SELECT * FROM docentes WHERE id_docente = :id", ['id' => $id_docente])->fetch();
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
                        <button type="button" class="btn-cancel">❌ CERRAR</button>
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
                                   class="btn-delete"
                                   onclick="return confirm('¿ELIMINAR ASIGNACIÓN?')">🗑️ QUITAR</a>
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
        
    </div>
</body>
</html>