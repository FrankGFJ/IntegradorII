<?php
// Configuración de la Base de Datos
$host = '127.0.0.1';
$port = '3306';
$dbname = 'oasis';
$db_user = 'root';
$db_pass = '';

try {
    // Establecer conexión con PDO
    $con = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    // Configurar el modo de errores de PDO para que lance excepciones
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Detener la ejecución si hay un error de conexión
    die("Error de conexión con la BD: " . $e->getMessage());
}

// Auto-migración para la tabla idiomas y la relación id_idioma
try {
    // Verificar si la tabla 'idiomas' existe
    $checkTable = $con->query("SHOW TABLES LIKE 'idiomas'");
    if ($checkTable->rowCount() == 0) {
        // Crear tabla idiomas
        $con->exec("CREATE TABLE IF NOT EXISTS idiomas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Insertar idiomas iniciales
        $con->exec("INSERT IGNORE INTO idiomas (nombre) VALUES ('Español'), ('Inglés'), ('Francés'), ('Portugués')");
    }

    // Verificar si la columna 'id_idioma' existe en 'libros'
    $checkCol = $con->query("SHOW COLUMNS FROM libros LIKE 'id_idioma'");
    if ($checkCol->rowCount() == 0) {
        // Asegurar la columna y la FK
        $con->exec("ALTER TABLE libros ADD COLUMN id_idioma INT NULL AFTER anio_edicion");
        $con->exec("ALTER TABLE libros ADD CONSTRAINT fk_libros_idioma FOREIGN KEY (id_idioma) REFERENCES idiomas(id) ON DELETE SET NULL");

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
        }
    }
} catch (PDOException $e) {
    // Silencioso o log error en desarrollo, no detiene el flujo principal
}
?>
