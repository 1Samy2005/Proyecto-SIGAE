<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tfa_temp_secret'])) {
    header('Location: /SIGAE/app/views/auth/login.php');
    exit;
}
require_once '../app/controllers/TwoFactorController.php';
$controller = new TwoFactorController();
$controller->activar();
?>