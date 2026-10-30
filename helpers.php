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

// --- Protección CSRF ---
// Cada sesión tiene un token secreto. Todo formulario POST lo manda en un campo
// oculto e index.php lo compara antes de hacer nada: un formulario armado en
// otro sitio no conoce el token, así que se rechaza.

/** Campo oculto con el token. Va dentro de cada <form method="POST">. */
function campoCsrf() {
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars($_SESSION['csrf']) . '">';
}

/** Corta el pedido si el token que llegó no es el de la sesión. */
function verificarCsrf() {
    $enviado = $_POST['csrf'] ?? '';
    if (!isset($_SESSION['csrf']) || !is_string($enviado) || !hash_equals($_SESSION['csrf'], $enviado)) {
        flash('danger', 'El formulario venció o no es válido. Volvé a intentarlo.');
        header('Location: index.php');
        exit;
    }
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

// Imágenes genéricas de uploads/ para quien no subió la suya. Las comparten
// muchos registros, así que nunca se borran. Para cambiarlas alcanza con
// reemplazar el archivo (tienen que coincidir con los DEFAULT de database.sql).
const PORTADA_POR_DEFECTO = 'default.png';
const AVATAR_POR_DEFECTO  = 'avatar.png';

/**
 * Guarda una imagen subida en uploads/ y devuelve el nombre final del archivo.
 * Si no subieron nada devuelve $porDefecto. Si el archivo no es una imagen
 * JPG, PNG o WEBP, o pesa más de 2 MB, devuelve null.
 */
function guardarImagen($archivo, $porDefecto) {
    if (empty($archivo['name'])) {
        return $porDefecto;
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

/** Borra una imagen de uploads/, salvo las genéricas. */
function borrarImagen($nombre) {
    $ruta = __DIR__ . '/uploads/' . basename($nombre);
    if (!in_array($nombre, [PORTADA_POR_DEFECTO, AVATAR_POR_DEFECTO]) && is_file($ruta)) {
        unlink($ruta);
    }
}

/**
 * Calcula la paginación de un listado con $total resultados y $porPagina por
 * página (0 = todos, sin paginar). Si $pagina está fuera de rango la corrige:
 * a 1 si es menor, a la última si es mayor.
 * Devuelve la página corregida, la cantidad de páginas y el LIMIT para
 * agregarle al SQL ('' si no se pagina). LIMIT y OFFSET son enteros que
 * calculamos acá (no texto del usuario), por eso pueden ir directo en el SQL.
 */
function calcularPaginacion($total, $pagina, $porPagina) {
    $porPagina = (int) $porPagina;
    if ($porPagina <= 0) {
        return ['pagina' => 1, 'paginas' => 1, 'limite' => ''];
    }
    $paginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min(max(1, (int) $pagina), $paginas);
    return [
        'pagina'  => $pagina,
        'paginas' => $paginas,
        'limite'  => ' LIMIT ' . $porPagina . ' OFFSET ' . ($pagina - 1) * $porPagina,
    ];
}

/** true si $fecha es una fecha real con formato AAAA-MM-DD. */
function fechaValida($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

/** Fecha de la base (AAAA-MM-DD) como día/mes/año: 27/10/2026. Si está vacía, un guion largo. */
function formatoFecha($fecha) {
    if (empty($fecha)) {
        return '—';
    }
    return date('d/m/Y', strtotime($fecha));
}

/** Texto de un período (fechas AAAA-MM-DD, '' = sin límite): "Del 01/10/2026 al 29/10/2026", "Todo el historial"... */
function textoPeriodo($desde, $hasta) {
    if ($desde !== '' && $hasta !== '') {
        return 'Del ' . formatoFecha($desde) . ' al ' . formatoFecha($hasta);
    }
    if ($desde !== '') {
        return 'Desde el ' . formatoFecha($desde);
    }
    if ($hasta !== '') {
        return 'Hasta el ' . formatoFecha($hasta);
    }
    return 'Todo el historial';
}

/** URL de la página $numero del listado actual: conserva todo lo que hay en la URL (filtros incluidos). */
function urlPagina($numero) {
    return 'index.php?' . http_build_query(array_merge($_GET, ['p' => $numero]));
}

/**
 * Un campo oculto por cada valor de $valores ([nombre => valor]) que no esté
 * vacío. Va dentro de un formulario POST de un listado (devolver, reservar,
 * cancelar...) para volver después al listado con sus filtros y su página.
 */
function camposOcultos($valores) {
    $html = '';
    foreach ($valores as $nombre => $valor) {
        if ($valor !== '') {
            $html .= '<input type="hidden" name="' . htmlspecialchars($nombre) . '" value="' . htmlspecialchars($valor) . '">';
        }
    }
    return $html;
}

/**
 * URL de un listado con los parámetros $base ([nombre => valor]) cambiados por
 * $cambios. Saca los vacíos, para que la URL quede corta, y no lleva la página:
 * al cambiar un filtro se vuelve a la 1.
 */
function urlFiltros($base, $cambios = []) {
    $parametros = array_filter(array_merge($base, $cambios), function ($valor) {
        return $valor !== '';
    });
    return 'index.php?' . http_build_query($parametros);
}
