@"
<?php
session_start();
if (!isset(`$_SESSION['user_id'])) {
    header('Location: ../app/views/auth/login.php');
    exit;
}

require_once '../app/controllers/TwoFactorController.php';

`$controller = new TwoFactorController();
`$controller->activar();
?>
"@ | Out-File -FilePath "C:\xampp\htdocs\SIGAE\twofactor\activar.php" -Encoding UTF8