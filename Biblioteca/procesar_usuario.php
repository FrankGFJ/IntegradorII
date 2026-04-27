<?php
session_start();
require_once 'consultas.php';

// Validar que exista la sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion == 'crear') {
            $usuario = trim($_POST['usuario']);
            $nombre = trim($_POST['nombre']);
            $correo = trim($_POST['correo']);
            $clave = trim($_POST['clave']);
            $rol = $_POST['rol'];
            $estado = $_POST['estado'];

            // Cifrar la contraseña con BCRYPT
            $clave_hash = password_hash($clave, PASSWORD_BCRYPT);

            insertar_usuario($con, $usuario, $nombre, $correo, $clave_hash, $rol, $estado);
            
            header("Location: admin.php?seccion=usuarios&msg=Usuario+registrado+exitosamente");
            exit();
        } elseif ($accion == 'editar') {
            $id = $_POST['id'];
            $usuario = trim($_POST['usuario']);
            $nombre = trim($_POST['nombre']);
            $correo = trim($_POST['correo']);
            $rol = $_POST['rol'];
            $estado = $_POST['estado'];

            actualizar_usuario($con, $id, $usuario, $nombre, $correo, $rol, $estado);
            
            header("Location: admin.php?seccion=usuarios&msg=Usuario+actualizado+exitosamente");
            exit();
        }
    } catch (Exception $e) {
        $error = urlencode($e->getMessage());
        header("Location: admin.php?seccion=usuarios&error=" . $error);
        exit();
    }
} else {
    // Si acceden directamente sin POST
    header("Location: admin.php?seccion=usuarios");
    exit();
}
?>
