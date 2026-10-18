<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    $configFile = __DIR__ . '/config.example.php';
}
$GLOBALS['config'] = require $configFile;

date_default_timezone_set(config('timezone', 'America/Sao_Paulo'));

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/pricing.php';
require __DIR__ . '/relatorios.php';
require __DIR__ . '/seeds.php';
require __DIR__ . '/documentos.php';
require __DIR__ . '/crm.php';

function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['config'][$key] ?? $default;
}
