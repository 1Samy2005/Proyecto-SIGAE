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
// AGREGAR ESTUDIANTE
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $cedula = $_POST['cedula'];
    $nombres = strtoupper($_POST['nombres']);
    $apellidos = strtoupper($_POST['apellidos']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $lugar_nacimiento = $_POST['lugar_nacimiento'];
    $pais_nacimiento = $_POST['pais_nacimiento'];
    $estado_nacimiento = $_POST['estado_nacimiento'];
    $municipio_nacimiento = $_POST['municipio_nacimiento'];
    $id_seccion = $_POST['id_seccion'];
    
    try {
        $db->beginTransaction();
        
        // Insertar estudiante
        $stmt = $db->query(
            "INSERT INTO estudiantes 
            (cedula, nombres, apellidos, fecha_nacimiento, lugar_nacimiento, 
             pais_nacimiento, estado_nacimiento, municipio_nacimiento, estatus_academico) 
            VALUES 
            (:cedula, :nombres, :apellidos, :fecha, :lugar, :pais, :estado, :municipio, 'activo') 
            RETURNING id_estudiante",
            [
                'cedula' => $cedula,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'fecha' => $fecha_nacimiento,
                'lugar' => $lugar_nacimiento,
                'pais' => $pais_nacimiento,
                'estado' => $estado_nacimiento,
                'municipio' => $municipio_nacimiento
            ]
        );
        $id_estudiante = $stmt->fetch()['id_estudiante'];
        
        // Crear inscripción
        $db->query(
            "INSERT INTO inscripciones (id_estudiante, id_seccion, anio_escolar, estatus_inscripcion) 
             VALUES (:estudiante, :seccion, '2026-2027', 'activo')",
            ['estudiante' => $id_estudiante, 'seccion' => $id_seccion]
        );
        
        $db->commit();
        header('Location: index.php?success=1');
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "❌ Error: " . $e->getMessage();
    }
}

// =============================================
// EDITAR ESTUDIANTE (VERSIÓN CORREGIDA)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $cedula = $_POST['cedula'];
    $nombres = strtoupper($_POST['nombres']);
    $apellidos = strtoupper($_POST['apellidos']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $lugar_nacimiento = $_POST['lugar_nacimiento'];
    $pais_nacimiento = $_POST['pais_nacimiento'];
    $estado_nacimiento = $_POST['estado_nacimiento'];
    $municipio_nacimiento = $_POST['municipio_nacimiento'];
    $id_seccion = $_POST['id_seccion'];
    $estatus = $_POST['estatus'];
    
    try {
        $db->beginTransaction();
        
        // 1. Actualizar datos del estudiante
        $db->query(
            "UPDATE estudiantes SET 
             cedula = :cedula, nombres = :nombres, apellidos = :apellidos,
             fecha_nacimiento = :fecha, lugar_nacimiento = :lugar,
             pais_nacimiento = :pais, estado_nacimiento = :estado, 
             municipio_nacimiento = :municipio, estatus_academico = :estatus
             WHERE id_estudiante = :id",
            [
                'cedula' => $cedula, 'nombres' => $nombres, 'apellidos' => $apellidos,
                'fecha' => $fecha_nacimiento, 'lugar' => $lugar_nacimiento,
                'pais' => $pais_nacimiento, 'estado' => $estado_nacimiento,
                'municipio' => $municipio_nacimiento, 'estatus' => $estatus, 'id' => $id
            ]
        );
        
        // 2. Actualizar la inscripción
        if (!empty($id_seccion)) {
            // Verificar si ya tiene inscripción para el año actual
            $inscripcion_actual = $db->query(
                "SELECT id_inscripcion FROM inscripciones 
                 WHERE id_estudiante = :estudiante AND anio_escolar = '2026-2027'",
                ['estudiante' => $id]
            )->fetch();
            
            if ($inscripcion_actual) {
                // Actualizar inscripción existente
                $db->query(
                    "UPDATE inscripciones SET id_seccion = :seccion 
                     WHERE id_inscripcion = :id",
                    ['seccion' => $id_seccion, 'id' => $inscripcion_actual['id_inscripcion']]
                );
            } else {
                // Crear nueva inscripción
                $db->query(
                    "INSERT INTO inscripciones (id_estudiante, id_seccion, anio_escolar, estatus_inscripcion) 
                     VALUES (:estudiante, :seccion, '2026-2027', 'activo')",
                    ['estudiante' => $id, 'seccion' => $id_seccion]
                );
            }
        }
        
        $db->commit();
        header('Location: index.php?success=2');
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "❌ Error al actualizar: " . $e->getMessage();
    }
}

