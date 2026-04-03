<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\TerminableInterface;

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$staticTarget = __DIR__.$requestPath;

if ($requestPath !== '/' && is_file($staticTarget)) {
    return false;
}

require dirname(__DIR__).'/vendor/autoload.php';

$appEnv = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? (getenv('APP_ENV') ?: 'e2e');
$appDebug = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? (getenv('APP_DEBUG') ?: '1');

$_SERVER['APP_ENV'] = (string) $appEnv;
$_SERVER['APP_DEBUG'] = (string) $appDebug;

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$app = require __DIR__.'/index.php';
$kernel = $app([
    'APP_ENV' => $_SERVER['APP_ENV'],
    'APP_DEBUG' => $_SERVER['APP_DEBUG'],
]);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

if ($kernel instanceof TerminableInterface) {
    $kernel->terminate($request, $response);
}
