<?php
// Archivo central para todas las consultas a la base de datos
require_once 'conexion.php';

/**
 * Obtiene un usuario válido buscando por nombre de usuario o correo.
 */
function obtener_usuario_por_login($con, $login)
{
    try {
        $stmt = $con->prepare("SELECT id, usuario, correo, nombre, clave, rol, estado FROM usuarios WHERE usuario = :login OR correo = :login LIMIT 1");
        $stmt->bindParam(':login', $login);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error al consultar el usuario: " . $e->getMessage());
    }
}

/**
 * Obtiene el inventario completo de libros con los nombres de autor y materia.
 */
function obtener_inventario_libros($con)
{
    try {
        $query = "SELECT l.*, a.nombre as autor_nombre, m.nombre as categoria_nombre, i.nombre as idioma_nombre 
                  FROM libros l 
                  LEFT JOIN autor a ON l.id_autor = a.id 
                  LEFT JOIN materias m ON l.id_materia = m.id
                  LEFT JOIN idiomas i ON l.id_idioma = i.id
                  ORDER BY l.id ASC";
        $stmt = $con->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error al cargar libros: " . $e->getMessage());
    }
}

/**
 * Obtiene toda la información de un libro usando su ID.
 */
function obtener_libro_por_id($con, $id)
{
    try {
        $stmt = $con->prepare("SELECT * FROM libros WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error al cargar el libro: " . $e->getMessage());
    }
}

/**
 * Verifica si un libro tiene préstamos activos (en curso).
 */
function tiene_prestamos_activos($con, $id_libro)
{
    try {
        $stmt = $con->prepare("SELECT COUNT(*) FROM prestamos WHERE id_libro = :id AND estado = 'activo'");
        $stmt->bindParam(':id', $id_libro, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        throw new Exception("Error al verificar préstamos activos del libro: " . $e->getMessage());
    }
}

/**
 * Verifica si ya existe otro libro registrado con el mismo título, autor y editorial.
 */
function existe_libro_duplicado($con, $titulo, $id_autor, $id_editorial, $exclude_id = null)
{
    try {
        $sql = "SELECT COUNT(*) FROM libros WHERE LOWER(TRIM(titulo)) = LOWER(TRIM(:titulo))";
        
        if ($id_autor === null) {
            $sql .= " AND id_autor IS NULL";
        } else {
            $sql .= " AND id_autor = :id_autor";
        }
        
        if ($id_editorial === null) {
            $sql .= " AND id_editorial IS NULL";
        } else {
            $sql .= " AND id_editorial = :id_editorial";
        }
        
        if ($exclude_id !== null) {
            $sql .= " AND id != :exclude_id";
        }
        
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':titulo', $titulo);
        
        if ($id_autor !== null) {
            $stmt->bindParam(':id_autor', $id_autor, PDO::PARAM_INT);
        }
        if ($id_editorial !== null) {
            $stmt->bindParam(':id_editorial', $id_editorial, PDO::PARAM_INT);
        }
        if ($exclude_id !== null) {
            $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        throw new Exception("Error al verificar duplicidad de libro: " . $e->getMessage());
    }
}


/**
 * Elimina un libro del inventario.
 */
function eliminar_libro($con, $id)
{
    try {
        // 1. Verificar si hay préstamos activos
        if (tiene_prestamos_activos($con, $id)) {
            throw new Exception("No se puede eliminar el libro porque tiene préstamos activos pendientes de devolución.");
        }

        // 2. Verificar si hay historial de préstamos (devueltos)
        $stmt = $con->prepare("SELECT COUNT(*) FROM prestamos WHERE id_libro = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el libro porque tiene historial de préstamos registrados en el sistema.");
        }

        // 3. Verificar si hay movimientos registrados en la bitácora
        $stmt = $con->prepare("SELECT COUNT(*) FROM movimientos WHERE id_libro = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el libro porque tiene movimientos históricos registrados en la bitácora.");
        }

        // 4. Intentar eliminar
        $stmt = $con->prepare("DELETE FROM libros WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            throw new Exception("No se puede eliminar el libro porque está asociado a otros registros (préstamos o movimientos).");
        }
        throw new Exception("Error al eliminar libro: " . $e->getMessage());
    }
}

