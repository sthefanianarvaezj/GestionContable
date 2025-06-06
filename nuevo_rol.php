<?php
session_start();
require_once 'config/database.php';
require_once 'funciones_auth.php';

verificar_permiso('gestionar_roles_permisos');

$titulo = 'Nuevo Rol';
$pagina_actual = 'roles'; // Para el menú activo

$nombre_rol = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_rol = trim($_POST['nombre_rol'] ?? '');

    if (empty($nombre_rol)) {
        $errores['nombre_rol'] = "El nombre del rol es obligatorio.";
    } else {
        // Verificar si el rol ya existe
        $stmt_check = $pdo->prepare("SELECT id FROM roles WHERE nombre_rol = ?");
        $stmt_check->execute([$nombre_rol]);
        if ($stmt_check->fetch()) {
            $errores['nombre_rol'] = "Este nombre de rol ya existe.";
        }
    }

    if (empty($errores)) {
        try {
            $stmt_insert = $pdo->prepare("INSERT INTO roles (nombre_rol) VALUES (?)");
            $stmt_insert->execute([$nombre_rol]);

            $_SESSION['mensaje'] = "Rol creado correctamente.";
            header('Location: roles.php');
            exit();
        } catch (PDOException $e) {
            $errores['db'] = "Error al crear el rol: " . $e->getMessage();
        }
    }
}

ob_start();
?>

<div class="container-fluid">
    <h1 class="mb-4"><?php echo $titulo; ?></h1>

    <?php if (!empty($errores['db'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errores['db']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            Datos del Nuevo Rol
        </div>
        <div class="card-body">
            <form action="nuevo_rol.php" method="POST">
                <div class="mb-3">
                    <label for="nombre_rol" class="form-label">Nombre del Rol <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errores['nombre_rol']) ? 'is-invalid' : ''; ?>" id="nombre_rol" name="nombre_rol" value="<?php echo htmlspecialchars($nombre_rol); ?>" required>
                    <?php if (isset($errores['nombre_rol'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errores['nombre_rol']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Guardar Rol</button>
                    <a href="roles.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?>