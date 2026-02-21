<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

// Obtener ID del estudiante (de la URL o del usuario logueado)
$id_estudiante = $_GET['id'] ?? $_SESSION['user_id'] ?? 0;

// =============================================
// DATOS DEL ESTUDIANTE
// =============================================
$estudiante = $db->query(
    "SELECT id_estudiante, cedula, nombres, apellidos,
            fecha_nacimiento, lugar_nacimiento,
            pais_nacimiento, estado_nacimiento, municipio_nacimiento
     FROM estudiantes WHERE id_estudiante = :id",
    ['id' => $id_estudiante]
)->fetch();

if (!$estudiante) {
    die("<h2 style='color: red; text-align: center; margin-top: 50px;'>❌ Estudiante no encontrado</h2>");
}

// =============================================
// HISTORIAL DE INSCRIPCIONES
// =============================================
$inscripciones = $db->query(
    "SELECT i.id_inscripcion, i.anio_escolar, s.nombre_seccion, i.estatus_inscripcion
     FROM inscripciones i
     JOIN secciones s ON i.id_seccion = s.id_seccion
     WHERE i.id_estudiante = :id_estudiante
     ORDER BY i.anio_escolar DESC",
    ['id_estudiante' => $id_estudiante]
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Académica - <?= htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255,255,255,0.95);
            color: #1a3e60;
            padding: 15px 30px;
            margin: -20px -20px 20px -20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .navbar a {
            color: #1a3e60;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 5px;
        }
        .navbar a:hover { background: #1a3e60; color: white; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        h2 {
            color: #1a3e60;
            margin-bottom: 20px;
            font-size: 22px;
            border-bottom: 3px solid #1a3e60;
            padding-bottom: 10px;
        }
        
        .datos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
        .dato-item {
            display: flex;
            flex-direction: column;
        }
        .dato-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .dato-value {
            font-size: 16px;
            font-weight: bold;
            color: #1a3e60;
            margin-top: 5px;
        }
        
        .anio-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid #1a3e60;
            cursor: pointer;
            transition: all 0.2s;
        }
        .anio-card:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        .anio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .anio-header h3 {
            color: #1a3e60;
            margin-bottom: 5px;
        }
        .estatus {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .estatus-activo { background: #28a745; color: white; }
        .estatus-graduado { background: #ffc107; color: #333; }
        .estatus-retirado { background: #dc3545; color: white; }
        
        .calificaciones {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .calificaciones.mostrar { display: block; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
        .nota {
            font-weight: bold;
            color: #1a3e60;
            font-size: 16px;
        }
        .btn {
            background: #1a3e60;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
        .btn:hover { background: #2c5a7a; }
        .loader {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .error-mensaje {
            color: #dc3545;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📚 SIGAE - Historia Académica</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <!-- DATOS DEL ESTUDIANTE -->
        <div class="card">
            <h2>🎓 DATOS DEL ESTUDIANTE</h2>
            <div class="datos-grid">
                <div class="dato-item">
                    <span class="dato-label">Cédula</span>
                    <span class="dato-value"><?= htmlspecialchars($estudiante['cedula']) ?></span>
                </div>
                <div class="dato-item">
                    <span class="dato-label">Nombre Completo</span>
                    <span class="dato-value"><?= htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']) ?></span>
                </div>
                <div class="dato-item">
                    <span class="dato-label">Fecha de Nacimiento</span>
                    <span class="dato-value"><?= date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) ?></span>
                </div>
                <div class="dato-item">
                    <span class="dato-label">Lugar de Nacimiento</span>
                    <span class="dato-value"><?= htmlspecialchars($estudiante['lugar_nacimiento'] ?? 'No registrado') ?></span>
                </div>
                <div class="dato-item">
                    <span class="dato-label">País / Estado / Municipio</span>
                    <span class="dato-value">
                        <?= htmlspecialchars($estudiante['pais_nacimiento'] ?? 'Venezuela') ?> / 
                        <?= htmlspecialchars($estudiante['estado_nacimiento'] ?? 'N/D') ?> / 
                        <?= htmlspecialchars($estudiante['municipio_nacimiento'] ?? 'N/D') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- HISTORIAL ACADÉMICO -->
        <div class="card">
            <h2>📋 HISTORIAL DE ESTUDIOS</h2>
            
            <?php if (empty($inscripciones)): ?>
                <p style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 10px;">
                    📭 No hay inscripciones registradas para este estudiante.
                </p>
            <?php else: ?>
                <?php foreach ($inscripciones as $ins): ?>
                <div class="anio-card" onclick="cargarCalificaciones(<?= $ins['id_inscripcion'] ?>, this)">
                    <div class="anio-header">
                        <div>
                            <h3>📅 Año Escolar: <?= htmlspecialchars($ins['anio_escolar']) ?></h3>
                            <p>Sección: <?= htmlspecialchars($ins['nombre_seccion']) ?></p>
                        </div>
                        <div>
                            <span class="estatus estatus-<?= $ins['estatus_inscripcion'] ?>">
                                <?= strtoupper($ins['estatus_inscripcion']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="calificaciones" id="calif-<?= $ins['id_inscripcion'] ?>"></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a href="javascript:history.back()" class="btn">⬅️ Regresar</a>
    </div>

    <script>
        async function cargarCalificaciones(inscripcionId, elemento) {
            const div = document.getElementById('calif-' + inscripcionId);
            
            // Si ya está visible, la ocultamos
            if (div.classList.contains('mostrar')) {
                div.classList.remove('mostrar');
                return;
            }

            // Si no tiene contenido, lo cargamos
            if (div.innerHTML === '') {
                div.innerHTML = '<div class="loader">Cargando calificaciones...</div>';
                
                try {
                    const response = await fetch(`get_calificaciones.php?inscripcion=${inscripcionId}`);
                    const data = await response.json();
                    
                    if (data.error) {
                        div.innerHTML = `<div class="error-mensaje">${data.error}</div>`;
                    } else if (data.length === 0) {
                        div.innerHTML = '<div style="text-align: center; padding: 20px;">No hay calificaciones registradas.</div>';
                    } else {
                        let html = '<table><tr><th>Materia</th><th>Período</th><th>Tipo</th><th>Nota</th></tr>';
                        data.forEach(c => {
                            html += `<tr>
                                <td>${c.codigo_materia} - ${c.nombre_materia}</td>
                                <td>${c.nombre_periodo || 'N/D'}</td>
                                <td>${c.tipo_evaluacion}</td>
                                <td class="nota">${parseFloat(c.valor_calificacion).toFixed(2)}</td>
                            </tr>`;
                        });
                        html += '</table>';
                        div.innerHTML = html;
                    }
                } catch (error) {
                    div.innerHTML = '<div class="error-mensaje">Error al cargar calificaciones.</div>';
                }
            }
            
            div.classList.add('mostrar');
        }
    </script>
</body>
</html>