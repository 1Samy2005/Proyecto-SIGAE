<?php
session_start();
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';

use App\Core\Database;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    try {
        $db = Database::getInstance();
        
        // Buscar usuario (incluyendo campos de 2FA)
        $stmt = $db->query(
            "SELECT id_usuario, nombre_usuario, email, password_hash, tfa_activo 
             FROM usuarios 
             WHERE nombre_usuario = :username AND estatus = 'activo'",
            ['username' => $username]
        );
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // =============================================
        // VERIFICACIÓN DE CONTRASEÑA
        // =============================================
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // =============================================
            // VERIFICACIÓN DE 2FA - CORREGIDO
            // =============================================
            
            // Verificar si el usuario tiene 2FA activado
            if (isset($user['tfa_activo']) && $user['tfa_activo'] == true) {
                // Usuario con 2FA - guardar ID temporalmente
                $_SESSION['2fa_user_id'] = $user['id_usuario'];
                $_SESSION['2fa_username'] = $user['nombre_usuario'];
                
                // REDIRECCIÓN CORREGIDA - RUTA ABSOLUTA
                header('Location: /SIGAE/twofactor/verificar.php');
                exit;
                
            } else {
                // Usuario sin 2FA - inicio de sesión normal
                
                // Obtener rol del usuario
                $stmt_rol = $db->query(
                    "SELECT r.nombre_rol 
                     FROM usuario_roles ur
                     JOIN roles r ON ur.id_rol = r.id_rol
                     WHERE ur.id_usuario = :id",
                    ['id' => $user['id_usuario']]
                );
                $rol = $stmt_rol->fetch();
                
                // Guardar datos en sesión
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['username'] = $user['nombre_usuario'];
                $_SESSION['rol'] = $rol['nombre_rol'] ?? 'sin_rol';
                
                // Redirigir al dashboard
                header('Location: /SIGAE/dashboard.php');
                exit;
            }
            
        } else {
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
            <p style="margin-top: 10px; color: #999;">Usuario: admin | Contraseña: Admin2026!</p>
        </div>
    </div>
</body>
</html>