<?php
session_start();
require_once 'config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Configuración para el layout
$titulo = 'Trabajos Contables';
$pagina_actual = 'trabajos';

// Obtener parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construir la consulta SQL base
$sql = "
    SELECT t.*, 
           c.nombre_completo AS nombre_cliente_asociado, -- Renamed to avoid conflict if t.nombre_cliente still exists
           c.id AS id_cliente_asociado,
           COALESCE(SUM(p.monto), 0) as total_pagado,
           t.valor_total - COALESCE(SUM(p.monto), 0) as saldo_pendiente
    FROM trabajos_contables t
    LEFT JOIN clientes c ON t.cliente_id = c.id -- JOIN con la tabla clientes
    LEFT JOIN pagos p ON t.id = p.trabajo_id
    WHERE 1=1
";

$params = [];

// Agregar condiciones de búsqueda
if (!empty($busqueda)) {
    // Buscar por descripción del trabajo O nombre del cliente asociado
    $sql .= " AND (t.descripcion LIKE ? OR c.nombre_completo LIKE ?)"; 
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if (!empty($fecha_desde)) {
    $sql .= " AND t.fecha_ingreso >= ?";
    $params[] = $fecha_desde;
}

if (!empty($fecha_hasta)) {
    $sql .= " AND t.fecha_ingreso <= ?";
    $params[] = $fecha_hasta;
}

if (!empty($estado)) {
    $sql .= " AND t.estado = ?";
    $params[] = $estado;
}

// Agrupar y ordenar
// Es importante agregar todas las columnas no agregadas del SELECT al GROUP BY
// o asegurarse de que son funcionalmente dependientes de la clave primaria agrupada (t.id en este caso).
// Para portabilidad y claridad, es mejor listarlas.
$sql .= " GROUP BY t.id, c.nombre_completo, c.id ORDER BY t.fecha_ingreso DESC, t.id DESC"; // Agregado t.id DESC para un ordenamiento secundario consistente

// Preparar y ejecutar la consulta
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trabajos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Iniciar el buffer de salida
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Trabajos Contables</h1>
        <a href="nuevo_trabajo.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Trabajo
        </a>
    </div>

    <!-- Formulario de búsqueda -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="busqueda" class="form-label">Buscar por cliente o descripción</label>
                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                           value="<?php echo htmlspecialchars($busqueda); ?>" 
                           placeholder="Nombre del cliente o descripción">
                </div>
                <div class="col-md-2">
                    <label for="fecha_desde" class="form-label">Fecha desde</label>
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                           value="<?php echo $fecha_desde; ?>">
                </div>
                <div class="col-md-2">
                    <label for="fecha_hasta" class="form-label">Fecha hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                           value="<?php echo $fecha_hasta; ?>">
                </div>
                <div class="col-md-2">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="recibido" <?php echo $estado === 'recibido' ? 'selected' : ''; ?>>Recibido</option>
                        <option value="en_fabricacion" <?php echo $estado === 'en_fabricacion' ? 'selected' : ''; ?>>En Fabricación</option>
                        <option value="entregado" <?php echo $estado === 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        <a href="trabajos.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($trabajos)): ?>
                <div class="alert alert-info">
                    No se encontraron trabajos que coincidan con los criterios de búsqueda.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Descripción</th>
                                <th>Valor Total</th>
                                <th>Pagado</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trabajos as $trabajo): ?>
                                <tr>
                                    <td><?php echo $trabajo['id']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($trabajo['fecha_ingreso'])); ?></td>
                                    <td>
                                        <?php if (!empty($trabajo['nombre_cliente_asociado'])): ?>
                                            <a href="editar_cliente.php?id=<?php echo $trabajo['id_cliente_asociado']; ?>">
                                                <?php echo htmlspecialchars($trabajo['nombre_cliente_asociado']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(mb_strimwidth($trabajo['descripcion'], 0, 50, "...")); ?></td>
                                    <td>$<?php echo number_format($trabajo['valor_total'] ?? 0, 2, ',', '.'); ?></td>
                                    <td>$<?php echo number_format($trabajo['total_pagado'] ?? 0, 2, ',', '.'); ?></td>
                                    <td>$<?php echo number_format($trabajo['saldo_pendiente'] ?? 0, 2, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $trabajo['estado'] === 'entregado' ? 'success' : 
                                                ($trabajo['estado'] === 'en_fabricacion' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($trabajo['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="ver_trabajo.php?id=<?php echo $trabajo['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Ver">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="editar_trabajo.php?id=<?php echo $trabajo['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($trabajo['estado'] !== 'entregado'): ?>
                                                <a href="cambiar_estado.php?id=<?php echo $trabajo['id']; ?>&estado=<?php 
                                                    echo $trabajo['estado'] === 'recibido' ? 'en_fabricacion' : 
                                                        ($trabajo['estado'] === 'en_fabricacion' ? 'entregado' : 'recibido'); 
                                                ?>" class="btn btn-sm btn-success" title="Cambiar Estado">
                                                    <i class="bi bi-arrow-right-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?>