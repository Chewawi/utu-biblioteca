<?php
require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Prestamo.php';

// ============================================================
//  Controller de Reservas
//  El socio reserva desde el catálogo. Si hay un ejemplar
//  disponible se lo apartamos; si no, queda en la lista de
//  espera del item hasta que se libere uno.
// ============================================================

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
