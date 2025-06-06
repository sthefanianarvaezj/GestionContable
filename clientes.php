<?php
session_start();
require_once 'config/database.php';
require_once 'funciones_auth.php';

verificar_permiso('gestionar_clientes'); // Asegúrate de que este permiso exista y esté asignado

$titulo = 'Gestión de Clientes';
$pagina_actual = 'clientes'; // Para marcar como activo en el layout

// Manejo de eliminación de cliente
if (isset($_POST['eliminar_cliente']) && isset($_POST['cliente_id'])) {
    if (!tiene_permiso('gestionar_clientes')) { // Doble verificación por si acaso
        $_SESSION['error'] = "No tienes permiso para eliminar clientes.";
        header('Location: clientes.php');
        exit();
    }
    $cliente_id_eliminar = filter_var($_POST['cliente_id'], FILTER_VALIDATE_INT);
    if ($cliente_id_eliminar) {
        try {
            // Opcional: Verificar si el cliente tiene trabajos asociados antes de eliminar
            // $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM trabajos WHERE cliente_id = ?");
            // $stmt_check->execute([$cliente_id_eliminar]);
            // if ($stmt_check->fetchColumn() > 0) {
            //     $_SESSION['error'] = "No se puede eliminar el cliente porque tiene trabajos asociados.";
            // } else {
                $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
                $stmt->execute([$cliente_id_eliminar]);
                $_SESSION['mensaje'] = "Cliente eliminado correctamente.";
            // }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al eliminar el cliente: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "ID de cliente no válido para eliminar.";
    }
    header('Location: clientes.php');
    exit();
}


// Obtener todos los clientes
try {
    $stmt = $pdo->query("SELECT id, nombre_completo, tipo_documento, numero_documento, email, telefono FROM clientes ORDER BY nombre_completo ASC");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_db = "Error al obtener los clientes: " . $e->getMessage();
    $clientes = [];
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $titulo; ?></h1>
        <?php if (tiene_permiso('gestionar_clientes')): // O un permiso más específico como 'crear_clientes' ?>
        <a href="nuevo_cliente.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Nuevo Cliente
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($error_db)): ?>
        <div class="alert alert-danger show"><?php echo htmlspecialchars($error_db); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            Lista de Clientes
        </div>
        <div class="card-body">
            <?php if (empty($clientes) && !isset($error_db)): ?>
                <p class="text-center">No hay clientes registrados.</p>
            <?php elseif (!empty($clientes)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre Completo</th>
                                <th>Tipo Doc.</th>
                                <th>Nro. Doc.</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cliente['id']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['tipo_documento'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['numero_documento'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['telefono'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (tiene_permiso('gestionar_clientes')): // O 'editar_clientes' ?>
                                        <a href="editar_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-warning btn-sm" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (tiene_permiso('gestionar_clientes')): // O 'eliminar_clientes' ?>
                                        <form action="clientes.php" method="POST" style="display: inline-block;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.');">
                                            <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                                            <button type="submit" name="eliminar_cliente" class="btn btn-danger btn-sm" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
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