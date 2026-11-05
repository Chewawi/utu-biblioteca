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

// Largo máximo de la descripción (la columna TEXT admite más, pero así queda legible).
const MAX_DESCRIPCION = 5000;

/** Los campos del formulario de ítems, sin espacios de más. El ISBN, normalizado. */
function leerFormItem() {
    $datos = [
        'tipo'        => trim($_POST['tipo'] ?? ''),
        'titulo'      => trim($_POST['titulo'] ?? ''),
        'autor'       => trim($_POST['autor'] ?? ''),
        'categoria'   => trim($_POST['categoria'] ?? ''),
        'isbn'        => Item::normalizarIsbn(trim($_POST['isbn'] ?? '')),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
        'stock'       => trim($_POST['stock'] ?? ''),
    ];
    // Solo los libros tienen ISBN: si es otro tipo, se descarta.
    if ($datos['tipo'] !== 'libro') {
        $datos['isbn'] = '';
    }
    return $datos;
}

/**
 * Revisa los campos del formulario ($datos, de leerFormItem()). Devuelve el
 * mensaje de error, o '' si está todo bien. $id es el ítem que se edita (null
 * al crear). Los largos máximos son los de las columnas en database.sql.
 */
function validarItem($pdo, $datos, $id = null) {
    if (!in_array($datos['tipo'], Item::TIPOS)) {
        return 'Elegí un tipo válido.';
    }
    if ($datos['titulo'] === '' || $datos['autor'] === '') {
        return 'Título y autor son obligatorios.';
    }
    if (mb_strlen($datos['titulo']) > 150 || mb_strlen($datos['autor']) > 100 || mb_strlen($datos['categoria']) > 50) {
        return 'El título admite hasta 150 caracteres, el autor 100 y la categoría 50.';
    }
    if ($datos['isbn'] !== '' && !Item::isbnValido($datos['isbn'])) {
        return 'El ISBN no es válido: tiene que tener 10 o 13 dígitos (con o sin guiones) y el último tiene que coincidir. Revisá que esté bien copiado.';
    }
    if ($datos['isbn'] !== '') {
        $otro = Item::tituloConIsbn($pdo, $datos['isbn'], $id);
        if ($otro !== null) {
            return 'Ese ISBN ya lo tiene «' . $otro . '».';
        }
    }
    if (mb_strlen($datos['descripcion']) > MAX_DESCRIPCION) {
        return 'La descripción admite hasta ' . MAX_DESCRIPCION . ' caracteres.';
    }
    // ctype_digit: solo dígitos, así descartamos negativos, decimales y texto.
    if (!ctype_digit($datos['stock']) || $datos['stock'] < 1 || $datos['stock'] > 9999) {
        return 'El stock tiene que ser un número entero entre 1 y 9999.';
    }
    return '';
}

/** true si el formulario se mandó con el botón "Traer portada" (que llena el campo oculto traer_portada). */
function pidioTraerPortada() {
    return ($_POST['traer_portada'] ?? '') === '1';
}

/**
 * La portada que se guarda con el formulario: la de Open Library si apretaron
 * "Traer portada", la que subieron si eligieron un archivo, o $actual si no.
 * Devuelve ['imagen' => nombre del archivo, 'error' => '']; si algo falla,
 * 'error' dice por qué y no hay que guardar nada.
 */
function portadaDelFormulario($isbn, $actual) {
    if (pidioTraerPortada()) {
        $portada = $isbn === ''
            ? ['imagen' => null, 'error' => 'Para traer la portada, cargá el ISBN del libro.']
            : traerPortada($isbn);
        if ($portada['error'] !== '') {
            $portada['error'] .= ' Todavía no se guardó nada: podés subir la portada a mano o apretar Guardar para dejarla como estaba.';
        }
        return $portada;
    }
    $imagen = guardarImagen($_FILES['imagen'] ?? [], $actual);
    if ($imagen === null) {
        return ['imagen' => null, 'error' => 'La imagen tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.'];
    }
    return ['imagen' => $imagen, 'error' => ''];
}

/** Adónde ir después de guardar: con "Traer portada", de vuelta al formulario para ver la portada nueva; si no, al catálogo. */
function urlDespuesDeGuardar($id) {
    return pidioTraerPortada() ? 'index.php?accion=formEditarItem&id=' . $id : 'index.php?accion=listarItems';
}

/** '' -> null, para guardar "sin ISBN" o "sin descripción" como NULL en la base. */
function nuloSiVacio($texto) {
    return $texto === '' ? null : $texto;
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

    // Para el botón "Reservar" de cada tarjeta.
    [$socio, $prestadosDelSocio, $reservasDelSocio] = socioYSusReservas($pdo);
    require __DIR__ . '/../views/items/listar.php';
}

