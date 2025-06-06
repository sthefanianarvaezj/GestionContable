<?php
session_start();
require_once 'config/database.php';
require_once 'funciones_auth.php';

verificar_permiso('gestionar_usuarios');

$titulo = 'Nuevo Usuario';
$pagina_actual = 'usuarios'; // Para que el menú 'Usuarios' se mantenga activo

$nombre = '';
$email = '';
$rol_id_seleccionado = '';
$errores = [];

// Obtener roles para el dropdown
try {
    $stmt_roles = $pdo->query("SELECT id, nombre_rol FROM roles ORDER BY nombre_rol ASC");
    $roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores['db'] = "Error al cargar roles: " . $e->getMessage();
    $roles = [];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    $rol_id_seleccionado = $_POST['rol_id'] ?? '';

    // Validaciones
    if (empty($nombre)) {
        $errores['nombre'] = "El nombre es obligatorio.";
    }
    if (empty($email)) {
        $errores['email'] = "El correo electrónico es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = "El formato del correo electrónico no es válido.";
    } else {
        // Verificar si el email ya existe
        $stmt_check_email = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt_check_email->execute([$email]);
        if ($stmt_check_email->fetch()) {
            $errores['email'] = "Este correo electrónico ya está registrado.";
        }
    }
    if (empty($password)) {
        $errores['password'] = "La contraseña es obligatoria.";
    } elseif (strlen($password) < 6) {
        $errores['password'] = "La contraseña debe tener al menos 6 caracteres.";
    }
    if ($password !== $confirmar_password) {
        $errores['confirmar_password'] = "Las contraseñas no coinciden.";
    }
    if (empty($rol_id_seleccionado)) {
        $errores['rol_id'] = "Debe seleccionar un rol para el usuario.";
    } elseif (!is_numeric($rol_id_seleccionado)) {
         $errores['rol_id'] = "El rol seleccionado no es válido.";
    }


    if (empty($errores)) {
        try {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol_id) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$nombre, $email, $password_hashed, $rol_id_seleccionado]);

            $_SESSION['mensaje'] = "Usuario creado correctamente.";
            header('Location: usuarios.php');
            exit();
        } catch (PDOException $e) {
            $errores['db'] = "Error al crear el usuario: " . $e->getMessage();
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
            Datos del Nuevo Usuario
        </div>
        <div class="card-body">
            <form action="nuevo_usuario.php" method="POST">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errores['nombre']) ? 'is-invalid' : ''; ?>" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                    <?php if (isset($errores['nombre'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errores['nombre']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" class="form-control <?php echo isset($errores['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <?php if (isset($errores['email'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errores['email']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control <?php echo isset($errores['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                        <?php if (isset($errores['password'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errores['password']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirmar_password" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control <?php echo isset($errores['confirmar_password']) ? 'is-invalid' : ''; ?>" id="confirmar_password" name="confirmar_password" required>
                        <?php if (isset($errores['confirmar_password'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errores['confirmar_password']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="rol_id" class="form-label">Rol <span class="text-danger">*</span></label>
                    <select class="form-select <?php echo isset($errores['rol_id']) ? 'is-invalid' : ''; ?>" id="rol_id" name="rol_id" required>
                        <option value="">Seleccione un rol...</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo htmlspecialchars($rol['id']); ?>" <?php echo ($rol_id_seleccionado == $rol['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['rol_id'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errores['rol_id']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                    <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?>