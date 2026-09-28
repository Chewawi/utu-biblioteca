<?php
require __DIR__ . '/../models/Libro.php';
require __DIR__ . '/../conexion.php'; // expone $pdo

function listarLibros()
{
    global $pdo;
    $libros = Libro::listar($pdo);

    require __DIR__ . '/../views/libros/listar.php';
}

function crearLibro()
{
    global $pdo;

    $titulo = trim($_POST['titulo'] ?? '');
    $autor  = trim($_POST['autor'] ?? '');

    if ($titulo === '' || $autor === '') {
        $error = "Título y autor son obligatorios";
        require __DIR__ . '/../views/libros/form.php';
        return;
    }

    $imagen = 'default.png';
    if (!empty($_FILES['imagen']['name'])) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $imagen = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['imagen']['tmp_name'], __DIR__ . '/../uploads/' . $imagen);
    }

    Libro::crear($pdo, $titulo, $autor, $imagen);
    header('Location: index.php?a=listar');
}

function formEditarLibro()
{
    global $pdo;
    $libro = Libro::buscarPorId($pdo, $_GET['id']);
    require __DIR__ . '/../views/libros/form.php';
}

function eliminarLibro()
{
    global $pdo;
    Libro::eliminar($pdo, $_GET['id']);
    header('Location: index.php?a=listar');
}
