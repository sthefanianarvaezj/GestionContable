<?php
session_start();
require_once 'config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Configuración para el layout
$titulo = 'Cambiar Estado de Trabajo';
$pagina_actual = 'trabajos';

// Verificar si se proporcionaron los parámetros necesarios
if (!isset($_GET['id']) || !isset($_GET['estado'])) {
    header('Location: trabajos.php');
    exit();
}

$id = $_GET['id'];
$nuevo_estado = $_GET['estado'];

// Validar el nuevo estado
$estados_validos = ['recibido', 'en_fabricacion', 'entregado'];
if (!in_array($nuevo_estado, $estados_validos)) {
    header('Location: trabajos.php');
    exit();
}

try {
    // Obtener el trabajo actual
    $stmt = $pdo->prepare("SELECT * FROM trabajos_contables WHERE id = ?");
    $stmt->execute([$id]);
    $trabajo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trabajo) {
        throw new Exception('Trabajo no encontrado');
    }

    // Validar que no se pueda marcar como entregado si hay saldo pendiente
    if ($nuevo_estado === 'entregado' && $trabajo['saldo_pendiente'] > 0) {
        throw new Exception('No se puede marcar como entregado un trabajo con saldo pendiente');
    }

    // Validar la secuencia de estados
    if ($nuevo_estado === 'en_fabricacion' && $trabajo['estado'] !== 'recibido') {
        throw new Exception('Solo se puede cambiar a "En Fabricación" desde estado "Recibido"');
    }

    if ($nuevo_estado === 'entregado' && $trabajo['estado'] === 'recibido') {
        throw new Exception('No se puede cambiar directamente de "Recibido" a "Entregado"');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Registrar el cambio en el historial
    $stmt = $pdo->prepare("
        INSERT INTO historial_estados (trabajo_id, estado_anterior, estado_nuevo)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$id, $trabajo['estado'], $nuevo_estado]);

    // Actualizar el estado del trabajo
    $stmt = $pdo->prepare("
        UPDATE trabajos_contables 
        SET estado = ?, fecha_cambio_estado = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$nuevo_estado, $id]);

    $pdo->commit();
    
    // Redirigir con mensaje de éxito
    header("Location: ver_trabajo.php?id=$id&mensaje=Estado actualizado exitosamente");
} catch (Exception $e) {
    // Solo hacer rollback si la transacción está activa
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Redirigir con mensaje de error
    header("Location: ver_trabajo.php?id=$id&error=" . urlencode($e->getMessage()));
}
exit(); 