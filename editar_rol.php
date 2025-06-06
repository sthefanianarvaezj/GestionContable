<?php
session_start();
require_once 'config/database.php';
require_once 'funciones_auth.php';

verificar_permiso('gestionar_roles_permisos');

$titulo = 'Editar Rol y Asignar Permisos'; // Título actualizado
$pagina_actual = 'roles';

$rol_id = null;
$nombre_rol = '';
$errores = [];
$rol_actual = null;
$permisos_disponibles = [];
$permisos_asignados_ids = []; // IDs de los permisos ya asignados al rol

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $rol_id = $_GET['id'];
} else {
    $_SESSION['error'] = "ID de rol no válido.";
    header('Location: roles.php');
    exit();
}

// Cargar datos del rol a editar
try {
    $stmt_rol = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt_rol->execute([$rol_id]);
    $rol_actual = $stmt_rol->fetch(PDO::FETCH_ASSOC);

    if (!$rol_actual) {
        $_SESSION['error'] = "Rol no encontrado.";
        header('Location: roles.php');
        exit();
    }
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        $nombre_rol = $rol_actual['nombre_rol'];
    }

    // Cargar todos los permisos disponibles
    $stmt_permisos_disp = $pdo->query("SELECT id, nombre_permiso, descripcion FROM permisos ORDER BY nombre_permiso ASC");
    $permisos_disponibles = $stmt_permisos_disp->fetchAll(PDO::FETCH_ASSOC);

    // Cargar permisos actualmente asignados a este rol
    $stmt_permisos_asig = $pdo->prepare("SELECT permiso_id FROM rol_permisos WHERE rol_id = ?");
    $stmt_permisos_asig->execute([$rol_id]);
    $permisos_asignados_raw = $stmt_permisos_asig->fetchAll(PDO::FETCH_ASSOC);
    foreach ($permisos_asignados_raw as $pa) {
        $permisos_asignados_ids[] = $pa['permiso_id'];
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar datos del rol o permisos: " . $e->getMessage();
    // $errores['db_load'] = "Error al cargar datos: " . $e->getMessage(); // Para mostrar en la página si no rediriges
    header('Location: roles.php'); // Simplificado: redirigir en caso de error de carga
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_rol = trim($_POST['nombre_rol'] ?? '');
    $permisos_seleccionados = $_POST['permisos'] ?? []; // Array de IDs de permisos seleccionados

    // Validaciones para el nombre del rol
    if (empty($nombre_rol)) {
        $errores['nombre_rol'] = "El nombre del rol es obligatorio.";
    } else {
        $stmt_check = $pdo->prepare("SELECT id FROM roles WHERE nombre_rol = ? AND id != ?");
        $stmt_check->execute([$nombre_rol, $rol_id]);
        if ($stmt_check->fetch()) {
            $errores['nombre_rol'] = "Este nombre de rol ya está en uso.";
        }
    }

    if (empty($errores)) {
        $pdo->beginTransaction();
        try {
            // 1. Actualizar el nombre del rol
            $stmt_update_rol = $pdo->prepare("UPDATE roles SET nombre_rol = ? WHERE id = ?");
            $stmt_update_rol->execute([$nombre_rol, $rol_id]);

            // 2. Eliminar todos los permisos actuales para este rol
            $stmt_delete_permisos = $pdo->prepare("DELETE FROM rol_permisos WHERE rol_id = ?");
            $stmt_delete_permisos->execute([$rol_id]);

            // 3. Insertar los nuevos permisos seleccionados
            if (!empty($permisos_seleccionados)) {
                $stmt_insert_permiso = $pdo->prepare("INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (?, ?)");
                foreach ($permisos_seleccionados as $permiso_id) {
                    if (filter_var($permiso_id, FILTER_VALIDATE_INT)) { // Asegurarse que es un ID válido
                        $stmt_insert_permiso->execute([$rol_id, $permiso_id]);
                    }
                }
            }
            
            $pdo->commit();
            $_SESSION['mensaje'] = "Rol y permisos actualizados correctamente.";
            // Si el rol editado es el del usuario actual, podríamos necesitar recargar sus permisos en sesión
            $usuario_actual_info = obtener_info_usuario_actual();
            if ($usuario_actual_info && $usuario_actual_info['rol_id'] == $rol_id) {
                unset($_SESSION['usuario_permisos']); // Forzar recarga de permisos en la próxima verificación
            }

            header('Location: roles.php');
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores['db'] = "Error al actualizar el rol y/o permisos: " . $e->getMessage();
        }
    } else {
        // Si hay errores de validación, los permisos seleccionados en el POST deben usarse para repoblar los checkboxes
        $permisos_asignados_ids = array_map('intval', $permisos_seleccionados);
    }
}

ob_start();
?>

<div class="container-fluid">
    <h1 class="mb-4"><?php echo $titulo; ?>: <?php echo htmlspecialchars($rol_actual['nombre_rol'] ?? ''); ?></h1>

    <?php if (!empty($errores['db'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errores['db']); ?></div>
    <?php endif; ?>
    <?php if (!empty($errores['db_load'])): // Para errores durante la carga inicial ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errores['db_load']); ?></div>
    <?php endif; ?>


    <form action="editar_rol.php?id=<?php echo $rol_id; ?>" method="POST">
        <div class="card mb-4">
            <div class="card-header">
                Datos del Rol
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="nombre_rol" class="form-label">Nombre del Rol <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errores['nombre_rol']) ? 'is-invalid' : ''; ?>" id="nombre_rol" name="nombre_rol" value="<?php echo htmlspecialchars($nombre_rol); ?>" required>
                    <?php if (isset($errores['nombre_rol'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errores['nombre_rol']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Asignar Permisos al Rol
            </div>
            <div class="card-body">
                <?php if (empty($permisos_disponibles)): ?>
                    <p>No hay permisos definidos en el sistema. <a href="nuevo_permiso.php">Crear un permiso</a>.</p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($permisos_disponibles as $permiso): ?>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permisos[]" value="<?php echo $permiso['id']; ?>" id="permiso_<?php echo $permiso['id']; ?>"
                                        <?php echo in_array($permiso['id'], $permisos_asignados_ids) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="permiso_<?php echo $permiso['id']; ?>" title="<?php echo htmlspecialchars($permiso['descripcion'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($permiso['nombre_permiso']); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Actualizar Rol y Permisos</button>
            <a href="roles.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?>