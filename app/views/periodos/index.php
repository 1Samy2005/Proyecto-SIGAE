<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../auth/login.php');
    exit;
}

require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

// Agregar período
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $nombre = $_POST['nombre'];
    $codigo = $_POST['codigo'];
    $anio = $_POST['anio_escolar'];
    $inicio = $_POST['fecha_inicio'];
    $fin = $_POST['fecha_fin'];
    $ponderacion = $_POST['ponderacion'];
    
    $db->query(
        "INSERT INTO periodos_academicos 
         (nombre_periodo, codigo_periodo, anio_escolar, fecha_inicio, fecha_fin, ponderacion) 
         VALUES (:nombre, :codigo, :anio, :inicio, :fin, :ponderacion)",
        ['nombre' => $nombre, 'codigo' => $codigo, 'anio' => $anio, 
         'inicio' => $inicio, 'fin' => $fin, 'ponderacion' => $ponderacion]
    );
    header('Location: index.php?success=1');
    exit;
}

// Obtener períodos
$periodos = $db->query(
    "SELECT * FROM periodos_academicos ORDER BY anio_escolar DESC, fecha_inicio"
)->fetchAll();

$tipos_evaluacion = $db->query("SELECT * FROM tipos_evaluacion WHERE activo = true")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>SIGAE - Períodos Académicos</title>
    <style>
        body { font-family: Arial; background: #f0f2f5; padding: 20px; }
        .navbar { background: #1a3e60; color: white; padding: 15px; margin: -20px -20px 20px -20px; }
        .card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        input, select, button { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #28a745; color: white; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1a3e60; color: white; padding: 10px; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .activo { color: green; font-weight: bold; }
        .inactivo { color: red; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📅 SIGAE - Períodos Académicos</h1>
        <a href="../../../dashboard.php">⬅️ Regresar</a>
    </div>
    
    <div class="card">
        <h2>➕ Agregar Nuevo Período</h2>
        <form method="POST">
            <div class="form-grid">
                <input type="text" name="nombre" placeholder="Nombre del período" required>
                <input type="text" name="codigo" placeholder="Código (ej: L1-2026)" required>
                <input type="text" name="anio_escolar" placeholder="Año escolar" value="2026-2027" required>
                <input type="date" name="fecha_inicio" required>
                <input type="date" name="fecha_fin" required>
                <input type="number" name="ponderacion" placeholder="Ponderación %" step="0.01" value="33.33" required>
                <button type="submit" name="agregar">Guardar Período</button>
            </div>
        </form>
    </div>
    
    <div class="card">
        <h2>📋 Lista de Períodos</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Año</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Ponderación</th>
                <th>Estado</th>
            </tr>
            <?php foreach ($periodos as $p): ?>
            <tr>
                <td><?= $p['id_periodo'] ?></td>
                <td><strong><?= htmlspecialchars($p['codigo_periodo']) ?></strong></td>
                <td><?= htmlspecialchars($p['nombre_periodo']) ?></td>
                <td><?= htmlspecialchars($p['anio_escolar']) ?></td>
                <td><?= $p['fecha_inicio'] ?></td>
                <td><?= $p['fecha_fin'] ?></td>
                <td><?= $p['ponderacion'] ?>%</td>
                <td class="<?= $p['activo'] ? 'activo' : 'inactivo' ?>">
                    <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>