// =============================================
// ELIMINAR ESTUDIANTE
// =============================================
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $db->query("DELETE FROM estudiantes WHERE id_estudiante = :id", ['id' => $id]);
    header('Location: index.php?success=3');
    exit;
}

// =============================================
// OBTENER DATOS (VERSIÓN CORREGIDA)
// =============================================
$estudiantes = $db->query("
    SELECT e.*, s.nombre_seccion 
    FROM estudiantes e
    LEFT JOIN inscripciones i ON e.id_estudiante = i.id_estudiante 
                              AND i.anio_escolar = '2026-2027'
    LEFT JOIN secciones s ON i.id_seccion = s.id_seccion
    ORDER BY e.id_estudiante DESC
")->fetchAll();

$secciones = $db->query("SELECT * FROM secciones ORDER BY nombre_seccion")->fetchAll();

// Obtener estudiante para editar (con su sección actual)
$editar_estudiante = null;
if (isset($_GET['editar'])) {
    $stmt = $db->query("
        SELECT e.*, i.id_seccion 
        FROM estudiantes e
        LEFT JOIN inscripciones i ON e.id_estudiante = i.id_estudiante 
                                  AND i.anio_escolar = '2026-2027'
        WHERE e.id_estudiante = :id
    ", ['id' => $_GET['editar']]);
    $editar_estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Gestión de Estudiantes</title>
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
            margin-bottom: 30px;
        }
        h2 {
            color: #1a3e60;
            margin-bottom: 20px;
            font-size: 22px;
            border-bottom: 3px solid #1a3e60;
            padding-bottom: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .form-section h3 {
            color: #1a3e60;
            margin-bottom: 15px;
            font-size: 16px;
            border-left: 4px solid #1a3e60;
            padding-left: 10px;
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
        tr:hover { background: #f5f5f5; }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            margin-right: 5px;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
        }
        .btn-history {
            background: #17a2b8;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>👥 SIGAE - Gestión de Estudiantes</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?> (<?= $_SESSION['rol'] ?>)</span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                $msgs = [
                    1 => '✅ Estudiante agregado correctamente',
                    2 => '✅ Estudiante actualizado correctamente',
                    3 => '✅ Estudiante eliminado correctamente'
                ];
                echo $msgs[$_GET['success']] ?? 'Operación exitosa';
                ?>
            </div>
        <?php endif; ?>
        
        <!-- FORMULARIO AGREGAR/EDITAR ESTUDIANTE -->
        <div class="card">
            <h2><?= $editar_estudiante ? '✏️ EDITAR ESTUDIANTE' : '➕ AGREGAR NUEVO ESTUDIANTE' ?></h2>
            <form method="POST">
                <?php if ($editar_estudiante): ?>
                    <input type="hidden" name="id" value="<?= $editar_estudiante['id_estudiante'] ?>">
                <?php endif; ?>
                
                <!-- DATOS PERSONALES -->
                <div class="form-section">
                    <h3>📋 DATOS PERSONALES</h3>
                    <div class="form-grid">
                        <input type="text" name="cedula" placeholder="Cédula" 
                               value="<?= $editar_estudiante['cedula'] ?? '' ?>" required>
                        
                        <input type="text" name="nombres" placeholder="Nombres" 
                               value="<?= $editar_estudiante['nombres'] ?? '' ?>" required>
                        
                        <input type="text" name="apellidos" placeholder="Apellidos" 
                               value="<?= $editar_estudiante['apellidos'] ?? '' ?>" required>
                        
                        <input type="date" name="fecha_nacimiento" placeholder="Fecha de nacimiento" 
                               value="<?= $editar_estudiante['fecha_nacimiento'] ?? '' ?>" required>
                    </div>
                </div>
                
                <!-- LUGAR DE NACIMIENTO -->
                <div class="form-section">
                    <h3>📍 LUGAR DE NACIMIENTO</h3>
                    <div class="form-grid">
                        <input type="text" name="lugar_nacimiento" placeholder="Ciudad / Localidad" 
                               value="<?= $editar_estudiante['lugar_nacimiento'] ?? '' ?>" required>
                        
                        <input type="text" name="pais_nacimiento" placeholder="País" 
                               value="<?= $editar_estudiante['pais_nacimiento'] ?? 'Venezuela' ?>" required>
                        
                        <input type="text" name="estado_nacimiento" placeholder="Estado / Provincia" 
                               value="<?= $editar_estudiante['estado_nacimiento'] ?? '' ?>" required>
                        
                        <input type="text" name="municipio_nacimiento" placeholder="Municipio / Distrito" 
                               value="<?= $editar_estudiante['municipio_nacimiento'] ?? '' ?>" required>
                    </div>
                </div>
                
                <!-- ASIGNACIÓN ACADÉMICA -->
                <div class="form-section">
                    <h3>📚 ASIGNACIÓN ACADÉMICA</h3>
                    <div class="form-grid">
                        <select name="id_seccion">
                            <option value="">Seleccione Sección (opcional)</option>
                            <?php foreach ($secciones as $s): ?>
                                <option value="<?= $s['id_seccion'] ?>" 
                                    <?= ($editar_estudiante && $editar_estudiante['id_seccion'] == $s['id_seccion']) ? 'selected' : '' ?>>
                                    <?= $s['nombre_seccion'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <?php if ($editar_estudiante): ?>
                            <select name="estatus">
                                <option value="activo" <?= $editar_estudiante['estatus_academico'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= $editar_estudiante['estatus_academico'] == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                <option value="graduado" <?= $editar_estudiante['estatus_academico'] == 'graduado' ? 'selected' : '' ?>>Graduado</option>
                                <option value="retirado" <?= $editar_estudiante['estatus_academico'] == 'retirado' ? 'selected' : '' ?>>Retirado</option>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <?php if ($editar_estudiante): ?>
                        <button type="submit" name="editar">✏️ ACTUALIZAR ESTUDIANTE</button>
                        <a href="index.php"><button type="button">❌ CANCELAR</button></a>
                    <?php else: ?>
                        <button type="submit" name="agregar">➕ GUARDAR ESTUDIANTE</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- LISTA DE ESTUDIANTES -->
        <div class="card">
            <h2>📋 LISTA DE ESTUDIANTES</h2>
            <table>
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Fecha Nac.</th>
                        <th>Lugar Nacimiento</th>
                        <th>Sección</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estudiantes as $e): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($e['cedula']) ?></strong></td>
                        <td><?= htmlspecialchars($e['nombres']) ?></td>
                        <td><?= htmlspecialchars($e['apellidos']) ?></td>
                        <td><?= $e['fecha_nacimiento'] ? date('d/m/Y', strtotime($e['fecha_nacimiento'])) : 'N/D' ?></td>
                        <td>
                            <?= htmlspecialchars($e['lugar_nacimiento'] ?? '') ?><br>
                            <small><?= $e['pais_nacimiento'] ?? '' ?> / <?= $e['estado_nacimiento'] ?? '' ?></small>
                        </td>
                        <td><?= htmlspecialchars($e['nombre_seccion'] ?? 'Sin asignar') ?></td>
                        <td>
                            <span style="color: <?= $e['estatus_academico'] == 'activo' ? 'green' : 'red' ?>;">
                                <?= strtoupper($e['estatus_academico'] ?? 'activo') ?>
                            </span>
                        </td>
                        <td>
                            <a href="?editar=<?= $e['id_estudiante'] ?>" class="btn-edit">✏️ Editar</a>
                            <a href="?eliminar=<?= $e['id_estudiante'] ?>" class="btn-delete" 
                               onclick="return confirm('¿Eliminar estudiante?')">🗑️ Eliminar</a>
                            <a href="../historia/index.php?id=<?= $e['id_estudiante'] ?>" class="btn-history">📚 Historia</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>