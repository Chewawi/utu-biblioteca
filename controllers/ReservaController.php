<?php
require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Prestamo.php';

// ============================================================
//  Controller de Reservas
//  El socio reserva desde el catálogo. Si hay un ejemplar
//  disponible se lo apartamos; si no, queda en la lista de
//  espera del item hasta que se libere uno. En "Mis reservas"
//  ve las suyas y las puede cancelar. El admin ve todas.
// ============================================================

/**
 * Lee los filtros del listado desde $datos ($_GET o $_POST) y descarta los
 * valores inválidos. Devuelve siempre las mismas claves; '' = sin filtrar.
 */
function leerFiltrosReservas($datos) {
    $filtros = [
        'socio_id' => trim($datos['socio_id'] ?? ''),
        'item_id'  => trim($datos['item_id'] ?? ''),
        'estado'   => trim($datos['estado'] ?? ''),
        'q'        => trim($datos['q'] ?? ''),
    ];
    if (!ctype_digit($filtros['socio_id'])) {
        $filtros['socio_id'] = '';
    }
    if (!ctype_digit($filtros['item_id'])) {
        $filtros['item_id'] = '';
    }
    if (!in_array($filtros['estado'], Reserva::FILTROS_ESTADO)) {
        $filtros['estado'] = '';
    }
    return $filtros;
}

function listarReservas() {
    global $pdo;
    requerirLogin();

    $filtros = leerFiltrosReservas($_GET);
    $paginacion = ['pagina' => $_GET['p'] ?? 1, 'por_pagina' => 10];

    if (esAdmin()) {
        // Opciones de los desplegables de "Más filtros": [id => texto].
        $opcionesSocios = Socio::nombres($pdo);
        $opcionesItems = Item::titulos($pdo);
        $resultado = Reserva::listar($pdo, array_merge($filtros, $paginacion));
        $conteo = Reserva::contarPorEstado($pdo, $filtros);
        $titulo = 'Reservas';
    } else {
        // Un socio solo ve las suyas: el filtro por socio no lo elige él, lo fijamos acá.
        $filtros['socio_id'] = '';
        $filtros['item_id'] = '';
        $resultado = ['filas' => [], 'total' => 0, 'pagina' => 1, 'paginas' => 1];
        $conteo = array_fill_keys(['', 'lista', 'pendiente', 'vencida', 'retirada', 'cancelada'], 0);
        $socio = Socio::buscarPorUsuarioId($pdo, $_SESSION['usuario']['id']);
        if ($socio) {
            $filtrosDelSocio = array_merge($filtros, ['socio_id' => $socio->getId()]);
            $resultado = Reserva::listar($pdo, array_merge($filtrosDelSocio, $paginacion));
            $conteo = Reserva::contarPorEstado($pdo, $filtrosDelSocio);
        }
        $titulo = 'Mis reservas';
    }
    $reservas = $resultado['filas'];

    require __DIR__ . '/../views/reservas/listar.php';
}

/**
 * Le pasa los ejemplares disponibles del item a su cola de reservas (ver
 * Reserva::avanzarCola) y le avisa por correo a cada socio al que le tocó uno.
 * Se llama cada vez que puede quedar un ejemplar libre: una reserva nueva,
 * una devolución, una cancelación o un aumento de stock.
 * Devuelve ['socios' => nombres de los que recibieron un ejemplar,
 *           'sin_aviso' => a cuántos no se les pudo mandar el correo].
 */
function repartirEjemplares($pdo, $itemId) {
    $reparto = ['socios' => [], 'sin_aviso' => 0];
    foreach (Reserva::avanzarCola($pdo, $itemId) as $r) {
        $reparto['socios'][] = $r['socio_nombre'];
        if (!enviarAvisoReservaLista($r['email'], $r['socio_nombre'], $r['item_titulo'], $r['fecha_vencimiento'])) {
            $reparto['sin_aviso']++;
        }
    }
    return $reparto;
}

/**
 * Texto para el admin después de repartirEjemplares(): para quién tiene que
 * apartar el ejemplar y si ya se le avisó. '' si no le tocó a nadie.
 */
function mensajeReparto($reparto) {
    $cantidad = count($reparto['socios']);
    if ($cantidad === 0) {
        return '';
    }
    if ($cantidad === 1) {
        $texto = ' Apartá el ejemplar para ' . $reparto['socios'][0] . ', que lo tenía reservado.';
    } else {
        $texto = ' Apartá un ejemplar para cada uno de estos socios, que lo tenían reservado: ' . implode(', ', $reparto['socios']) . '.';
    }
    if (!correoConfigurado()) {
        return $texto . ($cantidad === 1 ? ' Avisale que ya puede retirarlo.' : ' Avisales que ya pueden retirarlo.');
    }
    if ($reparto['sin_aviso'] > 0) {
        return $texto . ' No se pudo enviar el correo: ' . ($cantidad === 1 ? 'avisale vos.' : 'avisales vos.');
    }
    return $texto . ($cantidad === 1 ? ' Ya le avisamos por correo.' : ' Ya les avisamos por correo.');
}

