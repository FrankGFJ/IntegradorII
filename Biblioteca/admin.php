<?php
session_start();

// Incluir funciones de base de datos
require_once 'consultas.php';

// Validar que exista la sesión y que sea admin (o bibliotecario si tienes ese rol)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], ['admin', 'bibliotecario'])) {
    header("Location: index.php");
    exit();
}

// Determinar en qué sección estamos (por defecto: inventario)
$seccion = $_GET['seccion'] ?? 'inventario';

// Obtener datos de la base de datos según sección
$libros = [];
$autores = [];
$materias = [];
$editoriales = [];
$usuarios = [];

if ($seccion === 'inventario') {
    try {
        $libros = obtener_inventario_libros($con);
        $autores = obtener_autores($con);
        $materias = obtener_materias($con);
        $editoriales = obtener_editoriales($con);
    } catch (Exception $e) {
        $error_db = $e->getMessage();
    }
} elseif ($seccion === 'usuarios') {
    try {
        $usuarios = obtener_todos_usuarios($con);
    } catch (Exception $e) {
        $error_db = $e->getMessage();
    }
} elseif ($seccion === 'prestamos') {
    try {
        $prestamos = obtener_todos_prestamos($con);
    } catch (Exception $e) {
        $error_db = $e->getMessage();
    }
} elseif ($seccion === 'calendario') {
    try {
        $prestamos = obtener_todos_prestamos($con);
        $prestamosActivos = array_filter($prestamos, function($p) {
            return $p['estado'] === 'activo';
        });
        $prestamosActivos = array_values($prestamosActivos);
    } catch (Exception $e) {
        $error_db = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador - Oasis Literario</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>

<body class="admin-body">
    <div class="admin-layout">
        <!-- Barra lateral (Sidebar) -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <span class="brand-icon">📚🌿</span>
                <h2>Oasis Literario</h2>
            </div>

            <nav class="sidebar-nav">
                <!-- Opción de Inventario -->
                <a href="admin.php?seccion=inventario"
                    class="nav-item <?= $seccion === 'inventario' ? 'active' : '' ?>">
                    <span class="icon">📦</span> Inventario
                </a>

                <!-- Opción de Usuarios -->
                <a href="admin.php?seccion=usuarios" 
                    class="nav-item <?= $seccion === 'usuarios' ? 'active' : '' ?>">
                    <span class="icon">👥</span> Usuarios
                </a>

                <!-- Opción de Préstamos -->
                <a href="admin.php?seccion=prestamos" class="nav-item <?= $seccion === 'prestamos' ? 'active' : '' ?>">
                    <span class="icon">🔄</span> Préstamos
                </a>
                
                <!-- Opción de Calendario -->
                <a href="admin.php?seccion=calendario" class="nav-item <?= $seccion === 'calendario' ? 'active' : '' ?>">
                    <span class="icon">📅</span> Calendario
                </a>

                <a href="#" class="nav-item" style="opacity: 0.5; cursor: not-allowed;" title="Próximamente">
                    <span class="icon">📊</span> Reportes
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Administrador') ?></span>
                    <span class="user-role"><?= htmlspecialchars($_SESSION['rol'] ?? 'Admin') ?></span>
                </div>
                <!-- Botón de logout pendiente de crear logout.php -->
                <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </aside>

        <!-- Contenido Principal -->
        <main class="main-content">
<?php if ($seccion === 'inventario'): ?>
            <header class="top-header">
                <h1>Inventario de Libros</h1>
                <div class="header-actions" style="display: flex; gap: 1.5rem; align-items: center;">
                    <div class="search-box" style="position: relative;">
                        <span
                            style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 0.9rem;"></span>
                        <input type="text" id="searchInput" placeholder="Buscar por titulo..." onkeyup="filtrarTabla()"
                            style="padding: 0.65rem 1rem 0.65rem 2.5rem; border-radius: 9999px; border: 1px solid rgba(255,255,255,0.6); background: rgba(255,255,255,0.8); outline: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); color: var(--text-color); font-size: 0.95rem; width: 280px; transition: all 0.2s;">
                    </div>
                    <button class="btn-primary" onclick="abrirModalCrear()"
                        style="width: auto; margin: 0; padding: 0.6rem 1.25rem;">+ Nuevo Libro</button>
                </div>
            </header>

            <div class="content-body">
                <!-- Tabla del inventario (Con datos de prueba listos para ser cambiados por BDD) -->
                <div class="table-container">
                    <table class="data-table" id="inventory-table">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="ordenarTabla(0, 'inventory-table')" style="cursor: pointer;" title="Haz clic para ordenar">ID <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(1, 'inventory-table')" style="cursor: pointer;" title="Haz clic para ordenar">Título <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(2, 'inventory-table')" style="cursor: pointer;" title="Haz clic para ordenar">Autor/a <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(3, 'inventory-table')" style="cursor: pointer;" title="Haz clic para ordenar">Categoría <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(4, 'inventory-table')" style="cursor: pointer;" title="Haz clic para ordenar">Stock <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(5, 'inventory-table')" style="cursor: pointer;" title="Haz clic para ordenar">Estado <span class="sort-icon"></span></th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($libros)): ?>
                                <?php foreach ($libros as $libro): ?>
                                    <?php
                                    $stock = (int) $libro['cantidad'];
                                    $min_stock = (int) $libro['stock_minimo'];

                                    if ($stock <= 0) {
                                        $estado_clase = "background: rgba(254, 226, 226, 0.8); color: #991b1b; border: 1px solid #fecaca;";
                                        $estado_texto = "Agotado";
                                    } elseif ($stock <= $min_stock) {
                                        $estado_clase = "background-color: rgba(254, 243, 199, 0.8); color: #92400e; border: 1px solid #f59e0b;";
                                        $estado_texto = "Poco Stock";
                                    } else {
                                        $estado_clase = "background-color: rgba(209, 250, 229, 0.8); color: #065f46; border: 1px solid #10b981;";
                                        $estado_texto = "Disponible";
                                    }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($libro['id']) ?></td>
                                        <td><?= htmlspecialchars($libro['titulo']) ?></td>
                                        <td><?= htmlspecialchars($libro['autor_nombre'] ?? 'Sin autor') ?></td>
                                        <td><?= htmlspecialchars($libro['categoria_nombre'] ?? 'Sin categoría') ?></td>
                                        <td><?= htmlspecialchars($libro['cantidad']) ?></td>
                                        <td><span class="status-badge" style="<?= $estado_clase ?>"><?= $estado_texto ?></span>
                                        </td>
                                        <td>
                                            <button class="btn-icon" title="Editar"
                                                onclick='abrirModalEditar(<?= htmlspecialchars(json_encode($libro), ENT_QUOTES, "UTF-8") ?>)'>✏️</button>
                                            <button class="btn-icon" title="Eliminar"
                                                onclick='abrirModalEliminar(<?= $libro['id'] ?>, <?= htmlspecialchars(json_encode($libro['titulo']), ENT_QUOTES, "UTF-8") ?>)'>🗑️</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-light); padding: 3rem;">
                                        📦 No hay libros registrados en la base de datos.<br>
                                        <?php if (isset($error_db))
                                            echo "<span style='color:red;'>$error_db</span>"; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
<?php elseif ($seccion === 'usuarios'): ?>
            <header class="top-header">
                <h1>Panel de Usuarios</h1>
                <div class="header-actions" style="display: flex; gap: 1.5rem; align-items: center;">
                    <div class="search-box" style="position: relative;">
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 0.9rem;">🔍</span>
                        <input type="text" id="searchUser" placeholder="Buscar usuario..." onkeyup="filtrarUsuarios()" 
                            style="padding: 0.65rem 1rem 0.65rem 2.5rem; border-radius: 9999px; border: 1px solid rgba(255,255,255,0.6); background: rgba(255,255,255,0.8); outline: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); color: var(--text-color); font-size: 0.95rem; width: 280px; transition: all 0.2s;">
                    </div>
                    <button class="btn-primary" onclick="abrirModalUsuarioCrear()"
                        style="width: auto; margin: 0; padding: 0.6rem 1.25rem;">+ Nuevo Usuario</button>
                </div>
            </header>

            <div class="content-body">
                <div class="table-container">
                    <table class="data-table" id="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($usuarios)): ?>
                                <?php foreach ($usuarios as $usr): ?>
                                    <?php 
                                        $rol_class = "";
                                        if ($usr['rol'] == 'admin') $rol_class = "background: rgba(254, 226, 226, 0.8); color: #991b1b; border: 1px solid #fecaca;";
                                        if ($usr['rol'] == 'bibliotecario') $rol_class = "background: rgba(219, 234, 254, 0.8); color: #1e40af; border: 1px solid #bfdbfe;";
                                        if ($usr['rol'] == 'estudiante') $rol_class = "background: rgba(209, 250, 229, 0.8); color: #065f46; border: 1px solid #10b981;";
                                        
                                        $is_activo = ($usr['estado'] == 1);
                                        $estado_texto = $is_activo ? 'Activo' : 'Inactivo';
                                        $estado_class = $is_activo ? "background: #d1fae5; color: #065f46;" : "background: #fee2e2; color: #991b1b;";
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($usr['id']) ?></td>
                                        <td><strong><?= htmlspecialchars($usr['usuario']) ?></strong></td>
                                        <td><?= htmlspecialchars($usr['nombre']) ?></td>
                                        <td><?= htmlspecialchars($usr['correo']) ?></td>
                                        <td><span class="status-badge" style="<?= $rol_class ?>"><?= ucfirst(htmlspecialchars($usr['rol'])) ?></span></td>
                                        <td><span class="status-badge" style="<?= $estado_class ?>"><?= $estado_texto ?></span></td>
                                        <td>
                                            <button class="btn-icon" title="Editar" onclick='abrirModalUsuarioEditar(<?= htmlspecialchars(json_encode($usr), ENT_QUOTES, "UTF-8") ?>)'>✏️</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-light); padding: 3rem;">👥 No hay usuarios registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
