-- Importar este archivo desde phpMyAdmin (pestaña "Importar") o ejecutarlo
-- desde la pestaña SQL para crear la base y la tabla de ejemplo.

-- CREO UNA BASE DE DATOS SI NO EXISTE LLAMADA "biblioteca" 
CREATE DATABASE IF NOT EXISTS biblioteca CHARACTER SET utf8mb4;
USE biblioteca;

-- CREO LA TABLA "libros" 
CREATE TABLE IF NOT EXISTS libros (
    -- ESTE PROPIEDAD TENDRIA DE VALOR POR EJEMPLO...

    -- ID: 1
    id      INT AUTO_INCREMENT PRIMARY KEY,

    -- TITULO: Harry Potter
    titulo  VARCHAR(150) NOT NULL,

    -- AUTOR: J.K. Rowling
    autor   VARCHAR(100) NOT NULL,

    -- IMAGEN: ../uploads/default.png
    imagen  VARCHAR(100) NOT NULL
);

-- Datos de ejemplo, opcional
INSERT INTO libros (titulo, autor, imagen) VALUES
('Cien años de soledad', 'Gabriel García Márquez', 'default.png'),
('1984', 'George Orwell', 'default.png'),
('El nombre del viento', 'Patrick Rothfuss', 'default.png');

CREATE TABLE IF NOT EXISTS usuarios (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    rol     VARCHAR(50) NOT NULL,
    email   VARCHAR(100) NOT NULL UNIQUE,
    pass    VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS socio (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    nombre      VARCHAR(100) NOT NULL,
    direccion   VARCHAR(255) NOT NULL,
    telefono    int(20) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
