-- =====================================================================
--  Biblioteca UTU — esquema y datos de ejemplo
--  Importar desde phpMyAdmin (pestaña "Importar").
--  Si ya existe una base "biblioteca" anterior, borrarla antes.
-- =====================================================================
CREATE DATABASE IF NOT EXISTS biblioteca CHARACTER SET utf8mb4;
USE biblioteca;

-- ---------- USUARIOS ----------
CREATE TABLE IF NOT EXISTS usuarios (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    rol     ENUM('admin', 'socio') NOT NULL DEFAULT 'socio',
    email   VARCHAR(100) NOT NULL UNIQUE,
    pass    VARCHAR(255) NOT NULL          -- hash generado con password_hash()
);

-- ---------- SOCIOS ----------
CREATE TABLE IF NOT EXISTS socios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL UNIQUE,
    nombre      VARCHAR(100) NOT NULL,
    direccion   VARCHAR(255) NOT NULL,
    telefono    VARCHAR(20)  NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- ---------- LIBROS ----------
CREATE TABLE IF NOT EXISTS libros (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    titulo      VARCHAR(150) NOT NULL,
    autor       VARCHAR(100) NOT NULL,
    categoria   VARCHAR(50)  NULL,
    ejemplares  INT NOT NULL DEFAULT 1,
    imagen      VARCHAR(100) NOT NULL DEFAULT 'default.png'
);

-- ---------- PRÉSTAMOS ----------
CREATE TABLE IF NOT EXISTS prestamos (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    socio_id          INT  NOT NULL,
    libro_id          INT  NOT NULL,
    fecha_prestamo    DATE NOT NULL,
    fecha_limite      DATE NOT NULL,
    fecha_devolucion  DATE NULL,            -- NULL = todavía no devuelto
    FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE RESTRICT,
    FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE RESTRICT
);

-- =====================================================================
--  DATOS DE EJEMPLO
-- =====================================================================

-- Contraseñas: admin123 y socio123
INSERT INTO usuarios (rol, email, pass) VALUES
('admin', 'admin@biblioteca.com', '$2y$10$dn/kb6Lk2jVpYS1rgSVjSe.RYf7TBZdNb9/Ffz5uq1C7sci7Qk1ce'),
('socio', 'socio@biblioteca.com', '$2y$10$A3IkphAkjzxSq/ubkgnUtudItqnp3gYoRHZC2TvyONLddJlLJP7jC');

INSERT INTO socios (usuario_id, nombre, direccion, telefono) VALUES
(2, 'Socio de Prueba', 'Tel. Aviv 123', '099123456');

INSERT INTO libros (titulo, autor, categoria, ejemplares, imagen) VALUES
('Cien años de soledad',  'Gabriel García Márquez', 'Novela',    2, 'default.png'),
('1984',                  'George Orwell',          'Distopía',  3, 'default.png'),
('El nombre del viento',  'Patrick Rothfuss',       'Fantasía',  1, 'default.png');
-- 👉 El Arquitecto de Datos agrega al menos 7 libros más y 4 socios más (cada uno con su usuario).

INSERT INTO prestamos (socio_id, libro_id, fecha_prestamo, fecha_limite, fecha_devolucion) VALUES
(1, 1, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 6 DAY, NULL),                          -- vencido
(1, 2, CURDATE() - INTERVAL 3 DAY,  CURDATE() + INTERVAL 11 DAY, NULL),                         -- activo
(1, 3, CURDATE() - INTERVAL 30 DAY, CURDATE() - INTERVAL 16 DAY, CURDATE() - INTERVAL 18 DAY);  -- devuelto