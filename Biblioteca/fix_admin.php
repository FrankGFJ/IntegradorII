<?php
require_once 'conexion.php';

try {
    // Restaurar los privilegios de administrador del usuario base
    $stmt = $con->prepare("UPDATE usuarios SET rol = 'admin' WHERE usuario = 'admin'");
    $stmt->execute();
    
    // También aseguremos que su estado esté activo por si acaso
    $stmt = $con->prepare("UPDATE usuarios SET estado = 1 WHERE usuario = 'admin'");
    $stmt->execute();
    
    echo "<h1>¡Privilegios restaurados con éxito!</h1>";
    echo "<p>Tu cuenta 'admin' ha vuelto a tener el rol de Administrador.</p>";
    echo "<a href='index.php' style='display:inline-block; padding: 10px 20px; background: #2d6a4f; color: white; text-decoration: none; border-radius: 5px;'>Ir al Login</a>";
} catch (PDOException $e) {
    echo "Error al intentar reparar la cuenta: " . $e->getMessage();
}
?>
