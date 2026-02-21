<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: app/views/auth/login.php'); // ✅ RUTA RELATIVA CORREGIDA
    exit;
}

require_once 'app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

$total_secciones = $db->query("SELECT COUNT(*) as total FROM secciones")->fetch()['total'];
$total_materias = $db->query("SELECT COUNT(*) as total FROM materias")->fetch()['total'];
$total_estudiantes = $db->query("SELECT COUNT(*) as total FROM estudiantes")->fetch()['total'];
$total_docentes = $db->query("SELECT COUNT(*) as total FROM docentes")->fetch()['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>SIGAE - Dashboard</title>
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
        .navbar h1 {
            font-size: 24px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .logout {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-card .number {
            color: #1a3e60;
            font-size: 32px;
            font-weight: bold;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }
        .menu-item {
            text-decoration: none;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            color: #1a3e60;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .menu-item span {
            font-size: 48px;
            display: block;
            margin-bottom: 10px;
        }
        .menu-item h3 {
            margin-bottom: 5px;
        }
        .menu-item p {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🎓 SIGAE - Sistema Integrado de Gestión Académica</h1>
        <div class="user-info">
            <span>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="app/views/auth/logout.php" class="logout">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <h3>Total Secciones</h3>
                <div class="number"><?= $total_secciones ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Materias</h3>
                <div class="number"><?= $total_materias ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Estudiantes</h3>
                <div class="number"><?= $total_estudiantes ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Docentes</h3>
                <div class="number"><?= $total_docentes ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>✅ Sistema SIGAE funcionando correctamente</h2>
            <p style="margin-top: 10px; color: green;">¡Felicidades! Has completado la instalación y configuración del sistema.</p>
        </div>
        
        <div class="menu-grid">
            <!-- MÓDULO DE ESTUDIANTES -->
            <a href="app/views/estudiantes/index.php" class="menu-item">
                <span>👥</span>
                <h3>Estudiantes</h3>
                <p>Gestión de estudiantes</p>
            </a>
            
            <!-- MÓDULO DE DOCENTES -->
            <a href="app/views/docentes/index.php" class="menu-item">
                <span>👨‍🏫</span>
                <h3>Docentes</h3>
                <p>Gestión de docentes</p>
            </a>
            
            <!-- MÓDULO DE MATERIAS -->
            <a href="app/views/materias/index.php" class="menu-item">
                <span>📚</span>
                <h3>Materias</h3>
                <p>Gestión de materias</p>
            </a>
        </div>
    </div>
</body>
</html>
<!-- MÓDULO DE CALIFICACIONES -->
<a href="app/views/calificaciones/index.php" class="menu-item">
    <span>📊</span>
    <h3>Calificaciones</h3>
    <p>Registro de notas</p>
</a>
<!-- MÓDULO DE BOLETAS -->
<a href="app/views/boletas/index.php" class="menu-item">
    <span>📄</span>
    <h3>Boletas</h3>
    <p>Generar boletas de notas</p>

<!-- MÓDULO DE CUADROS DE MÉRITO -->
<a href="app/views/cuadros_merito/index.php" class="menu-item">
    <span>🏆</span>
    <h3>Cuadros de Mérito</h3>
    <p>Ranking y promedios</p>
</a>
<!-- MÓDULO DE REPORTES Y ESTADÍSTICAS -->
<a href="app/views/reportes/index.php" class="menu-item">
    <span>📊</span>
    <h3>Reportes</h3>
    <p>Estadísticas y gráficos</p>
</a>
<a href="http://localhost/SIGAE/app/views/historia/index.php?id=<?= $_SESSION['user_id'] ?>" class="menu-item">
    <span>📚</span>
    <h3>Historia</h3>
    <p>Ver historial de estudios</p>
</a>
<?php if ($_SESSION['rol'] === 'administrador'): ?>
<!-- MÓDULO DE PERÍODOS ACADÉMICOS -->
<a href="app/views/periodos/index.php" class="menu-item">
    <span>📅</span>
    <h3>Períodos</h3>
    <p>Gestión de lapsos</p>
</a>
<?php endif; ?>

