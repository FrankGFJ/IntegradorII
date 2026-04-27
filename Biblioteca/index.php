<?php require_once 'login.php'; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oasis Literario - Iniciar Sesión</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="overlay"></div>

    <div class="login-wrapper">
        <div class="brand">
            <span class="brand-icon">📚🌿</span>
            <h1>Oasis Literario</h1>
            <p>Sistema de Gestión de inventarios</p>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert <?= $login_exitoso ? 'alert-success' : 'alert-error' ?>">
                <?= $mensaje ?>
            </div>
            
            <?php if ($login_exitoso && isset($redirect_url)): ?>
                <script>
                    setTimeout(function() {
                        window.location.href = "<?= $redirect_url ?>";
                    }, 1500); // 1.5 seconds delay
                </script>
            <?php endif; ?>
        <?php endif; ?>

        <!-- El formulario envía los datos por POST a la misma página, que es interceptado por login.php -->
        <form method="POST" action="index.php">
            <div class="form-group">
                <label for="login">Usuario o Correo Electrónico</label>
                <input type="text" id="login" name="login" placeholder="ej: admin / admin@correo.com"
                    value="<?= isset($_POST['login']) ? htmlspecialchars($_POST['login']) : '' ?>" required>
            </div>

            <div class="form-group">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-primary">Ingresar al Sistema</button>
        </form>
    </div>
</body>

</html>