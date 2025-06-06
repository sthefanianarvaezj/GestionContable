<?php
require_once 'config/database.php';

// Clase para generar el reporte de trabajos contables
class ReporteTrabajosContables
{
    public function generar()
    {
        global $pdo;

        $sql = "SELECT t.id, t.fecha_ingreso, t.descripcion, t.valor_total, t.saldo_pendiente, t.estado, 
                       c.nombre_completo AS cliente
                FROM trabajos_contables t
                LEFT JOIN clientes c ON t.cliente_id = c.id
                ORDER BY t.fecha_ingreso DESC";
        $stmt = $pdo->query($sql);

        // Encabezados para descargar como Excel (CSV)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_trabajos_contables.csv');

        $output = fopen('php://output', 'w');
        // Encabezados del archivo
        fputcsv($output, [
            'ID', 'Fecha Ingreso', 'Cliente', 'Descripción', 'Valor Total', 'Saldo Pendiente', 'Estado'
        ]);

        // Filas de datos
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'],
                    $row['fecha_ingreso'],
                    $row['cliente'],
                    $row['descripcion'],
                    $row['valor_total'],
                    $row['saldo_pendiente'],
                    $row['estado']
                ]);
            }
        }

        fclose($output);
        exit;
    }
}

// Ejecutar el reporte
$reporte = new ReporteTrabajosContables();
$reporte->generar();