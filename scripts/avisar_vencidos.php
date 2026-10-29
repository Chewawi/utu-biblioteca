<?php
// ============================================================
//  avisar_vencidos.php — lo mismo que el botón "Avisar vencidos",
//  pero desde la terminal (por ejemplo, para programarlo con cron):
//      php scripts/avisar_vencidos.php
// ============================================================

// Solo desde la línea de comandos: desde el navegador no hace nada.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

date_default_timezone_set('America/Montevideo'); // igual que index.php

require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../correo.php';

if (!correoConfigurado()) {
    echo "El envío de correos no está configurado (falta config/correo.php o vendor/). No se mandó nada.\n";
    exit(1);
}

$r = enviarRecordatoriosVencidos($pdo);
echo 'Recordatorios enviados: ' . $r['enviados'] . "\n";
echo 'No se pudieron enviar: ' . $r['fallidos'] . "\n";

// Código de salida 1 si alguno falló, para que cron (o quien lo corra) se entere.
exit($r['fallidos'] > 0 ? 1 : 0);
