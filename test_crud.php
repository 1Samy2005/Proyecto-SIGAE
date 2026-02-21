<?php
require_once 'app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();
?>

<!DOCTYPE html>
<html>
<head>
    <title>SIGAE - Sistema Funcionando</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f2f5; }
        h1 { color: #1a3e60; }
        table { border-collapse: collapse; width: 100%; background: white; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #1a3e60; color: white; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="success">
        <h1>✅ SIGAE - CONEXIÓN EXITOSA A POSTGRESQL</h1>
        <p>🎉 ¡Felicidades! El sistema está funcionando correctamente.</p>
    </div>

    <h2>📋 SECCIONES (Año Escolar 2026-2027)</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Sección</th>
            <th>Nivel</th>
            <th>Año Escolar</th>
        </tr>
        <?php
        $stmt = $db->query("SELECT * FROM secciones ORDER BY id_seccion");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['id_seccion']}</td>";
            echo "<td>{$row['nombre_seccion']}</td>";
            echo "<td>{$row['nivel_academico']}</td>";
            echo "<td>{$row['anio_escolar']}</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <h2>📚 MATERIAS</h2>
    <table>
        <tr>
            <th>Código</th>
            <th>Materia</th>
            <th>Área</th>
        </tr>
        <?php
        $stmt = $db->query("SELECT * FROM materias ORDER BY nombre_materia");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['codigo_materia']}</td>";
            echo "<td>{$row['nombre_materia']}</td>";
            echo "<td>{$row['area_conocimiento']}</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <h2>👥 ROLES DEL SISTEMA</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Rol</th>
            <th>Descripción</th>
        </tr>
        <?php
        $stmt = $db->query("SELECT * FROM roles ORDER BY id_rol");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['id_rol']}</td>";
            echo "<td>{$row['nombre_rol']}</td>";
            echo "<td>{$row['descripcion']}</td>";
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>