<?php
session_start();
require_once 'config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Configuración para el layout
$titulo = 'Dashboard';
$pagina_actual = 'dashboard';

// Obtener estadísticas
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_trabajos,
        SUM(CASE WHEN estado = 'recibido' THEN 1 ELSE 0 END) as trabajos_recibidos,
        SUM(CASE WHEN estado = 'en_fabricacion' THEN 1 ELSE 0 END) as trabajos_en_fabricacion,
        SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as trabajos_entregados,
        SUM(valor_total) as valor_total_trabajos,
        SUM(saldo_pendiente) as saldo_pendiente_total
    FROM trabajos_contables
");
$stmt->execute();
$estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener últimos trabajos
$stmt = $pdo->prepare("
    SELECT * FROM trabajos_contables 
    ORDER BY fecha_ingreso DESC 
    LIMIT 5
");
$stmt->execute();
$ultimos_trabajos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Iniciar el buffer de salida
ob_start();
?>

<div class="container-fluid">
    <h1 class="mb-4">Dashboard</h1>

    <!-- Tarjetas de estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Trabajos</h5>
                    <h2 class="card-text"><?php echo $estadisticas['total_trabajos']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">En Fabricación</h5>
                    <h2 class="card-text"><?php echo $estadisticas['trabajos_en_fabricacion']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Entregados</h5>
                    <h2 class="card-text"><?php echo $estadisticas['trabajos_entregados']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Saldo Pendiente</h5>
                    <h2 class="card-text">$<?php echo number_format($estadisticas['saldo_pendiente_total'], 2); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Módulos -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Trabajos Contables</h5>
                    <p class="card-text">Gestione los trabajos contables, su estado y seguimiento.</p>
                    <a href="trabajos.php" class="btn btn-primary">
                        <i class="bi bi-briefcase"></i> Ir a Trabajos
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Clientes</h5>
                    <p class="card-text">Administre la información de sus clientes.</p>
                    <a href="clientes.php" class="btn btn-primary">
                        <i class="bi bi-people"></i> Ir a Clientes
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Usuarios</h5>
                    <p class="card-text">Gestione los usuarios del sistema.</p>
                    <a href="usuarios.php" class="btn btn-primary">
                        <i class="bi bi-person-circle"></i> Ir a Usuarios
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos trabajos -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Últimos Trabajos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_trabajos as $trabajo): ?>
                            <tr>
                                <td><?php echo $trabajo['id']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($trabajo['fecha_ingreso'])); ?></td>
                                <td><?php echo htmlspecialchars($trabajo['nombre_cliente']); ?></td>
                                <td>$<?php echo number_format($trabajo['valor_total'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $trabajo['estado'] === 'entregado' ? 'success' : 
                                            ($trabajo['estado'] === 'en_fabricacion' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst($trabajo['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="ver_trabajo.php?id=<?php echo $trabajo['id']; ?>" 
                                       class="btn btn-sm btn-info" title="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
require_once 'layout.php';
?> 