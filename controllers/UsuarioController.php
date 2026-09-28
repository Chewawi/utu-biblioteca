<?php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../conexion.php'; // expone $pdo

function formLogin()
{
    global $pdo;

    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['pass'] ?? '');

    $usuario = Usuario::buscarPorEmail($pdo, $email);

    if ($usuario && password_verify($pass, $usuario->getPass())) {
        $_SESSION['usuario_id'] = $usuario->getId();
        $_SESSION['rol'] = $usuario->getRol();
        header('Location: index.php');
        exit;
    } else {
        $error = "Email o contraseña incorrectos";
        require __DIR__ . '/../views/usuarios/login.php';
        return;
    }
}

function formEditarUsuario()
{
    global $pdo;
    $usuario = Usuario::buscarPorId($pdo, $_GET['id'] ?? 0);

    if (!$usuario) {
        header('Location: index.php?a=listar');
        exit;
    }
    require __DIR__ . '/../views/usuarios/form.php';
}

function editarUsuario()
{
    global $pdo;

    $id     = $_POST['id'] ?? 0;
    $rol = trim($_POST['rol'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass = trim($_POST['pass'] ?? '');
    $usuario  = Usuario::buscarPorId($pdo, $id);

    if (!$usuario) {
        header('Location: index.php?a=listar');
        exit;
    }

    if ($rol === '' || $email === '' || $pass === '') {
        $error = "Se deben de llenar todos los Espacios";
        require __DIR__ . '/../views/usuarios/form.php';
        return;
    }

    $pass = password_hash($pass, PASSWORD_DEFAULT);

    Usuario::actualizar($pdo, $id, $rol, $email, $pass);
    header('Location: index.php?a=listar');
    exit;
}

function eliminarUsuario()
{
    global $pdo;
    Usuario::eliminar($pdo, $_GET['id'] ?? 0);
    header('Location: index.php?a=listar');
    exit;
}
