<?php
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../models/Usuario.php';

// ============================================================
//  Controller de Socios
//  Mismo patrón que ItemController, repetido para esta entidad.
// ============================================================

/**
 * Revisa la dirección y el teléfono de un socio. La usan el formulario del
 * admin y "Mi perfil". Devuelve el mensaje de error, o '' si está todo bien.
 * Los largos máximos son los de las columnas en database.sql.
 */
function validarContacto($direccion, $telefono) {
    if ($direccion === '' || $telefono === '') {
        return 'Completá la dirección y el teléfono.';
    }
    if (mb_strlen($direccion) > 255 || mb_strlen($telefono) > 20) {
        return 'La dirección admite hasta 255 caracteres y el teléfono 20.';
    }
    if (!telefonoValido($telefono)) {
        return 'El teléfono tiene que tener entre 8 y 15 números (podés separarlos con espacios o guiones).';
    }
    return '';
}

/**
 * Revisa los campos del formulario. Devuelve el mensaje de error, o '' si está todo bien.
 * $email es el de la cuenta del usuario elegido: se guarda en usuarios.
 */
function validarSocio($pdo, $usuarioId, $nombre, $email, $direccion, $telefono, $usuariosDisponibles) {
    if ($usuarioId === '' || $nombre === '' || $email === '') {
        return 'Completá todos los campos y elegí un usuario.';
    }
    // El usuario tiene que ser uno de la lista: rol socio y sin otro socio asociado.
    $enLaLista = false;
    foreach ($usuariosDisponibles as $u) {
        if ($u->getId() == $usuarioId) {
            $enLaLista = true;
        }
    }
    if (!$enLaLista) {
        return 'Elegí un usuario de la lista.';
    }
    if (mb_strlen($nombre) > 100) {
        return 'El nombre admite hasta 100 caracteres.';
    }
    if (mb_strlen($email) > 100 || !emailValido($email)) {
        return 'Escribí un correo válido, de hasta 100 caracteres (por ejemplo, nombre@correo.com).';
    }
    $otro = Usuario::buscarPorEmail($pdo, $email);
    if ($otro && $otro->getId() != $usuarioId) {
        return 'Ya existe otro usuario con ese correo.';
    }
    return validarContacto($direccion, $telefono);
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
    $email     = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');

    $usuariosDisponibles = Usuario::listarDisponiblesParaSocio($pdo);
    $error = validarSocio($pdo, $usuarioId, $nombre, $email, $direccion, $telefono, $usuariosDisponibles);
    if ($error !== '') {
        require __DIR__ . '/../views/socios/form.php';
        return;
    }

    // La foto es de la cuenta del socio. Si no suben una, queda la que ya tenía.
    $fotoNueva = null;
    if (!empty($_FILES['foto']['name'])) {
        $fotoNueva = guardarImagen($_FILES['foto'], AVATAR_POR_DEFECTO);
        if ($fotoNueva === null) {
            $error = 'La foto tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.';
            require __DIR__ . '/../views/socios/form.php';
            return;
        }
    }

    Socio::crear($pdo, $usuarioId, $nombre, $direccion, $telefono);
    Usuario::actualizarEmail($pdo, $usuarioId, $email);
    if ($fotoNueva !== null) {
        cambiarFotoDeUsuario($pdo, Usuario::buscarPorId($pdo, $usuarioId), $fotoNueva);
    }
    flash('success', 'Socio creado correctamente.');
    header('Location: index.php?accion=listarSocios');
    exit;
}

function formEditarSocio() {
    global $pdo;
    requerirAdmin();
    $socio = Socio::buscarPorId($pdo, $_GET['id'] ?? '');
    if (!$socio) {
        flash('danger', 'Socio no encontrado.');
        header('Location: index.php?accion=listarSocios');
        exit;
    }
    $usuariosDisponibles = Usuario::listarDisponiblesParaSocio($pdo, $socio->getUsuario()->getId());
    require __DIR__ . '/../views/socios/form.php';
}

function editarSocio() {
    global $pdo;
    requerirAdmin();

    $id = $_POST['id'] ?? '';
    $socio = Socio::buscarPorId($pdo, $id);
    if (!$socio) {
        flash('danger', 'Socio no encontrado.');
        header('Location: index.php?accion=listarSocios');
        exit;
    }

    $usuarioId = $_POST['usuario_id'] ?? '';
    $nombre    = trim($_POST['nombre'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');

    $usuariosDisponibles = Usuario::listarDisponiblesParaSocio($pdo, $socio->getUsuario()->getId());
    $error = validarSocio($pdo, $usuarioId, $nombre, $email, $direccion, $telefono, $usuariosDisponibles);
    if ($error !== '') {
        require __DIR__ . '/../views/socios/form.php';
        return;
    }

    // La foto es de la cuenta del socio. Si no suben una nueva, queda la que ya tenía.
    $fotoNueva = null;
    if (!empty($_FILES['foto']['name'])) {
        $fotoNueva = guardarImagen($_FILES['foto'], AVATAR_POR_DEFECTO);
        if ($fotoNueva === null) {
            $error = 'La foto tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.';
            require __DIR__ . '/../views/socios/form.php';
            return;
        }
    }

    Socio::actualizar($pdo, $id, $usuarioId, $nombre, $direccion, $telefono);
    Usuario::actualizarEmail($pdo, $usuarioId, $email);
    if ($fotoNueva !== null) {
        cambiarFotoDeUsuario($pdo, Usuario::buscarPorId($pdo, $usuarioId), $fotoNueva);
    }
    flash('success', 'Socio actualizado correctamente.');
    header('Location: index.php?accion=listarSocios');
    exit;
}

function eliminarSocio() {
    global $pdo;
    requerirAdmin();

    $id = $_POST['id'] ?? '';
    $socio = Socio::buscarPorId($pdo, $id);
    if (!$socio) {
        flash('danger', 'Socio no encontrado.');
        header('Location: index.php?accion=listarSocios');
        exit;
    }

    if (Socio::tieneHistorial($pdo, $id)) {
        flash('danger', 'No se puede eliminar: el socio tiene préstamos o reservas registrados y se perdería el historial.');
        header('Location: index.php?accion=listarSocios');
        exit;
    }

    // La foto no se borra: es de la cuenta, que sigue existiendo.
    Socio::eliminar($pdo, $id);
    flash('success', 'Socio eliminado.');
    header('Location: index.php?accion=listarSocios');
    exit;
}
