<?php
/**
 * Hostinger entry point — upload to /public_html/index.php
 *
 * Bootstraps Laravel from /public_html/backend/ instead of the default
 * /backend/public/ structure. Achieved via $_ENV['APP_BASE_PATH'] which
 * Laravel's bootstrap/app.php reads when constructing the Application.
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Tell Laravel the framework lives in /public_html/backend
$_ENV['APP_BASE_PATH'] = __DIR__ . '/backend';

// Maintenance mode (looks inside /public_html/backend/storage)
if (file_exists($maintenance = __DIR__ . '/backend/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer autoload from /public_html/backend/vendor
require __DIR__ . '/backend/vendor/autoload.php';

// Bootstrap the framework — picks up APP_BASE_PATH set above
$app = require_once __DIR__ . '/backend/bootstrap/app.php';

// Pin the public_path() helper to this directory (where assets/ + uploads/ live)
$app->usePublicPath(__DIR__);

// Handle the incoming request
$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
