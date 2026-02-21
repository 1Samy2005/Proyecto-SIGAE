<?php
session_start();

// Verificar que el usuario viene del login con 2fa pendiente
if (!isset($_SESSION['2fa_user_id'])) {
    header('Location: /SIGAE/app/views/auth/login.php');
    exit;
}

require_once '../app/controllers/TwoFactorController.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $userId = $_SESSION['2fa_user_id'];
    
    // Instanciar el controlador 2FA
    $controller = new TwoFactorController();
    
    // Verificar el código usando el método que ya existe
    if ($controller->verificarCodigo($userId, $codigo)) {
        // Código correcto - iniciar sesión
        $_SESSION['user_id'] = $userId;
        unset($_SESSION['2fa_user_id']);
        
        // REDIRECCIÓN CORREGIDA - RUTA ABSOLUTA
        header('Location: /SIGAE/dashboard.php');
        exit;
    } else {
        $error = 'Código incorrecto. Intente nuevamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación 2FA - SIGAE</title>
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
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            text-align: center;
            letter-spacing: 4px;
            font-weight: bold;
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
            margin-top: 20px;
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
        .recuperacion {
            text-align: center;
            margin-top: 15px;
        }
        .recuperacion a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .recuperacion a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Verificación en Dos Pasos</h1>
        
        <p>Ingrese el código de 6 dígitos de su aplicación autenticadora</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="/SIGAE/twofactor/verificar.php">
            <input type="text" 
                   name="codigo" 
                   placeholder="123456" 
                   required 
                   pattern="[0-9]{6}" 
                   maxlength="6"
                   autocomplete="off"
                   autofocus>
            <button type="submit">Verificar</button>
        </form>
        
        <div class="recuperacion">
            <a href="/SIGAE/twofactor/usar_recuperacion.php">¿Perdió el acceso? Use un código de recuperación</a>
        </div>
        
        <div class="info">
            <p>SIGAE - Sistema de Gestión Académica</p>
        </div>
    </div>
</body>
</html>