<?php
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';
use App\Core\Database;

class AuditLogger
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Método privado para insertar un registro de auditoría.
     */
    private function registrar($operacion, $tabla = null, $id_registro = null, $valores_anteriores = null, $valores_nuevos = null, $id_usuario = null, $usuario_nombre = null, $ip = null, $userAgent = null)
    {
        // Si no se proporcionaron algunos datos, intentar obtener de la sesión o de la solicitud
        if (!$id_usuario && isset($_SESSION['user_id'])) {
            $id_usuario = $_SESSION['user_id'];
        }
        if (!$usuario_nombre && isset($_SESSION['username'])) {
            $usuario_nombre = $_SESSION['username'];
        }
        if (!$ip) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        if (!$userAgent) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }

        // Asegurar que los valores sean strings (si son arrays, convertirlos a JSON)
        if (is_array($valores_anteriores)) {
            $valores_anteriores = json_encode($valores_anteriores, JSON_UNESCAPED_UNICODE);
        }
        if (is_array($valores_nuevos)) {
            $valores_nuevos = json_encode($valores_nuevos, JSON_UNESCAPED_UNICODE);
        }

        // Usar consulta preparada
        $sql = "INSERT INTO auditoria 
                (tabla_afectada, operacion, id_registro_afectado, valores_anteriores, valores_nuevos, id_usuario, direccion_ip, user_agent) 
                VALUES 
                (:tabla, :operacion, :id_registro, :valores_anteriores, :valores_nuevos, :id_usuario, :ip, :user_agent)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tabla' => $tabla,
            'operacion' => $operacion,
            'id_registro' => $id_registro,
            'valores_anteriores' => $valores_anteriores,
            'valores_nuevos' => $valores_nuevos,
            'id_usuario' => $id_usuario,
            'ip' => $ip,
            'user_agent' => $userAgent
        ]);
    }

    // Métodos públicos para diferentes tipos de eventos

    public function logLoginExitoso($id_usuario, $usuario_nombre, $ip, $userAgent)
    {
        $this->registrar('LOGIN_EXITOSO', 'usuarios', $id_usuario, null, null, $id_usuario, $usuario_nombre, $ip, $userAgent);
    }

    public function logLoginFallido($usuario_intentado, $ip, $userAgent)
    {
        $valores_nuevos = json_encode(['username_intentado' => $usuario_intentado]);
        $this->registrar('LOGIN_FALLIDO', null, null, null, $valores_nuevos, null, null, $ip, $userAgent);
    }

    public function logLoginPendiente2FA($id_usuario, $usuario_nombre, $ip, $userAgent)
    {
        $this->registrar('LOGIN_2FA_PENDIENTE', 'usuarios', $id_usuario, null, null, $id_usuario, $usuario_nombre, $ip, $userAgent);
    }

    public function logInsert($tabla, $id_registro, $datos_nuevos, $id_usuario = null)
    {
        $this->registrar('INSERT', $tabla, $id_registro, null, $datos_nuevos, $id_usuario);
    }

    public function logUpdate($tabla, $id_registro, $datos_anteriores, $datos_nuevos, $id_usuario = null)
    {
        $this->registrar('UPDATE', $tabla, $id_registro, $datos_anteriores, $datos_nuevos, $id_usuario);
    }

    public function logDelete($tabla, $id_registro, $datos_anteriores, $id_usuario = null)
    {
        $this->registrar('DELETE', $tabla, $id_registro, $datos_anteriores, null, $id_usuario);
    }

    public function logAccesoDenegado($pagina_intentada, $id_usuario = null)
    {
        $valores_nuevos = json_encode(['pagina' => $pagina_intentada]);
        $this->registrar('ACCESO_DENEGADO', null, null, null, $valores_nuevos, $id_usuario);
    }
}
?>