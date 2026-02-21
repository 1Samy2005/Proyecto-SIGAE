<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

// Obtener secciones para el filtro
$secciones = $db->query("SELECT * FROM secciones WHERE estatus = 'activa' ORDER BY nombre_seccion")->fetchAll();

// Obtener estudiantes por sección
$estudiantes = [];
$seccion_seleccionada = $_GET['seccion'] ?? null;
$periodo = $_GET['periodo'] ?? '2026-2027';

if ($seccion_seleccionada) {
    $estudiantes = $db->query(
        "SELECT id_estudiante, cedula, nombres, apellidos 
         FROM estudiantes 
         WHERE id_seccion = :seccion AND estatus_academico = 'activo'
         ORDER BY apellidos, nombres",
        ['seccion' => $seccion_seleccionada]
    )->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Generar Boletas</title>
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
        .container { max-width: 1200px; margin: 0 auto; }
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
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
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
        select, button {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 100%;
        }
        button {
            background: #1a3e60;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }
        button:hover {
            background: #2c5a7a;
            transform: translateY(-2px);
        }
        .estudiantes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        .estudiante-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            border: 1px solid #ddd;
            transition: all 0.2s;
            display: block;
        }
        .estudiante-card:hover {
            background: #e9ecef;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-color: #1a3e60;
        }
        .estudiante-nombre {
            font-size: 16px;
            font-weight: bold;
            color: #1a3e60;
        }
        .estudiante-cedula {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            margin-top: 10px;
        }
        .alert {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
            color: #666;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📄 SIGAE - Generación de Boletas</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>🔍 SELECCIONAR ESTUDIANTE</h2>
            <form method="GET" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>📌 SECCIÓN</label>
                        <select name="seccion" required onchange="this.form.submit()">
                            <option value="">-- SELECCIONE SECCIÓN --</option>
                            <?php foreach ($secciones as $s): ?>
                                <option value="<?= $s['id_seccion'] ?>" <?= ($seccion_seleccionada == $s['id_seccion']) ? 'selected' : '' ?>>
                                    <?= $s['nombre_seccion'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>📅 PERÍODO</label>
                        <select name="periodo">
                            <option value="2026-2027" <?= ($periodo == '2026-2027') ? 'selected' : '' ?>>2026-2027</option>
                            <option value="2025-2026" <?= ($periodo == '2025-2026') ? 'selected' : '' ?>>2025-2026</option>
                            <option value="2024-2025" <?= ($periodo == '2024-2025') ? 'selected' : '' ?>>2024-2025</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit">🔍 BUSCAR</button>
                    </div>
                </div>
            </form>
            
            <?php if ($seccion_seleccionada): ?>
                <h3 style="margin-top: 30px; color: #1a3e60;">
                    👨‍🎓 ESTUDIANTES DE <?= htmlspecialchars($seccion_seleccionada) ?>
                </h3>
                
                <?php if (empty($estudiantes)): ?>
                    <div class="alert">
                        📭 No hay estudiantes activos en esta sección
                    </div>
                <?php else: ?>
                    <div class="estudiantes-grid">
                        <?php foreach ($estudiantes as $e): ?>
                            <a href="ver_boleta.php?id=<?= $e['id_estudiante'] ?>&periodo=<?= $periodo ?>" class="estudiante-card">
                                <span class="estudiante-nombre"><?= htmlspecialchars($e['apellidos'] . ', ' . $e['nombres']) ?></span>
                                <span class="estudiante-cedula">C.I: <?= htmlspecialchars($e['cedula']) ?></span>
                                <div><span class="badge">📄 GENERAR BOLETA</span></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>