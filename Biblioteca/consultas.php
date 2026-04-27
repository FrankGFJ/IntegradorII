<?php
// Archivo central para todas las consultas a la base de datos
require_once 'conexion.php';

/**
 * Obtiene un usuario válido buscando por nombre de usuario o correo.
 */
function obtener_usuario_por_login($con, $login) {
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
function obtener_inventario_libros($con) {
    try {
        $query = "SELECT l.*, a.nombre as autor_nombre, m.nombre as categoria_nombre 
                  FROM libros l 
                  LEFT JOIN autor a ON l.id_autor = a.id 
                  LEFT JOIN materias m ON l.id_materia = m.id
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
function obtener_libro_por_id($con, $id) {
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
 * Elimina un libro del inventario.
 */
function eliminar_libro($con, $id) {
    try {
        $stmt = $con->prepare("DELETE FROM libros WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        throw new Exception("Error al eliminar libro: " . $e->getMessage());
    }
}

/**
 * Actualiza un libro en el inventario.
 */
function actualizar_libro($con, $id, $titulo, $id_autor, $id_editorial, $id_materia, $cantidad, $stock_minimo, $num_pag, $anio_edicion) {
    try {
        $sql = "UPDATE libros SET 
                titulo = :titulo, 
                id_autor = :id_autor, 
                id_editorial = :id_editorial, 
                id_materia = :id_materia, 
                cantidad = :cantidad, 
                stock_minimo = :stock_minimo, 
                num_pag = :num_pag, 
                anio_edicion = :anio_edicion 
                WHERE id = :id";
                
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':titulo', $titulo);
        // Si el valor es vacío se puede registrar como NULL si la BD lo permite, pero asumimos texto o enteros válidos
        $stmt->bindParam(':id_autor', $id_autor, PDO::PARAM_INT);
        $stmt->bindParam(':id_editorial', $id_editorial, PDO::PARAM_INT);
        $stmt->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':stock_minimo', $stock_minimo, PDO::PARAM_INT);
        $stmt->bindParam(':num_pag', $num_pag, PDO::PARAM_INT);
        $stmt->bindParam(':anio_edicion', $anio_edicion, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        throw new Exception("Error al actualizar libro: " . $e->getMessage());
    }
}

// ==========================================
// FUNCIONES PARA LOS DESPLEGABLES (DROPDOWNS)
// ==========================================

function obtener_autores($con) {
    $stmt = $con->query("SELECT id, nombre FROM autor ORDER BY nombre ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtener_materias($con) {
    $stmt = $con->query("SELECT id, nombre FROM materias ORDER BY nombre ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtener_editoriales($con) {
    $stmt = $con->query("SELECT id, nombre FROM editorial ORDER BY nombre ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==========================================
// FUNCIONES DE CREACIÓN DE NUEVOS REGISTROS
// ==========================================

/**
 * Inserta un nuevo autor y devuelve su ID generado.
 */
function insertar_autor($con, $nombre) {
    $stmt = $con->prepare("INSERT INTO autor (nombre) VALUES (:nombre)");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->execute();
    return $con->lastInsertId();
}

/**
 * Inserta una nueva materia y devuelve su ID generado.
 */
function insertar_materia($con, $nombre) {
    $stmt = $con->prepare("INSERT INTO materias (nombre) VALUES (:nombre)");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->execute();
    return $con->lastInsertId();
}

/**
 * Inserta una nueva editorial y devuelve su ID generado.
 */
function insertar_editorial($con, $nombre) {
    $stmt = $con->prepare("INSERT INTO editorial (nombre) VALUES (:nombre)");
    $stmt->bindParam(':nombre', $nombre);
    $stmt->execute();
    return $con->lastInsertId();
}

/**
 * Agrega un nuevo libro al inventario.
 */
function agregar_libro($con, $titulo, $id_autor, $id_editorial, $id_materia, $cantidad, $stock_minimo, $num_pag, $anio_edicion) {
    try {
        $sql = "INSERT INTO libros (titulo, id_autor, id_editorial, id_materia, cantidad, stock_minimo, num_pag, anio_edicion, fecha_registro) 
                VALUES (:titulo, :id_autor, :id_editorial, :id_materia, :cantidad, :stock_minimo, :num_pag, :anio_edicion, NOW())";
                
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':id_autor', $id_autor, PDO::PARAM_INT);
        $stmt->bindParam(':id_editorial', $id_editorial, PDO::PARAM_INT);
        $stmt->bindParam(':id_materia', $id_materia, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':stock_minimo', $stock_minimo, PDO::PARAM_INT);
        $stmt->bindParam(':num_pag', $num_pag, PDO::PARAM_INT);
        $stmt->bindParam(':anio_edicion', $anio_edicion, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        throw new Exception("Error al agregar libro: " . $e->getMessage());
    }
}

// ==========================================
// FUNCIONES DEL MÓDULO DE USUARIOS
// ==========================================

function obtener_todos_usuarios($con) {
    try {
        $stmt = $con->query("SELECT id, usuario, nombre, correo, rol, estado FROM usuarios ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error al cargar usuarios: " . $e->getMessage());
    }
}

function insertar_usuario($con, $usuario, $nombre, $correo, $clave_hash, $rol, $estado) {
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

function actualizar_usuario($con, $id, $usuario, $nombre, $correo, $rol, $estado) {
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

// ==========================================
// FUNCIONES DE TRANSACCIÓN (ESTUDIANTES Y RESERVAS)
// ==========================================

function obtener_o_crear_estudiante($con, $id_usuario) {
    $stmt = $con->prepare("SELECT nombre, correo FROM usuarios WHERE id = ?");
    $stmt->execute([$id_usuario]);
    $usr = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usr) throw new Exception("Usuario base inválido");

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

function procesar_transaccion_reserva($con, $id_usuario, $id_libro, $fecha_devolucion) {
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

function obtener_libros_reservados_estudiante($con, $id_usuario) {
    try {
        $id_estudiante = obtener_o_crear_estudiante($con, $id_usuario);
        $stmt = $con->prepare("SELECT id_libro FROM prestamos WHERE id_estudiante = ? AND estado = 'activo'");
        $stmt->execute([$id_estudiante]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ids = [];
        foreach($rows as $row) {
            $ids[] = $row['id_libro'];
        }
        return $ids;
    } catch (Exception $e) {
        return [];
    }
}

function obtener_detalle_prestamos_estudiante($con, $id_usuario) {
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

function procesar_transaccion_devolucion($con, $id_usuario, $id_prestamo) {
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

?>
