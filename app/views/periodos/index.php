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
// AGREGAR PERÍODO
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $nombre_periodo = trim($_POST['nombre_periodo']);
    $codigo_periodo = trim($_POST['codigo_periodo']);
    $anio_escolar = trim($_POST['anio_escolar']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $ponderacion = floatval($_POST['ponderacion']);
    $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

    // Validar que fecha_fin no sea anterior a fecha_inicio
    if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
        $error = "❌ La fecha de fin no puede ser anterior a la fecha de inicio.";
    } else {
        $stmt = $db->prepare(
            "INSERT INTO periodos_academicos 
                (nombre_periodo, codigo_periodo, anio_escolar, fecha_inicio, fecha_fin, ponderacion, activo) 
             VALUES 
                (:nombre, :codigo, :anio, :inicio, :fin, :ponderacion, :activo)"
        );
        $stmt->execute([
            'nombre' => $nombre_periodo,
            'codigo' => $codigo_periodo,
            'anio' => $anio_escolar,
            'inicio' => $fecha_inicio,
            'fin' => $fecha_fin,
            'ponderacion' => $ponderacion,
            'activo' => $activo
        ]);
        header('Location: index.php?success=1');
        exit;
    }
}

// =============================================
// EDITAR PERÍODO
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre_periodo = trim($_POST['nombre_periodo']);
    $codigo_periodo = trim($_POST['codigo_periodo']);
    $anio_escolar = trim($_POST['anio_escolar']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $ponderacion = floatval($_POST['ponderacion']);
    $activo = (int)$_POST['activo'];

    if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
        $error = "❌ La fecha de fin no puede ser anterior a la fecha de inicio.";
    } else {
        $stmt = $db->prepare(
            "UPDATE periodos_academicos SET 
                nombre_periodo = :nombre,
                codigo_periodo = :codigo,
                anio_escolar = :anio,
                fecha_inicio = :inicio,
                fecha_fin = :fin,
                ponderacion = :ponderacion,
                activo = :activo
             WHERE id_periodo = :id"
        );
        $stmt->execute([
            'nombre' => $nombre_periodo,
            'codigo' => $codigo_periodo,
            'anio' => $anio_escolar,
            'inicio' => $fecha_inicio,
            'fin' => $fecha_fin,
            'ponderacion' => $ponderacion,
            'activo' => $activo,
            'id' => $id
        ]);
        header('Location: index.php?success=2');
        exit;
    }
}

// =============================================
// ELIMINAR PERÍODO
// =============================================
if (isset($_GET['eliminar'])) {
    $stmt = $db->prepare("DELETE FROM periodos_academicos WHERE id_periodo = :id");
    $stmt->execute(['id' => $_GET['eliminar']]);
    header('Location: index.php?success=3');
    exit;
}

// =============================================
// OBTENER LISTA DE PERÍODOS
// =============================================
$periodos = $db->query("SELECT * FROM periodos_academicos ORDER BY fecha_inicio DESC")->fetchAll();

// Obtener período para edición
$editar_periodo = null;
if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM periodos_academicos WHERE id_periodo = :id");
    $stmt->execute(['id' => $_GET['editar']]);
    $editar_periodo = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Períodos Académicos</title>
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
        .container { max-width: 1200px; margin: 0 auto; }
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
            border-bottom: 3px solid #1a3e60;
            padding-bottom: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        input, select, button {
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            width: 100%;
            transition: all 0.2s;
        }
        input:focus, select:focus {
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
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 5px;
            font-weight: bold;
        }
        .btn-edit:hover { background: #e0a800; }
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .btn-delete:hover { background: #c82333; }
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
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .badge-activo {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-inactivo {
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📅 SIGAE - Períodos Académicos</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">
                <?php 
                $msgs = [
                    1 => '✅ Período agregado correctamente',
                    2 => '✅ Período actualizado correctamente',
                    3 => '✅ Período eliminado correctamente'
                ];
                echo $msgs[$_GET['success']] ?? 'Operación exitosa';
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- FORMULARIO AGREGAR/EDITAR -->
        <div class="card">
            <h2><?= $editar_periodo ? '✏️ EDITAR PERÍODO' : '➕ AGREGAR NUEVO PERÍODO' ?></h2>
            <form method="POST">
                <?php if ($editar_periodo): ?>
                    <input type="hidden" name="id" value="<?= $editar_periodo['id_periodo'] ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <input type="text" name="nombre_periodo" placeholder="Nombre del período (ej. Segundo Lapso)" 
                           value="<?= htmlspecialchars($editar_periodo['nombre_periodo'] ?? '') ?>" required>
                    <input type="text" name="codigo_periodo" placeholder="Código del período (ej. LAP-2026-02)" 
                           value="<?= htmlspecialchars($editar_periodo['codigo_periodo'] ?? '') ?>" required>
                    <input type="text" name="anio_escolar" placeholder="Año escolar (ej. 2026-2027)" 
                           value="<?= htmlspecialchars($editar_periodo['anio_escolar'] ?? '') ?>" required>
                    <input type="date" name="fecha_inicio" 
                           value="<?= htmlspecialchars($editar_periodo['fecha_inicio'] ?? '') ?>" required>
                    <input type="date" name="fecha_fin" 
                           value="<?= htmlspecialchars($editar_periodo['fecha_fin'] ?? '') ?>" required>
                    <input type="number" step="0.01" name="ponderacion" placeholder="Ponderación (%)" 
                           value="<?= htmlspecialchars($editar_periodo['ponderacion'] ?? '33.33') ?>" required>
                    <?php if ($editar_periodo): ?>
                        <select name="activo">
                            <option value="1" <?= ($editar_periodo['activo'] == 1) ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?= ($editar_periodo['activo'] == 0) ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="activo" value="1">
                    <?php endif; ?>
                </div>
                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <?php if ($editar_periodo): ?>
                        <button type="submit" name="editar">✏️ ACTUALIZAR</button>
                        <a href="index.php" style="text-decoration: none;">
                            <button type="button" style="background: #6c757d;">❌ CANCELAR</button>
                        </a>
                    <?php else: ?>
                        <button type="submit" name="agregar">➕ AGREGAR</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- LISTA DE PERÍODOS -->
        <div class="card">
            <h2>📋 LISTA DE PERÍODOS</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Año escolar</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Pond.</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($periodos)): ?>
                        <tr><td colspan="9" style="text-align: center; padding: 30px;">📭 No hay períodos registrados</td></tr>
                    <?php else: ?>
                        <?php foreach ($periodos as $p): ?>
                        <tr>
                            <td><?= $p['id_periodo'] ?></td>
                            <td><?= htmlspecialchars($p['codigo_periodo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['nombre_periodo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['anio_escolar'] ?? '') ?></td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_fin'])) ?></td>
                            <td><?= number_format($p['ponderacion'] ?? 0, 2) ?>%</td>
                            <td>
                                <?php if ($p['activo'] == 1): ?>
                                    <span class="badge-activo">✅ ACTIVO</span>
                                <?php else: ?>
                                    <span class="badge-inactivo">❌ INACTIVO</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?editar=<?= $p['id_periodo'] ?>" class="btn-edit">✏️ EDITAR</a>
                                <a href="?eliminar=<?= $p['id_periodo'] ?>" class="btn-delete" 
                                   onclick="return confirm('⚠️ ¿ELIMINAR PERÍODO?\n\n<?= $p['nombre_periodo'] ?>\n\nEsta acción puede afectar calificaciones asociadas.')">🗑️ ELIMINAR</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>