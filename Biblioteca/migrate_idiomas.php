<?php
require_once 'conexion.php';

echo "<h1>Iniciando migración de idiomas...</h1>";

try {
    // Verificar si la tabla 'idiomas' existe
    $checkTable = $con->query("SHOW TABLES LIKE 'idiomas'");
    if ($checkTable->rowCount() == 0) {
        // Crear tabla idiomas
        $con->exec("CREATE TABLE IF NOT EXISTS idiomas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        echo "<p>Tabla 'idiomas' creada.</p>";

        // Insertar idiomas iniciales
        $con->exec("INSERT IGNORE INTO idiomas (nombre) VALUES ('Español'), ('Inglés'), ('Francés'), ('Portugués')");
        echo "<p>Idiomas iniciales insertados.</p>";
    } else {
        echo "<p>La tabla 'idiomas' ya existe.</p>";
    }

    // Verificar si la columna 'id_idioma' existe en 'libros'
    $checkCol = $con->query("SHOW COLUMNS FROM libros LIKE 'id_idioma'");
    if ($checkCol->rowCount() == 0) {
        // Asegurar la columna y la FK
        $con->exec("ALTER TABLE libros ADD COLUMN id_idioma INT NULL AFTER anio_edicion");
        $con->exec("ALTER TABLE libros ADD CONSTRAINT fk_libros_idioma FOREIGN KEY (id_idioma) REFERENCES idiomas(id) ON DELETE SET NULL");
        echo "<p>Columna 'id_idioma' añadida a 'libros'.</p>";

        // Migrar datos de la columna anterior 'idioma' si existe
        $checkOldCol = $con->query("SHOW COLUMNS FROM libros LIKE 'idioma'");
        if ($checkOldCol->rowCount() > 0) {
            $stmt = $con->query("SELECT id, idioma FROM libros");
            $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtIdiomas = $con->query("SELECT id, nombre FROM idiomas");
            $idiomasMap = [];
            foreach ($stmtIdiomas->fetchAll(PDO::FETCH_ASSOC) as $idiomaRow) {
                $idiomasMap[strtolower(trim($idiomaRow['nombre']))] = $idiomaRow['id'];
            }

            foreach ($libros as $libro) {
                $oldIdiomaVal = trim($libro['idioma'] ?? 'Español');
                if (empty($oldIdiomaVal)) {
                    $oldIdiomaVal = 'Español';
                }
                $key = strtolower($oldIdiomaVal);

                if (!isset($idiomasMap[$key])) {
                    $stmtIns = $con->prepare("INSERT INTO idiomas (nombre) VALUES (?)");
                    $stmtIns->execute([$oldIdiomaVal]);
                    $newId = $con->lastInsertId();
                    $idiomasMap[$key] = $newId;
                }

                $stmtUpd = $con->prepare("UPDATE libros SET id_idioma = ? WHERE id = ?");
                $stmtUpd->execute([$idiomasMap[$key], $libro['id']]);
            }

            $con->exec("ALTER TABLE libros DROP COLUMN idioma");
            echo "<p>Datos de idiomas migrados y columna antigua eliminada.</p>";
        }
    } else {
         echo "<p>La columna 'id_idioma' ya existe en 'libros'. Migración completada previamente.</p>";
    }
    
    echo "<h2>¡Migración completada con éxito!</h2>";
} catch (PDOException $e) {
    echo "<h3>Error durante la migración: " . $e->getMessage() . "</h3>";
}
?>
