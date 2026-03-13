<?php
/**
 * FUNCIONES DE VERIFICACIÓN DE PERMISOS
 */

function tiene_permiso($permiso) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['permisos'])) {
        return false;
    }
    if (in_array('*', $_SESSION['permisos'])) {
        return true;
    }
    return in_array($permiso, $_SESSION['permisos']);
}

function puede_gestionar_calificaciones() {
    $permisos_necesarios = ['gestion_calificaciones', 'registrar_calificaciones', '*'];
    foreach ($permisos_necesarios as $p) {
        if (tiene_permiso($p)) {
            return true;
        }
    }
    return false;
}

function puede_consultar_calificaciones() {
    $permisos_necesarios = ['consultar_calificaciones', 'gestion_calificaciones', '*'];
    foreach ($permisos_necesarios as $p) {
        if (tiene_permiso($p)) {
            return true;
        }
    }
    return false;
}

function requerir_permiso($permisos) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /SIGAE/auth/login.php');
        exit;
    }
    if (!is_array($permisos)) {
        $permisos = [$permisos];
    }
    foreach ($permisos as $p) {
        if (tiene_permiso($p)) {
            return;
        }
    }
    header('Location: /SIGAE/acceso_denegado.php.');
    exit;
}
?>