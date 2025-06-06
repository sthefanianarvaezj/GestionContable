<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Inicia la sesión si no está iniciada
}
require_once 'config/database.php'; // Asegura que la conexión a la BD esté disponible si es necesaria para los permisos
require_once 'funciones_auth.php';  // Aquí es donde se debería definir tiene_permiso()
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo ?? 'Sistema de Gestión Contable'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #2c3e50;
            min-height: 100vh;
            color: white;
            padding-top: 20px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 20px;
        }
        .sidebar .nav-link:hover {
            color: white;
            background-color: #34495e;
        }
        .sidebar .nav-link.active {
            color: white;
            background-color: #3498db;
        }
        .main-content {
            padding: 20px;
        }
        .navbar-brand {
            color: white !important;
            font-size: 1.5rem;
            padding: 15px 20px;
        }
        .welcome-card {
            background: linear-gradient(120deg, #2980b9, #8e44ad);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn {
            border-radius: 5px;
        }
        .table {
            margin-bottom: 0;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 15px;
        }
        .alert {
            display: none;
        }
        .alert.show {
            display: block;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0;
            font-weight: bold;
        }
        .nav-item.dropdown {
            display: flex;
            align-items: center;
        }
        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            border-radius: 20px;
            transition: background-color 0.3s;
        }
        .dropdown-toggle:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .dropdown-toggle::after {
            margin-left: 8px;
        }
        .dropdown-menu {
            margin-top: 10px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .dropdown-item {
            padding: 8px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .dropdown-item i {
            width: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="navbar-brand">
                    <i class="bi bi-calculator"></i> Gestión Contable
                </div>
                <?php
                    // DEBUGGING: Eliminar después de probar // <- ESTA SECCIÓN SERÁ ELIMINADA
                    // echo "<div style='background:yellow; color:black; padding:10px; margin:10px; font-size:12px; word-wrap:break-word;'>";
                    // echo "<strong>INFORMACIÓN DE DEPURACIÓN:</strong><br>";
                    // echo "isset(\$_SESSION['usuario_id']): "; var_dump(isset($_SESSION['usuario_id'])); echo "<br>";
                    // if (isset($_SESSION['usuario_id'])) {
                    //     echo "\$_SESSION['usuario_id'] valor: "; var_dump($_SESSION['usuario_id']); echo "<br>";
                    // } else {
                    //     echo "\$_SESSION['usuario_id'] NO ESTÁ DEFINIDO.<br>";
                    // }
                    
                    // echo "function_exists('tiene_permiso'): "; var_dump(function_exists('tiene_permiso')); echo "<br>";
                    // if (!function_exists('tiene_permiso')) {
                    //     echo "La función tiene_permiso() NO EXISTE. Asegúrate de que el archivo que la define esté incluido.<br>";
                    // }
                    
                    // if (function_exists('tiene_permiso')) {
                    //     echo "Prueba tiene_permiso('ver_dashboard'): "; var_dump(tiene_permiso('ver_dashboard')); echo "<br>";
                    //     echo "Prueba tiene_permiso('gestionar_usuarios'): "; var_dump(tiene_permiso('gestionar_usuarios')); echo "<br>";
                    //     // Puedes añadir más llamadas a tiene_permiso para otros permisos clave.
                    //     // Si tu función tiene_permiso depende de un array de permisos en la sesión,
                    //     // también podrías volcar ese array aquí, por ejemplo:
                    //     // echo "Permisos en sesión (\$_SESSION['permisos_usuario'] ?? 'No definido'): "; 
                    //     // var_dump($_SESSION['permisos_usuario'] ?? 'No definido'); echo "<br>";
                    // }
                    // echo "</div>";
                ?>
                <ul class="nav flex-column">
                    <?php if (isset($_SESSION['usuario_id']) && function_exists('tiene_permiso') && tiene_permiso('ver_dashboard')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pagina_actual ?? '') === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['usuario_id']) && function_exists('tiene_permiso') && tiene_permiso('gestionar_trabajos')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pagina_actual ?? '') === 'trabajos' ? 'active' : ''; ?>" href="trabajos.php">
                            <i class="bi bi-briefcase"></i> Trabajos Contables
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['usuario_id']) && function_exists('tiene_permiso') && tiene_permiso('gestionar_clientes')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pagina_actual ?? '') === 'clientes' ? 'active' : ''; ?>" href="clientes.php">
                            <i class="bi bi-people"></i> Clientes
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['usuario_id']) && function_exists('tiene_permiso') && tiene_permiso('gestionar_usuarios')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pagina_actual ?? '') === 'usuarios' ? 'active' : ''; ?>" href="usuarios.php">
                            <i class="bi bi-person-circle"></i> Usuarios
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['usuario_id']) && function_exists('tiene_permiso') && tiene_permiso('gestionar_roles_permisos')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pagina_actual ?? '') === 'roles' ? 'active' : ''; ?>" href="roles.php">
                            <i class="bi bi-shield-check"></i> Roles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pagina_actual ?? '') === 'permisos' ? 'active' : ''; ?>" href="permisos.php">
                            <i class="bi bi-person-check"></i> Permisos
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['usuario_id']) && function_exists('tiene_permiso') && tiene_permiso('acceder_configuracion')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pagina_actual ?? '') === 'configuracion' ? 'active' : ''; ?>" href="configuracion.php">
                            <i class="bi bi-gear"></i> Configuración
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['usuario_id']) && function_exists('tiene_permiso') && tiene_permiso('ver_reportes')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($pagina_actual ?? '') === 'reportes' ? 'active' : ''; ?>" href="reportes.php">
                            <i class="bi bi-bar-chart"></i> Reportes
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                    <div class="container-fluid">
                        <span class="navbar-text">
                            <?php echo $titulo ?? 'Sistema de Gestión Contable'; ?>
                        </span>
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php 
                                                $nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
                                                echo strtoupper(substr($nombre_usuario, 0, 1)); 
                                            ?>
                                        </div>
                                        <span class="d-none d-md-inline"><?php echo $nombre_usuario; ?></span>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person"></i> Mi Perfil</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- Content -->
                <div class="content">
                    <?php if (isset($_SESSION['mensaje']) && !empty($_SESSION['mensaje'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_permiso']) && !empty($_SESSION['error_permiso'])): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error_permiso']; unset($_SESSION['error_permiso']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($mensaje) && !empty($mensaje)): ?>
                        <div class="alert alert-success show"><?php echo $mensaje; ?></div>
                    <?php endif; ?>

                    <?php if (isset($error) && !empty($error)): ?>
                        <div class="alert alert-danger show"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php echo $contenido ?? ''; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-ocultar alertas después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.classList.remove('show');
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                });
            }, 5000);
        });
    </script>
</body>
</html>