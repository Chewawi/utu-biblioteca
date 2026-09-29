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
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS libros (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    titulo    VARCHAR(150) NOT NULL,
    autor     VARCHAR(100) NOT NULL,
    categoria VARCHAR(50)  NULL,
    imagen    VARCHAR(100) NOT NULL DEFAULT 'default.png'
);

-- Un préstamo conecta un socio con un libro. Cuando se devuelve el
-- libro, se borra la fila (no queda historial de préstamos pasados).
CREATE TABLE IF NOT EXISTS prestamos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    socio_id        INT  NOT NULL,
    libro_id        INT  NOT NULL,
    fecha_prestamo  DATE NOT NULL,
    FOREIGN KEY (socio_id) REFERENCES socios(id) ON DELETE CASCADE,
    FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE
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

INSERT INTO libros (titulo, autor, categoria, imagen) VALUES
('Cien años de soledad',        'Gabriel García Márquez',   'Novela',   'default.png'),
('1984',                        'George Orwell',            'Distopía', 'default.png'),
('El nombre del viento',        'Patrick Rothfuss',         'Fantasía', 'default.png'),
('Rayuela',                     'Julio Cortázar',           'Novela',   'default.png'),
('Fahrenheit 451',              'Ray Bradbury',             'Distopía', 'default.png'),
('El principito',               'Antoine de Saint-Exupéry', 'Infantil', 'default.png'),
('Sapiens',                     'Yuval Noah Harari',        'Ensayo',   'default.png'),
('El señor de los anillos',     'J. R. R. Tolkien',         'Fantasía', 'default.png');

INSERT INTO prestamos (socio_id, libro_id, fecha_prestamo) VALUES
(1, 1, CURDATE() - INTERVAL 5 DAY),
(2, 3, CURDATE() - INTERVAL 1 DAY);
