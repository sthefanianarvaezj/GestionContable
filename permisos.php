<?php
session_start();
require_once 'config/database.php';
//require_once 'includes/funciones.php'; // Asegúrate que esta ruta es correcta

// Verificar si el usuario está logueado y tiene permisos (esto es un ejemplo, ajústalo a tu sistema de roles)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
// Aquí podrías añadir una verificación de permisos específica para esta página si es necesario
// if (!tienePermiso($_SESSION['usuario_id'], 'ver_permisos')) {
// $_SESSION['error_permiso'] = 'No tienes permiso para acceder a esta página.';
// header('Location: dashboard.php');
// exit;
// }

$pagina_actual = 'permisos';
$titulo = 'Gestión de Permisos';

// Lógica para eliminar permiso (si se envía el formulario de eliminación)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_permiso'])) {
    $permiso_id = filter_input(INPUT_POST, 'permiso_id', FILTER_VALIDATE_INT);
    if ($permiso_id) {
        try {
            // Aquí podrías verificar si el permiso está en uso antes de eliminar
            $stmt = $pdo->prepare("DELETE FROM permisos WHERE id = ?");
            $stmt->execute([$permiso_id]);
            $_SESSION['mensaje'] = 'Permiso eliminado correctamente.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error al eliminar el permiso: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'ID de permiso inválido.';
    }
    header('Location: permisos.php');
    exit;
}

// Obtener todos los permisos
try {
    // Modificado para incluir descripcion y usar nombre_permiso
    $stmt = $pdo->query("SELECT id, nombre_permiso, descripcion FROM permisos ORDER BY nombre_permiso ASC");
    $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_db = "Error al obtener los permisos: " . $e->getMessage();
    $permisos = [];
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $titulo; ?></h1>
        <a href="nuevo_permiso.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Nuevo Permiso
        </a>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_db)): ?>
        <div class="alert alert-danger"><?php echo $error_db; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            Lista de Permisos
        </div>
        <div class="card-body">
            <?php if (empty($permisos) && !isset($error_db)): ?>
                <p class="text-center">No hay permisos registrados.</p>
            <?php elseif (!empty($permisos)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Permiso</th>
                                <th>Descripción</th> <!-- Columna añadida/confirmada -->
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($permisos as $permiso): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($permiso['id']); ?></td>
                                    <td><?php echo htmlspecialchars($permiso['nombre_permiso']); ?></td> <!-- Corregido de 'nombre' a 'nombre_permiso' -->
                                    <td><?php echo htmlspecialchars($permiso['descripcion'] ?? 'N/A'); ?></td> <!-- Mostrar descripción -->
                                    <td>
                                        <a href="editar_permiso.php?id=<?php echo $permiso['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="permisos.php" method="POST" style="display: inline-block;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este permiso? Considera que si está asignado a roles, deberás gestionar esas asignaciones.');">
                                            <input type="hidden" name="permiso_id" value="<?php echo $permiso['id']; ?>">
                                            <button type="submit" name="eliminar_permiso" class="btn btn-danger btn-sm" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?>