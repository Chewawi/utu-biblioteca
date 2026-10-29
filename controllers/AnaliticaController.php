<?php
require_once __DIR__ . '/../models/Analitica.php';
require_once __DIR__ . '/../models/Item.php';

// ============================================================
//  Controller de Analíticas
//  Una sola página, solo para el admin, con los indicadores
//  de la biblioteca. El período (desde/hasta) filtra los
//  préstamos; el inventario muestra siempre el estado actual.
// ============================================================

function verAnaliticas() {
    global $pdo;
    requerirAdmin();

    $desde = trim($_GET['desde'] ?? '');
    $hasta = trim($_GET['hasta'] ?? '');
    if (!fechaValida($desde)) {
        $desde = '';
    }
    if (!fechaValida($hasta)) {
        $hasta = '';
    }
    // Si las pusieron al revés, las damos vuelta.
    if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
        [$desde, $hasta] = [$hasta, $desde];
    }

    $itemsPorTipo = Analitica::itemsPorTipo($pdo);
    $totalSocios = Analitica::totalSocios($pdo);
    $menosDisponibles = Analitica::itemsConMenosDisponibilidad($pdo);

    $estados = Analitica::prestamosPorEstado($pdo, $desde, $hasta);
    $porMes = Analitica::prestamosPorMes($pdo, $desde, $hasta);
    $masPrestados = Analitica::itemsMasPrestados($pdo, $desde, $hasta);
    $sociosTop = Analitica::sociosConMasPrestamos($pdo, $desde, $hasta);
    $promedioDias = Analitica::promedioDiasDevolucion($pdo, $desde, $hasta);
    $tardias = Analitica::devolucionesTardias($pdo, $desde, $hasta);

    require __DIR__ . '/../views/analiticas/ver.php';
}
