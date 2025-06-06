<?php
require_once __DIR__ . '/config/database.php';

// Consulta usando PDO
$sql = "SELECT id, nombre, email, fecha_registro FROM usuarios";
$stmt = $pdo->query($sql);

// Encabezados para descargar como Excel (CSV)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=reporte_usuarios.csv');

// Abrir salida estándar
$output = fopen('php://output', 'w');

// Escribir encabezados
fputcsv($output, ['ID', 'Nombre', 'Correo', 'Fecha de Registro']);

// Escribir filas
if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [$row['id'], $row['nombre'], $row['email'], $row['fecha_registro']]);
    }
}

fclose($output);
exit;