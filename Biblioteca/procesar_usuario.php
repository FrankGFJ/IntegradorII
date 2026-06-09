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
            $clave = $_POST['clave'] ?? '';
            $confirmar_clave = $_POST['confirmar_clave'] ?? '';
            $rol = $_POST['rol'];
            $estado = $_POST['estado'];

            // Validar si el usuario ya existe
            if (existe_usuario_por_username($con, $usuario)) {
                throw new Exception("El nombre de usuario '$usuario' ya está registrado.");
            }

            // Validar si el correo ya existe
            if (existe_usuario_por_correo($con, $correo)) {
                throw new Exception("El correo electrónico '$correo' ya está registrado.");
            }

            // Validar coincidencia de contraseña
            if ($clave !== $confirmar_clave) {
                throw new Exception("Las contraseñas no coinciden.");
            }

            // Validar longitud de contraseña
            $len = strlen($clave);
            if ($len < 8 || $len > 20) {
                throw new Exception("La contraseña debe tener entre 8 y 20 caracteres.");
            }

            // Validar al menos una letra mayúscula
            if (!preg_match('/[A-Z]/', $clave)) {
                throw new Exception("La contraseña debe contener al menos una letra mayúscula (A-Z).");
            }

            // Validar al menos una letra minúscula
            if (!preg_match('/[a-z]/', $clave)) {
                throw new Exception("La contraseña debe contener al menos una letra minúscula (a-z).");
            }

            // Validar al menos un número
            if (!preg_match('/[0-9]/', $clave)) {
                throw new Exception("La contraseña debe contener al menos un número (0-9).");
            }

            // Validar espacios en blanco
            if (preg_match('/\s/', $clave)) {
                throw new Exception("La contraseña no debe contener espacios en blanco.");
            }

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

            // Validar si el usuario ya existe en otra cuenta
            if (existe_usuario_por_username($con, $usuario, $id)) {
                throw new Exception("El nombre de usuario '$usuario' ya está registrado por otro usuario.");
            }

            // Validar si el correo ya existe en otra cuenta
            if (existe_usuario_por_correo($con, $correo, $id)) {
                throw new Exception("El correo electrónico '$correo' ya está registrado por otro usuario.");
            }

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
