<?php
require_once __DIR__ . '/../models/Libro.php';

// ============================================================
//  Controller de Libros
//  Funciones que se llaman desde index.php según la "accion" de
//  la URL. Reciben el pedido, validan, le piden al modelo que
//  guarde/lea/borre, y muestran una vista o redirigen.
// ============================================================

/** Formatos y tamaño de portada permitidos. */
function extensionValida($nombreArchivo) {
    $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
    return in_array($ext, $permitidas) ? $ext : false;
}

/** Guarda la portada subida y devuelve el nombre final del archivo. */
function guardarImagenLibro($archivo) {
    if (empty($archivo['name'])) {
        return 'default.png'; // no subieron nada, usamos la genérica
    }

    $ext = extensionValida($archivo['name']);
    if (!$ext) {
        return null; // formato no permitido
    }
    if ($archivo['size'] > 2 * 1024 * 1024) {
        return null; // pesa más de 2 MB
    }

    $nombre = uniqid() . '.' . $ext;
    move_uploaded_file($archivo['tmp_name'], __DIR__ . '/../uploads/' . $nombre);
    return $nombre;
}

/** Catálogo de libros. Página pública, no requiere login. */
function listarLibros() {
    global $pdo;
    $q = trim($_GET['q'] ?? '');
    $libros = Libro::listar($pdo, $q);
    require __DIR__ . '/../views/libros/listar.php';
}

function formCrearLibro() {
    requerirAdmin();
    require __DIR__ . '/../views/libros/form.php';
}

function crearLibro() {
    global $pdo;
    requerirAdmin();

    $titulo    = trim($_POST['titulo'] ?? '');
    $autor     = trim($_POST['autor'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');

    if ($titulo === '' || $autor === '') {
        $error = 'Título y autor son obligatorios.';
        require __DIR__ . '/../views/libros/form.php';
        return;
    }

    $imagen = guardarImagenLibro($_FILES['imagen'] ?? []);
    if ($imagen === null) {
        $error = 'La imagen tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.';
        require __DIR__ . '/../views/libros/form.php';
        return;
    }

    Libro::crear($pdo, $titulo, $autor, $categoria, $imagen);
    flash('success', 'Libro creado correctamente.');
    header('Location: index.php?accion=listarLibros');
    exit;
}

function formEditarLibro() {
    global $pdo;
    requerirAdmin();
    $libro = Libro::buscarPorId($pdo, $_GET['id']);
    if (!$libro) {
        flash('danger', 'Libro no encontrado.');
        header('Location: index.php?accion=listarLibros');
        exit;
    }
    require __DIR__ . '/../views/libros/form.php';
}

function editarLibro() {
    global $pdo;
    requerirAdmin();

    $id = $_POST['id'];
    $libro = Libro::buscarPorId($pdo, $id);
    if (!$libro) {
        flash('danger', 'Libro no encontrado.');
        header('Location: index.php?accion=listarLibros');
        exit;
    }

    $titulo    = trim($_POST['titulo'] ?? '');
    $autor     = trim($_POST['autor'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');

    if ($titulo === '' || $autor === '') {
        $error = 'Título y autor son obligatorios.';
        require __DIR__ . '/../views/libros/form.php';
        return;
    }

    // Si no suben una portada nueva, conservamos la que ya tenía.
    $imagen = $libro->getImagen();
    if (!empty($_FILES['imagen']['name'])) {
        $imagenNueva = guardarImagenLibro($_FILES['imagen']);
        if ($imagenNueva === null) {
            $error = 'La imagen tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.';
            require __DIR__ . '/../views/libros/form.php';
            return;
        }
        $imagen = $imagenNueva;
    }

    Libro::actualizar($pdo, $id, $titulo, $autor, $categoria, $imagen);
    flash('success', 'Libro actualizado correctamente.');
    header('Location: index.php?accion=listarLibros');
    exit;
}

function eliminarLibro() {
    global $pdo;
    requerirAdmin();

    $id = $_POST['id'];

    if (Libro::tienePrestamos($pdo, $id)) {
        flash('danger', 'No se puede eliminar: el libro está prestado ahora mismo.');
        header('Location: index.php?accion=listarLibros');
        exit;
    }

    Libro::eliminar($pdo, $id);
    flash('success', 'Libro eliminado.');
    header('Location: index.php?accion=listarLibros');
    exit;
}
