<?php

require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../conexion.php';

function listarSocio()
{
    global $pdo;
    $libros = Libro::listar($pdo);

    require __DIR__ . '/../views/libros/listar.php';
}

function formCrearSocio()
{
    require __DIR__ . '/../views/libros/form.php';
}

function crearSocio()
{
    global $pdo;

    $usuario_id  = trim($_POST['usuario_id'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $direccion  = trim($_POST['direccion'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');

    if ($usuario_id === '' || $nombre === '' || $direccion === '' || $telefono === '') {
        $error = "Se deben de Completar todos los espacios.";
        require __DIR__ . '/../views/libros/form.php';
        return;
    }
}

function eliminarSocio()
{
    global $pdo;
    Socio::eliminar($pdo, $_GET['id'] ?? 0);
    header('Location: index.php?a=listar');
    exit;
}
