<?php
require_once 'C:/xampp/htdocs/SIGAE/vendor/autoload.php';
require_once 'C:/xampp/htdocs/SIGAE/app/core/Database.php';

use RobThree\Auth\TwoFactorAuth;
use App\Core\Database;

class TwoFactorController {
    private $tfa;
    private $db;
    private $appName = 'SIGAE - U.E.N. José Agustín Marquiegüi';
    
    public function __construct() {
        $this->tfa = new TwoFactorAuth($this->appName);
        $this->db = Database::getInstance();
    }
    
    // MÉTODO PARA VERIFICAR CÓDIGO
    public function verificarCodigo($userId, $codigo) {
        try {
            $stmt = $this->db->query(
                "SELECT tfa_secreto FROM usuarios WHERE id_usuario = :id",
                ['id' => $userId]
            );
            $usuario = $stmt->fetch();
            
            if (!$usuario || !$usuario['tfa_secreto']) {
                return false;
            }
            
            return $this->tfa->verifyCode($usuario['tfa_secreto'], $codigo);
        } catch (Exception $e) {
            error_log("Error en verificarCodigo: " . $e->getMessage());
            return false;
        }
    }
    
    // CONFIGURAR 2FA (VERSIÓN CORREGIDA)
   public function configurar() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /SIGAE/app/views/auth/login.php');
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    
    // Generar nuevo secreto (forzar incluso si ya existe)
    $secreto = $this->tfa->createSecret();
    $_SESSION['tfa_temp_secret'] = $secreto;
    $qrCode = $this->tfa->getQRCodeImageAsDataUri($username, $secreto);
    
