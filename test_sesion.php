<?php
session_start();
echo "<h1>🔍 Diagnóstico de Sesión</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "<p><a href='/SIGAE/app/views/auth/login.php'>Ir al Login</a></p>";
echo "<p><a href='/SIGAE/twofactor/configurar.php'>Ir a configurar 2FA</a></p>";
?>