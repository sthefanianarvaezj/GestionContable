<?php
session_start();
require_once 'config/database.php';
require_once 'funciones_auth.php';

verificar_permiso('gestionar_roles_permisos');

$titulo = 'Editar Permiso';
$pagina_actual = 'permisos';

$permiso_id = null;
$nombre_permiso = '';
$descripcion_permiso = '';
$errores = [];
$permiso_actual = null;

// Obtener ID del permiso de la URL
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $permiso_id = $_GET['id'];
} else {
    $_SESSION['error'] = "ID de permiso no válido.";
    header('Location: permisos.php');
    exit();
}

// Cargar datos del permiso a editar
try {
    $stmt_permiso = $pdo->prepare("SELECT * FROM permisos WHERE id = ?");
    $stmt_permiso->execute([$permiso_id]);
    $permiso_actual = $stmt_permiso->fetch(PDO::FETCH_ASSOC);

    if (!$permiso_actual) {
        $_SESSION['error'] = "Permiso no encontrado.";
        header('Location: permisos.php');
        exit();
    }
    // Pre-llenar el formulario si no es un POST (o si hay errores en POST)
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        $nombre_permiso = $permiso_actual['nombre_permiso'];
        $descripcion_permiso = $permiso_actual['descripcion'] ?? '';
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar datos del permiso: " . $e->getMessage();
    header('Location: permisos.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_permiso = trim($_POST['nombre_permiso'] ?? '');
    $descripcion_permiso = trim($_POST['descripcion'] ?? '');

    // Validaciones
    if (empty($nombre_permiso)) {
        $errores['nombre_permiso'] = "El nombre del permiso es obligatorio.";
    } else {
        // Verificar si el nuevo nombre de permiso ya existe PARA OTRO permiso
        $stmt_check = $pdo->prepare("SELECT id FROM permisos WHERE nombre_permiso = ? AND id != ?");
        $stmt_check->execute([$nombre_permiso, $permiso_id]);
        if ($stmt_check->fetch()) {
            $errores['nombre_permiso'] = "Este nombre de permiso ya está en uso por otro permiso.";
        }
    }

    if (empty($errores)) {
        try {
            $stmt_update = $pdo->prepare("UPDATE permisos SET nombre_permiso = ?, descripcion = ? WHERE id = ?");
            $stmt_update->execute([$nombre_permiso, $descripcion_permiso, $permiso_id]);

            $_SESSION['mensaje'] = "Permiso actualizado correctamente.";
            header('Location: permisos.php');
            exit();
        } catch (PDOException $e) {
            $errores['db'] = "Error al actualizar el permiso: " . $e->getMessage();
        }
    } else {
        // Si hay errores, volvemos a cargar los datos originales para no perderlos en el form
        // excepto los que el usuario intentó cambiar.
        // Esto es para que si falla la validación del nombre, la descripción no se borre.
        // $nombre_permiso ya tiene el valor del POST.
        // $descripcion_permiso ya tiene el valor del POST.
    }
}

ob_start();
?>

<div class="container-fluid">
    <h1 class="mb-4"><?php echo $titulo; ?>: <?php echo htmlspecialchars($permiso_actual['nombre_permiso'] ?? ''); ?></h1>

    <?php if (!empty($errores['db'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errores['db']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            Modificar Datos del Permiso
        </div>
        <div class="card-body">
            <form action="editar_permiso.php?id=<?php echo $permiso_id; ?>" method="POST">
                <div class="mb-3">
                    <label for="nombre_permiso" class="form-label">Nombre del Permiso <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errores['nombre_permiso']) ? 'is-invalid' : ''; ?>" id="nombre_permiso" name="nombre_permiso" value="<?php echo htmlspecialchars($nombre_permiso); ?>" required>
                     <small class="form-text text-muted">Utiliza un nombre descriptivo y único (ej: gestionar_usuarios, ver_reportes).</small>
                    <?php if (isset($errores['nombre_permiso'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errores['nombre_permiso']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción (Opcional)</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($descripcion_permiso); ?></textarea>
                    <small class="form-text text-muted">Explica brevemente qué permite hacer este permiso.</small>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Actualizar Permiso</button>
                    <a href="permisos.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?>