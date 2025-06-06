<?php
session_start();
require_once 'config/database.php'; // Asegúrate que la ruta a tu conexión PDO es correcta
require_once 'funciones_auth.php'; // Para verificar permisos

verificar_permiso('gestionar_roles_permisos'); // O el permiso que hayas definido para esto

$titulo = 'Nuevo Permiso';
$pagina_actual = 'permisos'; // Para que el menú 'Permisos' se mantenga activo

$nombre_permiso = '';
$descripcion_permiso = ''; // Campo opcional
$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_permiso = trim($_POST['nombre_permiso'] ?? '');
    $descripcion_permiso = trim($_POST['descripcion'] ?? ''); // Opcional

    // Validaciones
    if (empty($nombre_permiso)) {
        $errores['nombre_permiso'] = "El nombre del permiso es obligatorio.";
    } else {
        // Verificar si el nombre del permiso ya existe (debe ser único)
        $stmt_check = $pdo->prepare("SELECT id FROM permisos WHERE nombre_permiso = ?");
        $stmt_check->execute([$nombre_permiso]);
        if ($stmt_check->fetch()) {
            $errores['nombre_permiso'] = "Este nombre de permiso ya existe.";
        }
    }
    // Puedes añadir más validaciones para la descripción si es necesario

    if (empty($errores)) {
        try {
            $stmt_insert = $pdo->prepare("INSERT INTO permisos (nombre_permiso, descripcion) VALUES (?, ?)");
            $stmt_insert->execute([$nombre_permiso, $descripcion_permiso]);

            $_SESSION['mensaje'] = "Permiso creado correctamente.";
            header('Location: permisos.php');
            exit();
        } catch (PDOException $e) {
            $errores['db'] = "Error al crear el permiso: " . $e->getMessage();
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
            Datos del Nuevo Permiso
        </div>
        <div class="card-body">
            <form action="nuevo_permiso.php" method="POST">
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
                    <button type="submit" class="btn btn-primary">Guardar Permiso</button>
                    <a href="permisos.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php'; // Asegúrate que esta ruta es correcta
?>