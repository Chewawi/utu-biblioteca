<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Socio.php';

// ============================================================
//  Controller de Usuarios
//  Login, logout, y gestión de cuentas (admin).
// ============================================================

function formLogin() {
    if (isset($_SESSION['usuario'])) {
        header('Location: index.php?accion=listarItems');
        exit;
    }
    require __DIR__ . '/../views/usuarios/login.php';
}

function login() {
    global $pdo;

    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['pass'] ?? '');

    $usuario = Usuario::buscarPorEmail($pdo, $email);

    if ($usuario && password_verify($pass, $usuario->getPass())) {
        // Id de sesión nuevo al entrar: así no sirve uno que alguien conociera de antes.
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id'    => $usuario->getId(),
            'email' => $usuario->getEmail(),
            'rol'   => $usuario->getRol(),
        ];
        header('Location: index.php?accion=listarItems');
        exit;
    }

    $error = 'Email o contraseña incorrectos.';
    require __DIR__ . '/../views/usuarios/login.php';
}

function logout() {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php?accion=login');
    exit;
}


// --- Gestión de usuarios (solo admin) ---

function listarUsuarios() {
    global $pdo;
    requerirAdmin();
    $usuarios = Usuario::listar($pdo);
    require __DIR__ . '/../views/usuarios/listar.php';
}

function formCrearUsuario() {
    requerirAdmin();
    require __DIR__ . '/../views/usuarios/form.php';
}

function crearUsuario() {
    global $pdo;
    requerirAdmin();

    $rol   = trim($_POST['rol'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['pass'] ?? '');

    if (($rol !== 'admin' && $rol !== 'socio') || $email === '' || $pass === '') {
        $error = 'Completá todos los campos con un rol válido.';
        require __DIR__ . '/../views/usuarios/form.php';
        return;
    }
    if (mb_strlen($email) > 100) {
        $error = 'El email admite hasta 100 caracteres.';
        require __DIR__ . '/../views/usuarios/form.php';
        return;
    }
    if (Usuario::buscarPorEmail($pdo, $email)) {
        $error = 'Ya existe un usuario con ese email.';
        require __DIR__ . '/../views/usuarios/form.php';
        return;
    }
    if (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
        require __DIR__ . '/../views/usuarios/form.php';
        return;
    }

    Usuario::crear($pdo, $rol, $email, password_hash($pass, PASSWORD_DEFAULT));
    flash('success', 'Usuario creado correctamente.');
    header('Location: index.php?accion=listarUsuarios');
    exit;
}

function formEditarUsuario() {
    global $pdo;
    requerirAdmin();
    $usuario = Usuario::buscarPorId($pdo, $_GET['id'] ?? '');
    if (!$usuario) {
        flash('danger', 'Usuario no encontrado.');
        header('Location: index.php?accion=listarUsuarios');
        exit;
    }
    require __DIR__ . '/../views/usuarios/form.php';
}

function editarUsuario() {
    global $pdo;
    requerirAdmin();

    $id    = $_POST['id'] ?? '';
    $rol   = trim($_POST['rol'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['pass'] ?? '');

    $usuario = Usuario::buscarPorId($pdo, $id);
    if (!$usuario) {
        flash('danger', 'Usuario no encontrado.');
        header('Location: index.php?accion=listarUsuarios');
        exit;
    }

    $otro = Usuario::buscarPorEmail($pdo, $email);
    $error = '';
    if (($rol !== 'admin' && $rol !== 'socio') || $email === '') {
        $error = 'Completá el email y elegí un rol válido.';
    } elseif (mb_strlen($email) > 100) {
        $error = 'El email admite hasta 100 caracteres.';
    } elseif ($otro && $otro->getId() != $id) {
        $error = 'Ya existe otro usuario con ese email.';
    } elseif ($id == $_SESSION['usuario']['id'] && $rol !== 'admin') {
        // Así siempre queda al menos un administrador: el que está editando.
        $error = 'No podés quitarte a vos mismo el rol de administrador.';
    } elseif ($rol === 'admin' && Socio::buscarPorUsuarioId($pdo, $id)) {
        $error = 'Este usuario tiene un socio asociado: no puede ser administrador.';
    } elseif ($pass !== '' && strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if ($error !== '') {
        require __DIR__ . '/../views/usuarios/form.php';
        return;
    }

    // Si dejan la contraseña vacía, no la tocamos.
    $hash = $pass !== '' ? password_hash($pass, PASSWORD_DEFAULT) : $usuario->getPass();

    Usuario::actualizar($pdo, $id, $rol, $email, $hash);

    if ($_SESSION['usuario']['id'] == $id) {
        $_SESSION['usuario']['rol'] = $rol;
        $_SESSION['usuario']['email'] = $email;
    }

    flash('success', 'Usuario actualizado correctamente.');
    header('Location: index.php?accion=listarUsuarios');
    exit;
}

function eliminarUsuario() {
    global $pdo;
    requerirAdmin();

    $id = $_POST['id'] ?? '';

    if (!Usuario::buscarPorId($pdo, $id)) {
        flash('danger', 'Usuario no encontrado.');
        header('Location: index.php?accion=listarUsuarios');
        exit;
    }
    if ($id == $_SESSION['usuario']['id']) {
        flash('danger', 'No podés eliminar tu propia cuenta.');
        header('Location: index.php?accion=listarUsuarios');
        exit;
    }
    if (Socio::buscarPorUsuarioId($pdo, $id)) {
        flash('danger', 'Este usuario tiene un socio asociado. Eliminá primero al socio.');
        header('Location: index.php?accion=listarUsuarios');
        exit;
    }

    Usuario::eliminar($pdo, $id);
    flash('success', 'Usuario eliminado.');
    header('Location: index.php?accion=listarUsuarios');
    exit;
}
