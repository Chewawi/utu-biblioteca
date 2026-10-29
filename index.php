<?php
// ============================================================
//  index.php — Punto de entrada único (Router)
//  Toda petición pasa por acá. Miramos "accion" en la URL y
//  llamamos a la función que corresponde.
//  Ejemplo: index.php?accion=listarItems -> listarItems()
// ============================================================

// La cookie de sesión no se puede leer desde JavaScript (httponly) y el
// navegador no la manda en formularios que llegan desde otros sitios (Lax).
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// Las fechas de préstamo y devolución las calcula PHP con date(), así que
// tiene que usar la hora de Uruguay (XAMPP trae otra zona en php.ini).
date_default_timezone_set('America/Montevideo');

require_once __DIR__ . '/conexion.php'; // deja $pdo listo
require_once __DIR__ . '/helpers.php';

require_once __DIR__ . '/controllers/ItemController.php';
require_once __DIR__ . '/controllers/SocioController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/PrestamoController.php';
require_once __DIR__ . '/controllers/AnaliticaController.php';

// Todo formulario POST tiene que traer el token CSRF (ver helpers.php).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
}

$accion = $_GET['accion'] ?? 'listarItems';

switch ($accion) {
    // --- Sesión ---
    case 'login':
        formLogin();
        break;
    case 'auth':
        login();
        break;
    case 'logout':
        logout();
        break;

    // --- Items (catálogo) ---
    case 'listarItems':
        listarItems();
        break;
    case 'formCrearItem':
        formCrearItem();
        break;
    case 'crearItem':
        crearItem();
        break;
    case 'formEditarItem':
        formEditarItem();
        break;
    case 'editarItem':
        editarItem();
        break;
    case 'eliminarItem':
        eliminarItem();
        break;

    // --- Socios ---
    case 'listarSocios':
        listarSocios();
        break;
    case 'formCrearSocio':
        formCrearSocio();
        break;
    case 'crearSocio':
        crearSocio();
        break;
    case 'formEditarSocio':
        formEditarSocio();
        break;
    case 'editarSocio':
        editarSocio();
        break;
    case 'eliminarSocio':
        eliminarSocio();
        break;

    // --- Usuarios ---
    case 'listarUsuarios':
        listarUsuarios();
        break;
    case 'formCrearUsuario':
        formCrearUsuario();
        break;
    case 'crearUsuario':
        crearUsuario();
        break;
    case 'formEditarUsuario':
        formEditarUsuario();
        break;
    case 'editarUsuario':
        editarUsuario();
        break;
    case 'eliminarUsuario':
        eliminarUsuario();
        break;

    // --- Préstamos ---
    case 'listarPrestamos':
        listarPrestamos();
        break;
    case 'formCrearPrestamo':
        formCrearPrestamo();
        break;
    case 'crearPrestamo':
        crearPrestamo();
        break;
    case 'devolverPrestamo':
        devolverPrestamo();
        break;

    // --- Analíticas ---
    case 'verAnaliticas':
        verAnaliticas();
        break;

    default:
        listarItems();
        break;
}
