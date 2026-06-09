<?php
session_start();
require_once 'consultas.php';

// Validar que exista la sesión y que sea estudiante
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'estudiante') {
    header("Location: index.php");
    exit();
}

$seccion = $_GET['seccion'] ?? 'catalogo';

$libros = [];
if ($seccion === 'catalogo') {
    try {
        $libros = obtener_inventario_libros($con);
    } catch (Exception $e) {
        $error_db = $e->getMessage();
    }
}

// Variables para Dinámica de Préstamos
$libros_reservados_ids = obtener_libros_reservados_estudiante($con, $_SESSION['user_id']);
$tope_alcanzado = count($libros_reservados_ids) >= 2;
$tiene_retraso = tiene_prestamos_retrasados($con, $_SESSION['user_id']);

$mis_prestamos = obtener_detalle_prestamos_estudiante($con, $_SESSION['user_id']);
$prestamos_activos = [];
foreach ($mis_prestamos as $p) {
    if ($p['estado'] === 'activo' || $p['estado'] === 'retrasado') {
        $prestamos_activos[] = $p;
    }
}

$user_info = ['nombre' => $_SESSION['nombre'], 'correo' => '', 'rol' => 'Estudiante'];
try {
    $stmt = $con->prepare("SELECT nombre, correo, rol FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_info = $row;
    }
} catch (Exception $e) {}

