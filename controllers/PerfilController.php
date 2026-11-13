<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Socio.php';

// ============================================================
//  Controller de "Mi perfil"
//  Cualquier usuario logueado (admin o socio) cambia acá su foto
//  y su contraseña. El socio, además, su teléfono y su dirección.
// ============================================================

/** Muestra "Mi perfil" de quien está logueado, con el error de cada formulario si hubo uno. */
function mostrarPerfil($error = null, $errorPass = null) {
    global $pdo;
    $usuario = Usuario::buscarPorId($pdo, $_SESSION['usuario']['id']);
    $socio = Socio::buscarPorUsuarioId($pdo, $usuario->getId()); // null si es admin
    require __DIR__ . '/../views/perfil/ver.php';
}

function verPerfil() {
    requerirLogin();
    mostrarPerfil();
}

function editarPerfil() {
    global $pdo;
    requerirLogin();

    $usuario = Usuario::buscarPorId($pdo, $_SESSION['usuario']['id']);
    $socio = Socio::buscarPorUsuarioId($pdo, $usuario->getId());

    // El nombre y el correo no los cambia el socio: eso lo hace el admin.
    if ($socio) {
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $error = validarContacto($direccion, $telefono);
        if ($error !== '') {
            mostrarPerfil($error);
            return;
        }
    }

    // Si no suben una foto nueva, queda la que ya tenía.
    $fotoNueva = null;
    if (!empty($_FILES['foto']['name'])) {
        $fotoNueva = guardarImagen($_FILES['foto'], AVATAR_POR_DEFECTO);
        if ($fotoNueva === null) {
            mostrarPerfil('La foto tiene que ser JPG, PNG o WEBP, y pesar menos de 2 MB.');
            return;
        }
    }

    if ($socio) {
        Socio::actualizarContacto($pdo, $socio->getId(), $direccion, $telefono);
    }
    if ($fotoNueva !== null) {
        cambiarFotoDeUsuario($pdo, $usuario, $fotoNueva);
    }
    flash('success', 'Guardamos tus datos.');
    header('Location: index.php?accion=verPerfil');
    exit;
}

function cambiarPass() {
    global $pdo;
    requerirLogin();

    $usuario = Usuario::buscarPorId($pdo, $_SESSION['usuario']['id']);
    $actual   = trim($_POST['pass_actual'] ?? '');
    $nueva    = trim($_POST['pass_nueva'] ?? '');
    $repetida = trim($_POST['pass_repetida'] ?? '');

    // Pedimos la actual para que nadie la cambie desde una sesión que quedó abierta.
    $errorPass = '';
    if (!password_verify($actual, $usuario->getPass())) {
        $errorPass = 'La contraseña actual no es correcta.';
    } elseif (strlen($nueva) < 6) {
        $errorPass = 'La contraseña nueva debe tener al menos 6 caracteres.';
    } elseif ($nueva !== $repetida) {
        $errorPass = 'Las dos contraseñas nuevas no coinciden.';
    }
    if ($errorPass !== '') {
        mostrarPerfil(null, $errorPass);
        return;
    }

    Usuario::actualizarPass($pdo, $usuario->getId(), password_hash($nueva, PASSWORD_DEFAULT));
    flash('success', 'Cambiaste tu contraseña.');
    header('Location: index.php?accion=verPerfil');
    exit;
}
