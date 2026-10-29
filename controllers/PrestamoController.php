<?php
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Item.php';

// ============================================================
//  Controller de Préstamos
//  El admin ve todos los préstamos y puede crear/devolver.
//  Un socio logueado solo ve los suyos.
// ============================================================

/**
 * Lee los filtros del listado desde $datos ($_GET o $_POST) y descarta los
 * valores inválidos. Devuelve siempre las mismas claves; '' = sin filtrar.
 */
function leerFiltrosPrestamos($datos) {
    $filtros = [
        'item_id'  => trim($datos['item_id'] ?? ''),
        'socio_id' => trim($datos['socio_id'] ?? ''),
        'estado'   => trim($datos['estado'] ?? ''),
        'desde'    => trim($datos['desde'] ?? ''),
        'hasta'    => trim($datos['hasta'] ?? ''),
        'q'        => trim($datos['q'] ?? ''),
    ];

    if (!ctype_digit($filtros['item_id'])) {
        $filtros['item_id'] = '';
    }
    if (!ctype_digit($filtros['socio_id'])) {
        $filtros['socio_id'] = '';
    }
    if (!in_array($filtros['estado'], Prestamo::FILTROS_ESTADO)) {
        $filtros['estado'] = '';
    }
    if (!fechaValida($filtros['desde'])) {
        $filtros['desde'] = '';
    }
    if (!fechaValida($filtros['hasta'])) {
        $filtros['hasta'] = '';
    }
    return $filtros;
}

function listarPrestamos() {
    global $pdo;
    requerirLogin();

    $filtros = leerFiltrosPrestamos($_GET);
    $paginacion = ['pagina' => $_GET['p'] ?? 1, 'por_pagina' => 10];
    $items = Item::listar($pdo)['filas']; // todos, para el filtro por ítem

    if (esAdmin()) {
        $socios = Socio::listar($pdo)['filas']; // todos, para el filtro por socio
        $resultado = Prestamo::listar($pdo, array_merge($filtros, $paginacion));
        $titulo = 'Préstamos';
    } else {
        // Un socio solo ve lo suyo: el filtro por socio no lo elige él, lo fijamos acá.
        $filtros['socio_id'] = '';
        $socio = Socio::buscarPorUsuarioId($pdo, $_SESSION['usuario']['id']);
        $resultado = ['filas' => [], 'total' => 0, 'pagina' => 1, 'paginas' => 1];
        if ($socio) {
            $filtrosDelSocio = $filtros;
            $filtrosDelSocio['socio_id'] = $socio->getId();
            $resultado = Prestamo::listar($pdo, array_merge($filtrosDelSocio, $paginacion));
        }
        $titulo = 'Mis préstamos';
    }
    $prestamos = $resultado['filas'];

    require __DIR__ . '/../views/prestamos/listar.php';
}

function formCrearPrestamo() {
    global $pdo;
    requerirAdmin();
    $socios = Socio::listar($pdo)['filas'];
    $items = Item::listar($pdo)['filas'];
    require __DIR__ . '/../views/prestamos/form.php';
}

function crearPrestamo() {
    global $pdo;
    requerirAdmin();

    $socioId = $_POST['socio_id'] ?? '';
    $itemId  = $_POST['item_id'] ?? '';
    $fecha   = $_POST['fecha_prestamo'] ?? '';
    $fechaLimite = $_POST['fecha_limite'] ?? '';

    $error = '';
    if ($socioId === '' || $itemId === '' || $fecha === '' || $fechaLimite === '') {
        $error = 'Elegí un socio, un ítem, la fecha del préstamo y la fecha límite.';
    } elseif (!fechaValida($fecha) || $fecha > date('Y-m-d')) {
        // Las fechas AAAA-MM-DD se pueden comparar como texto.
        $error = 'La fecha del préstamo tiene que ser válida y no posterior a hoy.';
    } elseif (!fechaValida($fechaLimite) || $fechaLimite <= $fecha) {
        $error = 'La fecha límite tiene que ser posterior a la fecha del préstamo.';
    } elseif (!Socio::buscarPorId($pdo, $socioId)) {
        $error = 'Elegí un socio de la lista.';
    } else {
        $item = Item::buscarPorId($pdo, $itemId);
        if (!$item || $item->getDisponibles() < 1) {
            $error = 'No quedan ejemplares disponibles de ese ítem.';
        }
    }

    if ($error !== '') {
        $socios = Socio::listar($pdo)['filas'];
        $items = Item::listar($pdo)['filas'];
        require __DIR__ . '/../views/prestamos/form.php';
        return;
    }

    Prestamo::crear($pdo, $socioId, $itemId, $fecha, $fechaLimite);
    flash('success', 'Préstamo registrado.');
    header('Location: index.php?accion=listarPrestamos');
    exit;
}

function devolverPrestamo() {
    global $pdo;
    requerirAdmin();

    if (Prestamo::devolver($pdo, $_POST['id'], date('Y-m-d'))) {
        flash('success', 'Devolución registrada.');
    } else {
        flash('warning', 'Ese préstamo ya estaba devuelto.');
    }

    // Volvemos a la lista con los mismos filtros y la misma página que tenía
    // (vienen en campos ocultos). array_filter saca los vacíos, para que la URL quede corta.
    // Si esa página quedó vacía, el listado la corrige a la última que tenga resultados.
    $volverA = array_filter(leerFiltrosPrestamos($_POST));
    if (ctype_digit($_POST['p'] ?? '')) {
        $volverA['p'] = $_POST['p'];
    }
    header('Location: index.php?' . http_build_query(array_merge(['accion' => 'listarPrestamos'], $volverA)));
    exit;
}
