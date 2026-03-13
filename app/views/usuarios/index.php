<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Solo administradores pueden acceder a este módulo
if ($_SESSION['rol'] !== 'administrador') {
    header('Location: ../../../dashboard.php');
    exit;
}

require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

// =============================================
// AGREGAR USUARIO (CORREGIDO)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    $salt = bin2hex(random_bytes(16));
    
    try {
        $db->beginTransaction();
        
        // Insertar usuario (CONSULTA PREPARADA)
        $stmt = $db->prepare(
            "INSERT INTO usuarios (nombre_usuario, email, password_hash, password_salt, estatus) 
             VALUES (:usuario, :email, :password, :salt, 'activo') RETURNING id_usuario"
        );
        $stmt->execute([
            'usuario' => $nombre_usuario,
            'email' => $email,
            'password' => $password,
            'salt' => $salt
        ]);
        $id_usuario = $stmt->fetchColumn(); // Obtener el ID retornado
        
        // Asignar rol (CONSULTA PREPARADA)
        $stmt_rol = $db->prepare(
            "INSERT INTO usuario_roles (id_usuario, id_rol, asignado_por) 
             VALUES (:usuario, :rol, :admin)"
        );
        $stmt_rol->execute([
            'usuario' => $id_usuario,
            'rol' => $rol,
            'admin' => $_SESSION['user_id']
        ]);
        
        $db->commit();
        $success = "✅ Usuario creado exitosamente";
    } catch (PDOException $e) {
        $db->rollBack();
        // Verificar si es error de email duplicado
        if ($e->errorInfo[1] == 23505) { // Código de violación de unicidad en PostgreSQL
            $error = "❌ El email '$email' ya está registrado. Por favor, use otro email.";
        } else {
            $error = "❌ Error al crear usuario: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $db->rollBack();
        $error = "❌ Error general: " . $e->getMessage();
    }
}

// =============================================
// EDITAR USUARIO (CORREGIDO)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $email = trim($_POST['email']);
    $rol = $_POST['rol'];
    $estatus = $_POST['estatus'];
    
    try {
        $db->beginTransaction();
        
        // Actualizar usuario
        $stmt_upd = $db->prepare(
            "UPDATE usuarios SET nombre_usuario = :usuario, email = :email, estatus = :estatus 
             WHERE id_usuario = :id"
        );
        $stmt_upd->execute([
            'usuario' => $nombre_usuario,
            'email' => $email,
            'estatus' => $estatus,
            'id' => $id
        ]);
        
        // Actualizar rol
        $stmt_rol = $db->prepare(
            "UPDATE usuario_roles SET id_rol = :rol WHERE id_usuario = :id"
        );
        $stmt_rol->execute([
            'rol' => $rol,
            'id' => $id
        ]);
        
        $db->commit();
        $success = "✅ Usuario actualizado correctamente";
    } catch (PDOException $e) {
        $db->rollBack();
        if ($e->errorInfo[1] == 23505) {
            $error = "❌ El email '$email' ya está registrado por otro usuario.";
        } else {
            $error = "❌ Error al actualizar: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $db->rollBack();
        $error = "❌ Error general: " . $e->getMessage();
    }
}

// =============================================
// CAMBIAR CONTRASEÑA (CORREGIDO)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $id = $_POST['id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt_pass = $db->prepare("UPDATE usuarios SET password_hash = :password WHERE id_usuario = :id");
        $stmt_pass->execute(['password' => $password, 'id' => $id]);
        $success = "✅ Contraseña actualizada";
    } catch (Exception $e) {
        $error = "❌ Error al cambiar contraseña: " . $e->getMessage();
    }
}

// =============================================
// ELIMINAR USUARIO (CORREGIDO)
// =============================================
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    if ($id != 1) { // No permitir eliminar al admin principal
        try {
            $stmt_del = $db->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
            $stmt_del->execute(['id' => $id]);
            $success = "✅ Usuario eliminado";
        } catch (Exception $e) {
            $error = "❌ Error al eliminar: " . $e->getMessage();
        }
    } else {
        $error = "❌ No se puede eliminar al administrador principal";
    }
    header('Location: index.php');
    exit;
}

// =============================================
// OBTENER DATOS (CORREGIDO)
// =============================================
$usuarios = $db->query(
    "SELECT u.*, r.nombre_rol 
     FROM usuarios u
     LEFT JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
     LEFT JOIN roles r ON ur.id_rol = r.id_rol
     ORDER BY u.id_usuario"
)->fetchAll();

$roles = $db->query("SELECT * FROM roles ORDER BY id_rol")->fetchAll();

