<?php
session_start();
require_once 'config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Configuración para el layout
$titulo = 'Nuevo Trabajo Contable';
$pagina_actual = 'trabajos';

$mensaje = '';
$error = '';
$error_clientes = ''; // Para errores específicos de la carga de clientes
$lista_clientes = []; // Para el dropdown de clientes
$cliente_id_seleccionado = ''; // Para mantener el valor seleccionado en caso de error
$descripcion_trabajo = ''; // Para mantener el valor en caso de error
$valor_total_trabajo = ''; // Para mantener el valor en caso de error
$fecha_ingreso_trabajo = date('Y-m-d'); // Para mantener el valor en caso de error
$requiere_factura_trabajo = 0; // Para mantener el valor en caso de error
$numero_factura_trabajo = ''; // Para mantener el valor en caso de error


// Obtener lista de clientes para el dropdown
try {
    $stmt_clientes = $pdo->query("SELECT id, nombre_completo FROM clientes ORDER BY nombre_completo ASC");
    $lista_clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_clientes = "Error al cargar la lista de clientes: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger los datos del formulario para repoblarlos en caso de error
    $cliente_id_seleccionado = $_POST['cliente_id'] ?? '';
    $descripcion_trabajo = $_POST['descripcion'] ?? '';
    $valor_total_trabajo = $_POST['valor_total'] ?? '';
    $fecha_ingreso_trabajo = $_POST['fecha_ingreso'] ?? date('Y-m-d');
    $requiere_factura_trabajo = isset($_POST['requiere_factura']) ? 1 : 0;
    $numero_factura_trabajo = $_POST['numero_factura'] ?? '';

    try {
        // Validar datos
        // Cambiar la validación de 'nombre_cliente' a 'cliente_id'
        if (empty($_POST['cliente_id']) || empty($_POST['descripcion']) ||
            empty($_POST['valor_total']) || empty($_POST['fecha_ingreso'])) {
            throw new Exception('Todos los campos obligatorios (*) deben estar llenos.');
        }
        
        $valor_total_float = floatval(str_replace(',', '.', $_POST['valor_total']));
        if ($valor_total_float <= 0) {
            throw new Exception('El valor total debe ser un número positivo.');
        }


        // Preparar la consulta
        // Cambiar nombre_cliente por cliente_id en la consulta y en los VALUES
        $stmt = $pdo->prepare("
            INSERT INTO trabajos_contables (
                fecha_ingreso, cliente_id, descripcion,
                valor_total, saldo_pendiente, requiere_factura,
                numero_factura, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'recibido')
        ");

        // Ejecutar la consulta
        // Cambiar $_POST['nombre_cliente'] por (int)$_POST['cliente_id']
        $stmt->execute([
            $_POST['fecha_ingreso'],
            (int)$_POST['cliente_id'], // Asegurarse de que es un entero
            $_POST['descripcion'],
            $valor_total_float, // Usar el valor convertido a float
            $valor_total_float, // El saldo pendiente inicial es igual al valor total
            isset($_POST['requiere_factura']) ? 1 : 0,
            $_POST['numero_factura'] ?? null
        ]);

        $mensaje = 'Trabajo creado exitosamente.';
        $_SESSION['mensaje_exito'] = $mensaje; // Guardar en sesión para mostrar después de redirigir
        
        // Redirigir para evitar reenvío del formulario
        header("Location: trabajos.php");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Iniciar el buffer de salida
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Nuevo Trabajo Contable</h1>
        <a href="trabajos.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($error_clientes): ?>
        <div class="alert alert-warning"><?php echo htmlspecialchars($error_clientes); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="nuevo_trabajo.php" class="needs-validation" novalidate>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="fecha_ingreso" class="form-label">Fecha de Ingreso *</label>
                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso"
                               value="<?php echo htmlspecialchars($fecha_ingreso_trabajo); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="cliente_id" class="form-label">Cliente Asociado *</label>
                        <select class="form-select <?php echo (!empty($error) && empty($cliente_id_seleccionado)) ? 'is-invalid' : ''; ?>" id="cliente_id" name="cliente_id" required>
                            <option value="">Seleccione un cliente...</option>
                            <?php if (!empty($lista_clientes)): ?>
                                <?php foreach ($lista_clientes as $cliente_item): ?>
                                    <option value="<?php echo htmlspecialchars($cliente_item['id']); ?>"
                                        <?php echo ($cliente_id_seleccionado == $cliente_item['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cliente_item['nombre_completo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($lista_clientes) && empty($error_clientes)): ?>
                            <div class="form-text text-warning">No hay clientes disponibles. Puede <a href="nuevo_cliente.php" target="_blank">agregar un nuevo cliente</a>.</div>
                        <?php endif; ?>
                        <div class="invalid-feedback">
                            Por favor, seleccione un cliente.
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción del Trabajo *</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?php echo htmlspecialchars($descripcion_trabajo); ?></textarea>
                    <div class="invalid-feedback">
                        Por favor, ingrese una descripción.
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="valor_total" class="form-label">Valor Total *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control" id="valor_total" name="valor_total"
                                   placeholder="Ej: 1500,50"
                                   value="<?php echo htmlspecialchars($valor_total_trabajo); ?>" required>
                            <div class="invalid-feedback">
                                Por favor, ingrese un valor total válido.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="requiere_factura" name="requiere_factura" value="1" <?php echo ($requiere_factura_trabajo == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="requiere_factura">
                                Requiere Factura Electrónica
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="numero_factura" class="form-label">Número de Factura</label>
                        <input type="text" class="form-control" id="numero_factura" name="numero_factura" value="<?php echo htmlspecialchars($numero_factura_trabajo); ?>">
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="trabajos.php" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Crear Trabajo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validación del formulario Bootstrap
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?>