<?php
session_start();
echo "<h1 style='color: blue;'>🔍 DATOS DE SESIÓN ACTUAL</h1>";
echo "<hr>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "<p><strong>Rol actual:</strong> " . ($_SESSION['rol'] ?? 'No definido') . "</p>";
echo "<p><strong>Usuario ID:</strong> " . ($_SESSION['user_id'] ?? 'No definido') . "</p>";
echo "<p><strong>Nombre de usuario:</strong> " . ($_SESSION['username'] ?? 'No definido') . "</p>";
echo "<hr>";
echo "<a href='app/views/auth/login.php'>🔐 Ir al Login</a> | ";
echo "<a href='dashboard.php'>📊 Ir al Dashboard</a>";
?>