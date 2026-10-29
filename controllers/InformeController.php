<?php
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/../models/Analitica.php';

// ============================================================
//  Controller de Informes
//  Solo para el admin. Cada informe arma una tabla con las
//  consultas que ya usan los listados y las analíticas. La
//  misma tabla se muestra en pantalla, se imprime (o se guarda
//  como PDF desde el navegador) o se descarga como CSV.
//
//  Una tabla es un array con:
//    titulo, detalle -> lo que se muestra arriba de la tabla,
//    columnas        -> los encabezados,
//    filas           -> una lista por fila; los números van como int
//                       (así se alinean a la derecha y Excel los suma),
//    totales         -> fila final con los totales, o null si no tiene.
// ============================================================

const INFORMES = [
    'prestamos'  => 'Préstamos',
    'vencidos'   => 'Vencidos',
    'inventario' => 'Inventario',
    'socios'     => 'Socios',
];

// Cómo se puede agrupar el inventario.
const AGRUPAR_INVENTARIO = [
    ''               => 'Sin agrupar',
    'tipo'           => 'Por tipo',
    'categoria'      => 'Por categoría',
    'tipo_categoria' => 'Por tipo y categoría',
];

function verInforme() {
    global $pdo;
    requerirAdmin();

    $informe = $_GET['informe'] ?? 'prestamos';
    if (!isset(INFORMES[$informe])) {
        $informe = 'prestamos';
    }
    // Los mismos filtros (y la misma validación) que el listado de préstamos.
    $filtros = leerFiltrosPrestamos($_GET);
    $agrupar = $_GET['agrupar'] ?? '';
    if (!isset(AGRUPAR_INVENTARIO[$agrupar])) {
        $agrupar = '';
    }

    if ($informe === 'prestamos') {
        $tabla = informePrestamos($pdo, $filtros);
    } elseif ($informe === 'vencidos') {
        $tabla = informeVencidos($pdo);
    } elseif ($informe === 'inventario') {
        $tabla = informeInventario($pdo, $agrupar);
    } else {
        $tabla = informeSocios($pdo, $filtros);
    }

    if (($_GET['formato'] ?? '') === 'csv') {
        enviarCsv('informe-' . $informe . '-' . date('Y-m-d') . '.csv', $tabla);
        return;
    }
    require __DIR__ . '/../views/informes/ver.php';
}

/** Préstamos hechos en el período, con filtro de estado. */
function informePrestamos($pdo, $filtros) {
    $prestamos = Prestamo::listar($pdo, [
        'desde'  => $filtros['desde'],
        'hasta'  => $filtros['hasta'],
        'estado' => $filtros['estado'],
    ])['filas'];

    $filas = [];
    foreach ($prestamos as $p) {
        $filas[] = [
            $p->getSocioNombre(),
            $p->getItemTitulo(),
            $p->getEstadoTexto(),
            formatoFecha($p->getFechaPrestamo()),
            formatoFecha($p->getFechaLimite()),
            formatoFecha($p->getFechaDevolucion()),
        ];
    }

    $detalle = textoPeriodo($filtros['desde'], $filtros['hasta']);
    if ($filtros['estado'] !== '') {
        $detalle .= ' · solo ' . $filtros['estado'] . 's';
    }
    return [
        'titulo'   => 'Préstamos del período',
        'detalle'  => $detalle . ' · ' . count($filas) . ' préstamos',
        'columnas' => ['Socio', 'Ítem', 'Estado', 'Fecha de préstamo', 'Fecha límite', 'Fecha de devolución'],
        'filas'    => $filas,
        'totales'  => null,
    ];
}

/** Préstamos activos con la fecha límite pasada, primero los más atrasados. */
function informeVencidos($pdo) {
    $prestamos = Prestamo::listar($pdo, ['estado' => 'vencido'])['filas'];
    usort($prestamos, function ($a, $b) {
        return $b->getDiasDeAtraso() - $a->getDiasDeAtraso();
    });

    $filas = [];
    foreach ($prestamos as $p) {
        $filas[] = [
            $p->getSocioNombre(),
            $p->getSocioTelefono(),
            $p->getItemTitulo(),
            formatoFecha($p->getFechaPrestamo()),
            formatoFecha($p->getFechaLimite()),
            $p->getDiasDeAtraso(),
        ];
    }
    return [
        'titulo'   => 'Préstamos vencidos',
        'detalle'  => 'Al ' . formatoFecha(date('Y-m-d')) . ' · ' . count($filas) . ' préstamos',
        'columnas' => ['Socio', 'Teléfono', 'Ítem', 'Fecha de préstamo', 'Fecha límite', 'Días de atraso'],
        'filas'    => $filas,
        'totales'  => null,
    ];
}

