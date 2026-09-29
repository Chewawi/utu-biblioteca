<?php
// ============================================================
//  index.php — Punto de entrada único (Router)
//  Toda petición pasa por acá. Miramos "accion" en la URL y
//  llamamos a la función que corresponde.
//  Ejemplo: index.php?accion=listarLibros -> listarLibros()
// ============================================================

session_start();

require_once __DIR__ . '/conexion.php'; // deja $pdo listo
require_once __DIR__ . '/helpers.php';

require_once __DIR__ . '/controllers/LibroController.php';
require_once __DIR__ . '/controllers/SocioController.php';
require_once __DIR__ . '/controllers/UsuarioController.php';
require_once __DIR__ . '/controllers/PrestamoController.php';

$accion = $_GET['accion'] ?? 'listarLibros';

switch ($accion) {
    // --- Sesión y registro ---
    case 'login':
        formLogin();
        break;
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'registro':
        registro();
        break;
    case 'registrar':
        registrar();
        break;

    // --- Libros ---
    case 'listarLibros':
        listarLibros();
        break;
    case 'formCrearLibro':
        formCrearLibro();
        break;
    case 'crearLibro':
        crearLibro();
        break;
    case 'formEditarLibro':
        formEditarLibro();
        break;
    case 'editarLibro':
        editarLibro();
        break;
    case 'eliminarLibro':
        eliminarLibro();
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
    case 'eliminarPrestamo':
        eliminarPrestamo();
        break;

    default:
        listarLibros();
        break;
}
