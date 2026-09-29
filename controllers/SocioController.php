<?php
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Usuario.php';

// ============================================================
//  Controller de Socios
//  Mismo patrón que LibroController, repetido para esta entidad.
// ============================================================

function listarSocios() {
    global $pdo;
    requerirAdmin();
    $socios = Socio::listar($pdo);
    require __DIR__ . '/../views/socios/listar.php';
}

function formCrearSocio() {
    global $pdo;
    requerirAdmin();
    $usuariosDisponibles = Usuario::listarDisponiblesParaSocio($pdo);
    require __DIR__ . '/../views/socios/form.php';
}

function crearSocio() {
    global $pdo;
    requerirAdmin();

    $usuarioId = $_POST['usuario_id'] ?? '';
    $nombre    = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');

    if ($usuarioId === '' || $nombre === '' || $direccion === '' || $telefono === '') {
        $error = 'Completa todos los campos y elegí un usuario.';
        $usuariosDisponibles = Usuario::listarDisponiblesParaSocio($pdo);
        require __DIR__ . '/../views/socios/form.php';
        return;
    }

    Socio::crear($pdo, $usuarioId, $nombre, $direccion, $telefono);
    flash('success', 'Socio creado correctamente.');
    header('Location: index.php?accion=listarSocios');
    exit;
}

function formEditarSocio() {
    global $pdo;
    requerirAdmin();
    $socio = Socio::buscarPorId($pdo, $_GET['id']);
    if (!$socio) {
        flash('danger', 'Socio no encontrado.');
        header('Location: index.php?accion=listarSocios');
        exit;
    }
    $usuarioActualId = $socio->getUsuario() ? $socio->getUsuario()->getId() : null;
    $usuariosDisponibles = Usuario::listarDisponiblesParaSocio($pdo, $usuarioActualId);
    require __DIR__ . '/../views/socios/form.php';
}

function editarSocio() {
    global $pdo;
    requerirAdmin();

    $id = $_POST['id'];
    $socio = Socio::buscarPorId($pdo, $id);
    if (!$socio) {
        flash('danger', 'Socio no encontrado.');
        header('Location: index.php?accion=listarSocios');
        exit;
    }

    $usuarioId = $_POST['usuario_id'] ?? '';
    $nombre    = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');

    if ($usuarioId === '' || $nombre === '' || $direccion === '' || $telefono === '') {
        $error = 'Completa todos los campos y elegí un usuario.';
        $usuarioActualId = $socio->getUsuario() ? $socio->getUsuario()->getId() : null;
        $usuariosDisponibles = Usuario::listarDisponiblesParaSocio($pdo, $usuarioActualId);
        require __DIR__ . '/../views/socios/form.php';
        return;
    }

    Socio::actualizar($pdo, $id, $usuarioId, $nombre, $direccion, $telefono);
    flash('success', 'Socio actualizado correctamente.');
    header('Location: index.php?accion=listarSocios');
    exit;
}

function eliminarSocio() {
    global $pdo;
    requerirAdmin();

    $id = $_POST['id'];

    if (Socio::tienePrestamos($pdo, $id)) {
        flash('danger', 'No se puede eliminar: el socio tiene un préstamo activo.');
        header('Location: index.php?accion=listarSocios');
        exit;
    }

    Socio::eliminar($pdo, $id);
    flash('success', 'Socio eliminado.');
    header('Location: index.php?accion=listarSocios');
    exit;
}