/**
 * Actualiza un libro en el inventario.
 */
function actualizar_libro($con, $id, $titulo, $id_autor, $id_editorial, $id_materia, $cantidad, $num_pag, $anio_edicion, $id_idioma)
{
    try {
        $sql = "UPDATE libros SET 
                titulo = :titulo, 
                id_autor = :id_autor, 
                id_editorial = :id_editorial, 
                id_materia = :id_materia, 
                cantidad = :cantidad, 
                stock_minimo = 0, 
                num_pag = :num_pag, 
                anio_edicion = :anio_edicion,
                id_idioma = :id_idioma
                WHERE id = :id";

        $stmt = $con->prepare($sql);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':id_autor', $id_autor, PDO::PARAM_INT);
        $stmt->bindParam(':id_editorial', $id_editorial, PDO::PARAM_INT);
        $stmt->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':num_pag', $num_pag, PDO::PARAM_INT);
        $stmt->bindParam(':anio_edicion', $anio_edicion, PDO::PARAM_INT);
        $stmt->bindParam(':id_idioma', $id_idioma, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    } catch (PDOException $e) {
        throw new Exception("Error al actualizar libro: " . $e->getMessage());
    }
}

// ==========================================
// FUNCIONES PARA LOS DESPLEGABLES (DROPDOWNS)
// ==========================================

