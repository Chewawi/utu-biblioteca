<?php
session_start();

require __DIR__ . '/controllers/LibroController.php';
require __DIR__ . '/controllers/UsuarioController.php';

$accion = $_GET['a'] ?? 'listar';

$accionesPublicas = ['listar', 'login', 'doLogin'];

if (!in_array($accion, $accionesPublicas) && !isset($_SESSION['usuario_id'])) {
    header('Location: index.php?a=login');
    exit;
}

switch ($accion) {
    case 'listar':
        listarLibros();
        break;
    case 'formCrear':
        require __DIR__ . '/views/libros/form.php';
        break;
    case 'crear':
        crearLibro();
        break;
    case 'formEditar':
        formEditarLibro();
        break;
    case 'eliminar':
        eliminarLibro();
        break;
    case 'login':
        require __DIR__ . '/views/usuarios/login.php';
        break;
    case 'formLogin':
        formLogin();
        break;
    default:
        http_response_code(404);
        echo "Página no encontrada";
}
