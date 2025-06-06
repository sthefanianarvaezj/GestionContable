<?php
session_start();
require_once 'config/database.php';
require_once 'funciones_auth.php';

verificar_permiso('gestionar_usuarios');

$titulo = 'Editar Usuario';
$pagina_actual = 'usuarios';

$usuario_id = null;
$nombre = '';
$email = '';
$rol_id_seleccionado = '';
$errores = [];
$usuario_actual = null;

// Obtener ID del usuario de la URL
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $usuario_id = $_GET['id'];
} else {
    $_SESSION['error'] = "ID de usuario no válido.";
    header('Location: usuarios.php');
    exit();
}

// Obtener roles para el dropdown
try {
    $stmt_roles = $pdo->query("SELECT id, nombre_rol FROM roles ORDER BY nombre_rol ASC");
    $roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores['db'] = "Error al cargar roles: " . $e->getMessage();
    $roles = [];
}

// Cargar datos del usuario a editar
try {
    $stmt_usuario = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt_usuario->execute([$usuario_id]);
    $usuario_actual = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario_actual) {
        $_SESSION['error'] = "Usuario no encontrado.";
        header('Location: usuarios.php');
        exit();
    }
    // Pre-llenar el formulario si no es un POST (o si hay errores en POST y queremos mantener los datos originales)
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        $nombre = $usuario_actual['nombre'];
        $email = $usuario_actual['email'];
        $rol_id_seleccionado = $usuario_actual['rol_id'];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar datos del usuario: " . $e->getMessage();
    header('Location: usuarios.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Contraseña es opcional al editar
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
        // Verificar si el email ya existe PARA OTRO USUARIO
        $stmt_check_email = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt_check_email->execute([$email, $usuario_id]);
        if ($stmt_check_email->fetch()) {
            $errores['email'] = "Este correo electrónico ya está registrado por otro usuario.";
        }
    }

    if (!empty($password)) { // Solo validar contraseña si se ingresa una nueva
        if (strlen($password) < 6) {
            $errores['password'] = "La nueva contraseña debe tener al menos 6 caracteres.";
        }
        if ($password !== $confirmar_password) {
            $errores['confirmar_password'] = "Las nuevas contraseñas no coinciden.";
        }
    }
    
    if (empty($rol_id_seleccionado)) {
        $errores['rol_id'] = "Debe seleccionar un rol para el usuario.";
    } elseif (!is_numeric($rol_id_seleccionado)) {
         $errores['rol_id'] = "El rol seleccionado no es válido.";
    }

    if (empty($errores)) {
        try {
            if (!empty($password)) {
                $password_hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt_update = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, password = ?, rol_id = ? WHERE id = ?");
                $stmt_update->execute([$nombre, $email, $password_hashed, $rol_id_seleccionado, $usuario_id]);
            } else {
                // No actualizar contraseña si no se proporcionó una nueva
                $stmt_update = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol_id = ? WHERE id = ?");
                $stmt_update->execute([$nombre, $email, $rol_id_seleccionado, $usuario_id]);
            }

            $_SESSION['mensaje'] = "Usuario actualizado correctamente.";
            // Si el usuario editado es el mismo que está logueado, actualizar su info en sesión
            if ($usuario_id == $_SESSION['usuario_id']) {
                $_SESSION['nombre'] = $nombre;
                // Podrías querer recargar toda la info del usuario si el rol cambió
                unset($_SESSION['usuario_info']); 
                unset($_SESSION['usuario_permisos']);
            }
            header('Location: usuarios.php');
            exit();
        } catch (PDOException $e) {
            $errores['db'] = "Error al actualizar el usuario: " . $e->getMessage();
        }
    }
}

ob_start();
?>

<div class="container-fluid">
    <h1 class="mb-4"><?php echo $titulo; ?>: <?php echo htmlspecialchars($usuario_actual['nombre'] ?? ''); ?></h1>

    <?php if (!empty($errores['db'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errores['db']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            Modificar Datos del Usuario
        </div>
        <div class="card-body">
            <form action="editar_usuario.php?id=<?php echo $usuario_id; ?>" method="POST">
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

                <p class="text-muted">Deje los campos de contraseña en blanco si no desea cambiarla.</p>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control <?php echo isset($errores['password']) ? 'is-invalid' : ''; ?>" id="password" name="password">
                        <?php if (isset($errores['password'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errores['password']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirmar_password" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control <?php echo isset($errores['confirmar_password']) ? 'is-invalid' : ''; ?>" id="confirmar_password" name="confirmar_password">
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
                    <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
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