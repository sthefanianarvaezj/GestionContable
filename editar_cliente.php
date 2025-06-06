<?php
session_start();
require_once 'config/database.php';
require_once 'funciones_auth.php';

verificar_permiso('gestionar_clientes'); // O un permiso más específico como 'editar_clientes'

$titulo = 'Editar Cliente';
$pagina_actual = 'clientes';

$cliente_id = null;
$nombre_completo = '';
$tipo_documento = '';
$numero_documento = '';
$direccion = '';
$telefono = '';
$email = '';
$errores = [];
$cliente_actual = null;

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $cliente_id = $_GET['id'];
} else {
    $_SESSION['error'] = "ID de cliente no válido.";
    header('Location: clientes.php');
    exit();
}

// Cargar datos del cliente a editar
try {
    $stmt_cliente = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt_cliente->execute([$cliente_id]);
    $cliente_actual = $stmt_cliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente_actual) {
        $_SESSION['error'] = "Cliente no encontrado.";
        header('Location: clientes.php');
        exit();
    }

    // Poblar variables para el formulario si no es un POST (primera carga)
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        $nombre_completo = $cliente_actual['nombre_completo'];
        $tipo_documento = $cliente_actual['tipo_documento'];
        $numero_documento = $cliente_actual['numero_documento'];
        $direccion = $cliente_actual['direccion'];
        $telefono = $cliente_actual['telefono'];
        $email = $cliente_actual['email'];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cargar datos del cliente: " . $e->getMessage();
    header('Location: clientes.php');
    exit();
}


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
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE numero_documento = ? AND id != ?");
        $stmt->execute([$numero_documento, $cliente_id]);
        if ($stmt->fetch()) {
            $errores['numero_documento'] = "El número de documento ya está registrado para otro cliente.";
        }
    }
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = "El formato del email no es válido.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ? AND id != ?");
            $stmt->execute([$email, $cliente_id]);
            if ($stmt->fetch()) {
                $errores['email'] = "El email ya está registrado para otro cliente.";
            }
        }
    }

    if (empty($errores)) {
        try {
            $sql = "UPDATE clientes SET 
                        nombre_completo = ?, 
                        tipo_documento = ?, 
                        numero_documento = ?, 
                        direccion = ?, 
                        telefono = ?, 
                        email = ?
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nombre_completo, 
                $tipo_documento, 
                $numero_documento, 
                $direccion, 
                $telefono, 
                $email, 
                $cliente_id
            ]);
            
            $_SESSION['mensaje'] = "Cliente actualizado correctamente.";
            header('Location: clientes.php');
            exit();
        } catch (PDOException $e) {
            $errores['db'] = "Error al actualizar el cliente: " . $e->getMessage();
        }
    }
}

ob_start();
?>

<div class="container-fluid">
    <h1 class="mb-4"><?php echo $titulo; ?>: <?php echo htmlspecialchars($cliente_actual['nombre_completo'] ?? ''); ?></h1>

    <?php if (!empty($errores['db'])): ?>
        <div class="alert alert-danger show"><?php echo htmlspecialchars($errores['db']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            Datos del Cliente
        </div>
        <div class="card-body">
            <form action="editar_cliente.php?id=<?php echo $cliente_id; ?>" method="POST">
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
                    <button type="submit" class="btn btn-primary">Actualizar Cliente</button>
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