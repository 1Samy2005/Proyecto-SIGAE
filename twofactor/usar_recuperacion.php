<?php
session_start();

if (!isset($_SESSION['2fa_user_id'])) {
    header('Location: ../app/views/auth/login.php');
    exit;
}

require_once '../app/controllers/TwoFactorController.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $userId = $_SESSION['2fa_user_id'];
    
    $controller = new TwoFactorController();
    
    if ($controller->usarCodigoRecuperacion($userId, $codigo)) {
        $_SESSION['user_id'] = $userId;
        unset($_SESSION['2fa_user_id']);
        header('Location: ../dashboard.php?2fa=recuperado');
        exit;
    } else {
        $error = 'Código de recuperación inválido.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recuperación 2FA - SIGAE</title>
    <style>
        body {
            font-family: Arial;
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
            max-width: 400px;
        }
        input, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            cursor: pointer;
        }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔑 Usar Código de Recuperación</h2>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="codigo" placeholder="Ingrese su código de recuperación" required>
            <button type="submit">Verificar</button>
        </form>
        <p style="text-align: center;">
            <a href="verificar.php">Volver a la verificación normal</a>
        </p>
    </div>
</body>
</html>