<?php
require_once __DIR__ . '/../models/Item.php';

// ============================================================
//  Controller de Items
//  Funciones que se llaman desde index.php según la "accion" de
//  la URL. Reciben el pedido, validan, le piden al modelo que
//  guarde/lea/borre, y muestran una vista o redirigen.
// ============================================================

/** Revisa los campos del formulario. Devuelve el mensaje de error, o '' si está todo bien. */
function validarItem($tipo, $titulo, $autor, $stock) {
    if (!in_array($tipo, Item::TIPOS)) {
        return 'Elegí un tipo válido.';
    }
    if ($titulo === '' || $autor === '') {
        return 'Título y autor son obligatorios.';
    }
    // ctype_digit: solo dígitos, así descartamos negativos, decimales y texto.
    if (!ctype_digit($stock) || $stock < 1) {
        return 'El stock tiene que ser un número entero mayor que 0.';
    }
    return '';
}

/** Catálogo de items. Página pública, no requiere login. */
function listarItems() {
    global $pdo;
    $q = trim($_GET['q'] ?? '');
    $items = Item::listar($pdo, $q);
    require __DIR__ . '/../views/items/listar.php';
}

function formCrearItem() {
    requerirAdmin();
    require __DIR__ . '/../views/items/form.php';
}

function crearItem() {
    global $pdo;
    requerirAdmin();

    $tipo      = trim($_POST['tipo'] ?? '');
    $titulo    = trim($_POST['titulo'] ?? '');
    $autor     = trim($_POST['autor'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $stock     = trim($_POST['stock'] ?? '');

    $error = validarItem($tipo, $titulo, $autor, $stock);
    if ($error !== '') {
        require __DIR__ . '/../views/items/form.php';
        return;
    }

    $imagen = guardarImagen($_FILES['imagen'] ?? [], PORTADA_POR_DEFECTO);
    if ($imagen === null) {
        $error = 'La imagen tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.';
        require __DIR__ . '/../views/items/form.php';
        return;
    }

    Item::crear($pdo, $tipo, $titulo, $autor, $categoria, $stock, $imagen);
    flash('success', 'Ítem creado correctamente.');
    header('Location: index.php?accion=listarItems');
    exit;
}

function formEditarItem() {
    global $pdo;
    requerirAdmin();
    $item = Item::buscarPorId($pdo, $_GET['id']);
    if (!$item) {
        flash('danger', 'Ítem no encontrado.');
        header('Location: index.php?accion=listarItems');
        exit;
    }
    require __DIR__ . '/../views/items/form.php';
}

function editarItem() {
    global $pdo;
    requerirAdmin();

    $id = $_POST['id'];
    $item = Item::buscarPorId($pdo, $id);
    if (!$item) {
        flash('danger', 'Ítem no encontrado.');
        header('Location: index.php?accion=listarItems');
        exit;
    }

    $tipo      = trim($_POST['tipo'] ?? '');
    $titulo    = trim($_POST['titulo'] ?? '');
    $autor     = trim($_POST['autor'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $stock     = trim($_POST['stock'] ?? '');

    $error = validarItem($tipo, $titulo, $autor, $stock);
    if ($error === '' && $stock < $item->getPrestados()) {
        $error = 'Hay ' . $item->getPrestados() . ' ejemplares prestados: el stock no puede ser menor.';
    }
    if ($error !== '') {
        require __DIR__ . '/../views/items/form.php';
        return;
    }

    // Si no suben una portada nueva, conservamos la que ya tenía.
    $imagen = $item->getImagen();
    if (!empty($_FILES['imagen']['name'])) {
        $imagenNueva = guardarImagen($_FILES['imagen'], PORTADA_POR_DEFECTO);
        if ($imagenNueva === null) {
            $error = 'La imagen tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.';
            require __DIR__ . '/../views/items/form.php';
            return;
        }
        $imagen = $imagenNueva;
    }

    Item::actualizar($pdo, $id, $tipo, $titulo, $autor, $categoria, $stock, $imagen);
    if ($imagen !== $item->getImagen()) {
        borrarImagen($item->getImagen()); // la portada vieja ya no la usa nadie
    }
    flash('success', 'Ítem actualizado correctamente.');
    header('Location: index.php?accion=listarItems');
    exit;
}

function eliminarItem() {
    global $pdo;
    requerirAdmin();

    $id = $_POST['id'];
    $item = Item::buscarPorId($pdo, $id);
    if (!$item) {
        flash('danger', 'Ítem no encontrado.');
        header('Location: index.php?accion=listarItems');
        exit;
    }

    if ($item->getPrestados() > 0) {
        flash('danger', 'No se puede eliminar: el ítem está prestado ahora mismo.');
        header('Location: index.php?accion=listarItems');
        exit;
    }

    Item::eliminar($pdo, $id);
    borrarImagen($item->getImagen());
    flash('success', 'Ítem eliminado.');
    header('Location: index.php?accion=listarItems');
    exit;
}
