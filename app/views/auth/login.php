<?php
session_start();

// Incluir archivos necesarios (rutas absolutas, ajusta si es necesario)
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
require_once 'C:/xampp/htdocs/SIGAE/app/helpers/AuditLogger.php';

use App\Core\Database;

$error = '';
$audit = new AuditLogger();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Obtener IP y User Agent para auditoría
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        $db = Database::getInstance();
        
        // =============================================
        // BUSCAR USUARIO ACTIVO (CONSULTA PREPARADA)
        // =============================================
        $stmt = $db->prepare(
            "SELECT id_usuario, nombre_usuario, email, password_hash, tfa_activo 
             FROM usuarios 
             WHERE nombre_usuario = :username AND estatus = 'activo'"
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Verificar 2FA
            if (isset($user['tfa_activo']) && $user['tfa_activo'] == true) {
                // Registrar pendiente de 2FA
                $audit->logLoginPendiente2FA($user['id_usuario'], $user['nombre_usuario'], $ip, $userAgent);
                
                $_SESSION['2fa_user_id'] = $user['id_usuario'];
                $_SESSION['2fa_username'] = $user['nombre_usuario'];
                header('Location: /SIGAE/twofactor/verificar.php');
                exit;
            } else {
                // =============================================
                // OBTENER ROLES DEL USUARIO (CONSULTA PREPARADA)
                // =============================================
                $stmt_roles = $db->prepare(
                    "SELECT r.nombre_rol 
                     FROM usuario_roles ur
                     JOIN roles r ON ur.id_rol = r.id_rol
                     WHERE ur.id_usuario = :id"
                );
                $stmt_roles->execute(['id' => $user['id_usuario']]);
                $roles = $stmt_roles->fetchAll(PDO::FETCH_COLUMN);
                
                // =============================================
                // OBTENER PERMISOS ÚNICOS DE TODOS LOS ROLES
                // =============================================
                $permisos = [];
                if (!empty($roles)) {
                    // Crear placeholders dinámicos para la cláusula IN
                    $placeholders = implode(',', array_fill(0, count($roles), '?'));
                    $stmt_permisos = $db->prepare(
                        "SELECT permisos FROM roles WHERE nombre_rol IN ($placeholders)"
                    );
                    $stmt_permisos->execute($roles);
                    $rows = $stmt_permisos->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rows as $row) {
                        $perms = json_decode($row['permisos'], true);
                        if (is_array($perms)) {
                            $permisos = array_merge($permisos, $perms);
                        }
                    }
                    $permisos = array_unique($permisos);
                }
                
                // Guardar datos en sesión
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['username'] = $user['nombre_usuario'];
                $_SESSION['roles'] = $roles;
                $_SESSION['permisos'] = $permisos;
                $_SESSION['rol'] = $roles[0] ?? 'sin_rol'; // Compatibilidad
                
                // Registrar login exitoso en auditoría
                $audit->logLoginExitoso($user['id_usuario'], $user['nombre_usuario'], $ip, $userAgent);
                
                header('Location: /SIGAE/dashboard.php');
                exit;
            }
        } else {
            // Login fallido
            $audit->logLoginFallido($username, $ip, $userAgent);
            $error = 'Usuario o contraseña incorrectos';
        }
    } catch (Exception $e) {
        $error = 'Error de conexión: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Iniciar Sesión</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo span {
            background: #667eea;
            color: white;
            width: 60px;
            height: 60px;
            display: inline-block;
            line-height: 60px;
            border-radius: 10px;
            font-size: 24px;
            font-weight: bold;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .info {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <span>SIGAE</span>
        </div>
        <h1>Sistema de Gestión Académica</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" 
                       placeholder="Ingrese su usuario" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" 
                       placeholder="Ingrese su contraseña" required>
            </div>
            <button type="submit">Iniciar Sesión</button>
        </form>
        
        <div class="info">
            <p>SIGAE v2.0 - U.E.N. José Agustín Marquiegüi</p>
            <p style="margin-top: 10px; color: #999;">BIENVENIDO AL SISTEMA SIGAE</p>
        </div>
    </div>
</body>
</html>