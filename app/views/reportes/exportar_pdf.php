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
// OBTENER DATOS DE LA SECCIÓN (CONSULTA PREPARADA)
// =============================================
$stmt_seccion = $db->prepare("SELECT nombre_seccion FROM secciones WHERE id_seccion = :seccion");
$stmt_seccion->execute(['seccion' => $seccion]);
$seccion_info = $stmt_seccion->fetch(PDO::FETCH_ASSOC);

// =============================================
// CARGAR LOGO Y CONVERTIR A BASE64
// =============================================
$logoPath = 'C:/xampp/htdocs/SIGAE/public/assets/img/logo-escuela.png';
$logoSrc = '';

if (file_exists($logoPath) && is_readable($logoPath)) {
    $logoData = file_get_contents($logoPath);
    if ($logoData !== false) {
        $logoBase64 = base64_encode($logoData);
        $logoSrc = 'data:image/png;base64,' . $logoBase64;
    } else {
        error_log("exportar_pdf.php: No se pudo leer el archivo del logo: $logoPath");
    }
} else {
    error_log("exportar_pdf.php: El archivo del logo no existe o no es legible: $logoPath");
}

// =============================================
// GENERAR HTML PARA EL PDF
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
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #1a3e60;
        }
        .logo {
            width: 80px;
            height: auto;
        }
        .titulo h1 {
            color: #1a3e60;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .titulo h2 {
            color: #666;
            font-size: 18px;
            font-weight: normal;
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
    <div class="header">';

// Agregar el logo si se pudo cargar
if ($logoSrc) {
    $html .= '<img src="' . $logoSrc . '" class="logo" alt="Logo U.E.N. José Agustín Marquiegui">';
} else {
    $html .= '<div style="width:80px; height:80px; background:#f0f0f0; display:flex; align-items:center; justify-content:center;">[LOGO]</div>';
}

$html .= '
        <div class="titulo">
            <h1>U.E.N. JOSÉ AGUSTÍN MARQUIEGÜI</h1>
            <h2>SISTEMA DE GESTIÓN ACADÉMICA - SIGAE</h2>
            <h2>REPORTE DE RENDIMIENTO ACADÉMICO</h2>
        </div>
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
// REPORTE POR MATERIAS (CORREGIDO CON INSCRIPCIONES)
// =============================================
if ($tipo == 'materias') {
    $stmt = $db->prepare(
        "SELECT 
            m.codigo_materia,
            m.nombre_materia,
            COUNT(c.id_calificacion) AS total_notas,
            ROUND(AVG(c.valor_calificacion), 2) AS promedio,
            ROUND(MIN(c.valor_calificacion), 2) AS minima,
            ROUND(MAX(c.valor_calificacion), 2) AS maxima
         FROM calificaciones c
         JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
         JOIN materias m ON c.id_materia = m.id_materia
         WHERE i.id_seccion = :seccion 
           AND i.anio_escolar = :periodo
           AND c.periodo_academico = :periodo
         GROUP BY m.id_materia, m.codigo_materia, m.nombre_materia
         ORDER BY promedio DESC"
    );
    $stmt->execute(['seccion' => $seccion, 'periodo' => $periodo]);
    $data = $stmt->fetchAll();
    
    $html .= '<h2 style="color: #1a3e60; margin-top: 30px;">PROMEDIOS POR MATERIA</h2>';
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
                    <td><strong>' . htmlspecialchars($row['codigo_materia']) . '</strong></td>
                    <td>' . htmlspecialchars($row['nombre_materia']) . '</td>
                    <td style="text-align: center;">' . (int)$row['total_notas'] . '</td>
                    <td style="font-weight: bold; color: #1a3e60;">' . number_format($row['promedio'], 2) . '</td>
                    <td>' . number_format($row['minima'], 2) . '</td>
                    <td>' . number_format($row['maxima'], 2) . '</td>
                  </tr>';
    }
    
    $html .= '</tbody></table>';
}

// =============================================
// REPORTE COMPARATIVO (CORREGIDO CON INSCRIPCIONES)
// =============================================
if ($tipo == 'comparativa') {
    $stmt = $db->prepare(
        "SELECT 
            i.anio_escolar AS periodo_academico,
            COUNT(DISTINCT i.id_estudiante) AS estudiantes,
            COUNT(c.id_calificacion) AS calificaciones,
            ROUND(AVG(c.valor_calificacion), 2) AS promedio
         FROM calificaciones c
         JOIN inscripciones i ON c.id_inscripcion = i.id_inscripcion
         WHERE i.id_seccion = :seccion 
           AND i.anio_escolar IN (:periodo1, :periodo2)
           AND c.periodo_academico = i.anio_escolar
         GROUP BY i.anio_escolar
         ORDER BY i.anio_escolar DESC"
    );
    $stmt->execute([
        'seccion' => $seccion,
        'periodo1' => $periodo1,
        'periodo2' => $periodo2
    ]);
    $data = $stmt->fetchAll();
    
    $html .= '<h2 style="color: #1a3e60; margin-top: 30px;">COMPARATIVA ENTRE PERÍODOS</h2>';
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
                    <td><strong>' . htmlspecialchars($row['periodo_academico']) . '</strong></td>
                    <td style="text-align: center;">' . (int)$row['estudiantes'] . '</td>
                    <td style="text-align: center;">' . (int)$row['calificaciones'] . '</td>
                    <td style="font-weight: bold; color: #1a3e60;">' . number_format($row['promedio'], 2) . '</td>
                  </tr>';
    }
    
    $html .= '</tbody></table>';
}

// =============================================
// FOOTER
// =============================================
$html .= '<div class="footer">
            <p>Este reporte fue generado automáticamente por el Sistema SIGAE</p>
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
$options->set('isRemoteEnabled', true);

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