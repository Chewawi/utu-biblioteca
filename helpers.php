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
        header('Location: index.php?accion=listarLibros');
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
