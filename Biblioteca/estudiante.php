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

$mis_prestamos = [];
if ($seccion === 'mis_libros') {
    $mis_prestamos = obtener_detalle_prestamos_estudiante($con, $_SESSION['user_id']);
}

$user_info = ['nombre' => $_SESSION['nombre'], 'correo' => '', 'rol' => 'Estudiante'];
try {
    $stmt = $con->prepare("SELECT nombre, correo, rol FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_info = $row;
    }
} catch (Exception $e) {}
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
                
                <?php if ($tope_alcanzado): ?>
                    <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; color: #b45309; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                        <span>🛑 Límite de Cupo Alcanzado: No puedes reservar nuevos libros hasta no habilitar tus retornos en 'Mis Libros Activos'.</span>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Autor/es</th>
                                <th>Materia</th>
                                <th>Estado</th>
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
                                        <td><?= htmlspecialchars($libro['nombre_autor'] ?? 'Desconocido') ?></td>
                                        <td><span class="category-tag"><?= htmlspecialchars($libro['nombre_materia'] ?? 'General') ?></span></td>
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
                                    <td colspan="6" style="text-align: center; color: var(--text-light); padding: 3rem;">
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
                <div class="header-actions">
                    <span style="font-size: 0.95rem; color: var(--text-light);">Aquí verás tus préstamos actuales y pasados.</span>
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
                                    <button onclick="abrirModalDevolucion(<?= $prestamo['id_prestamo'] ?>, '<?= htmlspecialchars($prestamo['titulo'], ENT_QUOTES) ?>')" style="margin-top: 0.75rem; width: 100%; padding: 0.6rem; background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.25s ease;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                                        📤 Entregar Ahora
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
                            style="width: 100%; padding: 0.75rem; border-radius: 0.5rem; border: 1px solid rgba(0,0,0,0.1); background: #f9fafb; outline: none;">
                        <small style="color: var(--text-light); display: block; margin-top: 0.5rem;">Debes elegir como mínimo el día de mañana.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModalReserva()">Cancelar</button>
                    <button type="submit" class="btn-primary">Confirmar Reserva</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Transaccional de Devolución Anticipada -->
    <div id="modal-devolucion" class="modal-overlay">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 style="color: #ef4444;">Entregar Libro</h2>
                <button type="button" class="btn-close" onclick="cerrarModalDevolucion()">×</button>
            </div>
            <form action="procesar_devolucion.php" method="POST">
                <div class="modal-body">
                    <p style="color: var(--text-light); margin-bottom: 1rem; line-height: 1.5;">¿Estás seguro que deseas realizar la entrega del ejemplar?</p>
                    <p style="font-weight: 700; color: var(--text-color); font-size: 1.1rem; margin-bottom: 0.5rem;" id="txt_titulo_devolver"></p>
                    
                    <input type="hidden" name="id_prestamo" id="devolucion_id_prestamo" value="">
                    
                    <div style="background: rgba(245, 158, 11, 0.1); border-left: 3px solid #f59e0b; padding: 0.75rem; color: #b45309; font-size: 0.85rem; margin-top: 1rem;">
                        Al continuar se desvinculará este libro de tu historial activo y recuperarás tu cupo en el catálogo.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModalDevolucion()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background: #ef4444; border-color: #ef4444;">Hacer Entrega Oficial</button>
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
        // Control del Modal de Devolución
        function abrirModalDevolucion(idPrestamo, titulo) {
            const modal = document.getElementById('modal-devolucion');
            document.getElementById('devolucion_id_prestamo').value = idPrestamo;
            document.getElementById('txt_titulo_devolver').textContent = titulo;
            modal.classList.add('active');
        }
        
        function cerrarModalDevolucion() {
            document.getElementById('modal-devolucion').classList.remove('active');
        }
    </script>
</body>
</html>
