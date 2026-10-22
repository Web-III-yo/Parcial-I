<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/helpers/functions.php';

spl_autoload_register(static function (string $class): void {
    $directories = [
        __DIR__ . '/controllers/',
        __DIR__ . '/libraries/',
        __DIR__ . '/models/',
    ];

    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

set_exception_handler(static function (Throwable $exception): void {
    error_log((string) $exception);
    http_response_code(500);
    require __DIR__ . '/views/errors/server-error.php';
});

// Control de acceso por IP temporalmente desactivado para las pruebas.
// Cuando tengas las 3 IP reales, configúralas en MYBOX_ALLOWED_IPS (separadas por comas)
// o reemplaza las provisionales en app/config/app.php y descomenta este bloque:
/*
foreach (APP_CONFIG['allowed_ips'] as $allowedIp) {
    if (filter_var($allowedIp, FILTER_VALIDATE_IP) === false) {
        throw new RuntimeException('La lista MYBOX_ALLOWED_IPS contiene una IP no válida.');
    }
}

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($clientIp, APP_CONFIG['allowed_ips'], true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Acceso denegado: esta dirección IP no está autorizada.');
}
*/

session_start();
