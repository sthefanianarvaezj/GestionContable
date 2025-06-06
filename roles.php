<?php
session_start();
require_once 'config/database.php';
require_once 'funciones_auth.php';

verificar_permiso('gestionar_roles_permisos');

$titulo = 'Gestión de Roles';
$pagina_actual = 'roles'; // Para el menú activo

// Lógica para eliminar rol
if (isset($_GET['eliminar']) && filter_var($_GET['eliminar'], FILTER_VALIDATE_INT)) {
    $id_rol_eliminar = $_GET['eliminar'];

    // Validación: No permitir eliminar roles con usuarios asignados
    $stmt_check_users = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id = ?");
    $stmt_check_users->execute([$id_rol_eliminar]);
    $usuarios_con_rol = $stmt_check_users->fetchColumn();

    if ($usuarios_con_rol > 0) {
        $_SESSION['error'] = "No se puede eliminar el rol porque hay usuarios asignados a él. Reasigne los usuarios a otro rol primero.";
    } else {
        // Validación: No permitir eliminar roles con permisos asignados (opcional, pero buena práctica)
        $stmt_check_perms = $pdo->prepare("SELECT COUNT(*) FROM rol_permisos WHERE rol_id = ?");
        $stmt_check_perms->execute([$id_rol_eliminar]);
        $permisos_con_rol = $stmt_check_perms->fetchColumn();

        if ($permisos_con_rol > 0) {
             // Primero eliminar las asignaciones de permisos para este rol
            $stmt_delete_perms = $pdo->prepare("DELETE FROM rol_permisos WHERE rol_id = ?");
            $stmt_delete_perms->execute([$id_rol_eliminar]);
        }
        
        // Proceder a eliminar el rol
        try {
            $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->execute([$id_rol_eliminar]);
            $_SESSION['mensaje'] = "Rol eliminado correctamente.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al eliminar el rol: " . $e->getMessage();
        }
    }
    header('Location: roles.php');
    exit();
}

// Obtener todos los roles
try {
    $stmt = $pdo->query("SELECT id, nombre_rol FROM roles ORDER BY nombre_rol ASC");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_roles = "Error al cargar los roles: " . $e->getMessage();
    $roles = [];
}

ob_start();
?>

<div class="container-fluid">
    <h1 class="mb-4"><?php echo $titulo; ?></h1>

    <?php if (isset($error_roles)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_roles); ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <a href="nuevo_rol.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Nuevo Rol
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            Lista de Roles
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre del Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($roles)): ?>
                            <tr>
                                <td colspan="3" class="text-center">No hay roles registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($roles as $rol): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rol['id']); ?></td>
                                    <td><?php echo htmlspecialchars($rol['nombre_rol']); ?></td>
                                    <td>
                                        <a href="editar_rol.php?id=<?php echo $rol['id']; ?>" class="btn btn-sm btn-warning" title="Editar Rol y Asignar Permisos">
                                            <i class="bi bi-pencil-square"></i> <span class="d-none d-md-inline">Editar / Permisos</span>
                                        </a>
                                        <?php 
                                        // Opcional: No permitir eliminar roles predefinidos como Administrador si tienen ID específico
                                        // if ($rol['id'] > 2): // Suponiendo que ID 1 y 2 son Admin y Usuario Estándar
                                        ?>
                                        <a href="roles.php?eliminar=<?php echo $rol['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Eliminar Rol"
                                           onclick="return confirm('¿Estás seguro de que deseas eliminar este rol? Si hay usuarios o permisos asignados, la eliminación podría fallar o requerir pasos adicionales.');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php // endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?>