function obtener_autores($con)
{
    $stmt = $con->query("SELECT id, nombre FROM autor ORDER BY nombre ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtener_materias($con)
{
    $stmt = $con->query("SELECT id, nombre FROM materias ORDER BY nombre ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtener_editoriales($con)
{
    $stmt = $con->query("SELECT id, nombre FROM editorial ORDER BY nombre ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtener_idiomas($con)
{
    $stmt = $con->query("SELECT id, nombre FROM idiomas ORDER BY nombre ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==========================================
// FUNCIONES DE CREACIÓN DE NUEVOS REGISTROS
// ==========================================

/**
 * Inserta un nuevo autor y devuelve su ID generado.
 */
function insertar_autor($con, $nombre)
{
    $stmt = $con->prepare("INSERT INTO autor (nombre) VALUES (:nombre)");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->execute();
    return $con->lastInsertId();
}

/**
 * Inserta una nueva materia y devuelve su ID generado.
 */
function insertar_materia($con, $nombre)
{
    $stmt = $con->prepare("INSERT INTO materias (nombre) VALUES (:nombre)");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->execute();
    return $con->lastInsertId();
}

/**
 * Inserta una nueva editorial y devuelve su ID generado.
 */
function insertar_editorial($con, $nombre)
{
    $stmt = $con->prepare("INSERT INTO editorial (nombre) VALUES (:nombre)");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->execute();
    return $con->lastInsertId();
}

/**
 * Inserta un nuevo idioma y devuelve su ID generado.
 */
function insertar_idioma($con, $nombre)
{
    $stmt = $con->prepare("INSERT INTO idiomas (nombre) VALUES (:nombre)");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->execute();
    return $con->lastInsertId();
}

/**
 * Agrega un nuevo libro al inventario.
 */
function agregar_libro($con, $titulo, $id_autor, $id_editorial, $id_materia, $cantidad, $num_pag, $anio_edicion, $id_idioma)
{
    try {
        $sql = "INSERT INTO libros (titulo, id_autor, id_editorial, id_materia, cantidad, stock_minimo, num_pag, anio_edicion, id_idioma, fecha_registro) 
                VALUES (:titulo, :id_autor, :id_editorial, :id_materia, :cantidad, 0, :num_pag, :anio_edicion, :id_idioma, NOW())";

        $stmt = $con->prepare($sql);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':id_autor', $id_autor, PDO::PARAM_INT);
        $stmt->bindParam(':id_editorial', $id_editorial, PDO::PARAM_INT);
        $stmt->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':num_pag', $num_pag, PDO::PARAM_INT);
        $stmt->bindParam(':anio_edicion', $anio_edicion, PDO::PARAM_INT);
        $stmt->bindParam(':id_idioma', $id_idioma, PDO::PARAM_INT);

        return $stmt->execute();
    } catch (PDOException $e) {
        throw new Exception("Error al agregar libro: " . $e->getMessage());
    }
}

// ==========================================
// FUNCIONES DEL MÓDULO DE USUARIOS
// ==========================================

function obtener_todos_usuarios($con)
{
    try {
        $stmt = $con->query("SELECT id, usuario, nombre, correo, rol, estado FROM usuarios ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error al cargar usuarios: " . $e->getMessage());
    }
}

function insertar_usuario($con, $usuario, $nombre, $correo, $clave_hash, $rol, $estado)
{
    try {
        $stmt = $con->prepare("INSERT INTO usuarios (usuario, nombre, correo, clave, rol, estado) 
                               VALUES (:usuario, :nombre, :correo, :clave, :rol, :estado)");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':clave', $clave_hash);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        throw new Exception("Error al insertar usuario: " . $e->getMessage());
    }
}

function actualizar_usuario($con, $id, $usuario, $nombre, $correo, $rol, $estado)
{
    try {
        $stmt = $con->prepare("UPDATE usuarios SET usuario = :usuario, nombre = :nombre, correo = :correo, rol = :rol, estado = :estado WHERE id = :id");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        throw new Exception("Error al actualizar usuario: " . $e->getMessage());
    }
}

function tiene_prestamos_activos_usuario($con, $id_usuario)
{
    try {
        $stmt = $con->prepare("
            SELECT COUNT(*) 
            FROM prestamos p
            JOIN estudiantes e ON p.id_estudiante = e.id
            JOIN usuarios u ON u.correo = e.correo
            WHERE u.id = :id AND p.estado = 'activo'
        ");
        $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        throw new Exception("Error al verificar préstamos activos del usuario: " . $e->getMessage());
    }
}

function eliminar_usuario($con, $id)
{
    if (tiene_prestamos_activos_usuario($con, $id)) {
        throw new Exception("No se puede eliminar el usuario porque tiene préstamos activos pendientes de devolución.");
    }

    try {
        $stmt = $con->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            throw new Exception("No se puede eliminar el usuario porque tiene registros históricos asociados en el sistema.");
        }
        throw new Exception("Error al eliminar usuario: " . $e->getMessage());
    }
}

function actualizar_clave_usuario($con, $id, $clave_hash)
{
    try {
        $stmt = $con->prepare("UPDATE usuarios SET clave = :clave WHERE id = :id");
        $stmt->bindParam(':clave', $clave_hash);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        throw new Exception("Error al actualizar la contraseña: " . $e->getMessage());
    }
}


/**
 * Verifica si un nombre de usuario ya existe en la base de datos.
 */
function existe_usuario_por_username($con, $username, $exclude_id = null)
{
    try {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE usuario = :username";
        if ($exclude_id !== null) {
            $sql .= " AND id != :id";
        }
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':username', $username);
        if ($exclude_id !== null) {
            $stmt->bindParam(':id', $exclude_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        throw new Exception("Error al verificar el nombre de usuario: " . $e->getMessage());
    }
}

/**
 * Verifica si un correo electrónico ya existe en la base de datos.
 */
function existe_usuario_por_correo($con, $correo, $exclude_id = null)
{
    try {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE correo = :correo";
        if ($exclude_id !== null) {
            $sql .= " AND id != :id";
        }
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':correo', $correo);
        if ($exclude_id !== null) {
            $stmt->bindParam(':id', $exclude_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        throw new Exception("Error al verificar el correo electrónico: " . $e->getMessage());
    }
}

// ==========================================
// FUNCIONES DE TRANSACCIÓN (ESTUDIANTES Y RESERVAS)
// ==========================================

function obtener_o_crear_estudiante($con, $id_usuario)
{
    $stmt = $con->prepare("SELECT nombre, correo FROM usuarios WHERE id = ?");
    $stmt->execute([$id_usuario]);
    $usr = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usr)
        throw new Exception("Usuario base inválido");

    $stmt2 = $con->prepare("SELECT id FROM estudiantes WHERE correo = ?");
    $stmt2->execute([$usr['correo']]);
    $est = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($est) {
        return $est['id'];
    } else {
        $stmt3 = $con->prepare("INSERT INTO estudiantes (nombre, correo) VALUES (?, ?)");
        $stmt3->execute([$usr['nombre'], $usr['correo']]);
        return $con->lastInsertId();
    }
}

function procesar_transaccion_reserva($con, $id_usuario, $id_libro, $fecha_devolucion)
{
    try {
        $con->beginTransaction();

        // 1. Bridge Usuario -> Estudiante (Llave Foránea)
        $id_estudiante = obtener_o_crear_estudiante($con, $id_usuario);

        // 2. Registro Transaccional en Tabla Préstamos
        // estado es enum ('activo','devuelto','retrasado')
        $stmt = $con->prepare("INSERT INTO prestamos (id_estudiante, id_libro, cantidad, fecha_prestamo, fecha_devolucion, estado) VALUES (?, ?, 1, CURDATE(), ?, 'activo')");
        $stmt->execute([$id_estudiante, $id_libro, $fecha_devolucion]);

        // 3. Registro Físico en Tabla Movimientos
        // tipo es enum ('prestamo','devolucion','venta','ajuste')
        $stmt2 = $con->prepare("INSERT INTO movimientos (id_libro, tipo, cantidad, id_usuario) VALUES (?, 'prestamo', 1, ?)");
        $stmt2->execute([$id_libro, $id_usuario]);

        // 4. Actualización Lógica de Inventario (Con bloqueo preventivo de Agotado)
        $stmt3 = $con->prepare("UPDATE libros SET cantidad = cantidad - 1 WHERE id = ? AND cantidad > 0");
        $stmt3->execute([$id_libro]);

        if ($stmt3->rowCount() == 0) {
            throw new Exception("El libro se quedó sin stock justo mientras intentabas reservarlo.");
        }

        $con->commit();
        return true;
    } catch (Exception $e) {
        $con->rollBack();
        throw $e;
    }
}

// ==========================================
// CONSULTAS PARA LOS DASHBOARDS DEL ESTUDIANTE
// ==========================================

function obtener_libros_reservados_estudiante($con, $id_usuario)
{
    try {
        $id_estudiante = obtener_o_crear_estudiante($con, $id_usuario);
        $stmt = $con->prepare("SELECT id_libro FROM prestamos WHERE id_estudiante = ? AND estado = 'activo'");
        $stmt->execute([$id_estudiante]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = $row['id_libro'];
        }
        return $ids;
    } catch (Exception $e) {
        return [];
    }
}

function tiene_prestamos_retrasados($con, $id_usuario)
{
    try {
        $id_estudiante = obtener_o_crear_estudiante($con, $id_usuario);
        $stmt = $con->prepare("SELECT COUNT(*) FROM prestamos WHERE id_estudiante = ? AND estado = 'activo' AND fecha_devolucion < CURDATE()");
        $stmt->execute([$id_estudiante]);
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

function obtener_detalle_prestamos_estudiante($con, $id_usuario)
{
    try {
        $id_estudiante = obtener_o_crear_estudiante($con, $id_usuario);
        $sql = "
            SELECT 
                p.id as id_prestamo, 
                p.fecha_prestamo, 
                p.fecha_devolucion, 
                p.estado,
                l.titulo, 
                a.nombre as nombre_autor,
                m.nombre as nombre_materia
            FROM prestamos p
            JOIN libros l ON p.id_libro = l.id
            LEFT JOIN autor a ON l.id_autor = a.id
            LEFT JOIN materias m ON l.id_materia = m.id
            WHERE p.id_estudiante = ?
            ORDER BY 
                CASE WHEN p.estado = 'activo' THEN 1 ELSE 2 END, /* Activos primero */
                p.fecha_devolucion ASC
        ";
        $stmt = $con->prepare($sql);
        $stmt->execute([$id_estudiante]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function procesar_transaccion_devolucion($con, $id_usuario, $id_prestamo)
{
    try {
        $con->beginTransaction();

        $id_estudiante = obtener_o_crear_estudiante($con, $id_usuario);

        // Validar que el prestamo exista, sea suyo y esté activo
        $stmt_val = $con->prepare("SELECT id_libro, estado FROM prestamos WHERE id = ? AND id_estudiante = ? FOR UPDATE");
        $stmt_val->execute([$id_prestamo, $id_estudiante]);
        $prestamo = $stmt_val->fetch(PDO::FETCH_ASSOC);

        if (!$prestamo) {
            throw new Exception("El préstamo con ID $id_prestamo no fue encontrado como tuyo.");
        }
        if ($prestamo['estado'] === 'devuelto') {
            // Ya estaba devuelto, ignoramos para no generar duplicados, devolvemos success silencioso
            $con->commit();
            return true;
        }

        $id_libro = $prestamo['id_libro'];

        // 1. Actualizar Prestamo
        $stmt_upd = $con->prepare("UPDATE prestamos SET estado = 'devuelto' WHERE id = ?");
        $stmt_upd->execute([$id_prestamo]);

        // 2. Registrar Movimiento Inverso
        $stmt_mov = $con->prepare("INSERT INTO movimientos (id_libro, tipo, cantidad, id_usuario) VALUES (?, 'devolucion', 1, ?)");
        $stmt_mov->execute([$id_libro, $id_usuario]);

        // 3. Regresar Libro al Estante Virtual
        $stmt_lib = $con->prepare("UPDATE libros SET cantidad = cantidad + 1 WHERE id = ?");
        $stmt_lib->execute([$id_libro]);

        $con->commit();
        return true;
    } catch (Exception $e) {
        $con->rollBack();
        throw $e;
    }
}


function obtener_todos_prestamos($con)
{
    try {
        $sql = "
            SELECT 
                p.id as id_prestamo, 
                p.fecha_prestamo, 
                p.fecha_devolucion, 
                p.estado,
                l.titulo, 
                a.nombre as nombre_autor,
                e.nombre as nombre_estudiante,
                e.correo as correo_estudiante
            FROM prestamos p
            JOIN libros l ON p.id_libro = l.id
            LEFT JOIN autor a ON l.id_autor = a.id
            JOIN estudiantes e ON p.id_estudiante = e.id
            ORDER BY 
                CASE WHEN p.estado = 'activo' THEN 1 ELSE 2 END,
                p.fecha_devolucion ASC
        ";
        $stmt = $con->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function procesar_devolucion_admin($con, $id_prestamo, $id_admin)
{
    try {
        $con->beginTransaction();

        $stmt_val = $con->prepare("SELECT id_libro, estado FROM prestamos WHERE id = ? FOR UPDATE");
        $stmt_val->execute([$id_prestamo]);
        $prestamo = $stmt_val->fetch(PDO::FETCH_ASSOC);

        if (!$prestamo) {
            throw new Exception("El préstamo con ID $id_prestamo no fue encontrado.");
        }
        if ($prestamo['estado'] === 'devuelto') {
            $con->commit();
            return true;
        }

        $id_libro = $prestamo['id_libro'];

        // 1. Actualizar Prestamo
        $stmt_upd = $con->prepare("UPDATE prestamos SET estado = 'devuelto' WHERE id = ?");
        $stmt_upd->execute([$id_prestamo]);

        // 2. Registrar Movimiento Inverso
        $stmt_mov = $con->prepare("INSERT INTO movimientos (id_libro, tipo, cantidad, id_usuario) VALUES (?, 'devolucion', 1, ?)");
        $stmt_mov->execute([$id_libro, $id_admin]);

        // 3. Regresar Libro al Estante Virtual
        $stmt_lib = $con->prepare("UPDATE libros SET cantidad = cantidad + 1 WHERE id = ?");
        $stmt_lib->execute([$id_libro]);

        $con->commit();
        return true;
    } catch (Exception $e) {
        $con->rollBack();
        throw $e;
    }
}

// ==========================================
// MÓDULO DE REPORTES Y ESTADÍSTICAS 📊
// ==========================================

/**
 * Obtiene métricas globales (KPIs) de la biblioteca.
 */
function obtener_kpis_reportes($con)
{
    try {
        $kpis = [];

        // Títulos únicos
        $kpis['total_titulos'] = (int) $con->query("SELECT COUNT(*) FROM libros")->fetchColumn();

        // Stock físico total
        $kpis['stock_total'] = (int) $con->query("SELECT IFNULL(SUM(cantidad), 0) FROM libros")->fetchColumn();

        // Préstamos activos
        $kpis['prestamos_activos'] = (int) $con->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'activo'")->fetchColumn();

        // Préstamos vencidos/retrasados
        $kpis['prestamos_atrasados'] = (int) $con->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'activo' AND fecha_devolucion < CURDATE()")->fetchColumn();

        return $kpis;
    } catch (PDOException $e) {
        throw new Exception("Error al cargar KPIs de reportes: " . $e->getMessage());
    }
}

/**
 * Obtiene los libros más prestados históricamente.
 */
function obtener_libros_mas_prestados($con, $limite = 5)
{
    try {
        $sql = "SELECT l.titulo, COUNT(p.id) as total_prestamos 
                FROM prestamos p 
                JOIN libros l ON p.id_libro = l.id 
                GROUP BY p.id_libro 
                ORDER BY total_prestamos DESC 
                LIMIT :limite";
        $stmt = $con->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error al cargar libros más prestados: " . $e->getMessage());
    }
}

/**
 * Obtiene los préstamos agrupados por materia.
 */
function obtener_prestamos_por_materia($con)
{
    try {
        $sql = "SELECT m.nombre as materia, COUNT(p.id) as total 
                FROM prestamos p 
                JOIN libros l ON p.id_libro = l.id 
                JOIN materias m ON l.id_materia = m.id 
                GROUP BY l.id_materia 
                ORDER BY total DESC";
        return $con->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error al cargar préstamos por materia: " . $e->getMessage());
    }
}

/**
 * Obtiene el estado de todos los préstamos para la comparativa circular.
 */
function obtener_estado_prestamos($con)
{
    try {
        $estados = [
            'devuelto' => 0,
            'activo_a_tiempo' => 0,
            'retrasado' => 0
        ];

        $estados['devuelto'] = (int) $con->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'devuelto'")->fetchColumn();
        $estados['activo_a_tiempo'] = (int) $con->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'activo' AND fecha_devolucion >= CURDATE()")->fetchColumn();
        $estados['retrasado'] = (int) $con->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'activo' AND fecha_devolucion < CURDATE()")->fetchColumn();

        return $estados;
    } catch (PDOException $e) {
        throw new Exception("Error al cargar distribución de estados: " . $e->getMessage());
    }
}

/**
 * Obtiene los libros bajo el límite de stock mínimo.
 */
function obtener_libros_bajo_stock($con)
{
    try {
        $sql = "SELECT l.id, l.titulo, l.cantidad, l.stock_minimo, a.nombre as autor_nombre 
                FROM libros l 
                LEFT JOIN autor a ON l.id_autor = a.id 
                WHERE l.cantidad <= l.stock_minimo 
                ORDER BY l.cantidad ASC";
        return $con->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error al cargar libros con bajo stock: " . $e->getMessage());
    }
}

/**
 * Obtiene los estudiantes con más préstamos activos retrasados.
 */
function obtener_estudiantes_con_mas_retrasos($con)
{
    try {
        $sql = "SELECT e.nombre, e.correo, l.titulo, p.fecha_devolucion, DATEDIFF(CURDATE(), p.fecha_devolucion) as dias_retraso
                FROM prestamos p
                JOIN estudiantes e ON p.id_estudiante = e.id
                JOIN libros l ON p.id_libro = l.id
                WHERE p.estado = 'activo' AND p.fecha_devolucion < CURDATE()
                ORDER BY dias_retraso DESC";
        return $con->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error al cargar deudores: " . $e->getMessage());
    }
}

/**
 * Obtiene los movimientos históricos recientes con nombres de libros y usuarios.
 */
function obtener_movimientos_recientes($con, $limite = 5)
{
    try {
        $sql = "SELECT m.id, l.titulo, m.tipo, m.cantidad, m.fecha, u.nombre as usuario_nombre 
                FROM movimientos m 
                JOIN libros l ON m.id_libro = l.id 
                LEFT JOIN usuarios u ON m.id_usuario = u.id 
                ORDER BY m.id DESC 
                LIMIT :limite";
        $stmt = $con->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error al cargar movimientos recientes: " . $e->getMessage());
    }
}

?>