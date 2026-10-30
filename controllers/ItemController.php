<?php
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Reserva.php';

// ============================================================
//  Controller de Items
//  Funciones que se llaman desde index.php según la "accion" de
//  la URL. Reciben el pedido, validan, le piden al modelo que
//  guarde/lea/borre, y muestran una vista o redirigen.
// ============================================================

/**
 * Revisa los campos del formulario. Devuelve el mensaje de error, o '' si está todo bien.
 * Los largos máximos son los de las columnas en database.sql.
 */
function validarItem($tipo, $titulo, $autor, $categoria, $stock) {
    if (!in_array($tipo, Item::TIPOS)) {
        return 'Elegí un tipo válido.';
    }
    if ($titulo === '' || $autor === '') {
        return 'Título y autor son obligatorios.';
    }
    if (mb_strlen($titulo) > 150 || mb_strlen($autor) > 100 || mb_strlen($categoria) > 50) {
        return 'El título admite hasta 150 caracteres, el autor 100 y la categoría 50.';
    }
    // ctype_digit: solo dígitos, así descartamos negativos, decimales y texto.
    if (!ctype_digit($stock) || $stock < 1 || $stock > 9999) {
        return 'El stock tiene que ser un número entero entre 1 y 9999.';
    }
    return '';
}

/**
 * Muestra el formulario de alta o edición. $item es el que se edita (null al
 * crear) y $error el mensaje a mostrar, si hubo uno.
 */
function mostrarFormItem($item = null, $error = null) {
    global $pdo;
    $categorias = Item::categorias($pdo); // sugerencias para el campo categoría
    require __DIR__ . '/../views/items/form.php';
}

/**
 * Catálogo de items. Página pública, no requiere login.
 * Tiene una pestaña por tipo (?tipo=libro, ...) y "Todos" (sin tipo).
 * Se puede filtrar además por categoría (?categoria=Novela) y buscar (?q=...).
 */
function listarItems() {
    global $pdo;
    $tipo = $_GET['tipo'] ?? '';
    if (!in_array($tipo, Item::TIPOS)) {
        $tipo = '';
    }
    $categorias = Item::categorias($pdo); // para el selector de género
    $categoria = $_GET['categoria'] ?? '';
    if (!in_array($categoria, $categorias, true)) {
        $categoria = '';
    }
    $filtros = [
        'q'          => trim($_GET['q'] ?? ''),
        'tipo'       => $tipo,
        'categoria'  => $categoria,
        'pagina'     => $_GET['p'] ?? 1,
        'por_pagina' => 12, // 12 entra justo en la grilla de tarjetas (3 o 4 por fila)
    ];
    $resultado = Item::listar($pdo, $filtros);
    $items = $resultado['filas'];
    $conteo = Item::contarPorTipo($pdo, $filtros['q'], $categoria);

    // Para el botón "Reservar" de cada tarjeta: qué tiene prestado y reservado el socio logueado.
    $socio = null;
    $prestadosDelSocio = [];
    $reservasDelSocio = [];
    if (isset($_SESSION['usuario']) && !esAdmin()) {
        $socio = Socio::buscarPorUsuarioId($pdo, $_SESSION['usuario']['id']);
    }
    if ($socio) {
        $prestadosDelSocio = Prestamo::itemsPrestadosA($pdo, $socio->getId());
        $reservasDelSocio = Reserva::activasDelSocio($pdo, $socio->getId());
    }
    require __DIR__ . '/../views/items/listar.php';
}

function formCrearItem() {
    requerirAdmin();
    mostrarFormItem();
}

function crearItem() {
    global $pdo;
    requerirAdmin();

    $tipo      = trim($_POST['tipo'] ?? '');
    $titulo    = trim($_POST['titulo'] ?? '');
    $autor     = trim($_POST['autor'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $stock     = trim($_POST['stock'] ?? '');

    $error = validarItem($tipo, $titulo, $autor, $categoria, $stock);
    if ($error !== '') {
        mostrarFormItem(null, $error);
        return;
    }

    $imagen = guardarImagen($_FILES['imagen'] ?? [], PORTADA_POR_DEFECTO);
    if ($imagen === null) {
        mostrarFormItem(null, 'La imagen tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.');
        return;
    }

    $categoria = Item::unificarCategoria($pdo, $categoria);
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
    mostrarFormItem($item);
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

    $error = validarItem($tipo, $titulo, $autor, $categoria, $stock);
    $enUso = $item->getPrestados() + $item->getApartados();
    if ($error === '' && $stock < $enUso) {
        $error = 'Hay ' . $enUso . ' ejemplares prestados o apartados por reservas: el stock no puede ser menor.';
    }
    if ($error !== '') {
        mostrarFormItem($item, $error);
        return;
    }

    // Si no suben una portada nueva, conservamos la que ya tenía.
    $imagen = $item->getImagen();
    if (!empty($_FILES['imagen']['name'])) {
        $imagenNueva = guardarImagen($_FILES['imagen'], PORTADA_POR_DEFECTO);
        if ($imagenNueva === null) {
            mostrarFormItem($item, 'La imagen tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.');
            return;
        }
        $imagen = $imagenNueva;
    }

    $categoria = Item::unificarCategoria($pdo, $categoria, $id);

    Item::actualizar($pdo, $id, $tipo, $titulo, $autor, $categoria, $stock, $imagen);
    if ($imagen !== $item->getImagen()) {
        borrarImagen($item->getImagen()); // la portada vieja ya no la usa nadie
    }
    // Si se sumaron ejemplares, pueden ser para la lista de espera.
    flash('success', 'Ítem actualizado correctamente.' . mensajeReparto(repartirEjemplares($pdo, $id)));
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

    if (Item::tieneHistorial($pdo, $id)) {
        flash('danger', 'No se puede eliminar: el ítem tiene préstamos o reservas registrados y se perdería el historial.');
        header('Location: index.php?accion=listarItems');
        exit;
    }

    Item::eliminar($pdo, $id);
    borrarImagen($item->getImagen());
    flash('success', 'Ítem eliminado.');
    header('Location: index.php?accion=listarItems');
    exit;
}
