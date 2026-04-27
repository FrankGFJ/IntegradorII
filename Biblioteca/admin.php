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

                <!-- Opciones futuras simuladas -->
                <a href="#" class="nav-item" style="opacity: 0.5; cursor: not-allowed;" title="Próximamente">
                    <span class="icon">🔄</span> Préstamos
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
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Autor/a</th>
                                <th>Categoría</th>
                                <th>Stock</th>
                                <th>Estado</th>
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
<?php endif; ?>
        </main>
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

    <script>
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
    </script>
</body>

</html>