/**
 * Para el botón "Reservar": el socio logueado (null si no hay uno), los ids de
 * los ítems que tiene prestados y sus reservas activas (por id de ítem).
 */
function socioYSusReservas($pdo) {
    $socio = null;
    $prestados = [];
    $reservas = [];
    if (isset($_SESSION['usuario']) && !esAdmin()) {
        $socio = Socio::buscarPorUsuarioId($pdo, $_SESSION['usuario']['id']);
    }
    if ($socio) {
        $prestados = Prestamo::itemsPrestadosA($pdo, $socio->getId());
        $reservas = Reserva::activasDelSocio($pdo, $socio->getId());
    }
    return [$socio, $prestados, $reservas];
}

/** Página de un ítem, con su portada grande y su descripción. Pública, como el catálogo. */
function verItem() {
    global $pdo;
    $item = Item::buscarPorId($pdo, $_GET['id'] ?? '');
    if (!$item) {
        flash('danger', 'Ítem no encontrado.');
        header('Location: index.php?accion=listarItems');
        exit;
    }
    [$socio, $prestadosDelSocio, $reservasDelSocio] = socioYSusReservas($pdo);
    require __DIR__ . '/../views/items/ver.php';
}

function formCrearItem() {
    requerirAdmin();
    mostrarFormItem();
}

function crearItem() {
    global $pdo;
    requerirAdmin();

    $d = leerFormItem();
    $error = validarItem($pdo, $d);
    if ($error !== '') {
        mostrarFormItem(null, $error);
        return;
    }

    $portada = portadaDelFormulario($d['isbn'], PORTADA_POR_DEFECTO);
    if ($portada['error'] !== '') {
        mostrarFormItem(null, $portada['error']);
        return;
    }

    $categoria = Item::unificarCategoria($pdo, $d['categoria']);
    $id = Item::crear($pdo, $d['tipo'], $d['titulo'], $d['autor'], $categoria, nuloSiVacio($d['isbn']), nuloSiVacio($d['descripcion']), $d['stock'], $portada['imagen']);
    flash('success', 'Ítem creado correctamente.' . (pidioTraerPortada() ? ' La portada es la de Open Library.' : ''));
    header('Location: ' . urlDespuesDeGuardar($id));
    exit;
}

function formEditarItem() {
    global $pdo;
    requerirAdmin();
    $item = Item::buscarPorId($pdo, $_GET['id'] ?? '');
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

    $id = $_POST['id'] ?? '';
    $item = Item::buscarPorId($pdo, $id);
    if (!$item) {
        flash('danger', 'Ítem no encontrado.');
        header('Location: index.php?accion=listarItems');
        exit;
    }

    $d = leerFormItem();
    $error = validarItem($pdo, $d, $id);
    $enUso = $item->getPrestados() + $item->getApartados();
    if ($error === '' && $d['stock'] < $enUso) {
        $error = 'Hay ' . $enUso . ' ejemplares prestados o apartados por reservas: el stock no puede ser menor.';
    }
    if ($error !== '') {
        mostrarFormItem($item, $error);
        return;
    }

    // Si no piden una portada nueva, conservamos la que ya tenía.
    $portada = portadaDelFormulario($d['isbn'], $item->getImagen());
    if ($portada['error'] !== '') {
        mostrarFormItem($item, $portada['error']);
        return;
    }
    $imagen = $portada['imagen'];

    $categoria = Item::unificarCategoria($pdo, $d['categoria'], $id);

    Item::actualizar($pdo, $id, $d['tipo'], $d['titulo'], $d['autor'], $categoria, nuloSiVacio($d['isbn']), nuloSiVacio($d['descripcion']), $d['stock'], $imagen);
    if ($imagen !== $item->getImagen()) {
        borrarImagen($item->getImagen()); // la portada vieja ya no la usa nadie
    }
    // Si se sumaron ejemplares, pueden ser para la lista de espera.
    $mensaje = 'Ítem actualizado correctamente.' . (pidioTraerPortada() ? ' La portada es la de Open Library.' : '');
    // Si se sumaron ejemplares, pueden ser para la lista de espera.
    flash('success', $mensaje . mensajeReparto(repartirEjemplares($pdo, $id)));
    header('Location: ' . urlDespuesDeGuardar($id));
    exit;
}

function eliminarItem() {
    global $pdo;
    requerirAdmin();

    $id = $_POST['id'] ?? '';
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
