<?php
session_start();
require_once 'config/database.php';
require_once 'funciones_auth.php';

verificar_permiso('gestionar_clientes'); // O un permiso más específico como 'crear_clientes'

$titulo = 'Nuevo Cliente';
$pagina_actual = 'clientes'; // Para marcar como activo en el layout

$nombre_completo = '';
$tipo_documento = '';
$numero_documento = '';
$direccion = '';
$telefono = '';
$email = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $tipo_documento = trim($_POST['tipo_documento'] ?? '');
    $numero_documento = trim($_POST['numero_documento'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Validaciones
    if (empty($nombre_completo)) {
        $errores['nombre_completo'] = "El nombre completo es obligatorio.";
    }
    if (!empty($numero_documento)) {
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE numero_documento = ?");
        $stmt->execute([$numero_documento]);
        if ($stmt->fetch()) {
            $errores['numero_documento'] = "El número de documento ya está registrado.";
        }
    }
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = "El formato del email no es válido.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errores['email'] = "El email ya está registrado.";
            }
        }
    }
    // Puedes añadir más validaciones (longitud, formato de teléfono, etc.)

    if (empty($errores)) {
        try {
            $sql = "INSERT INTO clientes (nombre_completo, tipo_documento, numero_documento, direccion, telefono, email) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre_completo, $tipo_documento, $numero_documento, $direccion, $telefono, $email]);
            
            $_SESSION['mensaje'] = "Cliente registrado correctamente.";
            header('Location: clientes.php');
            exit();
        } catch (PDOException $e) {
            $errores['db'] = "Error al registrar el cliente: " . $e->getMessage();
        }
    }
}

ob_start();
?>

<div class="container-fluid">
    <h1 class="mb-4"><?php echo $titulo; ?></h1>

    <?php if (!empty($errores['db'])): ?>
        <div class="alert alert-danger show"><?php echo htmlspecialchars($errores['db']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            Datos del Cliente
        </div>
        <div class="card-body">
            <form action="nuevo_cliente.php" method="POST">
                <div class="mb-3">
                    <label for="nombre_completo" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errores['nombre_completo']) ? 'is-invalid' : ''; ?>" id="nombre_completo" name="nombre_completo" value="<?php echo htmlspecialchars($nombre_completo); ?>" required>
                    <?php if (isset($errores['nombre_completo'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errores['nombre_completo']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tipo_documento" class="form-label">Tipo de Documento</label>
                        <select class="form-select <?php echo isset($errores['tipo_documento']) ? 'is-invalid' : ''; ?>" id="tipo_documento" name="tipo_documento">
                            <option value="">Seleccione...</option>
                            <option value="CC" <?php echo ($tipo_documento === 'CC') ? 'selected' : ''; ?>>Cédula de Ciudadanía</option>
                            <option value="CE" <?php echo ($tipo_documento === 'CE') ? 'selected' : ''; ?>>Cédula de Extranjería</option>
                            <option value="PA" <?php echo ($tipo_documento === 'PA') ? 'selected' : ''; ?>>Pasaporte</option>
                            <option value="NIT" <?php echo ($tipo_documento === 'NIT') ? 'selected' : ''; ?>>NIT (Número de Identificación Tributaria)</option>
                            <option value="TI" <?php echo ($tipo_documento === 'TI') ? 'selected' : ''; ?>>Tarjeta de Identidad</option>
                            <option value="Otro" <?php echo ($tipo_documento === 'Otro') ? 'selected' : ''; ?>>Otro</option>
                        </select>
                        <?php if (isset($errores['tipo_documento'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errores['tipo_documento']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="numero_documento" class="form-label">Número de Documento</label>
                        <input type="text" class="form-control <?php echo isset($errores['numero_documento']) ? 'is-invalid' : ''; ?>" id="numero_documento" name="numero_documento" value="<?php echo htmlspecialchars($numero_documento); ?>">
                        <?php if (isset($errores['numero_documento'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errores['numero_documento']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="direccion" class="form-label">Dirección</label>
                    <textarea class="form-control <?php echo isset($errores['direccion']) ? 'is-invalid' : ''; ?>" id="direccion" name="direccion" rows="3"><?php echo htmlspecialchars($direccion); ?></textarea>
                    <?php if (isset($errores['direccion'])): ?>
                        <div class="invalid-feedback"><?php echo htmlspecialchars($errores['direccion']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control <?php echo isset($errores['telefono']) ? 'is-invalid' : ''; ?>" id="telefono" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>">
                        <?php if (isset($errores['telefono'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errores['telefono']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control <?php echo isset($errores['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <?php if (isset($errores['email'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errores['email']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                    <a href="clientes.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?>