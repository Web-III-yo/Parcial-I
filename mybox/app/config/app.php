<?php
$configuredAllowedIps = getenv('MYBOX_ALLOWED_IPS');
$allowedIps = $configuredAllowedIps === false || trim($configuredAllowedIps) === ''
    // Direcciones provisionales para pruebas locales; reemplazarlas por las 3 IP reales del laboratorio.
    ? ['127.0.0.1', '::1', '192.168.1.10']
    : array_map('trim', explode(',', $configuredAllowedIps));

define('APP_CONFIG', [
    'database' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3307',
        'name' => getenv('DB_NAME') ?: 'mybox_mvc',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') === false ? 'Yugrant@13' : getenv('DB_PASSWORD'),
    ],
    'max_upload_bytes' => 20 * 1024 * 1024,
    'allowed_ips' => $allowedIps,
    'base_url' => rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/\\'),
]);