    $this->mostrarConfiguracion($qrCode, $secreto, $username);
}
    
    // ACTIVAR 2FA
    public function activar() {
        session_start();
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['tfa_temp_secret'])) {
            header('Location: /SIGAE/app/views/auth/login.php');
            exit;
        }
        
        $codigo = $_POST['codigo'] ?? '';
        $secreto = $_SESSION['tfa_temp_secret'];
        
        if ($this->tfa->verifyCode($secreto, $codigo)) {
            $codigosRecuperacion = [];
            for ($i = 0; $i < 10; $i++) {
                $codigosRecuperacion[] = strtoupper(bin2hex(random_bytes(4)));
            }
            
            $this->db->query(
                "UPDATE usuarios SET 
                 tfa_secreto = :secreto, 
                 tfa_activo = true, 
                 tfa_fecha_activacion = NOW(),
                 tfa_codigos_recuperacion = :codigos
                 WHERE id_usuario = :id",
                [
                    'secreto' => $secreto,
                    'codigos' => '{' . implode(',', $codigosRecuperacion) . '}',
                    'id' => $_SESSION['user_id']
                ]
            );
            
            unset($_SESSION['tfa_temp_secret']);
            $this->mostrarCodigosRecuperacion($codigosRecuperacion);
        } else {
            $_SESSION['tfa_error'] = 'Código incorrecto';
            header('Location: /SIGAE/twofactor/configurar.php');
        }
        exit;
    }
    
    // DESACTIVAR 2FA
    public function desactivar() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /SIGAE/app/views/auth/login.php');
            exit;
        }
        
        $codigo = $_POST['codigo'] ?? '';
        $userId = $_SESSION['user_id'];
        
        $stmt = $this->db->query(
            "SELECT tfa_secreto FROM usuarios WHERE id_usuario = :id",
            ['id' => $userId]
        );
        $usuario = $stmt->fetch();
        
        if ($this->tfa->verifyCode($usuario['tfa_secreto'], $codigo)) {
            $this->db->query(
                "UPDATE usuarios SET 
                 tfa_activo = false, 
                 tfa_secreto = NULL,
                 tfa_codigos_recuperacion = NULL 
                 WHERE id_usuario = :id",
                ['id' => $userId]
            );
            header('Location: /SIGAE/dashboard.php?tfa=desactivado');
        } else {
            $_SESSION['tfa_error'] = 'Código incorrecto';
            header('Location: /SIGAE/twofactor/configurar.php?error=1');
        }
        exit;
    }
    
    // VERIFICAR LOGIN
    public function verificarLogin() {
        session_start();
        if (!isset($_SESSION['2fa_user_id'])) {
            header('Location: /SIGAE/app/views/auth/login.php');
            exit;
        }
        $this->mostrarVerificacion();
    }
    
    // USAR CÓDIGO DE RECUPERACIÓN
    public function usarCodigoRecuperacion() {
        session_start();
        if (!isset($_SESSION['2fa_user_id'])) {
            header('Location: /SIGAE/app/views/auth/login.php');
            exit;
        }
        
        $codigo = $_POST['codigo_recuperacion'] ?? '';
        $userId = $_SESSION['2fa_user_id'];
        
        $stmt = $this->db->query(
            "SELECT tfa_codigos_recuperacion FROM usuarios WHERE id_usuario = :id",
            ['id' => $userId]
        );
        $usuario = $stmt->fetch();
        
        $codigos = $usuario['tfa_codigos_recuperacion'] ?? '{}';
        $codigos = trim($codigos, '{}');
        $codigosArray = $codigos ? explode(',', $codigos) : [];
        
        if (in_array($codigo, $codigosArray)) {
            $nuevosCodigos = array_diff($codigosArray, [$codigo]);
            $this->db->query(
                "UPDATE usuarios SET tfa_codigos_recuperacion = :codigos WHERE id_usuario = :id",
                [
                    'codigos' => '{' . implode(',', $nuevosCodigos) . '}',
                    'id' => $userId
                ]
            );
            
            $_SESSION['user_id'] = $userId;
            unset($_SESSION['2fa_user_id']);
            header('Location: /SIGAE/dashboard.php?tfa=recuperado');
        } else {
            $error = 'Código de recuperación inválido';
            $this->mostrarVerificacion($error);
        }
        exit;
    }
    
    // VISTAS
    private function mostrarConfiguracion($qrCode, $secreto, $username) {
        echo "<!DOCTYPE html><html><head><title>Configurar 2FA</title></head><body>";
        echo "<h1>Configurar 2FA</h1>";
        echo "<img src='$qrCode' style='max-width:200px;'><br>";
        echo "<p>Secreto: <strong>$secreto</strong></p>";
        echo "<form action='/SIGAE/twofactor/activar.php' method='POST'>";
        echo "<input type='text' name='codigo' placeholder='Código 2FA' required>";
        echo "<button type='submit'>Activar</button>";
        echo "</form>";
        echo "<a href='/SIGAE/dashboard.php'>Cancelar</a>";
        echo "</body></html>";
    }
    
    private function mostrarDesactivar($userId, $username) {
        echo "<!DOCTYPE html><html><head><title>Desactivar 2FA</title></head><body>";
        echo "<h1>Desactivar 2FA</h1>";
        echo "<form action='/SIGAE/twofactor/desactivar.php' method='POST'>";
        echo "<input type='text' name='codigo' placeholder='Código 2FA' required>";
        echo "<button type='submit'>Desactivar</button>";
        echo "</form>";
        echo "<a href='/SIGAE/dashboard.php'>Cancelar</a>";
        echo "</body></html>";
    }
    
    private function mostrarVerificacion($error = '') {
        echo "<!DOCTYPE html><html><head><title>Verificación 2FA</title></head><body>";
        echo "<h1>Verificación 2FA</h1>";
        if ($error) echo "<p style='color:red;'>$error</p>";
        echo "<form action='/SIGAE/twofactor/verificar.php' method='POST'>";
        echo "<input type='text' name='codigo' placeholder='Código 2FA' required>";
        echo "<button type='submit'>Verificar</button>";
        echo "</form>";
        echo "<a href='/SIGAE/twofactor/usar_recuperacion.php'>Usar código de recuperación</a>";
        echo "</body></html>";
    }
    
    private function mostrarCodigosRecuperacion($codigos) {
        echo "<!DOCTYPE html><html><head><title>Códigos de Recuperación</title></head><body>";
        echo "<h1>Códigos de Recuperación</h1>";
        echo "<p>Guarda estos códigos en un lugar seguro:</p>";
        echo "<ul>";
        foreach ($codigos as $codigo) {
            echo "<li><strong>$codigo</strong></li>";
        }
        echo "</ul>";
        echo "<a href='/SIGAE/dashboard.php'>Ir al Dashboard</a>";
        echo "</body></html>";
    }
}
?>