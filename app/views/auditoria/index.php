<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Incluir funciones de permisos (ajusta la ruta si es necesario)
require_once '../../../app/helpers/functions.php';
// Solo administradores y control de estudio pueden ver auditoría
requerir_permiso(['administrador', 'control_estudio']);

require_once '../../../app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

// =============================================
// PROCESAR FILTROS
// =============================================
$filtro_usuario = $_GET['usuario'] ?? '';
$filtro_operacion = $_GET['operacion'] ?? '';
$filtro_tabla = $_GET['tabla'] ?? '';
$filtro_desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-30 days')); // Últimos 30 días por defecto
$filtro_hasta = $_GET['hasta'] ?? date('Y-m-d');

// =============================================
// CONSULTA CON FILTROS (PREPARADA)
// =============================================
$sql = "SELECT a.*, u.nombre_usuario 
        FROM auditoria a
        LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
        WHERE DATE(a.fecha_operacion) BETWEEN :desde AND :hasta";
$params = ['desde' => $filtro_desde, 'hasta' => $filtro_hasta];

if (!empty($filtro_usuario)) {
    $sql .= " AND u.nombre_usuario ILIKE :usuario"; // ILIKE para búsqueda insensible a mayúsculas en PostgreSQL
    $params['usuario'] = "%$filtro_usuario%";
}
if (!empty($filtro_operacion)) {
    $sql .= " AND a.operacion = :operacion";
    $params['operacion'] = $filtro_operacion;
}
if (!empty($filtro_tabla)) {
    $sql .= " AND a.tabla_afectada = :tabla";
    $params['tabla'] = $filtro_tabla;
}

$sql .= " ORDER BY a.fecha_operacion DESC LIMIT 1000"; // Máximo 1000 registros para no saturar

$stmt = $db->prepare($sql);
$stmt->execute($params);
$auditorias = $stmt->fetchAll();

// =============================================
// OBTENER LISTAS ÚNICAS PARA LOS FILTROS (operaciones y tablas)
// =============================================
$operaciones = $db->query("SELECT DISTINCT operacion FROM auditoria ORDER BY operacion")->fetchAll(PDO::FETCH_COLUMN);
$tablas = $db->query("SELECT DISTINCT tabla_afectada FROM auditoria WHERE tabla_afectada IS NOT NULL ORDER BY tabla_afectada")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Auditoría del Sistema</title>
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
        .navbar h1 { 
            font-size: 24px; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar a {
            color: #1a3e60;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.2s;
            font-weight: 500;
        }
        .navbar a:hover { 
            background: #1a3e60; 
            color: white;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* TARJETA DE FILTROS */
        .filtros-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .filtro-grupo {
            display: flex;
            flex-direction: column;
        }
        .filtro-grupo label {
            font-size: 12px;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .filtro-grupo input,
        .filtro-grupo select,
        .filtro-grupo button {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            width: 100%;
            transition: all 0.2s;
        }
        .filtro-grupo input:focus,
        .filtro-grupo select:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .filtro-grupo button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }
        .filtro-grupo button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .btn-limpiar {
            background: #6c757d !important;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        
        /* TABLA DE AUDITORÍA */
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #1a3e60;
            color: white;
            font-weight: 600;
            padding: 12px;
            text-align: left;
            white-space: nowrap;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            white-space: nowrap;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .json-details {
            max-width: 300px;
            overflow-x: auto;
        }
        .json-details summary {
            cursor: pointer;
            color: #1a3e60;
            font-weight: bold;
        }
        .json-details pre {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
            font-size: 11px;
            border: 1px solid #ddd;
            margin-top: 5px;
        }
        .alert {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 15px;
            color: #666;
            font-size: 18px;
            border: 2px dashed #ccc;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📋 SIGAE - Registro de Auditoría</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <!-- FILTROS -->
        <div class="filtros-card">
            <form method="GET" action="">
                <div class="filtros-grid">
                    <div class="filtro-grupo">
                        <label>🔍 USUARIO</label>
                        <input type="text" name="usuario" placeholder="Nombre de usuario" value="<?= htmlspecialchars($filtro_usuario) ?>">
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>📌 OPERACIÓN</label>
                        <select name="operacion">
                            <option value="">Todas</option>
                            <?php foreach ($operaciones as $op): ?>
                                <option value="<?= $op ?>" <?= $filtro_operacion == $op ? 'selected' : '' ?>><?= $op ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>📚 TABLA</label>
                        <select name="tabla">
                            <option value="">Todas</option>
                            <?php foreach ($tablas as $t): ?>
                                <option value="<?= $t ?>" <?= $filtro_tabla == $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>📅 DESDE</label>
                        <input type="date" name="desde" value="<?= $filtro_desde ?>">
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>📅 HASTA</label>
                        <input type="date" name="hasta" value="<?= $filtro_hasta ?>">
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>&nbsp;</label>
                        <button type="submit">🔍 FILTRAR</button>
                    </div>
                    
                    <div class="filtro-grupo">
                        <label>&nbsp;</label>
                        <a href="?" class="btn-limpiar" style="padding: 12px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; text-align: center;">🗑️ LIMPIAR</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- TABLA DE RESULTADOS -->
        <div class="table-container">
            <h2 style="color: #1a3e60; margin-bottom: 20px;">📋 REGISTROS DE AUDITORÍA</h2>
            
            <?php if (empty($auditorias)): ?>
                <div class="alert">
                    📭 No se encontraron registros con los filtros aplicados.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>FECHA/HORA</th>
                            <th>USUARIO</th>
                            <th>OPERACIÓN</th>
                            <th>TABLA</th>
                            <th>ID REGISTRO</th>
                            <th>IP</th>
                            <th>VALORES ANTERIORES</th>
                            <th>VALORES NUEVOS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditorias as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['fecha_operacion']) ?></td>
                            <td><?= htmlspecialchars($log['nombre_usuario'] ?? 'Sistema') ?></td>
                            <td>
                                <?php
                                // Asignar color según operación
                                $clase = 'badge-info';
                                if (strpos($log['operacion'], 'EXITOSO') !== false) $clase = 'badge-success';
                                elseif (strpos($log['operacion'], 'FALLIDO') !== false || $log['operacion'] == 'ACCESO_DENEGADO') $clase = 'badge-danger';
                                elseif ($log['operacion'] == 'LOGIN_2FA_PENDIENTE') $clase = 'badge-warning';
                                ?>
                                <span class="badge <?= $clase ?>"><?= htmlspecialchars($log['operacion']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($log['tabla_afectada'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($log['id_registro_afectado'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($log['direccion_ip'] ?? '-') ?></td>
                            <td class="json-details">
                                <?php if ($log['valores_anteriores']): ?>
                                    <details>
                                        <summary>🔍 Ver anterior</summary>
                                        <pre><?php
                                            $anterior = json_decode($log['valores_anteriores'], true);
                                            echo htmlspecialchars(json_encode($anterior, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                        ?></pre>
                                    </details>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="json-details">
                                <?php if ($log['valores_nuevos']): ?>
                                    <details>
                                        <summary>📝 Ver nuevo</summary>
                                        <pre><?php
                                            $nuevo = json_decode($log['valores_nuevos'], true);
                                            echo htmlspecialchars(json_encode($nuevo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                        ?></pre>
                                    </details>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>