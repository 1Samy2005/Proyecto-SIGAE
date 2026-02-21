<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

// Agregar materia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $codigo = $_POST['codigo'];
    $nombre = strtoupper($_POST['nombre']);
    $area = $_POST['area'];
    
    $db->query(
        "INSERT INTO materias (codigo_materia, nombre_materia, area_conocimiento, estatus) 
         VALUES (:codigo, :nombre, :area, 'activa')",
        ['codigo' => $codigo, 'nombre' => $nombre, 'area' => $area]
    );
    header('Location: index.php');
    exit;
}

// Editar materia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $codigo = $_POST['codigo'];
    $nombre = strtoupper($_POST['nombre']);
    $area = $_POST['area'];
    
    $db->query(
        "UPDATE materias SET codigo_materia = :codigo, nombre_materia = :nombre, area_conocimiento = :area WHERE id_materia = :id",
        ['codigo' => $codigo, 'nombre' => $nombre, 'area' => $area, 'id' => $id]
    );
    header('Location: index.php');
    exit;
}

// Eliminar materia
if (isset($_GET['eliminar'])) {
    $db->query("DELETE FROM materias WHERE id_materia = :id", ['id' => $_GET['eliminar']]);
    header('Location: index.php');
    exit;
}

// Obtener todas las materias
$materias = $db->query("SELECT * FROM materias ORDER BY id_materia")->fetchAll();

