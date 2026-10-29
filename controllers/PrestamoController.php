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
    // Opciones de los desplegables de "Más filtros": [id => texto].
    $opcionesItems = [];
    $opcionesSocios = [];

    if (esAdmin()) {
        foreach (Item::listar($pdo)['filas'] as $item) {
            $opcionesItems[$item->getId()] = $item->getTitulo();
        }
        foreach (Socio::listar($pdo)['filas'] as $socio) {
            $opcionesSocios[$socio->getId()] = $socio->getNombre();
        }
        $resultado = Prestamo::listar($pdo, array_merge($filtros, $paginacion));
        $conteo = Prestamo::contarPorEstado($pdo, $filtros);
        $titulo = 'Préstamos';
    } else {
        // Un socio solo ve lo suyo: el filtro por socio no lo elige él, lo fijamos acá.
        $filtros['socio_id'] = '';
        $socio = Socio::buscarPorUsuarioId($pdo, $_SESSION['usuario']['id']);
        $resultado = ['filas' => [], 'total' => 0, 'pagina' => 1, 'paginas' => 1];
        $conteo = ['' => 0, 'activo' => 0, 'vencido' => 0, 'devuelto' => 0];
        if ($socio) {
            // En el filtro por ítem, solo los que pidió alguna vez.
            $opcionesItems = Prestamo::itemsDelSocio($pdo, $socio->getId());
            if (!isset($opcionesItems[$filtros['item_id']])) {
                $filtros['item_id'] = '';
            }
            $filtrosDelSocio = $filtros;
            $filtrosDelSocio['socio_id'] = $socio->getId();
            $resultado = Prestamo::listar($pdo, array_merge($filtrosDelSocio, $paginacion));
            $conteo = Prestamo::contarPorEstado($pdo, $filtrosDelSocio);
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

    // El correo es un extra: si no está configurado o falla, el préstamo ya quedó guardado.
    $socio = Socio::buscarPorId($pdo, $socioId);
    if (!correoConfigurado()) {
        flash('success', 'Préstamo registrado.');
    } elseif (enviarConfirmacionPrestamo($socio->getUsuario()->getEmail(), $socio->getNombre(), $item->getTitulo(), $fecha, $fechaLimite)) {
        flash('success', 'Préstamo registrado. Le avisamos al socio por correo.');
    } else {
        flash('warning', 'Préstamo registrado, pero no se pudo enviar el correo al socio.');
    }
    header('Location: index.php?accion=listarPrestamos');
    exit;
}

/** Botón "Avisar vencidos": manda los recordatorios por correo y vuelve a los vencidos. */
function avisarVencidos() {
    global $pdo;
    requerirAdmin();

    if (!correoConfigurado()) {
        flash('warning', 'El envío de correos no está configurado.');
    } else {
        $r = enviarRecordatoriosVencidos($pdo);
        if ($r['enviados'] + $r['fallidos'] === 0) {
            flash('info', 'No hay préstamos vencidos.');
        } elseif ($r['fallidos'] === 0) {
            flash('success', 'Recordatorios enviados: ' . $r['enviados'] . '.');
        } else {
            flash('warning', 'Recordatorios enviados: ' . $r['enviados'] . '. No se pudieron enviar: ' . $r['fallidos'] . '.');
        }
    }
    header('Location: index.php?accion=listarPrestamos&estado=vencido');
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
