<?php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Socio.php';

// ============================================================
//  Controller de Usuarios
//  Login, logout, registro público, y gestión de cuentas (admin).
// ============================================================

function formLogin() {
    if (isset($_SESSION['usuario'])) {
        header('Location: index.php?accion=listarLibros');
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
        $_SESSION['usuario'] = [
            'id'    => $usuario->getId(),
            'email' => $usuario->getEmail(),
            'rol'   => $usuario->getRol(),
        ];
        header('Location: index.php?accion=listarLibros');
        exit;
    }

    $error = 'Email o contraseña incorrectos.';
    require __DIR__ . '/../views/usuarios/login.php';
}

function logout() {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php?accion=auth');
    exit;
}

// --- Registro público: cualquiera puede crearse una cuenta de socio ---

function registro() {
    if (isset($_SESSION['usuario'])) {
        header('Location: index.php?accion=listarLibros');
        exit;
    }
    require __DIR__ . '/../views/usuarios/registro.php';
}

function registrar() {
    global $pdo;

    $email     = trim($_POST['email'] ?? '');
    $pass      = trim($_POST['pass'] ?? '');
    $nombre    = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');

    if ($email === '' || $pass === '' || $nombre === '' || $direccion === '' || $telefono === '') {
        $error = 'Completa todos los campos.';
        require __DIR__ . '/../views/usuarios/registro.php';
        return;
    }
    if (strlen($pass) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
        require __DIR__ . '/../views/usuarios/registro.php';
        return;
    }
    if (Usuario::buscarPorEmail($pdo, $email)) {
        $error = 'Ya existe una cuenta con ese email.';
        require __DIR__ . '/../views/usuarios/registro.php';
        return;
    }

    // Creamos la cuenta (siempre socio) y la ficha de socio, y la logueamos.
    $usuarioId = Usuario::crear($pdo, 'socio', $email, password_hash($pass, PASSWORD_DEFAULT));
    Socio::crear($pdo, $usuarioId, $nombre, $direccion, $telefono);

    $_SESSION['usuario'] = ['id' => $usuarioId, 'email' => $email, 'rol' => 'socio'];
    flash('success', '¡Cuenta creada! Ya podés pedir libros prestados.');
    header('Location: index.php?accion=listarLibros');
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
        $error = 'Completa todos los campos con un rol válido.';
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
    $usuario = Usuario::buscarPorId($pdo, $_GET['id']);
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

    $id    = $_POST['id'];
    $rol   = trim($_POST['rol'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['pass'] ?? '');

    $usuario = Usuario::buscarPorId($pdo, $id);
    if (!$usuario) {
        flash('danger', 'Usuario no encontrado.');
        header('Location: index.php?accion=listarUsuarios');
        exit;
    }

    if (($rol !== 'admin' && $rol !== 'socio') || $email === '') {
        $error = 'Completa el email y elegí un rol válido.';
        require __DIR__ . '/../views/usuarios/form.php';
        return;
    }

    // Si dejan la contraseña vacía, no la tocamos.
    $hash = $usuario->getPass();
    if ($pass !== '') {
        if (strlen($pass) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
            require __DIR__ . '/../views/usuarios/form.php';
            return;
        }
        $hash = password_hash($pass, PASSWORD_DEFAULT);
    }

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

    $id = $_POST['id'];

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
