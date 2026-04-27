<?php
// Configuración de la Base de Datos
$host = '127.0.0.1';
$port = '3306';
$dbname = 'oasis';
$db_user = 'root';
$db_pass = '';

try {
    // Establecer conexión con PDO
    $con = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    // Configurar el modo de errores de PDO para que lance excepciones
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Detener la ejecución si hay un error de conexión
    die("Error de conexión con la BD: " . $e->getMessage());
}
?>