/** Botón "Reservar" del catálogo. */
function crearReserva() {
    global $pdo;
    requerirLogin();

    // Volvemos al catálogo como estaba: el formulario manda sus filtros y su página.
    $volverA = urlFiltros([
        'accion'    => 'listarItems',
        'tipo'      => $_POST['tipo'] ?? '',
        'categoria' => $_POST['categoria'] ?? '',
        'q'         => $_POST['q'] ?? '',
        'p'         => $_POST['p'] ?? '',
    ]);

    $socio = Socio::buscarPorUsuarioId($pdo, $_SESSION['usuario']['id']);
    $item = Item::buscarPorId($pdo, $_POST['item_id'] ?? '');
    $error = '';
    if (!$socio) {
        $error = 'Para reservar necesitás una cuenta de socio.';
    } elseif (!$item) {
        $error = 'Ítem no encontrado.';
    } else {
        $activas = Reserva::activasDelSocio($pdo, $socio->getId());
        if (in_array($item->getId(), Prestamo::itemsPrestadosA($pdo, $socio->getId()))) {
            $error = 'Ya tenés «' . $item->getTitulo() . '» prestado.';
        } elseif (isset($activas[$item->getId()])) {
            $error = 'Ya tenés una reserva de «' . $item->getTitulo() . '». La ves en Mis reservas.';
        } elseif (count($activas) >= Reserva::MAXIMO_ACTIVAS) {
            $error = 'Ya tenés ' . Reserva::MAXIMO_ACTIVAS . ' reservas activas, que es el máximo. Cancelá alguna para reservar otra.';
        }
    }
    if ($error !== '') {
        flash('warning', $error);
        header('Location: ' . $volverA);
        exit;
    }

    // Entra al final de la cola; si hay ejemplares disponibles, se reparten
    // por orden de llegada y puede tocarle a esta misma reserva.
    $id = Reserva::crear($pdo, $socio->getId(), $item->getId(), date('Y-m-d'));
    repartirEjemplares($pdo, $item->getId());

    $reserva = Reserva::buscarPorId($pdo, $id);
    if ($reserva->estaLista()) {
        flash('success', 'Te apartamos un ejemplar de «' . $item->getTitulo() . '» hasta el '
            . formatoFecha($reserva->getFechaVencimiento()) . '. Pasá a retirarlo por la biblioteca.');
    } else {
        flash('success', 'Quedaste en la lista de espera de «' . $item->getTitulo() . '», en el puesto ' . $reserva->getPuesto()
            . '. Cuando te toque, la reserva pasa a «Lista»' . (correoConfigurado() ? ' y te avisamos por correo.' : '.'));
    }
    header('Location: ' . $volverA);
    exit;
}

/** El socio cancela una reserva suya; el admin, cualquiera. */
function cancelarReserva() {
    global $pdo;
    requerirLogin();

    $reserva = Reserva::buscarPorId($pdo, $_POST['id'] ?? '');
    $esSuya = false;
    if ($reserva && !esAdmin()) {
        $socio = Socio::buscarPorUsuarioId($pdo, $_SESSION['usuario']['id']);
        $esSuya = $socio && $socio->getId() == $reserva->getSocioId();
    }

    if (!$reserva || (!esAdmin() && !$esSuya)) {
        flash('danger', 'Reserva no encontrada.');
    } elseif (!Reserva::cancelar($pdo, $reserva->getId())) {
        flash('warning', 'Esa reserva ya estaba retirada o cancelada.');
    } else {
        // Si tenía un ejemplar apartado, quedó libre: pasa al siguiente de la cola.
        $reparto = repartirEjemplares($pdo, $reserva->getItemId());
        if (esAdmin()) {
            flash('success', 'Reserva cancelada.' . mensajeReparto($reparto));
        } else {
            flash('success', 'Cancelaste tu reserva de «' . $reserva->getItemTitulo() . '».');
        }
    }

    // Volvemos a la lista con los mismos filtros y la misma página (vienen en campos ocultos).
    $volverA = array_merge(['accion' => 'listarReservas'], leerFiltrosReservas($_POST));
    if (ctype_digit($_POST['p'] ?? '')) {
        $volverA['p'] = $_POST['p'];
    }
    header('Location: ' . urlFiltros($volverA));
    exit;
}
