<?php
require __DIR__ . '/controllers/LibroController.php';

$accion = $_GET['accion'] ?? 'listar';

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
    default:
        http_response_code(404);
        echo "Página no encontrada";
}