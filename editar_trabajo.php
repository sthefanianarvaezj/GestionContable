<?php
session_start();
require_once 'config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Configuración para el layout
$titulo = 'Editar Trabajo Contable';
$pagina_actual = 'trabajos';

// Verificar si se proporcionó un ID
if (!isset($_GET['id'])) {
    header('Location: trabajos.php');
    exit();
}

$id = $_GET['id'];
$mensaje = '';
$error = '';
$lista_clientes = []; // Para el dropdown de clientes

// Obtener lista de clientes para el dropdown
try {
    $stmt_clientes = $pdo->query("SELECT id, nombre_completo FROM clientes ORDER BY nombre_completo ASC");
    $lista_clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_clientes = "Error al cargar la lista de clientes: " . $e->getMessage();
    // Puedes decidir cómo manejar este error, por ejemplo, mostrar un mensaje o deshabilitar la selección.
    // Por ahora, si hay un error, el dropdown estará vacío o mostrará un error.
}

// Obtener información del trabajo
$stmt = $pdo->prepare("SELECT * FROM trabajos_contables WHERE id = ?");
$stmt->execute([$id]);
$trabajo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trabajo) {
    header('Location: trabajos.php');
    exit();
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos
        // Cambiamos la validación de nombre_cliente a cliente_id
        if (empty($_POST['cliente_id']) || empty($_POST['descripcion']) || 
            empty($_POST['valor_total']) || empty($_POST['fecha_ingreso'])) {
            throw new Exception('Todos los campos obligatorios deben estar llenos (Cliente, Descripción, Valor Total, Fecha Ingreso).');
        }

        $cliente_id_seleccionado = (int)$_POST['cliente_id'];

        // Calcular la diferencia en el valor total
        $valor_total_anterior = $trabajo['valor_total'];
        $valor_total_nuevo = floatval($_POST['valor_total']);
        $diferencia = $valor_total_nuevo - $valor_total_anterior;

        // Actualizar el saldo pendiente si el valor total cambió
        $nuevo_saldo = $trabajo['saldo_pendiente'] + $diferencia;

        // Preparar la consulta
        // Cambiamos nombre_cliente = ? por cliente_id = ?
        $stmt = $pdo->prepare("
            UPDATE trabajos_contables 
            SET fecha_ingreso = ?,
                cliente_id = ?, 
                descripcion = ?,
                valor_total = ?,
                saldo_pendiente = ?,
                requiere_factura = ?,
                numero_factura = ?
            WHERE id = ?
        ");

        // Ejecutar la consulta
        // Añadimos cliente_id_seleccionado y quitamos $_POST['nombre_cliente']
        $stmt->execute([
            $_POST['fecha_ingreso'],
            $cliente_id_seleccionado,
            $_POST['descripcion'],
            $valor_total_nuevo,
            $nuevo_saldo,
            isset($_POST['requiere_factura']) ? 1 : 0,
            $_POST['numero_factura'] ?? null,
            $id
        ]);

        $mensaje = 'Trabajo actualizado exitosamente';
        
        // Actualizar datos del trabajo para mostrar en el formulario
        $trabajo['fecha_ingreso'] = $_POST['fecha_ingreso'];
        $trabajo['cliente_id'] = $cliente_id_seleccionado; // Actualizado
        // $trabajo['nombre_cliente'] ya no se usa directamente aquí si cliente_id es la referencia
        $trabajo['descripcion'] = $_POST['descripcion'];
        $trabajo['valor_total'] = $valor_total_nuevo;
        $trabajo['saldo_pendiente'] = $nuevo_saldo;
        $trabajo['requiere_factura'] = isset($_POST['requiere_factura']) ? 1 : 0;
        $trabajo['numero_factura'] = $_POST['numero_factura'] ?? null;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Iniciar el buffer de salida
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Editar Trabajo Contable</h1>
        <a href="ver_trabajo.php?id=<?php echo $id; ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (isset($error_clientes)): ?>
        <div class="alert alert-warning"><?php echo $error_clientes; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="fecha_ingreso" class="form-label">Fecha de Ingreso *</label>
                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" 
                               value="<?php echo $trabajo['fecha_ingreso']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="cliente_id" class="form-label">Cliente Asociado *</label>
                        <select class="form-select <?php echo (isset($error) && strpos($error, 'Cliente') !== false) ? 'is-invalid' : ''; ?>" id="cliente_id" name="cliente_id" required>
                            <option value="">Seleccione un cliente...</option>
                            <?php if (!empty($lista_clientes)): ?>
                                <?php foreach ($lista_clientes as $cliente_item): ?>
                                    <option value="<?php echo htmlspecialchars($cliente_item['id']); ?>" 
                                        <?php echo (isset($trabajo['cliente_id']) && $trabajo['cliente_id'] == $cliente_item['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cliente_item['nombre_completo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (isset($error) && strpos($error, 'Cliente') !== false): ?>
                            <div class="invalid-feedback">Debe seleccionar un cliente.</div>
                        <?php endif; ?>
                         <?php if (empty($lista_clientes) && !isset($error_clientes)): ?>
                            <div class="form-text text-warning">No hay clientes disponibles para seleccionar. Puede <a href="nuevo_cliente.php">agregar un nuevo cliente</a>.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción del Trabajo *</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?php 
                        echo htmlspecialchars($trabajo['descripcion']); 
                    ?></textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="valor_total" class="form-label">Valor Total *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="valor_total" name="valor_total" 
                                   step="0.01" min="0" value="<?php echo $trabajo['valor_total']; ?>" required>
                        </div>
                        <div class="form-text">
                            Saldo pendiente actual: $<?php echo number_format($trabajo['saldo_pendiente'], 2); ?>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="requiere_factura" name="requiere_factura"
                                   <?php echo $trabajo['requiere_factura'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="requiere_factura">
                                Requiere Factura Electrónica
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="numero_factura" class="form-label">Número de Factura</label>
                        <input type="text" class="form-control" id="numero_factura" name="numero_factura"
                               value="<?php echo htmlspecialchars($trabajo['numero_factura'] ?? ''); ?>">
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="ver_trabajo.php?id=<?php echo $id; ?>" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Validación del formulario
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