// Obtener materia para editar
$editar_materia = null;
if (isset($_GET['editar'])) {
    $stmt = $db->query("SELECT * FROM materias WHERE id_materia = :id", ['id' => $_GET['editar']]);
    $editar_materia = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Gestión de Materias</title>
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
        .container { max-width: 1300px; margin: 0 auto; }
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
        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        form {
            display: grid;
            grid-template-columns: 1fr 2fr 2fr auto auto;
            gap: 15px;
            align-items: center;
        }
        input, select, button {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        input:focus, select:focus {
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
        }
        button:hover { 
            background: #218838; 
            transform: translateY(-2px);
        }
        .btn-cancel {
            background: #6c757d;
        }
        .btn-cancel:hover { 
            background: #5a6268;
        }
        .table-container {
            overflow-x: auto;
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
        tr:hover {
            background: #f5f5f5;
        }
        .badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
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
        .btn-edit:hover { 
            background: #e0a800;
            transform: translateY(-2px);
        }
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
        .btn-delete:hover { 
            background: #c82333;
            transform: translateY(-2px);
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
            font-size: 16px;
        }
        .codigo-ejemplo {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📚 SIGAE - Gestión de Materias</h1>
        <div>
            <span>👤 Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2><?= $editar_materia ? '✏️ EDITAR MATERIA' : '➕ AGREGAR NUEVA MATERIA' ?></h2>
            <div class="form-container">
                <form method="POST">
                    <?php if ($editar_materia): ?>
                        <input type="hidden" name="id" value="<?= $editar_materia['id_materia'] ?>">
                    <?php endif; ?>
                    
                    <input type="text" name="codigo" placeholder="📌 Código (ej: CAS-001)" 
                           value="<?= $editar_materia['codigo_materia'] ?? '' ?>" 
                           pattern="[A-Z]{3,4}-\d{3}" 
                           title="Formato: XXX-001 (3-4 letras, guión, 3 números)"
                           required>
                    
                    <select name="nombre" required>
                        <option value="">🔽 SELECCIONE MATERIA</option>
                        <option value="CASTELLANO">CASTELLANO</option>
                        <option value="INGLÉS">INGLÉS</option>
                        <option value="MATEMÁTICA">MATEMÁTICA</option>
                        <option value="EDUCACIÓN FÍSICA">EDUCACIÓN FÍSICA</option>
                        <option value="FÍSICA">FÍSICA</option>
                        <option value="QUÍMICA">QUÍMICA</option>
                        <option value="CIENCIAS DE LA TIERRA">CIENCIAS DE LA TIERRA</option>
                        <option value="BIOLOGÍA">BIOLOGÍA</option>
                        <option value="GHC">GHC (GEOGRAFÍA, HISTORIA Y CIUDADANÍA)</option>
                        <option value="FSN">FSN (FORMACIÓN PARA LA SOBERANÍA NACIONAL)</option>
                        <option value="ORIENTACIÓN">ORIENTACIÓN</option>
                        <option value="PROYECTO">PROYECTO</option>
                        <option value="GRCP">GRCP (GRUPOS DE CREACIÓN, RECREACIÓN Y PRODUCCIÓN)</option>
                    </select>
                    
                    <select name="area" required>
                        <option value="">🔽 SELECCIONE ÁREA</option>
                        <option value="Humanidades">Humanidades</option>
                        <option value="Idiomas">Idiomas</option>
                        <option value="Ciencias Básicas">Ciencias Básicas</option>
                        <option value="Deportes">Deportes</option>
                        <option value="Ciencias Naturales">Ciencias Naturales</option>
                        <option value="Ciencias Sociales">Ciencias Sociales</option>
                        <option value="Formación Ciudadana">Formación Ciudadana</option>
                        <option value="Formación Personal">Formación Personal</option>
                        <option value="Investigación">Investigación</option>
                    </select>
                    
                    <?php if ($editar_materia): ?>
                        <button type="submit" name="editar">✏️ ACTUALIZAR</button>
                        <a href="index.php" style="text-decoration: none;">
                            <button type="button" class="btn-cancel">❌ CANCELAR</button>
                        </a>
                    <?php else: ?>
                        <button type="submit" name="agregar">➕ GUARDAR MATERIA</button>
                    <?php endif; ?>
                </form>
                <div class="codigo-ejemplo">
                    📝 Formato de código: 3-4 letras + guión + 3 números (Ej: CAS-001, BIO-001, GRCP-001)
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>📋 LISTA DE MATERIAS</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>CÓDIGO</th>
                            <th>MATERIA</th>
                            <th>ÁREA</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($materias)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px;">
                                    🚫 No hay materias registradas en el sistema
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($materias as $m): ?>
                            <tr>
                                <td><span class="badge"><?= htmlspecialchars($m['codigo_materia']) ?></span></td>
                                <td><strong><?= htmlspecialchars($m['nombre_materia']) ?></strong></td>
                                <td><?= htmlspecialchars($m['area_conocimiento']) ?></td>
                                <td>
                                    <a href="?editar=<?= $m['id_materia'] ?>" class="btn-edit">✏️ EDITAR</a>
                                    <a href="?eliminar=<?= $m['id_materia'] ?>" 
                                       class="btn-delete" 
                                       onclick="return confirm('⚠️ ¿ESTÁ SEGURO DE ELIMINAR LA MATERIA:\n\n<?= $m['nombre_materia'] ?>?\n\nEsta acción no se puede deshacer.')">🗑️ ELIMINAR</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="stats">
                <span style="font-size: 16px; color: #1a3e60;">
                    📊 Total de materias en el sistema:
                </span>
                <span class="total">
                    <?= count($materias) ?> / 13
                </span>
            </div>
        </div>
    </div>

    <!-- SOLUCIÓN: ACTUALIZAR GRCP EN LA BASE DE DATOS -->
    <!-- 
    ⚠️ SI GRCP SIGUE APARECIENDO INCORRECTO, EJECUTA ESTO EN pgAdmin:
    
    UPDATE materias 
    SET nombre_materia = 'GRCP (GRUPOS DE CREACIÓN, RECREACIÓN Y PRODUCCIÓN)'
    WHERE codigo_materia = 'GRCP-001';
    
    SELECT * FROM materias WHERE codigo_materia = 'GRCP-001';
    -->
</body>
</html>