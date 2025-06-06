<?php
session_start();
require_once 'config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Configuración para el layout
$titulo = 'Registrar Pago';
$pagina_actual = 'trabajos';

// Verificar si se proporcionó un ID de trabajo
if (!isset($_GET['id'])) {
    header('Location: trabajos.php');
    exit();
}

$id = $_GET['id'];
$mensaje = isset($_GET['mensaje']) ? $_GET['mensaje'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Obtener información del trabajo
$stmt = $pdo->prepare("
    SELECT t.*, 
           COALESCE(SUM(p.monto), 0) as total_pagado,
           t.valor_total - COALESCE(SUM(p.monto), 0) as saldo_pendiente
    FROM trabajos_contables t
    LEFT JOIN pagos p ON t.id = p.trabajo_id
    WHERE t.id = ?
    GROUP BY t.id
");
$stmt->execute([$id]);
$trabajo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trabajo) {
    header('Location: trabajos.php');
    exit();
}

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto = isset($_POST['monto']) ? floatval($_POST['monto']) : 0;
    $metodo_pago = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : '';
    $fecha_pago = isset($_POST['fecha_pago']) ? $_POST['fecha_pago'] : date('Y-m-d');
    $observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';

    // Validar el monto
    if ($monto <= 0) {
        $error = 'El monto debe ser mayor a cero';
    } elseif ($monto > $trabajo['saldo_pendiente']) {
        $error = 'El monto no puede ser mayor al saldo pendiente';
    } elseif (empty($metodo_pago)) {
        $error = 'Debe seleccionar un método de pago';
    } else {
        try {
            // Iniciar transacción
            $pdo->beginTransaction();

            // Insertar el pago
            $stmt = $pdo->prepare("
                INSERT INTO pagos (trabajo_id, monto, metodo_pago, fecha_pago, observaciones)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id, $monto, $metodo_pago, $fecha_pago, $observaciones]);

            // Actualizar el saldo pendiente en la tabla de trabajos
            $stmt = $pdo->prepare("
                UPDATE trabajos_contables 
                SET saldo_pendiente = saldo_pendiente - ?
                WHERE id = ?
            ");
            $stmt->execute([$monto, $id]);

            $pdo->commit();
            
            // Redirigir con mensaje de éxito
            header("Location: ver_trabajo.php?id=$id&mensaje=Pago registrado exitosamente");
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Error al registrar el pago: ' . $e->getMessage();
        }
    }
}

// Iniciar el buffer de salida
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Registrar Pago</h1>
        <div>
            <a href="ver_trabajo.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Información del Trabajo</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>ID:</th>
                            <td><?php echo $trabajo['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Cliente:</th>
                            <td><?php echo htmlspecialchars($trabajo['nombre_cliente']); ?></td>
                        </tr>
                        <tr>
                            <th>Valor Total:</th>
                            <td>$<?php echo number_format($trabajo['valor_total'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Total Pagado:</th>
                            <td>$<?php echo number_format($trabajo['total_pagado'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Saldo Pendiente:</th>
                            <td>$<?php echo number_format($trabajo['saldo_pendiente'], 2); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Registrar Nuevo Pago</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="monto" class="form-label">Monto a Pagar</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="monto" name="monto" step="0.01" min="0.01" max="<?php echo $trabajo['saldo_pendiente']; ?>" value="<?php echo $trabajo['saldo_pendiente']; ?>" required>
                            </div>
                            <div class="form-text">Máximo: $<?php echo number_format($trabajo['saldo_pendiente'], 2); ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="metodo_pago" class="form-label">Método de Pago</label>
                            <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                <option value="">Seleccione un método</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="cheque">Cheque</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                            <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Registrar Pago</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?> 