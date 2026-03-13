<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - SIGAE</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 400px;
        }
        h1 {
            color: #b22222;
            font-size: 32px;
            margin-bottom: 20px;
        }
        p {
            color: #333;
            font-size: 18px;
            margin-bottom: 30px;
        }
        a {
            display: inline-block;
            padding: 12px 30px;
            background: #1a3e60;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s;
        }
        a:hover {
            background: #0f2a3f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⛔ Acceso Denegado</h1>
        <p>No tienes permisos para acceder a esta sección.</p>
        <p>Si crees que esto es un error, contacta al administrador.</p>
        <a href="dashboard.php">Volver al Inicio</a>
    </div>
</body>
</html>