<?php
session_start();

// Incluir consultas a la base de datos
require_once 'consultas.php';
$mensaje = "";
$login_exitoso = false;
$usuario_encontrado = null;

// Lógica para procesar el formulario de Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_login = trim($_POST['login'] ?? '');
    $input_clave = $_POST['clave'] ?? '';

    try {

        if (!empty($input_login)) {
            $usuario_db = obtener_usuario_por_login($con, $input_login);

            if ($usuario_db) {
                // Verificar la contraseña (soporta texto plano actual o hash futuro)
                if ($input_clave === $usuario_db['clave'] || password_verify($input_clave, $usuario_db['clave'])) {
                    if ($usuario_db['estado'] == 1) {
                        $login_exitoso = true;
                        
                        // Regenerar ID de sesión para prevenir Session Fixation (Seguridad)
                        session_regenerate_id(true);
                        
                        // Guardar datos en la sesión
                        $_SESSION['user_id'] = $usuario_db['id'];
                        $_SESSION['rol'] = $usuario_db['rol'];
                        $_SESSION['nombre'] = $usuario_db['nombre'];
                        
                        // Determinar a qué página redirigir
                        $redirect_url = ($usuario_db['rol'] === 'estudiante') ? 'estudiante.php' : 'admin.php';
                        
                        $mensaje = "¡Bienvenido, " . htmlspecialchars($usuario_db['nombre']) . "! Ingresaste como: " . ucfirst($usuario_db['rol']) . ". Redirigiendo...";
                    } else {
                        $mensaje = "El usuario está desactivado. Contacta al administrador.";
                    }
                } else {
                    $mensaje = "Contraseña incorrecta.";
                }
            } else {
                $mensaje = "El usuario o correo ingresado no existe.";
            }
        }
    } catch (PDOException $e) {
        $mensaje = "Error de conexión con la BD: " . $e->getMessage();
    }
}
?>
