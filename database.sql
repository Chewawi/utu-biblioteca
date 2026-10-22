-- =====================================================================
--  Bibliodeko — esquema y datos de ejemplo
--
--  Cómo importar: phpMyAdmin > pestaña "Importar" > elegir este archivo.
--  Si ya existía una base "biblioteca" de antes: DROP DATABASE biblioteca;
-- =====================================================================
CREATE DATABASE IF NOT EXISTS biblioteca CHARACTER SET utf8mb4;
USE biblioteca;

-- Cuentas de acceso. rol: 'admin' gestiona todo, 'socio' solo mira el
-- catálogo y sus préstamos. "pass" guarda un hash, nunca texto plano.
CREATE TABLE IF NOT EXISTS usuarios (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    rol     ENUM('admin', 'socio') NOT NULL DEFAULT 'socio',
    email   VARCHAR(100) NOT NULL UNIQUE,
    pass    VARCHAR(255) NOT NULL
);

-- Datos personales del socio, enganchado a una cuenta de usuarios.
CREATE TABLE IF NOT EXISTS socios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL UNIQUE,
    nombre      VARCHAR(100) NOT NULL,
    direccion   VARCHAR(255) NOT NULL,
    telefono    VARCHAR(20)  NOT NULL,
    foto        VARCHAR(100) NOT NULL DEFAULT 'default.png',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Todo lo que se puede pedir prestado. "stock" es cuántos ejemplares
-- tiene la biblioteca de ese item.
CREATE TABLE IF NOT EXISTS items (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    tipo      ENUM('libro', 'juego', 'proyecto') NOT NULL DEFAULT 'libro',
    titulo    VARCHAR(150) NOT NULL,
    autor     VARCHAR(100) NOT NULL,
    categoria VARCHAR(50)  NULL,
    stock     INT UNSIGNED NOT NULL DEFAULT 1,
    imagen    VARCHAR(100) NOT NULL DEFAULT 'default.png'
);

-- Un préstamo conecta un socio con un item. "estado" dice si sigue
-- prestado o ya se devolvió. fecha_devolucion queda vacía mientras está
-- 'activo' y se completa al pasar a 'devuelto'. La fila no se borra:
-- queda como historial.
CREATE TABLE IF NOT EXISTS prestamos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    socio_id        INT  NOT NULL,
    item_id         INT  NOT NULL,
    fecha_prestamo  DATE NOT NULL,
    fecha_devolucion DATE NULL,
    estado          ENUM('activo', 'devuelto') NOT NULL DEFAULT 'activo',
    FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- =====================================================================
--  DATOS DE EJEMPLO
-- =====================================================================

-- Contraseñas de prueba: admin123 y socio123
INSERT INTO usuarios (rol, email, pass) VALUES
('admin', 'admin@biblioteca.com',  '$2y$10$dn/kb6Lk2jVpYS1rgSVjSe.RYf7TBZdNb9/Ffz5uq1C7sci7Qk1ce'),
('socio', 'socio@biblioteca.com',  '$2y$10$A3IkphAkjzxSq/ubkgnUtudItqnp3gYoRHZC2TvyONLddJlLJP7jC'),
('socio', 'mariana@biblioteca.com','$2y$10$A3IkphAkjzxSq/ubkgnUtudItqnp3gYoRHZC2TvyONLddJlLJP7jC');

INSERT INTO socios (usuario_id, nombre, direccion, telefono) VALUES
(2, 'Socio de Prueba',   'Av. Principal 123', '099123456'),
(3, 'Mariana Fernández', 'Bulevar Artigas 456', '098765432');

INSERT INTO items (tipo, titulo, autor, categoria, stock, imagen) VALUES
('libro',    'Cien años de soledad',          'Gabriel García Márquez',   'Novela',     2, 'default.png'),
('libro',    '1984',                          'George Orwell',            'Distopía',   1, 'default.png'),
('libro',    'El nombre del viento',          'Patrick Rothfuss',         'Fantasía',   1, 'default.png'),
('libro',    'Rayuela',                       'Julio Cortázar',           'Novela',     1, 'default.png'),
('libro',    'Fahrenheit 451',                'Ray Bradbury',             'Distopía',   2, 'default.png'),
('libro',    'El principito',                 'Antoine de Saint-Exupéry', 'Infantil',   3, 'default.png'),
('libro',    'Sapiens',                       'Yuval Noah Harari',        'Ensayo',     1, 'default.png'),
('libro',    'El señor de los anillos',       'J. R. R. Tolkien',         'Fantasía',   1, 'default.png'),
('juego',    'Catan',                         'Klaus Teuber',             'Estrategia', 2, 'default.png'),
('juego',    'Dixit',                         'Jean-Louis Roubira',       'Familiar',   1, 'default.png'),
('proyecto', 'Estación meteorológica Arduino', 'Grupo 3.º B',              'Robótica',   1, 'default.png'),
('proyecto', 'Huerta con riego automático',   'Grupo 2.º A',              'Electrónica', 1, 'default.png');

INSERT INTO prestamos (socio_id, item_id, fecha_prestamo, fecha_devolucion, estado) VALUES
(1, 1,  CURDATE() - INTERVAL 5 DAY,  NULL,                         'activo'),
(2, 3,  CURDATE() - INTERVAL 1 DAY,  NULL,                         'activo'),
(2, 9,  CURDATE() - INTERVAL 3 DAY,  NULL,                         'activo'),
(1, 9,  CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 12 DAY,  'devuelto'),
(2, 2,  CURDATE() - INTERVAL 30 DAY, CURDATE() - INTERVAL 16 DAY,  'devuelto'),
(1, 6,  CURDATE() - INTERVAL 45 DAY, CURDATE() - INTERVAL 40 DAY,  'devuelto');
