<?php
session_start();
require_once 'consultas.php';

// Validar que exista la sesión y que sea admin
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_prestamo = $_POST['id_prestamo'] ?? null;
    $id_admin = $_SESSION['user_id'];

    if (empty($id_prestamo)) {
        header("Location: admin.php?seccion=prestamos&error=" . urlencode("ID de préstamo inválido."));
        exit();
    }

    try {
        procesar_devolucion_admin($con, $id_prestamo, $id_admin);
        header("Location: admin.php?seccion=prestamos&msg=" . urlencode("Préstamo procesado y devuelto correctamente."));
        exit();
    } catch (Exception $e) {
        $error = urlencode($e->getMessage());
        header("Location: admin.php?seccion=prestamos&error=" . $error);
        exit();
    }
} else {
    header("Location: admin.php?seccion=prestamos");
    exit();
}
?>
