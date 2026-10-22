<?php
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Item.php';

// ============================================================
//  Controller de Préstamos
//  El admin ve todos los préstamos y puede crear/devolver.
//  Un socio logueado solo ve los suyos.
// ============================================================

/** true si $fecha es una fecha real con formato AAAA-MM-DD. */
function fechaValida($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

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
    if (!in_array($filtros['estado'], Prestamo::ESTADOS)) {
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
    $items = Item::listar($pdo); // para el filtro por ítem

    if (esAdmin()) {
        $socios = Socio::listar($pdo); // para el filtro por socio
        $prestamos = Prestamo::listar($pdo, $filtros);
        $titulo = 'Préstamos';
    } else {
        // Un socio solo ve lo suyo: el filtro por socio no lo elige él, lo fijamos acá.
        $filtros['socio_id'] = '';
        $socio = Socio::buscarPorUsuarioId($pdo, $_SESSION['usuario']['id']);
        $prestamos = [];
        if ($socio) {
            $filtrosDelSocio = $filtros;
            $filtrosDelSocio['socio_id'] = $socio->getId();
            $prestamos = Prestamo::listar($pdo, $filtrosDelSocio);
        }
        $titulo = 'Mis préstamos';
    }

    require __DIR__ . '/../views/prestamos/listar.php';
}

function formCrearPrestamo() {
    global $pdo;
    requerirAdmin();
    $socios = Socio::listar($pdo);
    $items = Item::listar($pdo);
    require __DIR__ . '/../views/prestamos/form.php';
}

function crearPrestamo() {
    global $pdo;
    requerirAdmin();

    $socioId = $_POST['socio_id'] ?? '';
    $itemId  = $_POST['item_id'] ?? '';
    $fecha   = $_POST['fecha_prestamo'] ?? '';

    $error = '';
    if ($socioId === '' || $itemId === '' || $fecha === '') {
        $error = 'Elegí un socio, un ítem, y la fecha del préstamo.';
    } else {
        $item = Item::buscarPorId($pdo, $itemId);
        if (!$item || $item->getDisponibles() < 1) {
            $error = 'No quedan ejemplares disponibles de ese ítem.';
        }
    }

    if ($error !== '') {
        $socios = Socio::listar($pdo);
        $items = Item::listar($pdo);
        require __DIR__ . '/../views/prestamos/form.php';
        return;
    }

    Prestamo::crear($pdo, $socioId, $itemId, $fecha);
    flash('success', 'Préstamo registrado.');
    header('Location: index.php?accion=listarPrestamos');
    exit;
}

function devolverPrestamo() {
    global $pdo;
    requerirAdmin();

    if (Prestamo::devolver($pdo, $_POST['id'])) {
        flash('success', 'Devolución registrada.');
    } else {
        flash('warning', 'Ese préstamo ya estaba devuelto.');
    }

    // Volvemos a la lista con los mismos filtros que tenía (vienen en campos ocultos).
    // array_filter saca los vacíos, para que la URL quede corta.
    $filtros = array_filter(leerFiltrosPrestamos($_POST));
    header('Location: index.php?' . http_build_query(array_merge(['accion' => 'listarPrestamos'], $filtros)));
    exit;
}