function render_notif_bell($prestamos_activos) {
    $num_activos = count($prestamos_activos);
    
    $tiene_retrasados = false;
    $tiene_proximos = false;
    foreach ($prestamos_activos as $p) {
        $dias = (strtotime($p['fecha_devolucion']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
        if ($p['estado'] === 'retrasado' || $dias < 0) {
            $tiene_retrasados = true;
        } elseif ($dias <= 2) {
            $tiene_proximos = true;
        }
    }

    $titulo_notif = "Préstamos Activos";
    $badge_bg = "background: var(--primary);";
    if ($tiene_retrasados) {
        $titulo_notif = "⚠️ Préstamo Vencido";
        $badge_bg = "background: #ef4444;";
    } elseif ($tiene_proximos) {
        $titulo_notif = "⏳ Préstamo por vencer";
        $badge_bg = "background: #ea580c;";
    }

    ob_start();
    ?>
    <div class="notif-container">
        <button type="button" class="notif-bell-btn" onclick="toggleNotifDropdown(event)" title="Notificaciones de préstamos">
            🔔
            <?php if ($num_activos > 0): ?>
                <span class="notif-badge" style="<?= $tiene_retrasados ? 'background-color: #ef4444; box-shadow: 0 2px 4px rgba(239,68,68,0.3);' : ($tiene_proximos ? 'background-color: #ea580c; box-shadow: 0 2px 4px rgba(234,88,12,0.3);' : '') ?>"><?= $num_activos ?></span>
            <?php endif; ?>
        </button>
        <div class="notif-dropdown" id="notifDropdown">
            <div class="notif-dropdown-header">
                <span><?= $titulo_notif ?></span>
                <span style="font-size: 0.8rem; <?= $badge_bg ?> color: white; padding: 0.15rem 0.5rem; border-radius: 9999px;"><?= $num_activos ?></span>
            </div>
            <div class="notif-dropdown-body">
                <?php if ($num_activos > 0): ?>
                    <?php foreach ($prestamos_activos as $p): ?>
                        <?php 
                            $dias = (strtotime($p['fecha_devolucion']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                            $es_retraso = $p['estado'] === 'retrasado' || $dias < 0;
                            if ($es_retraso) {
                                $time_text = "⚠️ RETRASADO (" . abs($dias) . " días)";
                                $time_color = "color: #ef4444;";
                            } elseif ($dias == 0) {
                                $time_text = "⏰ DEVOLVER HOY";
                                $time_color = "color: #ea580c;";
                            } else {
                                $time_text = "⏱️ Faltan $dias días";
                                $time_color = "color: #059669;";
                            }
                        ?>
                        <a href="estudiante.php?seccion=mis_libros" class="notif-item">
                            <div class="notif-item-title"><?= htmlspecialchars($p['titulo']) ?></div>
                            <div class="notif-item-time" style="<?= $time_color ?>"><?= $time_text ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="notif-item-empty">
                        🎉 ¡Al día! No tienes préstamos activos.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Estudiantil - Oasis Literario</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos amigables específicos para estudiantes */
        .btn-reservation {
            background-color: rgba(59, 130, 246, 0.1); /* Fondo translúcido tipo glass */
            color: #2563eb; /* Azul sofisticado */
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 0.4rem 1.25rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            display: inline-flex;
            align-items: center;
            gap: 0.35rem; /* Espaciado con el icono */
        }
        .btn-reservation:hover { 
            background-color: #3b82f6;
            color: #ffffff;
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(59, 130, 246, 0.25);
        }

        .student-avatar {
            width: 60px; 
            height: 60px; 
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%); 
            color: white; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.8rem; 
            font-weight: bold;
            margin: 0 auto 0.75rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .student-badge {
            display: inline-block; 
            background: #d1fae5; 
            color: #065f46; 
            padding: 0.25rem 0.75rem; 
            border-radius: 9999px; 
            font-size: 0.75rem; 
            font-weight: 700; 
            margin-bottom: 1.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Contenedor de la campanita y dropdown */
        .notif-container {
            position: relative;
            display: inline-block;
        }

        /* Botón de la campanita */
        .notif-bell-btn {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 50%;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            cursor: pointer;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .notif-bell-btn:hover {
            transform: scale(1.1) rotate(15deg);
            background: #ffffff;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
        }

        /* Badge rojo indicador */
        .notif-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background-color: #ef4444;
            color: white;
            font-size: 0.75rem;
            font-weight: 800;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Panel Dropdown de Notificaciones */
        .notif-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 12px);
            width: 340px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 1.25rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .notif-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Cabecera del dropdown */
        .notif-dropdown-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 700;
            color: var(--text-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            background: rgba(249, 250, 251, 0.5);
        }

        /* Cuerpo del dropdown */
        .notif-dropdown-body {
            max-height: 280px;
            overflow-y: auto;
            padding: 0.5rem 0;
        }

        /* Elemento del listado de préstamos */
        .notif-item {
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            transition: background 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .notif-item:last-child {
            border-bottom: none;
        }

        .notif-item:hover {
            background: rgba(45, 106, 79, 0.05);
        }

        .notif-item-title {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .notif-item-time {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .notif-item-empty {
            padding: 2.5rem 1.25rem;
            text-align: center;
            color: var(--text-light);
            font-size: 0.9rem;
        }
    </style>
</head>

<body class="admin-body">
    <!-- Un overlay más claro y suave para la vista de estudiantes -->
    <div class="overlay" style="background: rgba(243, 244, 246, 0.85); z-index: -1; position: fixed; width: 100%; height: 100%;"></div>

    <!-- Corregido el contenedor para que acople perfecto al 100vh usando la clase nativa -->
    <div class="admin-layout" style="background: rgba(255, 255, 255, 0.85); box-shadow: 0 10px 40px rgba(0,0,0,0.08);">
        <!-- Barra Lateral del Estudiante -->
        <aside class="sidebar" style="background: rgba(255,255,255,0.6); border-right: 1px solid rgba(0,0,0,0.05);">
            <div class="sidebar-brand" style="justify-content: center; flex-direction: column; text-align: center; border-bottom: none; padding-bottom: 0;">
                <span class="brand-icon" style="font-size: 3rem; margin-bottom: 0.5rem;">🎓</span>
                <h2 style="font-size: 1.4rem; color: var(--primary);">Mi Portal</h2>
            </div>

            <nav class="sidebar-nav" style="margin-top: 1rem;">
                <a href="estudiante.php?seccion=catalogo" class="nav-item <?= $seccion === 'catalogo' ? 'active' : '' ?>">
                    <span class="icon">📚</span> Listado de Catálogo
                </a>

                <a href="estudiante.php?seccion=mis_libros" class="nav-item <?= $seccion === 'mis_libros' ? 'active' : '' ?>">
                    <span class="icon">📜</span> Mis Libros Activos
                </a>

                <a href="estudiante.php?seccion=cambiar_contrasena" class="nav-item <?= $seccion === 'cambiar_contrasena' ? 'active' : '' ?>">
                    <span class="icon">🔑</span> Cambiar Contraseña
                </a>
            </nav>

            <div class="sidebar-footer" style="padding: 1.5rem; text-align: center; border-top: 1px solid rgba(0,0,0,0.05);">
                <div class="student-avatar">
                    <?= strtoupper(substr($user_info['nombre'], 0, 1)) ?>
                </div>
                <strong style="display: block; color: var(--text-color); font-size: 1.05rem;"><?= htmlspecialchars($user_info['nombre']) ?></strong>
                <span style="display: block; font-size: 0.85rem; color: var(--text-light); margin-bottom: 0.4rem;"><?= htmlspecialchars($user_info['correo']) ?></span>
                <span class="student-badge">Estudiante</span>
                
                <a href="logout.php" class="btn-logout" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 0.75rem; font-weight: 600;">Cerrar Sesión</a>
            </div>
        </aside>

        <!-- Contenido Principal -->
        <main class="main-content">
<?php if ($seccion === 'catalogo'): ?>
            <header class="top-header">
                <h1>Catálogo de la Biblioteca</h1>
                <div class="header-actions" style="display: flex; gap: 1.5rem; align-items: center;">
                    <?= render_notif_bell($prestamos_activos) ?>
                    <div class="search-box" style="position: relative;">
                        <!-- Lupa icon -->
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 0.9rem;">🔍</span>
                        <input type="text" id="searchInput" placeholder="Buscar título o libro..." onkeyup="filtrarTabla()"
                            style="padding: 0.65rem 1rem 0.65rem 2.5rem; border-radius: 9999px; border: 1px solid rgba(255,255,255,0.6); background: rgba(255,255,255,0.8); outline: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); color: var(--text-color); font-size: 0.95rem; width: 280px; transition: all 0.2s;">
                    </div>
                </div>
            </header>

            <div class="content-body">
                <!-- Zona de Mensajes y Alertas -->
                <?php if (isset($_GET['msg'])): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                        ✅ <?= htmlspecialchars($_GET['msg']) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                        ⚠️ <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($tiene_retraso): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                        <span>🚫 Tienes libros con retraso. Por favor, devuélvelos para poder realizar nuevas reservas.</span>
                    </div>
                <?php elseif ($tope_alcanzado): ?>
                    <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; color: #b45309; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                        <span>🛑 Límite de Cupo Alcanzado: No puedes reservar nuevos libros hasta no habilitar tus retornos en 'Mis Libros Activos'.</span>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="data-table" id="student-catalog-table">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="ordenarTabla(0, 'student-catalog-table')" style="cursor: pointer;" title="Haz clic para ordenar">ID <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(1, 'student-catalog-table')" style="cursor: pointer;" title="Haz clic para ordenar">Título <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(2, 'student-catalog-table')" style="cursor: pointer;" title="Haz clic para ordenar">Autor/es <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(3, 'student-catalog-table')" style="cursor: pointer;" title="Haz clic para ordenar">Materia <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(4, 'student-catalog-table')" style="cursor: pointer;" title="Haz clic para ordenar">Idioma <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(5, 'student-catalog-table')" style="cursor: pointer;" title="Haz clic para ordenar">Estado <span class="sort-icon"></span></th>
                                <th>Movimientos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($libros)): ?>
                                <?php foreach ($libros as $libro): ?>
                                    <?php 
                                        $hay_stock = ($libro['cantidad'] > 0);
                                        $estado_class = $hay_stock ? 'status-active' : 'status-inactive';
                                        $estado_texto = $hay_stock ? 'Disponible' : 'Agotado';
                                        $ya_reservado = in_array($libro['id'], $libros_reservados_ids);
                                    ?>
                                    <tr>
                                        <td><span style="color: var(--text-light); font-size: 0.85rem;">#<?= htmlspecialchars($libro['id']) ?></span></td>
                                        <td><strong><?= htmlspecialchars($libro['titulo']) ?></strong></td>
                                        <td><?= htmlspecialchars($libro['autor_nombre'] ?? 'Desconocido') ?></td>
                                        <td><span class="category-tag"><?= htmlspecialchars($libro['categoria_nombre'] ?? 'General') ?></span></td>
                                        <td><?= htmlspecialchars($libro['idioma_nombre'] ?? 'Español') ?></td>
                                        <td>
                                            <span class="status-badge <?= $estado_class ?>">
                                                <?= $estado_texto ?>
                                            </span>
                                        </td>
                                        <td>
                                            <!-- Botones de Movimientos Inteligentes -->
                                            <?php if ($ya_reservado): ?>
                                                <span style="display: inline-block; padding: 0.35rem 0.85rem; background: rgba(55, 65, 81, 0.08); color: #4b5563; border: 1px solid rgba(55, 65, 81, 0.2); border-radius: 9999px; font-size: 0.8rem; font-weight: 700;">
                                                    📌 Reservado por ti
                                                </span>
                                            <?php elseif ($tiene_retraso): ?>
                                                <button class="btn-reservation" style="opacity: 0.5; background: #ef4444; border-color: #ef4444; color: white; cursor: not-allowed;" disabled>
                                                    🚫 Bloqueado
                                                </button>
                                            <?php elseif ($tope_alcanzado): ?>
                                                <button class="btn-reservation" style="opacity: 0.5; background: #9ca3af; border-color: #9ca3af; color: white; cursor: not-allowed;" disabled>
                                                    ⛔ Cupo Lleno
                                                </button>
                                            <?php elseif ($hay_stock): ?>
                                                <button class="btn-reservation" onclick="abrirModalReserva(<?= $libro['id'] ?>, '<?= htmlspecialchars($libro['titulo'], ENT_QUOTES) ?>')">
                                                    📅 Reservar
                                                </button>
                                            <?php else: ?>
                                                <span style="color: #ef4444; font-size: 0.85rem; font-weight: 600; padding: 0.4rem 1rem; background: rgba(239,68,68,0.1); border-radius: 9999px;">Agotado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-light); padding: 3rem;">
                                        📚 El catálogo actualmente está vacío.<br>
                                        <?php if (isset($error_db)) echo "<span style='color:red;'>$error_db</span>"; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
<?php elseif ($seccion === 'mis_libros'): ?>
            <header class="top-header">
                <h1>Mis Libros Activos</h1>
                <div class="header-actions" style="display: flex; gap: 1.5rem; align-items: center;">
                    <span style="font-size: 0.95rem; color: var(--text-light);">Aquí verás tus préstamos actuales y pasados.</span>
                    <?= render_notif_bell($prestamos_activos) ?>
                </div>
            </header>

            <div class="content-body" style="overflow-y: auto;">
                <?php if (empty($mis_prestamos)): ?>
                    <div style="text-align: center; color: var(--text-light); padding: 5rem 0;">
                        <span style="font-size: 4rem; display: block; margin-bottom: 1rem;">📭</span>
                        <h3 style="color: var(--text-color); font-size: 1.25rem;">Aún no tienes ningún libro en tu poder</h3>
                        <p>Visita el catálogo para explorar nuestra colección.</p>
                        <a href="estudiante.php?seccion=catalogo" class="btn-primary" style="display: inline-block; text-decoration: none; border-radius: 9999px; padding: 0.5rem 1.5rem; margin-top: 1rem; width: auto;">Ir al Catálogo</a>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
                        <?php foreach($mis_prestamos as $prestamo): ?>
                            <?php 
                                $es_activo = $prestamo['estado'] === 'activo';
                                $es_retraso = $prestamo['estado'] === 'retrasado';
                                
                                // Color de tarjeta según estado
                                $card_bg = $es_activo ? 'background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(240,249,255,0.8)); border: 1px solid rgba(59,130,246,0.3);' : 'background: rgba(255,255,255,0.6); opacity: 0.8;';
                                if ($es_retraso) $card_bg = 'background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(254,226,226,0.8)); border: 1px solid rgba(239,68,68,0.4);';
                                
                                // Calcular días restantes
                                $dias_restantes = (strtotime($prestamo['fecha_devolucion']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                                $texto_tiempo = "";
                                $color_tiempo = "";
                                if ($es_activo) {
                                    if ($dias_restantes > 0) {
                                        $texto_tiempo = "⏱️ Faltan $dias_restantes días";
                                        $color_tiempo = "color: #059669;"; // Verde esmeralda
                                    } elseif ($dias_restantes == 0) {
                                        $texto_tiempo = "⏰ DEVOLVER HOY";
                                        $color_tiempo = "color: #ea580c; font-weight: 800;"; // Naranja intenso
                                    } else {
                                        $texto_tiempo = "⚠️ RETRASADO (" . abs($dias_restantes) . " días)";
                                        $color_tiempo = "color: #dc2626; font-weight: 800;"; // Rojo
                                    }
                                }
                            ?>
                            <div style="border-radius: 1rem; padding: 1.5rem; <?= $card_bg ?> box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); position: relative; overflow: hidden; display: flex; flex-direction: column;">
                                <?php if ($es_activo): ?>
                                    <div style="position: absolute; top: 0; right: 0; background: var(--primary); color: white; border-bottom-left-radius: 1rem; padding: 0.3rem 1rem; font-size: 0.75rem; font-weight: bold; box-shadow: -2px 2px 5px rgba(0,0,0,0.1);">
                                        ACTIVO
                                    </div>
                                <?php else: ?>
                                    <div style="position: absolute; top: 0; right: 0; background: #9ca3af; color: white; border-bottom-left-radius: 1rem; padding: 0.3rem 1rem; font-size: 0.75rem; font-weight: bold;">
                                        DEVUELTO
                                    </div>
                                <?php endif; ?>
                                
                                <span style="font-size: 0.8rem; color: var(--text-light); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; margin-bottom: 0.25rem;">
                                    <?= htmlspecialchars($prestamo['nombre_materia'] ?? 'Libro') ?>
                                </span>
                                <h3 style="color: var(--text-color); font-size: 1.15rem; font-weight: 800; margin-bottom: 0.25rem; line-height: 1.3;">
                                    <?= htmlspecialchars($prestamo['titulo']) ?>
                                </h3>
                                <span style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 1.5rem;">
                                    por <?= htmlspecialchars($prestamo['nombre_autor'] ?? 'Desconocido') ?>
                                </span>
                                
                                <div style="margin-top: auto; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 1rem; display: flex; flex-direction: column; gap: 0.25rem;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                                        <span style="color: var(--text-light);">Adquirido:</span>
                                        <strong><?= date('d M, Y', strtotime($prestamo['fecha_prestamo'])) ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                                        <span style="color: var(--text-light);">Límite:</span>
                                        <strong><?= date('d M, Y', strtotime($prestamo['fecha_devolucion'])) ?></strong>
                                    </div>
                                    
                                    <?php if ($es_activo): ?>
                                    <div style="margin-top: 0.5rem; text-align: center; padding: 0.5rem; background: rgba(255,255,255,0.7); border-radius: 0.5rem; <?= $color_tiempo ?> font-size: 0.85rem;">
                                        <?= $texto_tiempo ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
<?php elseif ($seccion === 'cambiar_contrasena'): ?>
            <header class="top-header">
                <h1>Cambiar Contraseña</h1>
                <div class="header-actions" style="display: flex; gap: 1.5rem; align-items: center;">
                    <span style="font-size: 0.95rem; color: var(--text-light);">Mantén tu cuenta segura actualizando tu contraseña periódicamente.</span>
                    <?= render_notif_bell($prestamos_activos) ?>
                </div>
            </header>

            <div class="content-body" style="overflow-y: auto;">
                <?php if (isset($_GET['msg'])): ?>
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        ✅ <?= htmlspecialchars($_GET['msg']) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        ⚠️ <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>

                <div style="max-width: 500px; background: rgba(255, 255, 255, 0.85); border-radius: 1.25rem; padding: 2rem; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); border: 1px solid rgba(255,255,255,0.6); backdrop-filter: blur(15px);">
                    <form action="procesar_cambio_clave.php" method="POST">
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="clave_actual" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-color);">Contraseña Actual</label>
                            <input type="password" name="clave_actual" id="clave_actual" required 
                                style="width: 100%; padding: 0.85rem 1rem; border-radius: 0.75rem; border: 1px solid #d1d5db; background: rgba(255, 255, 255, 0.9); outline: none;">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="clave_nueva" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-color);">Nueva Contraseña</label>
                            <input type="password" name="clave_nueva" id="clave_nueva" required 
                                style="width: 100%; padding: 0.85rem 1rem; border-radius: 0.75rem; border: 1px solid #d1d5db; background: rgba(255, 255, 255, 0.9); outline: none;">
                            <small style="color: var(--text-light); display: block; margin-top: 0.5rem;">La contraseña debe tener entre 8 y 20 caracteres, contener al menos una mayúscula, una minúscula y un número, y no tener espacios.</small>
                        </div>

                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label for="clave_confirmar" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-color);">Confirmar Nueva Contraseña</label>
                            <input type="password" name="clave_confirmar" id="clave_confirmar" required 
                                style="width: 100%; padding: 0.85rem 1rem; border-radius: 0.75rem; border: 1px solid #d1d5db; background: rgba(255, 255, 255, 0.9); outline: none;">
                        </div>

                        <button type="submit" class="btn-primary" style="margin-top: 0; width: 100%;">Actualizar Contraseña</button>
                    </form>
                </div>
            </div>
<?php endif; ?>
        </main>
    </div>

    <!-- Modal Transaccional de Reserva -->
    <div id="modal-reserva" class="modal-overlay">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 style="color: var(--primary);">Confirmar Reserva</h2>
                <button type="button" class="btn-close" onclick="cerrarModalReserva()">×</button>
            </div>
            <form action="procesar_reserva.php" method="POST">
                <div class="modal-body">
                    <p style="color: var(--text-light); margin-bottom: 1rem; line-height: 1.5;">Estás a punto de agendar la reserva del siguiente título:</p>
                    <p style="font-weight: 700; color: var(--text-color); font-size: 1.1rem; margin-bottom: 1.5rem;" id="txt_titulo_libro"></p>
                    
                    <input type="hidden" name="id_libro" id="reserva_id_libro" value="">
                    
                    <div class="form-group">
                        <label for="fecha_devolucion" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-color);">Fecha estimada de devolución:</label>
                        <input type="date" name="fecha_devolucion" id="fecha_devolucion" required 
                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                            max="<?= date('Y-m-d', strtotime('+7 days')) ?>" 
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(0,0,0,0.1); background: #f9fafb; outline: none;">
                        <small style="color: var(--text-light); display: block; margin-top: 0.5rem;">Debes elegir como mínimo el día de mañana y como máximo 7 días a partir de hoy.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModalReserva()">Cancelar</button>
                    <button type="submit" class="btn-primary">Confirmar Reserva</button>
                </div>
            </form>
        </div>
    </div>



    <script>
        // Búsqueda instantánea en cliente
        function filtrarTabla() {
            let input = document.getElementById("searchInput");
            let filter = input.value.toLowerCase();
            let table = document.querySelector(".data-table tbody");
            let tr = table.getElementsByTagName("tr");

            for (let i = 0; i < tr.length; i++) {
                if (tr[i].getElementsByTagName("td").length === 1) continue;
                
                // Buscar por Título  (columna index=1)
                let tdTitulo = tr[i].getElementsByTagName("td")[1];
                if (tdTitulo) {
                    let txtValue = tdTitulo.textContent || tdTitulo.innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // Control del Modal de Reservas
        function abrirModalReserva(id, titulo) {
            const modal = document.getElementById('modal-reserva');
            document.getElementById('reserva_id_libro').value = id;
            document.getElementById('txt_titulo_libro').textContent = titulo;
            modal.classList.add('active');
        }
        
        function cerrarModalReserva() {
            document.getElementById('modal-reserva').classList.remove('active');
        }

        const iconDefault = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; opacity: 0.4; margin-left: 4px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>`;
        const iconAsc = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-left: 4px;"><polyline points="18 15 12 9 6 15"></polyline></svg>`;
        const iconDesc = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-left: 4px;"><polyline points="6 9 12 15 18 9"></polyline></svg>`;

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.sort-icon').forEach(span => {
                span.innerHTML = iconDefault;
            });
        });

        // Función para ordenar tablas
        function ordenarTabla(n, tableId) {
            let table = document.getElementById(tableId);
            let tbody = table.querySelector("tbody");
            let rows = Array.from(tbody.querySelectorAll("tr"));
            
            // Si la tabla está vacía (tiene la fila de 'no hay datos'), no hacemos nada
            if (rows.length === 0 || rows[0].getElementsByTagName("td").length === 1) return;

            let asc = table.getAttribute("data-sort-dir") === "asc";
            let currentSortCol = table.getAttribute("data-sort-col");
            
            if (currentSortCol == n) {
                asc = !asc; // Alternar dirección si es la misma columna
            } else {
                asc = true; // Por defecto ascendente para nueva columna
            }
            
            table.setAttribute("data-sort-dir", asc ? "asc" : "desc");
            table.setAttribute("data-sort-col", n);

            rows.sort(function(a, b) {
                let x = a.getElementsByTagName("td")[n];
                let y = b.getElementsByTagName("td")[n];
                
                if (!x || !y) return 0;
                
                let valX = x.textContent.trim().toLowerCase();
                let valY = y.textContent.trim().toLowerCase();
                
                // Limpiar el "#" de ID para estudiantes
                if (valX.startsWith('#')) valX = valX.substring(1);
                if (valY.startsWith('#')) valY = valY.substring(1);
                
                let numX = parseFloat(valX);
                let numY = parseFloat(valY);
                
                if(!isNaN(numX) && !isNaN(numY) && valX == numX && valY == numY) {
                    return asc ? numX - numY : numY - numX;
                }
                
                if (valX < valY) return asc ? -1 : 1;
                if (valX > valY) return asc ? 1 : -1;
                return 0;
            });

            // Actualizar iconos de las cabeceras
            let headers = table.querySelectorAll("thead th");
            headers.forEach(function(th, index) {
                let iconSpan = th.querySelector('.sort-icon');
                if (iconSpan) {
                    if (index === n) {
                        iconSpan.innerHTML = asc ? iconAsc : iconDesc;
                    } else {
                        iconSpan.innerHTML = iconDefault;
                    }
                }
            });

            // Re-añadir las filas en el nuevo orden
            rows.forEach(function(row) {
                tbody.appendChild(row);
            });
        }

        // Control de Dropdown de Notificaciones
        function toggleNotifDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('notifDropdown');
            if (dropdown) {
                dropdown.classList.toggle('active');
            }
        }

        // Cerrar dropdown si se hace clic afuera
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notifDropdown');
            if (dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>
