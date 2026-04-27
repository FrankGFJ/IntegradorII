DROP DATABASE IF EXISTS oasis;
CREATE DATABASE oasis;
USE oasis;

-- =========================
-- TABLA USUARIOS (CON ROLES)
-- =========================
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  nombre VARCHAR(150) NOT NULL,
  correo VARCHAR(100) NOT NULL UNIQUE,
  clave VARCHAR(255) NOT NULL,
  rol ENUM('admin','bibliotecario','estudiante') DEFAULT 'estudiante',
  estado TINYINT(1) DEFAULT 1,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- TABLA EMPRESA
-- =========================
CREATE TABLE empresa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ruc VARCHAR(20),
  nombre VARCHAR(150),
  telefono VARCHAR(20),
  correo VARCHAR(100),
  direccion VARCHAR(255)
);

-- =========================
-- TABLAS BASE
-- =========================
CREATE TABLE autor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL
);

CREATE TABLE editorial (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL
);

CREATE TABLE materias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL
);

-- =========================
-- TABLA LIBROS (INVENTARIO)
-- =========================
CREATE TABLE libros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  id_editorial INT,
  id_autor INT,
  id_materia INT,
  cantidad INT DEFAULT 0,
  stock_minimo INT DEFAULT 5,
  num_pag INT,
  anio_edicion INT,
  fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (id_editorial) REFERENCES editorial(id) ON DELETE SET NULL,
  FOREIGN KEY (id_autor) REFERENCES autor(id) ON DELETE SET NULL,
  FOREIGN KEY (id_materia) REFERENCES materias(id) ON DELETE SET NULL
);

-- =========================
-- TABLA ESTUDIANTES
-- =========================
CREATE TABLE estudiantes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  documento VARCHAR(20) UNIQUE,
  codigo VARCHAR(20),
  nombre VARCHAR(150),
  telefono VARCHAR(20),
  correo VARCHAR(100),
  carrera VARCHAR(150)
);

-- =========================
-- TABLA PRÉSTAMOS
-- =========================
CREATE TABLE prestamos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_estudiante INT,
  id_libro INT,
  cantidad INT NOT NULL,
  fecha_prestamo DATE,
  fecha_devolucion DATE,
  estado ENUM('activo','devuelto','retrasado') DEFAULT 'activo',

  FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id) ON DELETE CASCADE,
  FOREIGN KEY (id_libro) REFERENCES libros(id) ON DELETE CASCADE
);

-- =========================
-- TABLA MOVIMIENTOS (CONTROL INVENTARIO)
-- =========================
CREATE TABLE movimientos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_libro INT,
  tipo ENUM('prestamo','devolucion','venta','ajuste') NOT NULL,
  cantidad INT NOT NULL,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  id_usuario INT,

  FOREIGN KEY (id_libro) REFERENCES libros(id),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

-- =========================
-- TABLA VENTAS
-- =========================
CREATE TABLE ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  total DECIMAL(10,2) DEFAULT 0,
  id_usuario INT,

  FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

-- =========================
-- DETALLE DE VENTAS
-- =========================
CREATE TABLE detalle_ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_venta INT,
  id_libro INT,
  cantidad INT,
  precio DECIMAL(10,2),

  FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE,
  FOREIGN KEY (id_libro) REFERENCES libros(id)
);

-- =========================
-- DATOS INICIALES
-- =========================

INSERT INTO usuarios (usuario, nombre, correo, clave, rol) VALUES
('estudiante', 'estudiante', 'estudiante@correo.com', 'estudiante123', 'estudiante');

INSERT INTO empresa (ruc, nombre, telefono, correo, direccion) VALUES
('123456789', 'Sistema Biblioteca', '999999999', 'info@biblioteca.com', 'Ciudad');

INSERT INTO autor (nombre) VALUES
('Autor 1'), ('Autor 2');

INSERT INTO editorial (nombre) VALUES
('Editorial 1'), ('Editorial 2');

INSERT INTO materias (nombre) VALUES
('Base de Datos'), ('Programación');

INSERT INTO libros (titulo, id_editorial, id_autor, id_materia, cantidad, num_pag, anio_edicion) VALUES
('Libro Ejemplo', 1, 1, 1, 10, 200, 2024);

INSERT INTO estudiantes (documento, codigo, nombre, telefono, correo, carrera) VALUES
('12345678', 'EST001', 'Juan Perez', '987654321', 'juan@mail.com', 'Ingeniería');

SELECT * FROM autor;
SELECT * FROM editorial;
SELECT * FROM materias;

DELETE FROM libros WHERE id IN (1);

DELETE FROM autor WHERE id IN (1,2);
DELETE FROM editorial WHERE id IN (1,2);
DELETE FROM materias WHERE id IN (1,2);

ALTER TABLE autor AUTO_INCREMENT = 1;
ALTER TABLE editorial AUTO_INCREMENT = 1;
ALTER TABLE materias AUTO_INCREMENT = 1;
ALTER TABLE libros AUTO_INCREMENT = 1;

INSERT INTO autor (nombre) VALUES
('Robert C. Martin'),
('Donald Knuth'),
('Ian Sommerville'),
('Martin Fowler'),
('Andrew Tanenbaum'),
('James Clear'),
('Stephen Hawking');

INSERT INTO editorial (nombre) VALUES
('Pearson'),
('McGraw-Hill'),
('O\'Reilly Media'),
('Addison-Wesley'),
('Springer'),
('Planeta');

INSERT INTO materias (nombre) VALUES
('Algoritmos'),
('Ingeniería de Software'),
('Redes'),
('Ciencia de Datos'),
('Física'),
('Productividad');

INSERT INTO libros 
(titulo, id_editorial, id_autor, id_materia, cantidad, stock_minimo, num_pag, anio_edicion)
VALUES
('Prueba de Stock', 3, 1, 4, 22, 22, 400, 2023);

use oasis;
select * from usuarios;