/** Ejemplares de cada ítem: stock, prestados y disponibles. Se puede agrupar por tipo y/o categoría. */
function informeInventario($pdo, $agrupar) {
    $items = Item::listar($pdo)['filas'];
    $porTipo = in_array($agrupar, ['tipo', 'tipo_categoria']);
    $porCategoria = in_array($agrupar, ['categoria', 'tipo_categoria']);

    $filas = [];
    $totales = ['titulos' => 0, 'stock' => 0, 'prestados' => 0, 'disponibles' => 0];
    foreach ($items as $item) {
        $stock = (int) $item->getStock();
        $prestados = (int) $item->getPrestados();
        $categoria = $item->getCategoria() ?: 'Sin categoría';

        if ($agrupar === '') {
            $filas[] = [$item->getTitulo(), ucfirst($item->getTipo()), $categoria, $stock, $prestados, $stock - $prestados];
        } else {
            // Una fila por grupo: las columnas del grupo y después títulos, stock, prestados y disponibles.
            $grupo = [];
            if ($porTipo) {
                $grupo[] = ucfirst($item->getTipo());
            }
            if ($porCategoria) {
                $grupo[] = $categoria;
            }
            $clave = implode(' / ', $grupo);
            if (!isset($filas[$clave])) {
                $filas[$clave] = array_merge($grupo, [0, 0, 0, 0]);
            }
            $n = count($grupo); // dónde empiezan los números
            $filas[$clave][$n] += 1;
            $filas[$clave][$n + 1] += $stock;
            $filas[$clave][$n + 2] += $prestados;
            $filas[$clave][$n + 3] += $stock - $prestados;
        }

        $totales['titulos']++;
        $totales['stock'] += $stock;
        $totales['prestados'] += $prestados;
        $totales['disponibles'] += $stock - $prestados;
    }

    if ($agrupar === '') {
        $columnas = ['Título', 'Tipo', 'Categoría', 'Stock', 'Prestados', 'Disponibles'];
        $filaTotales = ['Total: ' . $totales['titulos'] . ' ítems', '', '', $totales['stock'], $totales['prestados'], $totales['disponibles']];
    } else {
        ksort($filas); // los grupos en orden alfabético
        $filas = array_values($filas);
        $columnas = [];
        if ($porTipo) {
            $columnas[] = 'Tipo';
        }
        if ($porCategoria) {
            $columnas[] = 'Categoría';
        }
        // La palabra "Total" va en la primera columna; si hay dos columnas de grupo, la segunda queda vacía.
        $filaTotales = array_merge(['Total'], array_fill(0, count($columnas) - 1, ''), array_values($totales));
        $columnas = array_merge($columnas, ['Ítems', 'Stock', 'Prestados', 'Disponibles']);
    }

    return [
        'titulo'   => 'Inventario',
        'detalle'  => 'Al ' . formatoFecha(date('Y-m-d')) . ' · ' . mb_strtolower(AGRUPAR_INVENTARIO[$agrupar]),
        'columnas' => $columnas,
        'filas'    => $filas,
        'totales'  => $filaTotales,
    ];
}

/** Cuántos préstamos tuvo cada socio en el período y cómo los devolvió. */
function informeSocios($pdo, $filtros) {
    $filas = [];
    foreach (Analitica::actividadSocios($pdo, $filtros['desde'], $filtros['hasta']) as $f) {
        $filas[] = [
            $f['nombre'],
            $f['telefono'],
            (int) $f['prestamos'],
            (int) $f['activos'],
            (int) $f['vencidos'],
            (int) $f['devueltos'],
            (int) $f['tardias'],
            formatoFecha($f['ultimo']),
        ];
    }
    return [
        'titulo'   => 'Actividad de socios',
        'detalle'  => textoPeriodo($filtros['desde'], $filtros['hasta']) . ' · según la fecha de préstamo',
        'columnas' => ['Socio', 'Teléfono', 'Préstamos', 'Activos', 'Vencidos', 'Devueltos', 'Devueltos tarde', 'Último préstamo'],
        'filas'    => $filas,
        'totales'  => null,
    ];
}

/** Descarga la $tabla como un archivo CSV que Excel abre bien. */
function enviarCsv($nombreArchivo, $tabla) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');

    $salida = fopen('php://output', 'w');
    // Estos 3 bytes (BOM) le avisan a Excel que el archivo es UTF-8; sin ellos, las tildes y la ñ salen mal.
    fwrite($salida, "\xEF\xBB\xBF");
    // Excel en español separa las columnas con punto y coma, no con coma.
    fputcsv($salida, $tabla['columnas'], ';');
    foreach ($tabla['filas'] as $fila) {
        fputcsv($salida, array_map('celdaCsv', $fila), ';');
    }
    if ($tabla['totales'] !== null) {
        fputcsv($salida, array_map('celdaCsv', $tabla['totales']), ';');
    }
    fclose($salida);
}

/** Ajusta un valor para que Excel lo muestre tal cual. */
function celdaCsv($valor) {
    if (!is_string($valor) || $valor === '') {
        return $valor;
    }
    // Excel toma como fórmula un texto que empieza con = + - o @. Con un apóstrofo adelante queda como texto.
    if (strpos('=+-@', $valor[0]) !== false) {
        return "'" . $valor;
    }
    // Un teléfono como 099123456 Excel lo convierte en número y le borra el 0. Escrito ="099123456" lo deja como texto.
    if (preg_match('/^0[0-9]+$/', $valor)) {
        return '="' . $valor . '"';
    }
    return $valor;
}
