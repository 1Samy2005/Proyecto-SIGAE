<?php
// Ruta de tu logo (cámbiala si es .jpg)
$ruta_logo = 'C:/xampp/htdocs/SIGAE/public/assets/img/logo-escuela.png';

// Verificar si el archivo existe
if (!file_exists($ruta_logo)) {
    die("❌ EL ARCHIVO NO EXISTE EN: " . $ruta_logo);
}

// Obtener tipo de imagen (png, jpg, etc.)
$tipo = pathinfo($ruta_logo, PATHINFO_EXTENSION);

// Leer la imagen
$datos = file_get_contents($ruta_logo);

// Convertir a Base64
$base64 = 'data:image/' . $tipo . ';base64,' . base64_encode($datos);

// Mostrar resultado
echo "<h1>🔐 LOGO EN BASE64</h1>";
echo "<p><strong>Ruta del logo:</strong> $ruta_logo</p>";
echo "<p><strong>Tipo:</strong> $tipo</p>";
echo "<p><strong>Base64 generado:</strong></p>";
echo "<textarea style='width:100%; height:200px; font-family:monospace;' onclick='this.select()'>";
echo $base64;
echo "</textarea>";
echo "<hr>";
echo "<p>✅ Copia TODO el contenido del cuadro de arriba.</p>";
?>