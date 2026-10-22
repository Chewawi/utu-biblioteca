<?php
// ============================================================
//  helpers.php
//  Funciones que se usan en varias partes del sistema, para no
//  repetir el mismo código en cada controller.
// ============================================================

/** Manda al login si no hay nadie logueado. */
function requerirLogin() {
    if (!isset($_SESSION['usuario'])) {
        header('Location: index.php?accion=login');
        exit;
    }
}

/** Igual que requerirLogin(), pero además exige que el rol sea 'admin'. */
function requerirAdmin() {
    requerirLogin();
    if ($_SESSION['usuario']['rol'] !== 'admin') {
        flash('danger', 'No tenés permiso para hacer eso.');
        header('Location: index.php?accion=listarItems');
        exit;
    }
}

/** true si hay alguien logueado y es admin. */
function esAdmin() {
    return isset($_SESSION['usuario']) && $_SESSION['usuario']['rol'] === 'admin';
}

// Mensajes flash (un avisito que se muestra una sola vez) 
function flash($tipo, $mensaje) {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function mostrarFlash() {
    if (isset($_SESSION['flash'])) {
        $tipo = $_SESSION['flash']['tipo'];
        $msg = htmlspecialchars($_SESSION['flash']['mensaje']);
        echo "<div class='alert alert-{$tipo} alert-dismissible fade show'>
                {$msg}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        unset($_SESSION['flash']);
    }
}

/**
 * Guarda una imagen subida en uploads/ y devuelve el nombre final del archivo.
 * Si no subieron nada devuelve 'default.png'. Si el archivo no es una imagen
 * JPG, PNG o WEBP, o pesa más de 2 MB, devuelve null.
 */
function guardarImagen($archivo) {
    if (empty($archivo['name'])) {
        return 'default.png';
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 2 * 1024 * 1024) {
        return null;
    }

    // Miramos el contenido real del archivo, no la extensión del nombre (que
    // cualquiera puede cambiar). getimagesize() devuelve false si no es una imagen.
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $info = getimagesize($archivo['tmp_name']);
    if (!$info || !isset($permitidos[$info['mime']])) {
        return null;
    }

    // La extensión del archivo guardado sale del contenido, no del nombre original.
    $nombre = uniqid() . '.' . $permitidos[$info['mime']];
    if (!move_uploaded_file($archivo['tmp_name'], __DIR__ . '/uploads/' . $nombre)) {
        return null;
    }
    return $nombre;
}

/** Borra una imagen de uploads/, salvo default.png, que la comparten todos. */
function borrarImagen($nombre) {
    $ruta = __DIR__ . '/uploads/' . basename($nombre);
    if ($nombre !== 'default.png' && is_file($ruta)) {
        unlink($ruta);
    }
}
