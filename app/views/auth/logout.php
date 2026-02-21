<?php
session_start();
session_destroy();
header('Location: /SIGAE/app/views/auth/login.php');
exit;
?>