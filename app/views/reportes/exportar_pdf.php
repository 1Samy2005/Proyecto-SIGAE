<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once 'C:/xampp/htdocs/SIGAE/vendor/autoload.php';
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Core\Database;

$db = Database::getInstance();

$tipo = $_GET['tipo'] ?? 'materias';
$seccion = $_GET['seccion'] ?? 0;
$periodo = $_GET['periodo'] ?? '2026-2027';
$periodo1 = $_GET['periodo1'] ?? '2026-2027';
$periodo2 = $_GET['periodo2'] ?? '2025-2026';

// =============================================
// OBTENER DATOS DE LA SECCIÓN
// =============================================
$seccion_info = $db->query(
    "SELECT nombre_seccion FROM secciones WHERE id_seccion = :seccion",
    ['seccion' => $seccion]
)->fetch();

// =============================================
// GENERAR HTML PARA EL PDF (CON LOGO INCRUSTADO EN BASE64)
// =============================================
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte SIGAE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            color: #333;
        }
        .header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #1a3e60;
        }
        .logo {
            width: 100px;
            height: auto;
            margin-bottom: 15px;
        }
        .institucion {
            color: #1a3e60;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }
        .sistema {
            color: #666;
            font-size: 16px;
            font-weight: normal;
            text-align: center;
            margin-bottom: 5px;
        }
        .reporte {
            color: #1a3e60;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-value {
            font-size: 18px;
            font-weight: bold;
            color: #1a3e60;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #1a3e60;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
   <!-- HEADER CON LOGO CENTRADO Y TEXTO INSTITUCIONAL -->
<div class="header">
   <img src="http://localhost/SIGAE/public/assets/img/logo-escuela.png" 
     class="logo" 
     alt="Logo U.E.N. José Agustín Marquiegüi">
      
    <div class="institucion">U.E.N. JOSÉ AGUSTÍN MARQUIEGÜI</div>
    <div class="sistema">SISTEMA DE GESTIÓN ACADÉMICA - SIGAE</div>
    <div class="reporte">REPORTE DE RENDIMIENTO ACADÉMICO</div>
</div>
    
    <div class="info-section">
        <div class="info-item">
            <span class="info-label">SECCIÓN</span>
            <span class="info-value">' . htmlspecialchars($seccion_info['nombre_seccion'] ?? '') . '</span>
        </div>
        <div class="info-item">
            <span class="info-label">PERÍODO</span>
            <span class="info-value">' . htmlspecialchars($periodo) . '</span>
        </div>
        <div class="info-item">
            <span class="info-label">FECHA DE GENERACIÓN</span>
            <span class="info-value">' . date('d/m/Y H:i:s') . '</span>
        </div>
        <div class="info-item">
            <span class="info-label">GENERADO POR</span>
            <span class="info-value">' . htmlspecialchars($_SESSION['username']) . '</span>
        </div>
    </div>';

// =============================================
// REPORTE POR MATERIAS
// =============================================
if ($tipo == 'materias') {
    $data = $db->query(
        "SELECT 
            m.codigo_materia,
            m.nombre_materia,
            COUNT(c.id_calificacion) AS total_notas,
            ROUND(AVG(c.valor_calificacion), 2) AS promedio,
            ROUND(MIN(c.valor_calificacion), 2) AS minima,
            ROUND(MAX(c.valor_calificacion), 2) AS maxima
         FROM materias m
         JOIN calificaciones c ON m.id_materia = c.id_materia
         JOIN estudiantes e ON c.id_estudiante = e.id_estudiante
         WHERE e.id_seccion = :seccion AND c.periodo_academico = :periodo
         GROUP BY m.id_materia, m.codigo_materia, m.nombre_materia
         ORDER BY promedio DESC",
        ['seccion' => $seccion, 'periodo' => $periodo]
    )->fetchAll();
    
    $html .= '<h2 style="color: #1a3e60; margin-top: 30px;">📚 PROMEDIOS POR MATERIA</h2>';
    $html .= '<table>
                <thead>
                    <tr>
                        <th>CÓDIGO</th>
                        <th>MATERIA</th>
                        <th>TOTAL NOTAS</th>
                        <th>PROMEDIO</th>
                        <th>MÍNIMA</th>
                        <th>MÁXIMA</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($data as $row) {
        $html .= '<tr>
                    <td><strong>' . $row['codigo_materia'] . '</strong></td>
                    <td>' . $row['nombre_materia'] . '</td>
                    <td style="text-align: center;">' . $row['total_notas'] . '</td>
                    <td style="font-weight: bold; color: #1a3e60;">' . number_format($row['promedio'], 2) . '</td>
                    <td>' . number_format($row['minima'], 2) . '</td>
                    <td>' . number_format($row['maxima'], 2) . '</td>
                  </tr>';
    }
    
    $html .= '</tbody></table>';
}

// =============================================
// REPORTE COMPARATIVO
// =============================================
if ($tipo == 'comparativa') {
    $data = $db->query(
        "SELECT 
            c.periodo_academico,
            COUNT(DISTINCT e.id_estudiante) AS estudiantes,
            COUNT(c.id_calificacion) AS calificaciones,
            ROUND(AVG(c.valor_calificacion), 2) AS promedio
         FROM estudiantes e
         JOIN calificaciones c ON e.id_estudiante = c.id_estudiante
         WHERE e.id_seccion = :seccion 
           AND c.periodo_academico IN (:periodo1, :periodo2)
         GROUP BY c.periodo_academico
         ORDER BY c.periodo_academico DESC",
        ['seccion' => $seccion, 'periodo1' => $periodo1, 'periodo2' => $periodo2]
    )->fetchAll();
    
    $html .= '<h2 style="color: #1a3e60; margin-top: 30px;">🔄 COMPARATIVA ENTRE PERÍODOS</h2>';
    $html .= '<table>
                <thead>
                    <tr>
                        <th>PERÍODO</th>
                        <th>TOTAL ESTUDIANTES</th>
                        <th>TOTAL CALIFICACIONES</th>
                        <th>PROMEDIO GENERAL</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($data as $row) {
        $html .= '<tr>
                    <td><strong>' . $row['periodo_academico'] . '</strong></td>
                    <td style="text-align: center;">' . $row['estudiantes'] . '</td>
                    <td style="text-align: center;">' . $row['calificaciones'] . '</td>
                    <td style="font-weight: bold; color: #1a3e60;">' . number_format($row['promedio'], 2) . '</td>
                  </tr>';
    }
    
    $html .= '</tbody></table>';
}

$html .= '<div class="footer">
            <p>📄 Este reporte fue generado automáticamente por el Sistema SIGAE</p>
            <p>U.E.N. José Agustín Marquiegüi - Caracas, Venezuela</p>
          </div>
        </body>
</html>';

// =============================================
// CONFIGURAR Y GENERAR PDF
// =============================================
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true); // ✅ PERMITE CARGAR IMÁGENES DESDE HTTP

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Nombre del archivo
$filename = "reporte_{$tipo}_" . date('Ymd_His') . ".pdf";

// Enviar PDF al navegador
$dompdf->stream($filename, array("Attachment" => true));
exit;
?>