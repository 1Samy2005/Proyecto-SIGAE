<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../auth/login.php');
    exit;
}

require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

// =============================================
// EDITAR PERMISOS DE ROL
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_permisos'])) {
    $id_rol = $_POST['id_rol'];
    $permisos = json_encode($_POST['permisos'] ?? []);
    
    $db->query(
        "UPDATE roles SET permisos = :permisos WHERE id_rol = :id",
        ['permisos' => $permisos, 'id' => $id_rol]
    );
    $success = "✅ Permisos actualizados";
}

$roles = $db->query("SELECT * FROM roles ORDER BY id_rol")->fetchAll();
$rol_seleccionado = $_GET['rol'] ?? 1;
$permisos_actuales = $db->query("SELECT permisos FROM roles WHERE id_rol = :id", ['id' => $rol_seleccionado])->fetch();
$permisos_array = json_decode($permisos_actuales['permisos'] ?? '[]', true);
?>

<!DOCTYPE html>
<html>
<head>
    <title>SIGAE - Permisos de Roles</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f0f2f5; padding: 20px; }
        .navbar { background: #1a3e60; color: white; padding: 15px 30px; margin: -20px -20px 20px -20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #1a3e60; margin-bottom: 20px; }
        select, button { padding: 10px; width: 100%; margin-bottom: 20px; }
        .permiso-item { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        button { background: #28a745; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🔐 SIGAE - Permisos de Roles</h1>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>📋 SELECCIONAR ROL</h2>
            <form method="GET">
                <select name="rol" onchange="this.form.submit()">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id_rol'] ?>" <?= $rol_seleccionado == $r['id_rol'] ? 'selected' : '' ?>>
                            <?= strtoupper($r['nombre_rol']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            
            <form method="POST">
                <input type="hidden" name="id_rol" value="<?= $rol_seleccionado ?>">
                
                <h2>🔧 PERMISOS</h2>
                
                <div class="permiso-item">
                    <label>
                        <input type="checkbox" name="permisos[]" value="ver_usuarios" 
                               <?= in_array('ver_usuarios', $permisos_array) ? 'checked' : '' ?>>
                        Ver usuarios
                    </label>
                </div>
                
                <div class="permiso-item">
                    <label>
                        <input type="checkbox" name="permisos[]" value="gestion_estudiantes" 
                               <?= in_array('gestion_estudiantes', $permisos_array) ? 'checked' : '' ?>>
                        Gestionar estudiantes
                    </label>
                </div>
                
                <div class="permiso-item">
                    <label>
                        <input type="checkbox" name="permisos[]" value="gestion_docentes" 
                               <?= in_array('gestion_docentes', $permisos_array) ? 'checked' : '' ?>>
                        Gestionar docentes
                    </label>
                </div>
                
                <div class="permiso-item">
                    <label>
                        <input type="checkbox" name="permisos[]" value="gestion_materias" 
                               <?= in_array('gestion_materias', $permisos_array) ? 'checked' : '' ?>>
                        Gestionar materias
                    </label>
                </div>
                
                <div class="permiso-item">
                    <label>
                        <input type="checkbox" name="permisos[]" value="gestion_calificaciones" 
                               <?= in_array('gestion_calificaciones', $permisos_array) ? 'checked' : '' ?>>
                        Gestionar calificaciones
                    </label>
                </div>
                
                <div class="permiso-item">
                    <label>
                        <input type="checkbox" name="permisos[]" value="generar_reportes" 
                               <?= in_array('generar_reportes', $permisos_array) ? 'checked' : '' ?>>
                        Generar reportes
                    </label>
                </div>
                
                <div class="permiso-item">
                    <label>
                        <input type="checkbox" name="permisos[]" value="ver_auditoria" 
                               <?= in_array('ver_auditoria', $permisos_array) ? 'checked' : '' ?>>
                        Ver auditoría
                    </label>
                </div>
                
                <button type="submit" name="guardar_permisos">💾 GUARDAR PERMISOS</button>
            </form>
        </div>
    </div>
</body>
</html>