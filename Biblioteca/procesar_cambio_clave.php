<?php
session_start();
require_once 'consultas.php';

// Validar que exista la sesión y que sea estudiante
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $clave_actual = $_POST['clave_actual'] ?? '';
    $clave_nueva = $_POST['clave_nueva'] ?? '';
    $clave_confirmar = $_POST['clave_confirmar'] ?? '';

    try {
        // 1. Obtener la clave actual del usuario de la BD
        $stmt = $con->prepare("SELECT clave FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            throw new Exception("Usuario no encontrado.");
        }

        // 2. Verificar la contraseña actual (soporta texto plano o hash BCRYPT)
        if ($usuario['clave'] !== $clave_actual && !password_verify($clave_actual, $usuario['clave'])) {
            throw new Exception("La contraseña actual es incorrecta.");
        }

        // 3. Validar coincidencia de contraseña nueva
        if ($clave_nueva !== $clave_confirmar) {
            throw new Exception("Las contraseñas nuevas no coinciden.");
        }

        // 4. Validar longitud de contraseña nueva
        $len = strlen($clave_nueva);
        if ($len < 8 || $len > 20) {
            throw new Exception("La nueva contraseña debe tener entre 8 y 20 caracteres.");
        }

        // Validar al menos una letra mayúscula
        if (!preg_match('/[A-Z]/', $clave_nueva)) {
            throw new Exception("La nueva contraseña debe contener al menos una letra mayúscula (A-Z).");
        }

        // Validar al menos una letra minúscula
        if (!preg_match('/[a-z]/', $clave_nueva)) {
            throw new Exception("La nueva contraseña debe contener al menos una letra minúscula (a-z).");
        }

        // Validar al menos un número
        if (!preg_match('/[0-9]/', $clave_nueva)) {
            throw new Exception("La nueva contraseña debe contener al menos un número (0-9).");
        }

        // Validar espacios en blanco
        if (preg_match('/\s/', $clave_nueva)) {
            throw new Exception("La nueva contraseña no debe contener espacios en blanco.");
        }

        // 5. Cifrar la nueva contraseña con BCRYPT
        $clave_hash = password_hash($clave_nueva, PASSWORD_BCRYPT);

        // 6. Actualizar la contraseña
        actualizar_clave_usuario($con, $_SESSION['user_id'], $clave_hash);

        header("Location: estudiante.php?seccion=cambiar_contrasena&msg=" . urlencode("Contraseña actualizada exitosamente."));
        exit();

    } catch (Exception $e) {
        header("Location: estudiante.php?seccion=cambiar_contrasena&error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: estudiante.php?seccion=cambiar_contrasena");
    exit();
}
?>
