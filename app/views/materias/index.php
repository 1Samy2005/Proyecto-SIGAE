<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Solo administradores y control de estudio pueden gestionar materias
if (!in_array($_SESSION['rol'], ['administrador', 'control_estudio'])) {
    header('Location: ../../../dashboard.php');
    exit;
}

require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

$db = Database::getInstance();

// =============================================
// AGREGAR MATERIA
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $codigo = strtoupper(trim($_POST['codigo']));
    $nombre = strtoupper(trim($_POST['nombre']));
    $area_conocimiento = $_POST['area_conocimiento']; // Cambia según el nombre real

    // Validar formato del código
    if (!preg_match('/^[A-Z]{3,4}-\d{3}$/', $codigo)) {
        $error = "❌ El código debe tener el formato: 3-4 letras + guión + 3 números (ej: CAS-001)";
    } else {
        try {
            $stmt = $db->prepare(
                "INSERT INTO materias (codigo_materia, nombre_materia, area_conocimiento, estatus) 
                 VALUES (:codigo, :nombre, :area, 'activa')"
            );
            $stmt->execute([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'area' => $area_conocimiento
            ]);
            header('Location: index.php?success=1');
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 23505) {
                $error = "❌ El código '$codigo' ya está registrado.";
            } else {
                $error = "❌ Error al agregar: " . $e->getMessage();
            }
        }
    }
}

// =============================================
// EDITAR MATERIA
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $codigo = strtoupper(trim($_POST['codigo']));
    $nombre = strtoupper(trim($_POST['nombre']));
    $area_conocimiento = $_POST['area_conocimiento'];

    if (!preg_match('/^[A-Z]{3,4}-\d{3}$/', $codigo)) {
        $error = "❌ El código debe tener el formato: 3-4 letras + guión + 3 números (ej: CAS-001)";
    } else {
        try {
            $stmt = $db->prepare(
                "UPDATE materias SET 
                    codigo_materia = :codigo, 
                    nombre_materia = :nombre, 
                    area_conocimiento = :area 
                 WHERE id_materia = :id"
            );
            $stmt->execute([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'area' => $area_conocimiento,
                'id' => $id
            ]);
            header('Location: index.php?success=2');
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 23505) {
                $error = "❌ El código '$codigo' ya está registrado por otra materia.";
            } else {
                $error = "❌ Error al actualizar: " . $e->getMessage();
            }
        }
    }
}

// =============================================
// ELIMINAR MATERIA
// =============================================
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $db->prepare("DELETE FROM materias WHERE id_materia = :id");
    $stmt->execute(['id' => $id]);
    header('Location: index.php?success=3');
    exit;
}

// =============================================
// OBTENER LISTA DE MATERIAS
// =============================================
$materias = $db->query("SELECT * FROM materias ORDER BY codigo_materia")->fetchAll();

