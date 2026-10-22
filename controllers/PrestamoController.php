<?php
require_once __DIR__ . '/../models/Prestamo.php';
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Item.php';

// ============================================================
//  Controller de Préstamos
//  El admin ve todos los préstamos y puede crear/eliminar.
//  Un socio logueado solo ve los suyos.
// ============================================================

function listarPrestamos() {
    global $pdo;
    requerirLogin();

    if (esAdmin()) {
        $prestamos = Prestamo::listar($pdo);
        $titulo = 'Préstamos';
    } else {
        $socio = Socio::buscarPorUsuarioId($pdo, $_SESSION['usuario']['id']);
        $prestamos = $socio ? Prestamo::listarPorSocio($pdo, $socio->getId()) : [];
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

    if ($socioId === '' || $itemId === '' || $fecha === '') {
        $error = 'Elegí un socio, un ítem, y la fecha del préstamo.';
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

    Prestamo::devolver($pdo, $_POST['id']);
    flash('success', 'Devolución registrada.');
    header('Location: index.php?accion=listarPrestamos');
    exit;
}
