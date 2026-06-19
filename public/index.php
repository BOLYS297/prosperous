<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Dotenv\Dotenv;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__ . '/../vendor/autoload.php';
// In local development, allow .env to override system environment variables
// so the application uses the project SMTP settings instead of Windows-level defaults.
$envPath = dirname(__DIR__);
if (file_exists($envPath . '/.env')) {
    Dotenv::createMutable($envPath)->load();
}
// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
