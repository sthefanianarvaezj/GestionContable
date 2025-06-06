<?php
session_start();
require_once 'config/database.php';
require_once 'funciones_auth.php'; // Para verificar permisos y obtener roles

verificar_permiso('gestionar_usuarios'); // Solo usuarios con este permiso pueden acceder

// Configuración para el layout
$titulo = 'Gestión de Usuarios';
$pagina_actual = 'usuarios';

// Lógica para eliminar usuario
if (isset($_GET['eliminar']) && filter_var($_GET['eliminar'], FILTER_VALIDATE_INT)) {
    $id_usuario_eliminar = $_GET['eliminar'];

    // Evitar que un usuario se elimine a sí mismo
    if ($id_usuario_eliminar == $_SESSION['usuario_id']) {
        $_SESSION['error'] = "No puedes eliminar tu propia cuenta.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id_usuario_eliminar]);
            $_SESSION['mensaje'] = "Usuario eliminado correctamente.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al eliminar el usuario: " . $e->getMessage();
        }
    }
    header('Location: usuarios.php');
    exit();
}

// Obtener todos los usuarios con sus roles
try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre, u.email, u.fecha_registro, r.nombre_rol 
        FROM usuarios u
        LEFT JOIN roles r ON u.rol_id = r.id
        ORDER BY u.nombre ASC
    ");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_usuarios = "Error al cargar los usuarios: " . $e->getMessage();
    $usuarios = [];
}

ob_start();
?>

<div class="container-fluid">
    <h1 class="mb-4"><?php echo $titulo; ?></h1>

    <?php if (isset($error_usuarios)): ?>
        <div class="alert alert-danger"><?php echo $error_usuarios; ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <a href="nuevo_usuario.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Nuevo Usuario
        </a>
        <!-- Botón para descargar el reporte de usuarios -->
        <a href="reportes/usuarios_excel.php" class="btn btn-primary">
            <i class="bi bi-download"></i> Descargar Excel
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            Lista de Usuarios
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Fecha de Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No hay usuarios registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nombre_rol'] ?? 'No asignado'); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?></td>
                                    <td>
                                        <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <?php if ($usuario['id'] != $_SESSION['usuario_id']): // No mostrar botón de eliminar para el usuario actual ?>
                                        <a href="usuarios.php?eliminar=<?php echo $usuario['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Eliminar"
                                           onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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

<?php
// reporte_usuarios.php
require_once __DIR__ . '/../config/database.php';

$sql = "SELECT u.id, u.nombre, u.email, u.fecha_registro, r.nombre_rol 
        FROM usuarios u
        LEFT JOIN roles r ON u.rol_id = r.id
        ORDER BY u.nombre ASC";
$stmt = $pdo->query($sql);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=reporte_usuarios.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Nombre', 'Correo', 'Rol', 'Fecha de Registro']);

if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['nombre'],
            $row['email'],
            $row['nombre_rol'] ?? 'No asignado',
            $row['fecha_registro']
        ]);
    }
}

fclose($output);
exit;