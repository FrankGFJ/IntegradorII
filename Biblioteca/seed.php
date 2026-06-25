<?php
require_once 'conexion.php';

try {
    $con->beginTransaction();

    // 1. Insertar Idiomas
    $idiomas = ['Italiano', 'Alemán', 'Ruso', 'Chino', 'Japonés', 'Latín'];
    foreach ($idiomas as $idioma) {
        $con->exec("INSERT IGNORE INTO idiomas (nombre) VALUES ('$idioma')");
    }

    // 2. Insertar Autores (Tabla: autor)
    $autores = [
        'Gabriel García Márquez', 'J.K. Rowling', 'George R.R. Martin', 'Stephen King', 
        'Isaac Asimov', 'Julio Cortázar', 'Agatha Christie', 'Edgar Allan Poe', 
        'Haruki Murakami', 'Jane Austen', 'Fiódor Dostoyevski', 'Leo Tolstoy'
    ];
    foreach ($autores as $autor) {
        $con->exec("INSERT IGNORE INTO autor (nombre) VALUES ('$autor')");
    }

    // 3. Insertar Materias (Categorías) (Tabla: materias)
    $materias = [
        'Ficción Mágica', 'Fantasía', 'Terror', 'Ciencia Ficción', 
        'Misterio', 'Romance Clásico', 'Literatura Rusa', 'Filosofía'
    ];
    foreach ($materias as $mat) {
        $con->exec("INSERT IGNORE INTO materias (nombre) VALUES ('$mat')");
    }

    // 4. Insertar Editoriales (Tabla: editorial)
    $editoriales = [
        'Penguin Random House', 'Planeta', 'Anagrama', 'Alfaguara', 
        'Minotauro', 'Lumen', 'Ediciones B', 'Plaza & Janés'
    ];
    foreach ($editoriales as $edit) {
        $con->exec("INSERT IGNORE INTO editorial (nombre) VALUES ('$edit')");
    }

    $con->commit();

    // 5. Recuperar IDs para crear libros
    $autores_db = $con->query("SELECT id FROM autor")->fetchAll(PDO::FETCH_COLUMN);
    $materias_db = $con->query("SELECT id FROM materias")->fetchAll(PDO::FETCH_COLUMN);
    $idiomas_db = $con->query("SELECT id FROM idiomas")->fetchAll(PDO::FETCH_COLUMN);
    $editoriales_db = $con->query("SELECT id FROM editorial")->fetchAll(PDO::FETCH_COLUMN);

    $libros = [
        "Cien años de soledad", "El amor en los tiempos del cólera", "Crónica de una muerte anunciada",
        "Harry Potter y la piedra filosofal", "Juego de tronos", "Choque de reyes",
        "El resplandor", "It", "Fundación", "Yo, robot", "Rayuela", "Bestiario",
        "Asesinato en el Orient Express", "Diez negritos", "El cuervo", "Tokio blues",
        "Kafka en la orilla", "Orgullo y prejuicio", "Crimen y castigo", "Guerra y paz"
    ];

    $con->beginTransaction();
    foreach ($libros as $titulo) {
        $id_autor = $autores_db[array_rand($autores_db)];
        $id_materia = $materias_db[array_rand($materias_db)];
        $id_idioma = $idiomas_db[array_rand($idiomas_db)];
        $id_editorial = $editoriales_db[array_rand($editoriales_db)];
        $cantidad = rand(3, 15);
        $paginas = rand(150, 900);
        $anio = rand(1950, 2023);

        $stmt = $con->prepare("INSERT INTO libros (titulo, id_autor, id_editorial, id_materia, id_idioma, cantidad, num_pag, anio_edicion) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $id_autor, $id_editorial, $id_materia, $id_idioma, $cantidad, $paginas, $anio]);
    }
    $con->commit();

    echo "Seeding completado con exito.\n";

} catch (Exception $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
