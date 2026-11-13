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
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Cerrar'></button>
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
 * Revisa el archivo de $ruta antes de guardarlo como imagen. Devuelve la
 * extensión que le corresponde (jpg, png o webp), o null si no es una imagen
 * de esos tipos o pesa más de 2 MB.
 */
function extensionDeImagen($ruta) {
    if (filesize($ruta) > 2 * 1024 * 1024) {
        return null;
    }
    // Miramos el contenido real del archivo, no la extensión del nombre (que
    // cualquiera puede cambiar). getimagesize() devuelve false si no es una imagen.
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $info = getimagesize($ruta);
    if (!$info || !isset($permitidos[$info['mime']])) {
        return null;
    }
    return $permitidos[$info['mime']];
}

/**
 * Guarda una imagen subida en uploads/ y devuelve el nombre final del archivo.
 * Si no subieron nada devuelve $porDefecto. Si el archivo no es una imagen
 * JPG, PNG o WEBP, o pesa más de 2 MB, devuelve null.
 */
function guardarImagen($archivo, $porDefecto) {
    if (empty($archivo['name'])) {
        return $porDefecto;
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $extension = extensionDeImagen($archivo['tmp_name']);
    if ($extension === null) {
        return null;
    }

    // La extensión del archivo guardado sale del contenido, no del nombre original.
    $nombre = uniqid() . '.' . $extension;
    if (!move_uploaded_file($archivo['tmp_name'], __DIR__ . '/uploads/' . $nombre)) {
        return null;
    }
    return $nombre;
}

/**
 * Descarga de Open Library la portada del libro con ISBN $isbn y la guarda en
 * uploads/, revisada igual que una imagen subida (con extensionDeImagen()).
 * Hace una sola consulta, con espera corta: el servicio admite hasta 100
 * consultas cada 5 minutos desde una misma IP.
 * Devuelve ['imagen' => nombre del archivo guardado, 'error' => ''] o, si no
 * se pudo, ['imagen' => null, 'error' => el motivo, para mostrárselo al admin].
 */
function traerPortada($isbn) {
    // default=false: si no tiene portada, Open Library responde 404 en vez de una imagen vacía.
    $curl = curl_init('https://covers.openlibrary.org/b/isbn/' . urlencode($isbn) . '-L.jpg?default=false');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, // que curl_exec() devuelva la imagen en vez de mostrarla
        CURLOPT_FOLLOWLOCATION => true, // Open Library redirige al servidor donde está la imagen
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_TIMEOUT        => 8,    // segundos: si tarda más, lo damos por fallado
        CURLOPT_USERAGENT      => 'Bibliodeko (biblioteca del curso)',
    ]);
    $datos = curl_exec($curl);
    $codigo = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if ($datos === false || $codigo === 0) {
        return ['imagen' => null, 'error' => 'No se pudo conectar con Open Library. Revisá la conexión a internet o probá más tarde.'];
    }
    if ($codigo === 404) {
        return ['imagen' => null, 'error' => 'Open Library no tiene una portada para el ISBN ' . $isbn . '.'];
    }
    if ($codigo === 429) {
        return ['imagen' => null, 'error' => 'Open Library recibió demasiadas consultas. Esperá unos minutos y volvé a probar.'];
    }
    if ($codigo !== 200) {
        return ['imagen' => null, 'error' => 'Open Library no pudo responder (error ' . $codigo . '). Probá más tarde.'];
    }

    // La pasamos a un archivo temporal para revisarla igual que una imagen subida.
    $temporal = tempnam(sys_get_temp_dir(), 'portada');
    file_put_contents($temporal, $datos);
    $extension = extensionDeImagen($temporal);
    if ($extension === null) {
        unlink($temporal);
        return ['imagen' => null, 'error' => 'Lo que mandó Open Library no es una imagen JPG, PNG o WEBP de menos de 2 MB.'];
    }

    $nombre = uniqid() . '.' . $extension;
    // copy() y no rename(): así queda con los mismos permisos que una imagen subida.
    $guardada = copy($temporal, __DIR__ . '/uploads/' . $nombre);
    unlink($temporal);
    if (!$guardada) {
        return ['imagen' => null, 'error' => 'No se pudo guardar la portada en la carpeta uploads/.'];
    }
    return ['imagen' => $nombre, 'error' => ''];
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

/** true si $email tiene formato de correo (nombre@dominio.algo). */
function emailValido($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * true si $telefono parece un teléfono: dígitos, espacios o guiones, con un +
 * opcional adelante, y entre 8 y 15 dígitos (un fijo de Uruguay tiene 8, un
 * celular 9 y con el código de país son más).
 */
function telefonoValido($telefono) {
    if (!preg_match('/^\+?[0-9 -]+$/', $telefono)) {
        return false;
    }
    $digitos = strlen(preg_replace('/[^0-9]/', '', $telefono));
    return $digitos >= 8 && $digitos <= 15;
}

/** N° de socio para mostrar: el id del socio con ceros a la izquierda (7 -> "0007"). */
function numeroSocio($id) {
    return str_pad($id, 4, '0', STR_PAD_LEFT);
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
