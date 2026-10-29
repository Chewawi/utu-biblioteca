<?php
// ============================================================
//  correo.php
//  Envío de correos con PHPMailer. Es opcional: si falta
//  config/correo.php o la carpeta vendor/ (composer install),
//  correoConfigurado() da false y no se manda nada.
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;

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
    $texto = $saludo . "\n\n" . implode("\n\n", $parrafos) . "\n\n";
    foreach ($lista as $linea) {
        $texto .= '- ' . $linea . "\n";
    }
    return $texto . "\nBiblioteca — este es un mensaje automático.\n";
}
