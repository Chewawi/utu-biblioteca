<?php
// ============================================================
//  correo.php
//  Envío de correos con PHPMailer. Es opcional: si falta
//  config/correo.php o la carpeta vendor/ (composer install),
//  correoConfigurado() da false y no se manda nada.
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/models/Prestamo.php';

/** true si hay configuración de SMTP y PHPMailer está instalado. */
function correoConfigurado() {
    return is_file(__DIR__ . '/config/correo.php') && is_file(__DIR__ . '/vendor/autoload.php');
}

/**
 * Manda un correo a $para con el texto simple $texto y la versión HTML $html.
 * Devuelve true si se envió. Si falla devuelve false y deja el motivo en el
 * log de PHP (nunca en pantalla, para no mostrar datos del servidor).
 */
function enviarCorreo($para, $nombre, $asunto, $texto, $html) {
    if (!correoConfigurado()) {
        return false;
    }
    require_once __DIR__ . '/vendor/autoload.php';

    $mail = new PHPMailer(true); // true: los errores llegan como excepciones
    try {
        $config = require __DIR__ . '/config/correo.php';
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Port = $config['puerto'];
        $mail->SMTPSecure = $config['seguridad'];
        // Sin cifrado configurado, que tampoco intente pasarse a TLS por su cuenta.
        $mail->SMTPAutoTLS = $config['seguridad'] !== '';
        if ($config['usuario'] !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $config['usuario'];
            $mail->Password = $config['clave'];
        }
        $mail->Timeout = 10; // segundos: si el servidor no responde, no colgamos la página
        $mail->CharSet = PHPMailer::CHARSET_UTF8;

        $mail->setFrom($config['remitente'], $config['nombre']);
        $mail->addAddress($para, $nombre);
        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $texto;
        $mail->send();
        return true;
    } catch (Throwable $e) { // Throwable: también un error en config/correo.php
        error_log('No se pudo enviar el correo: ' . $e->getMessage());
        return false;
    }
}

/** Arma el HTML de un correo: un saludo, párrafos y una lista. Todo el texto se escapa. */
function htmlCorreo($saludo, $parrafos, $lista = []) {
    $html = '<div style="font-family: Arial, sans-serif; font-size: 15px; color: #222; max-width: 560px;">';
    $html .= '<p>' . htmlspecialchars($saludo) . '</p>';
    foreach ($parrafos as $parrafo) {
        $html .= '<p>' . htmlspecialchars($parrafo) . '</p>';
    }
    if (!empty($lista)) {
        $html .= '<ul>';
        foreach ($lista as $linea) {
            $html .= '<li>' . htmlspecialchars($linea) . '</li>';
        }
        $html .= '</ul>';
    }
    $html .= '<p style="color: #666; font-size: 13px;">Biblioteca — este es un mensaje automático.</p>';
    return $html . '</div>';
}

/** Arma la versión en texto simple del mismo correo. */
function textoCorreo($saludo, $parrafos, $lista = []) {
    $bloques = array_merge([$saludo], $parrafos);
    if (!empty($lista)) {
        $bloques[] = '- ' . implode("\n- ", $lista);
    }
    $bloques[] = 'Biblioteca — este es un mensaje automático.';
    return implode("\n\n", $bloques) . "\n";
}

/** Le avisa al socio que se registró su préstamo y hasta cuándo lo tiene. */
function enviarConfirmacionPrestamo($email, $nombre, $titulo, $fechaPrestamo, $fechaLimite) {
    $saludo = 'Hola, ' . $nombre . ':';
    $parrafos = [
        'Registramos tu préstamo de «' . $titulo . '» el ' . formatoFecha($fechaPrestamo) . '.',
        'Tenés que devolverlo hasta el ' . formatoFecha($fechaLimite) . '.',
    ];
    return enviarCorreo($email, $nombre, 'Préstamo registrado: ' . $titulo,
        textoCorreo($saludo, $parrafos), htmlCorreo($saludo, $parrafos));
}

/** Le avisa al socio que su reserva quedó lista y hasta cuándo le guardamos el ejemplar. */
function enviarAvisoReservaLista($email, $nombre, $titulo, $fechaVencimiento) {
    $saludo = 'Hola, ' . $nombre . ':';
    $parrafos = [
        'Tu reserva de «' . $titulo . '» está lista: te apartamos un ejemplar.',
        'Podés retirarlo en la biblioteca hasta el ' . formatoFecha($fechaVencimiento) . '. Después la reserva vence y el ejemplar queda libre para otro socio.',
    ];
    return enviarCorreo($email, $nombre, 'Tu reserva está lista: ' . $titulo,
        textoCorreo($saludo, $parrafos), htmlCorreo($saludo, $parrafos));
}

/**
 * Manda un recordatorio a cada socio que tenga préstamos vencidos: un solo
 * correo por socio, con todos sus ítems atrasados. Lo usan el botón
 * "Avisar vencidos" y scripts/avisar_vencidos.php.
 * Devuelve ['enviados' => cantidad, 'fallidos' => cantidad].
 */
function enviarRecordatoriosVencidos($pdo) {
    // Agrupamos por socio: [socio_id => ['nombre' => ..., 'email' => ..., 'items' => [...]]].
    $porSocio = [];
    foreach (Prestamo::vencidosConEmail($pdo) as $f) {
        $porSocio[$f['socio_id']]['nombre'] = $f['socio_nombre'];
        $porSocio[$f['socio_id']]['email'] = $f['email'];
        $porSocio[$f['socio_id']]['items'][] = '«' . $f['item_titulo'] . '»: vencía el ' . formatoFecha($f['fecha_limite']);
    }

    $resultado = ['enviados' => 0, 'fallidos' => 0];
    foreach ($porSocio as $socio) {
        $saludo = 'Hola, ' . $socio['nombre'] . ':';
        $parrafos = [count($socio['items']) === 1
            ? 'Este préstamo ya pasó su fecha límite. Por favor, devolvelo lo antes posible en la biblioteca:'
            : 'Estos préstamos ya pasaron su fecha límite. Por favor, devolvelos lo antes posible en la biblioteca:'];
        $enviado = enviarCorreo($socio['email'], $socio['nombre'], 'Recordatorio: tenés préstamos vencidos',
            textoCorreo($saludo, $parrafos, $socio['items']), htmlCorreo($saludo, $parrafos, $socio['items']));
        $resultado[$enviado ? 'enviados' : 'fallidos']++;
    }
    return $resultado;
}
