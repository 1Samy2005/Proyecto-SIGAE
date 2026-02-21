<?php
$rutas = [
    'C:/xampp/htdocs/SIGAE/public/assets/img/logo-colegio.png',
    'C:/xampp/htdocs/SIGAE/public/assets/img/logo-escuela.png'
];

foreach ($rutas as $ruta) {
    if (file_exists($ruta)) {
        $tipo = pathinfo($ruta, PATHINFO_EXTENSION);
        $datos = file_get_contents($ruta);
        $base64 = 'data:image/' . $tipo . ';base64,' . base64_encode($datos);
        echo "<h3>✅ Logo encontrado: $ruta</h3>";
        echo "<textarea style='width:100%; height:100px;' onclick='this.select()'>$base64</textarea><br><br>";
    } else {
        echo "<h3>❌ No existe: $ruta</h3>";
    }
}
?>