<?php elseif ($seccion === 'prestamos'): ?>
            <header class="top-header">
                <div>
                    <h1 style="font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #111827; margin-bottom: 0.25rem;">Préstamos</h1>
                    <span style="color: var(--text-light); font-size: 0.95rem;">Todas las reservas de la comunidad y su estado de devolución.</span>
                </div>
                <div class="header-actions" style="display: flex; gap: 1rem; align-items: center;">
                    <div class="search-box" style="position: relative;">
                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 0.9rem;">🔍</span>
                        <input type="text" id="searchPrestamo" placeholder="Buscar por libro o estudiante..." onkeyup="filtrarPrestamos()" 
                            style="padding: 0.65rem 1rem 0.65rem 2.5rem; border-radius: 9999px; border: 1px solid rgba(255,255,255,0.6); background: rgba(255,255,255,0.8); outline: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); color: var(--text-color); font-size: 0.95rem; width: 280px; transition: all 0.2s;">
                    </div>
                </div>
            </header>

            <div class="content-body">
                <!-- Filtros (Pills) -->
                <div style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem;">
                    <?php 
                        $activos = 0; $devueltos = 0;
                        foreach($prestamos as $p) {
                            if($p['estado'] == 'activo') $activos++;
                            if($p['estado'] == 'devuelto') $devueltos++;
                        }
                    ?>
                    <button class="filter-pill active" onclick="filtrarEstadoPrestamo('todos', this)" style="padding: 0.4rem 1rem; border-radius: 9999px; border: 1px solid var(--text-light); background: #111827; color: white; cursor: pointer; font-weight: 600; transition: all 0.2s;">Todos</button>
                    <button class="filter-pill" onclick="filtrarEstadoPrestamo('activo', this)" style="padding: 0.4rem 1rem; border-radius: 9999px; border: 1px solid #e5e7eb; background: white; color: var(--text-color); cursor: pointer; font-weight: 600; transition: all 0.2s;">Activos <span style="background: rgba(0,0,0,0.05); padding: 0.1rem 0.4rem; border-radius: 9999px; font-size: 0.8rem; margin-left: 0.25rem;"><?= $activos ?></span></button>
                    <button class="filter-pill" onclick="filtrarEstadoPrestamo('devuelto', this)" style="padding: 0.4rem 1rem; border-radius: 9999px; border: 1px solid #e5e7eb; background: white; color: var(--text-color); cursor: pointer; font-weight: 600; transition: all 0.2s;">Devueltos <span style="background: rgba(0,0,0,0.05); padding: 0.1rem 0.4rem; border-radius: 9999px; font-size: 0.8rem; margin-left: 0.25rem;"><?= $devueltos ?></span></button>
                </div>

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

                <div class="table-container">
                    <table class="data-table" id="prestamos-table">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="ordenarTabla(0, 'prestamos-table')" style="cursor: pointer;" title="Haz clic para ordenar">ID <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(1, 'prestamos-table')" style="cursor: pointer;" title="Haz clic para ordenar">Libro <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(2, 'prestamos-table')" style="cursor: pointer;" title="Haz clic para ordenar">Estudiante <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(3, 'prestamos-table')" style="cursor: pointer;" title="Haz clic para ordenar">Solicitado <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(4, 'prestamos-table')" style="cursor: pointer;" title="Haz clic para ordenar">Vence <span class="sort-icon"></span></th>
                                <th class="sortable" onclick="ordenarTabla(5, 'prestamos-table')" style="cursor: pointer;" title="Haz clic para ordenar">Estado <span class="sort-icon"></span></th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($prestamos)): ?>
                                <?php foreach ($prestamos as $p): ?>
                                    <?php 
                                        $es_activo = $p['estado'] == 'activo';
                                        $estado_class = $es_activo ? "background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe;" : "background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb;";
                                        $estado_texto = $es_activo ? "Activo" : "Devuelto";
                                    ?>
                                    <tr data-estado="<?= $p['estado'] ?>">
                                        <td style="color: var(--text-light); font-size: 0.85rem;">PRE-<?= str_pad($p['id_prestamo'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td>
                                            <strong style="display: block; color: var(--text-color);"><?= htmlspecialchars($p['titulo']) ?></strong>
                                            <span style="font-size: 0.8rem; color: var(--text-light);"><?= htmlspecialchars($p['nombre_autor'] ?? 'Desconocido') ?></span>
                                        </td>
                                        <td>
                                            <strong style="display: block; color: var(--text-color);"><?= htmlspecialchars($p['nombre_estudiante']) ?></strong>
                                            <span style="font-size: 0.8rem; color: var(--text-light);">@<?= explode('@', htmlspecialchars($p['correo_estudiante']))[0] ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($p['fecha_prestamo']) ?></td>
                                        <td><?= htmlspecialchars($p['fecha_devolucion']) ?></td>
                                        <td>
                                            <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 600; <?= $estado_class ?>">
                                                <span style="width: 6px; height: 6px; border-radius: 50%; background: <?= $es_activo ? '#4338ca' : '#9ca3af' ?>;"></span>
                                                <?= $estado_texto ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($es_activo): ?>
                                            <button onclick="abrirModalDevolucionAdmin(<?= $p['id_prestamo'] ?>, '<?= htmlspecialchars($p['titulo'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p['nombre_estudiante'], ENT_QUOTES) ?>')" style="padding: 0.4rem 0.85rem; border-radius: 9999px; border: 1px solid #e5e7eb; background: white; color: var(--text-color); cursor: pointer; font-size: 0.85rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.35rem; transition: all 0.2s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                                                ✓ Procesar devolución
                                            </button>
                                            <?php else: ?>
                                            <span style="color: var(--text-light); font-size: 0.85rem;">---</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-light); padding: 3rem;">No hay préstamos registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
<?php elseif ($seccion === 'calendario'): ?>
            <header class="top-header" style="margin-bottom: 1.5rem;">
                <div>
                    <h1 style="font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #111827; margin-bottom: 0.25rem;">Calendario</h1>
                    <span id="cal-resumen" style="color: var(--text-light); font-size: 0.95rem;">Cargando...</span>
                </div>
                <button onclick="hoyCalendario()" style="padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; background: white; cursor: pointer; font-weight: 500;">
                    Hoy
                </button>
            </header>

            <div style="display: flex; gap: 1rem; align-items: flex-start; padding: 0 3rem 2rem 3rem;">
                <!-- Columna Izquierda: Calendario -->
                <div style="flex: 1; display: flex; flex-direction: column; min-width: 0;">
                    <!-- Controles del Mes -->
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: baseline; gap: 1rem;">
                            <h2 id="mes-anio-texto" style="font-family: 'Playfair Display', serif; font-size: 1.8rem; color: #111827;"></h2>
                            <span id="eventos-mes-texto" style="color: var(--text-light); font-size: 0.9rem;"></span>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button onclick="cambiarMes(-1)" style="width: 32px; height: 32px; border-radius: 0.25rem; border: none; background: #f3f4f6; cursor: pointer;">&lt;</button>
                            <button onclick="cambiarMes(1)" style="width: 32px; height: 32px; border-radius: 0.25rem; border: none; background: #f3f4f6; cursor: pointer;">&gt;</button>
                        </div>
                    </div>

                    <!-- Días de la semana -->
                    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.75rem; margin-bottom: 0.5rem; padding: 0 0.5rem;">
                        <div style="text-align: center; font-size: 0.85rem; color: var(--text-light); font-weight: 600;">L</div>
                        <div style="text-align: center; font-size: 0.85rem; color: var(--text-light); font-weight: 600;">M</div>
                        <div style="text-align: center; font-size: 0.85rem; color: var(--text-light); font-weight: 600;">M</div>
                        <div style="text-align: center; font-size: 0.85rem; color: var(--text-light); font-weight: 600;">J</div>
                        <div style="text-align: center; font-size: 0.85rem; color: var(--text-light); font-weight: 600;">V</div>
                        <div style="text-align: center; font-size: 0.85rem; color: var(--text-light); font-weight: 600;">S</div>
                        <div style="text-align: center; font-size: 0.85rem; color: var(--text-light); font-weight: 600;">D</div>
                    </div>
                    
                    <!-- Cuadrícula de días -->
                    <div id="grid-calendario" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.75rem; padding: 0.5rem;">
                        <!-- Días inyectados por JS -->
                    </div>
                </div>
            </div>
<?php endif; ?>
        </main>
    </div>

    <!-- Modal Detalles del Día -->
    <div id="modal-detalle-dia" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 id="modal-panel-fecha">Selecciona un día</h2>
                <button type="button" class="btn-close" onclick="cerrarModalDetalleDia()">×</button>
            </div>
            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                <span id="modal-panel-eventos-count" style="color: var(--text-light); font-size: 0.9rem; display: block; margin-bottom: 1.5rem;">0 eventos</span>
                <div id="modal-panel-lista-eventos" style="display: flex; flex-direction: column; gap: 1rem;">
                    <!-- Eventos inyectados por JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModalDetalleDia()">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Edición -->
    <div id="modal-edicion" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal_title">Editar Libro</h2>
                <button type="button" class="btn-close" onclick="cerrarModal()">×</button>
            </div>
            <form id="form-editar" method="POST" action="procesar_libro.php">
                <div class="modal-body">
                    <input type="hidden" name="accion" id="form_accion" value="editar">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="form-grid">
                        <div class="form-group full-col">
                            <label>Título del Libro</label>
                            <input type="text" name="titulo" id="edit_titulo" required>
                        </div>

                        <div class="form-group">
                            <label>Autor (ID - Nombre)</label>
                            <select name="id_autor" id="edit_autor" onchange="verificarNuevo('autor')">
                                <option value="">-- Seleccione un autor --</option>
                                <option value="NEW" style="font-weight:bold; color:var(--primary);">Agregar Nuevo
                                </option>
                                <?php foreach ($autores as $autor): ?>
                                    <option value="<?= $autor['id'] ?>"><?= $autor['id'] ?> -
                                        <?= htmlspecialchars($autor['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="nuevo_autor" id="input_nuevo_autor"
                                style="display:none; margin-top:0.5rem;" placeholder="Nombre del nuevo autor...">
                        </div>

                        <div class="form-group">
                            <label>Editorial (ID - Nombre)</label>
                            <select name="id_editorial" id="edit_editorial" onchange="verificarNuevo('editorial')">
                                <option value="">-- Seleccione una editorial --</option>
                                <option value="NEW" style="font-weight:bold; color:var(--primary);"> Agregar Nueva
                                </option>
                                <?php foreach ($editoriales as $ed): ?>
                                    <option value="<?= $ed['id'] ?>"><?= $ed['id'] ?> -
                                        <?= htmlspecialchars($ed['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="nuevo_editorial" id="input_nuevo_editorial"
                                style="display:none; margin-top:0.5rem;" placeholder="Nombre de la nueva editorial...">
                        </div>

                        <div class="form-group">
                            <label>Materia (ID - Nombre)</label>
                            <select name="id_materia" id="edit_materia" onchange="verificarNuevo('materia')">
                                <option value="">-- Seleccione una categoría --</option>
                                <option value="NEW" style="font-weight:bold; color:var(--primary);"> Agregar Nueva
                                </option>
                                <?php foreach ($materias as $mat): ?>
                                    <option value="<?= $mat['id'] ?>"><?= $mat['id'] ?> -
                                        <?= htmlspecialchars($mat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="nuevo_materia" id="input_nuevo_materia"
                                style="display:none; margin-top:0.5rem;" placeholder="Nombre de la nueva materia...">
                        </div>

                        <div class="form-group">
                            <label>Cantidad (Stock)</label>
                            <input type="number" name="cantidad" id="edit_cantidad" min="0">
                        </div>

                        <div class="form-group">
                            <label>Stock Mínimo</label>
                            <input type="number" name="stock_minimo" id="edit_stock_minimo" min="0">
                        </div>

                        <div class="form-group">
                            <label>Páginas (Opcional)</label>
                            <input type="number" name="num_pag" id="edit_num_pag" min="1">
                        </div>

                        <div class="form-group">
                            <label>Año (Opcional)</label>
                            <input type="number" name="anio_edicion" id="edit_anio_edicion" min="1500" max="2100">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="width:auto; margin-top:0;">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Eliminación -->
    <div id="modal-eliminar" class="modal-overlay">
        <div class="modal-content" style="width: 400px; text-align: center;">
            <div class="modal-header">
                <h2 style="color: #ef4444;">Eliminar Libro</h2>
                <button type="button" class="btn-close" onclick="cerrarModalEliminar()">×</button>
            </div>
            <form method="POST" action="procesar_libro.php">
                <div class="modal-body" style="padding-bottom: 0;">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" id="delete_id">
                    <p style="margin-bottom: 1rem;">¿Estás seguro de que deseas eliminar permanentemente el libro:</p>
                    <h3 id="delete_titulo" style="color: var(--text-color); margin-bottom: 1rem; font-weight: 800;">
                    </h3>
                    <p style="color: var(--text-light); font-size: 0.85rem;">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer" style="justify-content: center; border-top: none; padding-top: 1.5rem;">
                    <button type="button" class="btn-secondary" onclick="cerrarModalEliminar()">Cancelar</button>
                    <button type="submit" class="btn-primary"
                        style="background: #ef4444; width: auto; margin-top: 0; border: none;">Sí, Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Usuarios (Añadir / Editar) -->
    <div id="modal-usuario" class="modal-overlay">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="usr_modal_title">Registrar Nuevo Usuario</h2>
                <button type="button" class="btn-close" onclick="cerrarModalUsuario()">×</button>
            </div>
            <form method="POST" action="procesar_usuario.php">
                <div class="modal-body">
                    <input type="hidden" name="accion" id="usr_accion" value="crear">
                    <input type="hidden" name="id" id="usr_id" value="">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre de Usuario</label>
                            <input type="text" name="usuario" id="usr_usuario" required placeholder="Ej: fcastillo">
                        </div>
                        <div class="form-group">
                            <label>Nombre Real</label>
                            <input type="text" name="nombre" id="usr_nombre" required placeholder="Ej: Frank Castillo">
                        </div>
                        <div class="form-group full-col">
                            <label>Correo Electrónico</label>
                            <input type="email" name="correo" id="usr_correo" required placeholder="frank@ejemplo.com">
                        </div>
                        <div class="form-group" id="usr_clave_container">
                            <label>Contraseña</label>
                            <input type="password" name="clave" id="usr_clave" required placeholder="••••••••">
                        </div>
                        <div class="form-group">
                            <label>Rol del Sistema</label>
                            <select name="rol" id="usr_rol" required>
                                <option value="estudiante">Estudiante</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Estado Inicial</label>
                            <select name="estado" id="usr_estado" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModalUsuario()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="width: auto; margin-top: 0;">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Transaccional de Devolución Anticipada para Admin -->
    <div id="modal-devolucion-admin" class="modal-overlay">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 style="color: #4338ca;">Procesar Devolución</h2>
                <button type="button" class="btn-close" onclick="cerrarModalDevolucionAdmin()">×</button>
            </div>
            <form action="procesar_devolucion_admin.php" method="POST">
                <div class="modal-body">
                    <p style="color: var(--text-light); margin-bottom: 1rem; line-height: 1.5;">Vas a registrar la devolución de este libro. El inventario se actualizará automáticamente.</p>
                    <div style="background: rgba(243, 244, 246, 0.8); padding: 1rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; margin-bottom: 1rem;">
                        <span style="display: block; font-size: 0.8rem; color: var(--text-light); text-transform: uppercase; font-weight: 600; margin-bottom: 0.25rem;">Libro</span>
                        <strong style="display: block; color: var(--text-color); margin-bottom: 0.75rem;" id="txt_dev_titulo"></strong>
                        
                        <span style="display: block; font-size: 0.8rem; color: var(--text-light); text-transform: uppercase; font-weight: 600; margin-bottom: 0.25rem;">Estudiante</span>
                        <strong style="display: block; color: var(--text-color);" id="txt_dev_estudiante"></strong>
                    </div>
                    
                    <input type="hidden" name="id_prestamo" id="dev_admin_id_prestamo" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModalDevolucionAdmin()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background: #4338ca; border-color: #4338ca;">Confirmar Devolución</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // --- LÓGICA DEL CALENDARIO ---
        const prestamosRaw = <?= isset($prestamosActivos) ? json_encode($prestamosActivos) : '[]' ?>;
        const prestamosPorDia = {};
        prestamosRaw.forEach(p => {
            if(!prestamosPorDia[p.fecha_devolucion]) prestamosPorDia[p.fecha_devolucion] = [];
            prestamosPorDia[p.fecha_devolucion].push(p);
        });

        const meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        let fechaActual = new Date();
        let mesActual = fechaActual.getMonth();
        let anioActual = fechaActual.getFullYear();
        let hoyString = fechaActual.getFullYear() + "-" + String(fechaActual.getMonth()+1).padStart(2, '0') + "-" + String(fechaActual.getDate()).padStart(2, '0');

        function actualizarResumen() {
            let total = prestamosRaw.length;
            let vencidos = 0;
            let proximos = 0;
            
            prestamosRaw.forEach(p => {
                if (p.fecha_devolucion < hoyString) vencidos++;
                else {
                    let diffTime = new Date(p.fecha_devolucion) - new Date(hoyString);
                    let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    if(diffDays <= 2) proximos++;
                }
            });

            document.getElementById('cal-resumen').textContent = 
                `${total} devoluciones programadas • ${vencidos} vencidas • ${proximos} vencen en 2 días o menos`;
        }

        function renderCalendario() {
            const grid = document.getElementById('grid-calendario');
            if(!grid) return;

            grid.innerHTML = '';
            document.getElementById('mes-anio-texto').textContent = `${meses[mesActual]} ${anioActual}`;
            
            let primerDiaDelMes = new Date(anioActual, mesActual, 1).getDay();
            let indexInicio = primerDiaDelMes === 0 ? 6 : primerDiaDelMes - 1;
            
            let diasEnMes = new Date(anioActual, mesActual + 1, 0).getDate();
            let eventosEnMes = 0;

            for(let i=0; i<indexInicio; i++) {
                grid.innerHTML += `<div style="min-height: 110px; border-radius: 0.75rem;"></div>`;
            }

            for(let d=1; d<=diasEnMes; d++) {
                let fechaStr = `${anioActual}-${String(mesActual+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                let eventos = prestamosPorDia[fechaStr] || [];
                eventosEnMes += eventos.length;
                
                let isHoy = (fechaStr === hoyString);
                
                let eventosHtml = '';
                eventos.forEach(ev => {
                    let estadoStyle = "background: #e0e7ff; color: #3730a3;"; 
                    if(fechaStr < hoyString) {
                        estadoStyle = "background: #fee2e2; color: #991b1b;"; 
                    } else if (fechaStr === hoyString) {
                        estadoStyle = "background: #fef3c7; color: #92400e;"; 
                    } else {
                        let diffTime = new Date(fechaStr) - new Date(hoyString);
                        let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        if(diffDays <= 2) {
                            estadoStyle = "background: #dcfce3; color: #166534;"; 
                        }
                    }

                    eventosHtml += `
                        <div style="${estadoStyle} font-size: 0.75rem; padding: 0.35rem 0.5rem; border-radius: 0.5rem; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; flex-direction: column; line-height: 1.2;">
                            <span style="font-weight: 600;">• ${ev.titulo}</span>
                            <span style="font-size: 0.7rem; opacity: 0.85; margin-top: 0.1rem;">${ev.nombre_estudiante}</span>
                        </div>
                    `;
                });

                let celdaActivaStyle = isHoy ? "border: 2px solid #059669;" : "border: 1px solid #e5e7eb;";
                
                grid.innerHTML += `
                    <div onclick="mostrarDetalleDia('${fechaStr}', ${d})" style="background: white; min-height: 110px; padding: 0.75rem; border-radius: 0.75rem; cursor: pointer; position: relative; display: flex; flex-direction: column; box-shadow: 0 1px 2px rgba(0,0,0,0.02); ${celdaActivaStyle} transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.05)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                        <span style="font-size: 0.95rem; font-weight: 600; color: ${isHoy ? '#059669' : '#374151'}; margin-bottom: 0.5rem;">
                            ${d}
                        </span>
                        <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                            ${eventosHtml}
                        </div>
                    </div>
                `;
            }

            document.getElementById('eventos-mes-texto').textContent = `${eventosEnMes} eventos`;
        }

        function cambiarMes(diff) {
            mesActual += diff;
            if(mesActual > 11) {
                mesActual = 0;
                anioActual++;
            } else if (mesActual < 0) {
                mesActual = 11;
                anioActual--;
            }
            renderCalendario();
        }

        function hoyCalendario() {
            mesActual = fechaActual.getMonth();
            anioActual = fechaActual.getFullYear();
            renderCalendario();
        }

        function mostrarDetalleDia(fechaStr, dia) {
            const parts = fechaStr.split('-');
            const dateObj = new Date(parts[0], parts[1]-1, parts[2]);
            const diasSemana = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
            const diaSemana = diasSemana[dateObj.getDay()];
            
            document.getElementById('modal-panel-fecha').textContent = `${diaSemana}, ${dia} de ${meses[dateObj.getMonth()]}`;
            
            const eventos = prestamosPorDia[fechaStr] || [];
            document.getElementById('modal-panel-eventos-count').textContent = `${eventos.length} evento${eventos.length !== 1 ? 's' : ''}`;
            
            const lista = document.getElementById('modal-panel-lista-eventos');
            if(eventos.length === 0) {
                lista.innerHTML = '<div style="color: var(--text-light); font-size: 0.95rem; background: #f9fafb; padding: 1rem; border-radius: 0.5rem; text-align: center; border: 1px dashed #d1d5db;">☕ Sin devoluciones para este día.</div>';
            } else {
                let html = '';
                eventos.forEach(ev => {
                    let badgeStyle = "background: #e0e7ff; color: #3730a3;";
                    let badgeText = "Activo";

                    if(fechaStr < hoyString) {
                        badgeStyle = "background: #fee2e2; color: #991b1b;";
                        badgeText = "Urgente";
                    } else if (fechaStr === hoyString) {
                        badgeStyle = "background: #fef3c7; color: #92400e;";
                        badgeText = "Hoy";
                    } else {
                        let diffTime = new Date(fechaStr) - new Date(hoyString);
                        let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        if(diffDays <= 2) {
                            badgeStyle = "background: #dcfce3; color: #166534;";
                            badgeText = "Próximo";
                        }
                    }

                    html += `
                        <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; background: #f9fafb; transition: all 0.2s;" onmouseover="this.style.borderColor='#cbd5e1'; this.style.background='white';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.background='#f9fafb';">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                <strong style="color: var(--text-color); font-size: 1.05rem;">${ev.titulo}</strong>
                                <span style="${badgeStyle} font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 9999px; font-weight: 600;">• ${badgeText}</span>
                            </div>
                            <div style="color: var(--text-color); font-size: 0.9rem; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.35rem;">
                                <span style="background: #e5e7eb; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 0.7rem;">👤</span>
                                ${ev.nombre_estudiante}
                            </div>
                            <div style="color: var(--text-light); font-size: 0.8rem; margin-left: 28px;">PRE-${String(ev.id_prestamo).padStart(4,'0')} • Prestado: ${ev.fecha_prestamo}</div>
                        </div>
                    `;
                });
                lista.innerHTML = html;
            }
            
            // Abrir el modal
            document.getElementById('modal-detalle-dia').classList.add('active');
        }

        function cerrarModalDetalleDia() {
            document.getElementById('modal-detalle-dia').classList.remove('active');
        }

        // --- FIN LÓGICA DEL CALENDARIO ---

        // Ejecutar inmediatamente (Script al final del body)
        if (document.getElementById('grid-calendario')) {
            actualizarResumen();
            hoyCalendario();
        }

        function abrirModalDevolucionAdmin(idPrestamo, titulo, estudiante) {
            document.getElementById('dev_admin_id_prestamo').value = idPrestamo;
            document.getElementById('txt_dev_titulo').textContent = titulo;
            document.getElementById('txt_dev_estudiante').textContent = estudiante;
            document.getElementById('modal-devolucion-admin').classList.add('active');
        }
        
        function cerrarModalDevolucionAdmin() {
            document.getElementById('modal-devolucion-admin').classList.remove('active');
        }

        function filtrarPrestamos() {
            let input = document.getElementById("searchPrestamo");
            let filter = input.value.toLowerCase();
            let table = document.querySelector("#prestamos-table tbody");
            if (!table) return;
            let tr = table.getElementsByTagName("tr");

            // Solo mostrar filas de acuerdo al filtro de texto, pero también hay que considerar las píldoras de estado.
            // Para simplificar, buscamos si el estado activo de la píldora.
            let activePill = document.querySelector('.filter-pill.active');
            let estadoFiltro = 'todos';
            if(activePill && activePill.textContent.includes('Activos')) estadoFiltro = 'activo';
            if(activePill && activePill.textContent.includes('Devueltos')) estadoFiltro = 'devuelto';

            for (let i = 0; i < tr.length; i++) {
                if (tr[i].getElementsByTagName("td").length === 1) continue;
                
                let filaEstado = tr[i].getAttribute('data-estado');
                let td1 = tr[i].getElementsByTagName("td")[1]; // Libro
                let td2 = tr[i].getElementsByTagName("td")[2]; // Estudiante
                
                let textMatch = false;
                if (td1 || td2) {
                    let txtValue1 = td1.textContent || td1.innerText;
                    let txtValue2 = td2.textContent || td2.innerText;
                    if (txtValue1.toLowerCase().indexOf(filter) > -1 || txtValue2.toLowerCase().indexOf(filter) > -1) {
                        textMatch = true;
                    }
                }
                
                if (textMatch && (estadoFiltro === 'todos' || filaEstado === estadoFiltro)) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }

        function filtrarEstadoPrestamo(estado, btn) {
            document.querySelectorAll('.filter-pill').forEach(b => {
                b.classList.remove('active');
                b.style.background = 'white';
                b.style.color = 'var(--text-color)';
                b.style.borderColor = '#e5e7eb';
            });
            btn.classList.add('active');
            btn.style.background = '#111827';
            btn.style.color = 'white';
            btn.style.borderColor = 'var(--text-light)';

            filtrarPrestamos(); // Reaplicar el filtro de texto con el nuevo estado
        }

        function verificarNuevo(tipo) {
            let select = document.getElementById('edit_' + tipo);
            let input = document.getElementById('input_nuevo_' + tipo);
            if (select.value === 'NEW') {
                input.style.display = 'block';
                input.required = true;
            } else {
                input.style.display = 'none';
                input.required = false;
                input.value = '';
            }
        }

        function abrirModalCrear() {
            document.getElementById('modal_title').textContent = 'Registrar Nuevo Libro';
            document.getElementById('form_accion').value = 'crear';
            document.getElementById('edit_id').value = '';

            document.getElementById('edit_titulo').value = '';
            document.getElementById('edit_autor').value = '';
            document.getElementById('edit_editorial').value = '';
            document.getElementById('edit_materia').value = '';
            document.getElementById('edit_cantidad').value = '1';
            document.getElementById('edit_stock_minimo').value = '0';
            document.getElementById('edit_num_pag').value = '';
            document.getElementById('edit_anio_edicion').value = '';

            verificarNuevo('autor');
            verificarNuevo('editorial');
            verificarNuevo('materia');

            document.getElementById('modal-edicion').classList.add('active');
        }

        function abrirModalEditar(libro) {
            document.getElementById('modal_title').textContent = 'Editar Libro';
            document.getElementById('form_accion').value = 'editar';

            verificarNuevo('autor');
            verificarNuevo('editorial');
            verificarNuevo('materia');
            document.getElementById('edit_id').value = libro.id;
            document.getElementById('edit_titulo').value = libro.titulo;
            document.getElementById('edit_autor').value = libro.id_autor || '';
            document.getElementById('edit_editorial').value = libro.id_editorial || '';
            document.getElementById('edit_materia').value = libro.id_materia || '';
            document.getElementById('edit_cantidad').value = libro.cantidad;
            document.getElementById('edit_stock_minimo').value = libro.stock_minimo;
            document.getElementById('edit_num_pag').value = libro.num_pag || '';
            document.getElementById('edit_anio_edicion').value = libro.anio_edicion || '';

            document.getElementById('modal-edicion').classList.add('active');
        }

        function cerrarModal() {
            document.getElementById('modal-edicion').classList.remove('active');
        }

        function abrirModalEliminar(id, titulo) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_titulo').textContent = titulo;
            document.getElementById('modal-eliminar').classList.add('active');
        }

        function cerrarModalEliminar() {
            document.getElementById('modal-eliminar').classList.remove('active');
        }

        // Filtro de búsqueda instantánea para la tabla de libros
        function filtrarTabla() {
            let input = document.getElementById("searchInput");
            let filter = input.value.toLowerCase();
            let table = document.querySelector(".data-table tbody");
            let tr = table.getElementsByTagName("tr");

            for (let i = 0; i < tr.length; i++) {
                // Ignorar filas que no tienen datos (ej: "No hay libros")
                if (tr[i].getElementsByTagName("td").length === 1) continue;

                // Buscar en la segunda celda [índice 1] (Título)
                let td = tr[i].getElementsByTagName("td")[1];
                if (td) {
                    let txtValue = td.textContent || td.innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // --- FUNCIONES DEL MÓDULO DE USUARIOS ---
        
        function abrirModalUsuarioCrear() {
            document.getElementById('usr_modal_title').textContent = 'Registrar Nuevo Usuario';
            document.getElementById('usr_accion').value = 'crear';
            document.getElementById('usr_id').value = '';
            
            document.getElementById('usr_usuario').value = '';
            document.getElementById('usr_nombre').value = '';
            document.getElementById('usr_correo').value = '';
            document.getElementById('usr_rol').value = 'estudiante';
            document.getElementById('usr_estado').value = '1';
            
            // Mostrar y requerir clave para nuevo usuario
            document.getElementById('usr_clave_container').style.display = 'block';
            document.getElementById('usr_clave').required = true;
            document.getElementById('usr_clave').value = '';

            document.getElementById('modal-usuario').classList.add('active');
        }

        function abrirModalUsuarioEditar(usr) {
            document.getElementById('usr_modal_title').textContent = 'Editar Usuario';
            document.getElementById('usr_accion').value = 'editar';
            document.getElementById('usr_id').value = usr.id;
            
            document.getElementById('usr_usuario').value = usr.usuario;
            document.getElementById('usr_nombre').value = usr.nombre;
            document.getElementById('usr_correo').value = usr.correo;
            document.getElementById('usr_rol').value = usr.rol;
            document.getElementById('usr_estado').value = usr.estado;
            
            // Ocultar campo de contraseña (no se edita aquí)
            document.getElementById('usr_clave_container').style.display = 'none';
            document.getElementById('usr_clave').required = false;
            document.getElementById('usr_clave').value = '';

            document.getElementById('modal-usuario').classList.add('active');
        }

        function cerrarModalUsuario() {
            document.getElementById('modal-usuario').classList.remove('active');
        }

        function filtrarUsuarios() {
            let input = document.getElementById("searchUser");
            let filter = input.value.toLowerCase();
            let table = document.querySelector("#users-table tbody");
            let tr = table.getElementsByTagName("tr");

            for (let i = 0; i < tr.length; i++) {
                if (tr[i].getElementsByTagName("td").length === 1) continue;
                
                // Buscar en Nombre Real o Usuario (Columnas 1 y 2 indexadas form HTML)
                let td1 = tr[i].getElementsByTagName("td")[1]; // Usuario
                let td2 = tr[i].getElementsByTagName("td")[2]; // Nombre Real
                if (td1 || td2) {
                    let txtValue1 = td1.textContent || td1.innerText;
                    let txtValue2 = td2.textContent || td2.innerText;
                    if (txtValue1.toLowerCase().indexOf(filter) > -1 || txtValue2.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
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
    </script>
</body>

</html>