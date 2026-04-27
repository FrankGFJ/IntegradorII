<?php
session_start();
require_once 'consultas.php';

// Validar que exista la sesión y que sea estudiante
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_prestamo = $_POST['id_prestamo'] ?? null;
    $id_usuario = $_SESSION['user_id'];

    if (empty($id_prestamo)) {
        header("Location: estudiante.php?seccion=mis_libros&error=" . urlencode("No se identificó el préstamo a devolver."));
        exit();
    }

    try {
        procesar_transaccion_devolucion($con, $id_usuario, $id_prestamo);
        header("Location: estudiante.php?seccion=mis_libros&msg=" . urlencode("¡Has devuelto el libro con éxito! Tu cupo ha quedado libre."));
        exit();
    } catch (Exception $e) {
        $error = urlencode($e->getMessage());
        header("Location: estudiante.php?seccion=mis_libros&error=" . $error);
        exit();
    }
} else {
    header("Location: estudiante.php?seccion=mis_libros");
    exit();
}
?>
