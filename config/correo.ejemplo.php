<?php
// ============================================================
//  Configuración del correo (ejemplo)
//  Copiá este archivo como correo.php, en esta misma carpeta, y
//  completá los datos de tu servidor SMTP. correo.php no se sube
//  al repo porque lleva la contraseña.
//  Sin correo.php la app funciona igual: solo no manda correos.
// ============================================================

return [
    'host'      => 'smtp.ejemplo.com',
    'puerto'    => 587,
    'seguridad' => '',     // '' = sin cifrar, para un SMTP de prueba local. Para un SMTP real: 'tls' (puerto 587) o 'ssl' (puerto 465)
    'usuario'   => '',     // vacío = el servidor no pide usuario
    'clave'     => '',
    'remitente' => 'biblioteca@ejemplo.com',
    'nombre'    => 'Biblioteca',
];
