<?php
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Usuario.php';

// ============================================================
//  Controller de Socios
//  Mismo patrón que ItemController, repetido para esta entidad.
// ============================================================

/**
 * Revisa los campos del formulario. Devuelve el mensaje de error, o '' si está todo bien.
 * Los largos máximos son los de las columnas en database.sql.
 */
function validarSocio($usuarioId, $nombre, $direccion, $telefono, $usuariosDisponibles) {
    if ($usuarioId === '' || $nombre === '' || $direccion === '' || $telefono === '') {
        return 'Completa todos los campos y elegí un usuario.';
    }
    if (mb_strlen($nombre) > 100 || mb_strlen($direccion) > 255 || mb_strlen($telefono) > 20) {
        return 'El nombre admite hasta 100 caracteres, la dirección 255 y el teléfono 20.';
    }
    // El usuario tiene que ser uno de la lista: rol socio y sin otro socio asociado.
    foreach ($usuariosDisponibles as $u) {
        if ($u->getId() == $usuarioId) {
            return '';
        }
    }
    return 'Elegí un usuario de la lista.';
}

function listarSocios() {
    global $pdo;
    requerirAdmin();
    $filtros = [
        'q'          => trim($_GET['q'] ?? ''),
        'pagina'     => $_GET['p'] ?? 1,
        'por_pagina' => 10,
    ];
    $resultado = Socio::listar($pdo, $filtros);
    $socios = $resultado['filas'];
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

    $usuariosDisponibles = Usuario::listarDisponiblesParaSocio($pdo);
    $error = validarSocio($usuarioId, $nombre, $direccion, $telefono, $usuariosDisponibles);
    if ($error !== '') {
        require __DIR__ . '/../views/socios/form.php';
        return;
    }

    $foto = guardarImagen($_FILES['foto'] ?? [], AVATAR_POR_DEFECTO);
    if ($foto === null) {
        $error = 'La foto tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.';
        require __DIR__ . '/../views/socios/form.php';
        return;
    }

    Socio::crear($pdo, $usuarioId, $nombre, $direccion, $telefono, $foto);
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

    $usuarioActualId = $socio->getUsuario() ? $socio->getUsuario()->getId() : null;
    $usuariosDisponibles = Usuario::listarDisponiblesParaSocio($pdo, $usuarioActualId);
    $error = validarSocio($usuarioId, $nombre, $direccion, $telefono, $usuariosDisponibles);
    if ($error !== '') {
        require __DIR__ . '/../views/socios/form.php';
        return;
    }

    // Si no suben una foto nueva, conservamos la que ya tenía.
    $foto = $socio->getFoto();
    if (!empty($_FILES['foto']['name'])) {
        $fotoNueva = guardarImagen($_FILES['foto'], AVATAR_POR_DEFECTO);
        if ($fotoNueva === null) {
            $error = 'La foto tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.';
            require __DIR__ . '/../views/socios/form.php';
            return;
        }
        $foto = $fotoNueva;
    }

    Socio::actualizar($pdo, $id, $usuarioId, $nombre, $direccion, $telefono, $foto);
    if ($foto !== $socio->getFoto()) {
        borrarImagen($socio->getFoto()); // la foto vieja ya no la usa nadie
    }
    flash('success', 'Socio actualizado correctamente.');
    header('Location: index.php?accion=listarSocios');
    exit;
}

function eliminarSocio() {
    global $pdo;
    requerirAdmin();

    $id = $_POST['id'];
    $socio = Socio::buscarPorId($pdo, $id);
    if (!$socio) {
        flash('danger', 'Socio no encontrado.');
        header('Location: index.php?accion=listarSocios');
        exit;
    }

    if (Socio::tienePrestamos($pdo, $id)) {
        flash('danger', 'No se puede eliminar: el socio tiene préstamos registrados y se perdería el historial.');
        header('Location: index.php?accion=listarSocios');
        exit;
    }

    Socio::eliminar($pdo, $id);
    borrarImagen($socio->getFoto());
    flash('success', 'Socio eliminado.');
    header('Location: index.php?accion=listarSocios');
    exit;
}
