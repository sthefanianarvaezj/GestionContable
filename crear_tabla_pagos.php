<?php
require_once 'config/database.php';

try {
    // Leer el archivo SQL
    $sql = file_get_contents('sql/pagos.sql');
    
    // Ejecutar el SQL
    $pdo->exec($sql);
    
    echo "Tabla de pagos creada exitosamente.";
} catch (PDOException $e) {
    echo "Error al crear la tabla de pagos: " . $e->getMessage();
}
?> 