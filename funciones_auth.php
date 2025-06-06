<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php'; // Asegúrate que la ruta es correcta

function verificar_login() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}

function obtener_info_usuario_actual() {
    global $pdo;
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }

    if (isset($_SESSION['usuario_info'])) {
        return $_SESSION['usuario_info'];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT u.*, r.nombre_rol 
            FROM usuarios u
            LEFT JOIN roles r ON u.rol_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['usuario_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $_SESSION['usuario_info'] = $usuario; // Cache user info
            return $usuario;
        }
        return null;
    } catch (PDOException $e) {
        // Manejar error, por ejemplo, loguearlo
        error_log("Error al obtener info de usuario: " . $e->getMessage());
        return null;
    }
}

function obtener_permisos_usuario_actual() {
    global $pdo;
    $usuario_info = obtener_info_usuario_actual();

    if (!$usuario_info || !isset($usuario_info['rol_id'])) {
        return [];
    }

    if (isset($_SESSION['usuario_permisos'])) {
        return $_SESSION['usuario_permisos'];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT p.nombre_permiso 
            FROM rol_permisos rp
            JOIN permisos p ON rp.permiso_id = p.id
            WHERE rp.rol_id = ?
        ");
        $stmt->execute([$usuario_info['rol_id']]);
        $permisos_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $permisos = [];
        foreach ($permisos_raw as $permiso) {
            $permisos[] = $permiso['nombre_permiso'];
        }
        $_SESSION['usuario_permisos'] = $permisos; // Cache permissions
        return $permisos;
    } catch (PDOException $e) {
        error_log("Error al obtener permisos: " . $e->getMessage());
        return [];
    }
}

function tiene_permiso($permiso_requerido) {
    $permisos_usuario = obtener_permisos_usuario_actual();
    return in_array($permiso_requerido, $permisos_usuario);
}

function verificar_permiso($permiso_requerido, $redireccionar_a = 'dashboard.php') {
    verificar_login(); // Primero asegurar que está logueado
    if (!tiene_permiso($permiso_requerido)) {
        $_SESSION['error_permiso'] = "No tienes permiso para acceder a esta sección.";
        header("Location: $redireccionar_a"); // O una página de acceso denegado
        exit();
    }
}

function es_administrador() {
    $usuario_info = obtener_info_usuario_actual();
    return $usuario_info && isset($usuario_info['nombre_rol']) && $usuario_info['nombre_rol'] === 'Administrador';
}

?>