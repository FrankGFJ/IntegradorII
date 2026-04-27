<?php
require_once 'conexion.php';

try {
    echo "<h1>Estructura de Tablas:</h1>";
    
    // Tabla Movimientos
    echo "<h2>Tabla: movimientos</h2>";
    $stmt = $con->query('DESCRIBE movimientos');
    mostrar_tabla($stmt->fetchAll(PDO::FETCH_ASSOC));

    // Tabla Prestamos
    echo "<h2>Tabla: prestamos</h2>";
    $stmt = $con->query('DESCRIBE prestamos');
    mostrar_tabla($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

function mostrar_tabla($columnas) {
    if (empty($columnas)) {
        echo "<p>La tabla no existe o está vacía.</p>";
        return;
    }
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 2rem;'>";
    echo "<tr style='background: #f3f4f6;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columnas as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