// Obtener materia para edición
$editar_materia = null;
if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM materias WHERE id_materia = :id");
    $stmt->execute(['id' => $_GET['editar']]);
    $editar_materia = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGAE - Gestión de Materias</title>
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            border-bottom: 3px solid #1a3e60;
            padding-bottom: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        input, select, button {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            width: 100%;
        }
        input:focus, select:focus {
            border-color: #667eea;
            outline: none;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover { transform: translateY(-2px); }
        .btn-icon {
            text-decoration: none;
            margin: 0 8px;
            color: #1a3e60;
            font-size: 20px;
            transition: color 0.2s, transform 0.2s;
            display: inline-block;
        }
        .btn-icon:hover {
            color: #667eea;
            transform: scale(1.1);
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
        tr:hover { background: #f5f5f5; }
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .tooltip-personalizado {
            position: absolute;
            background: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            pointer-events: none;
            z-index: 1000;
            opacity: 0.9;
            white-space: nowrap;
        }
        .info-code {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📚 SIGAE - Gestión de Materias</h1>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="../../../dashboard.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert-success">
                <?php 
                $msgs = [
                    1 => '✅ Materia agregada correctamente',
                    2 => '✅ Materia actualizada correctamente',
                    3 => '✅ Materia eliminada correctamente'
                ];
                echo $msgs[$_GET['success']] ?? 'Operación exitosa';
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- FORMULARIO AGREGAR/EDITAR -->
        <div class="card">
            <h2><?= $editar_materia ? '✏️ EDITAR MATERIA' : '➕ AGREGAR NUEVA MATERIA' ?></h2>
            <form method="POST">
                <?php if ($editar_materia): ?>
                    <input type="hidden" name="id" value="<?= $editar_materia['id_materia'] ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div>
                        <input type="text" name="codigo" placeholder="Código (ej: CAS-001)" 
                               value="<?= htmlspecialchars($editar_materia['codigo_materia'] ?? '') ?>" 
                               pattern="[A-Za-z]{3,4}-\d{3}" 
                               title="Formato: 3-4 letras + guión + 3 números (ej: CAS-001)" 
                               required>
                        <div class="info-code">Formato: 3-4 letras + guión + 3 números (Ej: CAS-001, BIO-001, GRCP-001)</div>
                    </div>
                    
                    <input type="text" name="nombre" placeholder="Nombre de la materia" 
                           value="<?= htmlspecialchars($editar_materia['nombre_materia'] ?? '') ?>" required>
                    
                    <select name="area_conocimiento" required> <!-- Cambia el name según la columna real -->
                        <option value="">-- SELECCIONE ÁREA --</option>
                        <option value="Humanidades" <?= ($editar_materia && ($editar_materia['area_conocimiento'] ?? '') == 'Humanidades') ? 'selected' : '' ?>>Humanidades</option>
                        <option value="Idiomas" <?= ($editar_materia && ($editar_materia['area_conocimiento'] ?? '') == 'Idiomas') ? 'selected' : '' ?>>Idiomas</option>
                        <option value="Ciencias Básicas" <?= ($editar_materia && ($editar_materia['area_conocimiento'] ?? '') == 'Ciencias Básicas') ? 'selected' : '' ?>>Ciencias Básicas</option>
                        <option value="Ciencias Naturales" <?= ($editar_materia && ($editar_materia['area_conocimiento'] ?? '') == 'Ciencias Naturales') ? 'selected' : '' ?>>Ciencias Naturales</option>
                        <option value="Deportes" <?= ($editar_materia && ($editar_materia['area_conocimiento'] ?? '') == 'Deportes') ? 'selected' : '' ?>>Deportes</option>
                        <option value="Arte" <?= ($editar_materia && ($editar_materia['area_conocimiento'] ?? '') == 'Arte') ? 'selected' : '' ?>>Arte</option>
                        <option value="Tecnología" <?= ($editar_materia && ($editar_materia['area_conocimiento'] ?? '') == 'Tecnología') ? 'selected' : '' ?>>Tecnología</option>
                    </select>
                    
                    <div>
                        <?php if ($editar_materia): ?>
                            <button type="submit" name="editar">✏️ ACTUALIZAR</button>
                        <?php else: ?>
                            <button type="submit" name="agregar">➕ GUARDAR</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- LISTA DE MATERIAS -->
        <div class="card">
            <h2>📋 LISTA DE MATERIAS</h2>
            <table>
                <thead>
                    <tr>
                        <th>CÓDIGO</th>
                        <th>MATERIA</th>
                        <th>ÁREA</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($materias)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px;">📭 No hay materias registradas</td></tr>
                    <?php else: ?>
                        <?php foreach ($materias as $m): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($m['codigo_materia'] ?? '') ?></strong></td>
                            <td><?= htmlspecialchars($m['nombre_materia'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['area_conocimiento'] ?? 'Sin área') ?></td> <!-- Cambia aquí -->
                            <td>
                                <!-- Icono de editar -->
                                <a href="?editar=<?= $m['id_materia'] ?>" class="btn-icon" title="Editar materia">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <!-- Icono de eliminar -->
                                <a href="?eliminar=<?= $m['id_materia'] ?>" class="btn-icon" 
                                   title="Modificar materia" 
                                   onclick="return confirm('¿ELIMINAR MATERIA?\n\n<?= htmlspecialchars($m['codigo_materia'] ?? '') ?> - <?= htmlspecialchars($m['nombre_materia'] ?? '') ?>\n\nEsta acción puede afectar calificaciones asociadas.')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // =============================================
        // TOOLTIPS PERSONALIZADOS
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const iconos = document.querySelectorAll('.btn-icon');
            iconos.forEach(icono => {
                icono.addEventListener('mouseenter', function(e) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip-personalizado';
                    tooltip.textContent = this.getAttribute('title');
                    document.body.appendChild(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
                    
                    this.addEventListener('mouseleave', function() {
                        tooltip.remove();
                    }, { once: true });
                });
            });
        });
    </script>
</body>
</html>