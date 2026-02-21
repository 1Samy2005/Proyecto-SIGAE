<?php
$password = 'Admin2026!';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "<h1>🔐 HASH GENERADO PARA Admin2026!</h1>";
echo "<p><strong>Hash:</strong> " . $hash . "</p>";
echo "<p style='background: #f0f0f0; padding: 15px; font-family: monospace;'>" . $hash . "</p>";
echo "<hr>";
echo "<h3>📋 COPIA ESTE HASH EXACTAMENTE:</h3>";
echo "<textarea style='width: 100%; height: 100px; font-family: monospace;' onclick='this.select()'>" . $hash . "</textarea>";
?>