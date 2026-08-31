-- CREO UNA BASE DE DATOS SI NO EXISTE LLAMADA "biblioteca" 
CREATE DATABASE IF NOT EXISTS biblioteca;
USE biblioteca;

-- CREO LA TABLA "libros" 
CREATE TABLE libros (
    -- ESTE PROPIEDAD TENDRIA DE VALOR POR EJEMPLO...

    -- ID: 1
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- TITULO: Harry Potter
    titulo VARCHAR(150) NOT NULL,

    -- AUTOR: J.K. Rowling
    autor VARCHAR(100) NOT NULL,

    -- IMAGEN: ../uploads/default.png
    imagen VARCHAR(100) NOT NULL,
);