// Obtener usuario para editar
$editar_usuario = null;
if (isset($_GET['editar'])) {
    $stmt = $db->prepare(
        "SELECT u.*, ur.id_rol 
         FROM usuarios u
         LEFT JOIN usuario_roles ur ON u.id_usuario = ur.id_usuario
         WHERE u.id_usuario = :id"
    );
    $stmt->execute(['id' => $_GET['editar']]);
    $editar_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener usuario para cambiar contraseña
$cambiar_password = null;
if (isset($_GET['cambiar_password'])) {
    $stmt = $db->prepare("SELECT id_usuario, nombre_usuario FROM usuarios WHERE id_usuario = :id");
    $stmt->execute(['id' => $_GET['cambiar_password']]);
    $cambiar_password = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Gestión de Usuarios y Roles</title>
    <style>
        /* (mantén los mismos estilos que ya tenías) */
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
        .navbar h1 { font-size: 24px; }
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
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
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
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-admin { background: #dc3545; color: white; }
        .badge-docente { background: #28a745; color: white; }
        .badge-control { background: #17a2b8; color: white; }
        .badge-admin2 { background: #ffc107; color: #333; }
        
        .btn-edit { background: #ffc107; color: #333; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px; }
        .btn-password { background: #17a2b8; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🔐 SIGAE - Gestión de Usuarios y Roles</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?> (<?= $_SESSION['rol'] ?>)</span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- FORMULARIO AGREGAR/EDITAR USUARIO -->
        <div class="card">
            <h2><?= $editar_usuario ? '✏️ EDITAR USUARIO' : '➕ AGREGAR NUEVO USUARIO' ?></h2>
            <form method="POST">
                <?php if ($editar_usuario): ?>
                    <input type="hidden" name="id" value="<?= $editar_usuario['id_usuario'] ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <input type="text" name="nombre_usuario" placeholder="Usuario" 
                           value="<?= htmlspecialchars($editar_usuario['nombre_usuario'] ?? '') ?>" required>
                    
                    <input type="email" name="email" placeholder="Email" 
                           value="<?= htmlspecialchars($editar_usuario['email'] ?? '') ?>" required>
                    
                    <?php if (!$editar_usuario): ?>
                        <input type="password" name="password" placeholder="Contraseña" required>
                    <?php endif; ?>
                    
                    <select name="rol" required>
                        <option value="">-- SELECCIONE ROL --</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id_rol'] ?>" 
                                <?= ($editar_usuario && $editar_usuario['id_rol'] == $r['id_rol']) ? 'selected' : '' ?>>
                                <?= strtoupper($r['nombre_rol']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if ($editar_usuario): ?>
                        <select name="estatus">
                            <option value="activo" <?= $editar_usuario['estatus'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= $editar_usuario['estatus'] == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            <option value="bloqueado" <?= $editar_usuario['estatus'] == 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                        </select>
                    <?php endif; ?>
                    
                    <?php if ($editar_usuario): ?>
                        <button type="submit" name="editar">✏️ ACTUALIZAR</button>
                        <a href="index.php"><button type="button">❌ CANCELAR</button></a>
                    <?php else: ?>
                        <button type="submit" name="agregar">➕ GUARDAR</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- FORMULARIO CAMBIAR CONTRASEÑA -->
        <?php if ($cambiar_password): ?>
        <div class="card">
            <h2>🔑 CAMBIAR CONTRASEÑA - <?= htmlspecialchars($cambiar_password['nombre_usuario']) ?></h2>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $cambiar_password['id_usuario'] ?>">
                <div class="form-grid" style="grid-template-columns: 1fr auto;">
                    <input type="password" name="password" placeholder="Nueva contraseña" required>
                    <button type="submit" name="cambiar_password">🔑 ACTUALIZAR</button>
                    <a href="index.php"><button type="button">❌ CANCELAR</button></a>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- LISTA DE USUARIOS -->
        <div class="card">
            <h2>📋 LISTA DE USUARIOS</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>USUARIO</th>
                        <th>EMAIL</th>
                        <th>ROL</th>
                        <th>ESTADO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><strong><?= $u['id_usuario'] ?></strong></td>
                        <td><?= htmlspecialchars($u['nombre_usuario']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?php
                            $clase = 'badge-admin';
                            if ($u['nombre_rol'] == 'docente') $clase = 'badge-docente';
                            if ($u['nombre_rol'] == 'control_estudio') $clase = 'badge-control';
                            if ($u['nombre_rol'] == 'administrativo') $clase = 'badge-admin2';
                            ?>
                            <span class="badge <?= $clase ?>"><?= strtoupper($u['nombre_rol'] ?? 'SIN ROL') ?></span>
                        </td>
                        <td>
                            <span style="color: <?= $u['estatus'] == 'activo' ? 'green' : 'red' ?>;">
                                <?= strtoupper($u['estatus']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="?editar=<?= $u['id_usuario'] ?>" class="btn-edit">✏️ EDITAR</a>
                            <a href="?cambiar_password=<?= $u['id_usuario'] ?>" class="btn-password">🔑 PASS</a>
                            <?php if ($u['id_usuario'] != 1): ?>
                                <a href="?eliminar=<?= $u['id_usuario'] ?>" class="btn-delete" 
                                   onclick="return confirm('¿ELIMINAR USUARIO <?= htmlspecialchars($u['nombre_usuario']) ?>?')">🗑️ ELIMINAR</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>