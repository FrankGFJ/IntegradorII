<?php
session_start();

// Permitir solo a los administradores o bibliotecarios hacer cambios
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], ['admin', 'bibliotecario'])) {
    header("Location: index.php");
    exit();
}

require_once 'conexion.php';
require_once 'consultas.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    try {
        if ($accion === 'eliminar') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                eliminar_libro($con, $id);
                header("Location: admin.php?seccion=inventario&msg=eliminado");
                exit();
            }
        } elseif ($accion === 'editar' || $accion === 'crear') {
            $id = $_POST['id'] ?? null;
            $titulo = trim($_POST['titulo'] ?? '');
            $id_autor = empty($_POST['id_autor']) ? null : $_POST['id_autor'];
            $id_editorial = empty($_POST['id_editorial']) ? null : $_POST['id_editorial'];
            $id_materia = empty($_POST['id_materia']) ? null : $_POST['id_materia'];
            $cantidad = $_POST['cantidad'] ?? 5; // Por defecto es 5
            $num_pag = empty($_POST['num_pag']) ? null : $_POST['num_pag'];
            $anio_edicion = empty($_POST['anio_edicion']) ? null : $_POST['anio_edicion'];
            
            $id_idioma = empty($_POST['id_idioma']) ? null : $_POST['id_idioma'];
            
            // Si el usuario seleccionó agregar nuevo, crearlo sobre la marcha
            if ($id_autor === 'NEW' && !empty($_POST['nuevo_autor'])) {
                $id_autor = insertar_autor($con, trim($_POST['nuevo_autor']));
            }
            if ($id_editorial === 'NEW' && !empty($_POST['nuevo_editorial'])) {
                $id_editorial = insertar_editorial($con, trim($_POST['nuevo_editorial']));
            }
            if ($id_materia === 'NEW' && !empty($_POST['nuevo_materia'])) {
                $id_materia = insertar_materia($con, trim($_POST['nuevo_materia']));
            }
            if ($id_idioma === 'NEW' && !empty($_POST['nuevo_idioma'])) {
                $id_idioma = insertar_idioma($con, trim($_POST['nuevo_idioma']));
            }
            
            // Si el idioma sigue vacío, buscar 'Español' por defecto
            if (empty($id_idioma)) {
                $stmtEsp = $con->prepare("SELECT id FROM idiomas WHERE nombre = 'Español' LIMIT 1");
                $stmtEsp->execute();
                $id_idioma = $stmtEsp->fetchColumn() ?: null;
            }
            
            if ($accion === 'editar') {
                if ($id && $titulo) {
                    // Validar si el libro tiene préstamos/reservas activas
                    if (tiene_prestamos_activos($con, $id)) {
                        throw new Exception("No se puede editar la información de este libro porque actualmente se encuentra prestado o reservado por un estudiante.");
                    }
                    // Validar duplicidad
                    if (existe_libro_duplicado($con, $titulo, $id_autor, $id_editorial, $id)) {
                        throw new Exception("Ya existe un libro registrado con el mismo título, autor y editorial.");
                    }
                    actualizar_libro($con, $id, $titulo, $id_autor, $id_editorial, $id_materia, $cantidad, $num_pag, $anio_edicion, $id_idioma);
                    header("Location: admin.php?seccion=inventario&msg=actualizado");
                    exit();
                } else {
                    throw new Exception("Datos incompletos para actualizar.");
                }
            } else {
                if ($titulo) {
                    // Validar duplicidad
                    if (existe_libro_duplicado($con, $titulo, $id_autor, $id_editorial)) {
                        throw new Exception("Ya existe un libro registrado con el mismo título, autor y editorial.");
                    }
                    agregar_libro($con, $titulo, $id_autor, $id_editorial, $id_materia, $cantidad, $num_pag, $anio_edicion, $id_idioma);
                    header("Location: admin.php?seccion=inventario&msg=creado");
                    exit();
                } else {
                    throw new Exception("El título es obligatorio para registrar un libro.");
                }
            }
        }
    } catch (Exception $e) {
        $error = urlencode($e->getMessage());
        header("Location: admin.php?seccion=inventario&error=" . $error);
        exit();
    }
}

// Si se accede directamente sin verbo POST
header("Location: admin.php?seccion=inventario");
exit();
?>
