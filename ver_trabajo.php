<?php
session_start();
require_once 'config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Configuración para el layout
$titulo = 'Ver Trabajo Contable';
$pagina_actual = 'trabajos';

// Verificar si se proporcionó un ID
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

// Obtener historial de estados
$stmt = $pdo->prepare("
    SELECT * FROM historial_estados 
    WHERE trabajo_id = ? 
    ORDER BY fecha_cambio DESC
");
$stmt->execute([$id]);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener historial de pagos
$stmt = $pdo->prepare("
    SELECT * FROM pagos 
    WHERE trabajo_id = ? 
    ORDER BY fecha_pago DESC
");
$stmt->execute([$id]);
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Iniciar el buffer de salida
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Detalles del Trabajo Contable</h1>
        <div>
            <a href="trabajos.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <a href="editar_trabajo.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="registrar_pago.php?id=<?php echo $id; ?>" class="btn btn-success">
                <i class="bi bi-cash"></i> Registrar Pago
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Información General</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>ID:</th>
                            <td><?php echo $trabajo['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Fecha de Ingreso:</th>
                            <td><?php echo date('d/m/Y', strtotime($trabajo['fecha_ingreso'])); ?></td>
                        </tr>
                        <tr>
                            <th>Cliente:</th>
                            <td><?php echo htmlspecialchars($trabajo['nombre_cliente']); ?></td>
                        </tr>
                        <tr>
                            <th>Descripción:</th>
                            <td><?php echo htmlspecialchars($trabajo['descripcion']); ?></td>
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
                        <tr>
                            <th>Estado:</th>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $trabajo['estado'] === 'entregado' ? 'success' : 
                                        ($trabajo['estado'] === 'en_fabricacion' ? 'warning' : 'info'); 
                                ?>">
                                    <?php echo ucfirst($trabajo['estado']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Requiere Factura:</th>
                            <td><?php echo $trabajo['requiere_factura'] ? 'Sí' : 'No'; ?></td>
                        </tr>
                        <?php if ($trabajo['numero_factura']): ?>
                        <tr>
                            <th>Número de Factura:</th>
                            <td><?php echo htmlspecialchars($trabajo['numero_factura']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historial de Estados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Estado Anterior</th>
                                    <th>Estado Nuevo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historial as $cambio): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($cambio['fecha_cambio'])); ?></td>
                                        <td><?php echo ucfirst($cambio['estado_anterior']); ?></td>
                                        <td><?php echo ucfirst($cambio['estado_nuevo']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Historial de Pagos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pagos)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No hay pagos registrados</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pagos as $pago): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($pago['fecha_pago'])); ?></td>
                                            <td>$<?php echo number_format($pago['monto'], 2); ?></td>
                                            <td><?php echo ucfirst($pago['metodo_pago']); ?></td>
                                            <td><?php echo htmlspecialchars($pago['observaciones'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?> 