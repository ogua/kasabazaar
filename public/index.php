<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Env;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// This app and the Kasabazaar backend share one Apache/mod_php instance on
// port 7000 (kmarket.com and shipping.com vhosts). putenv() writes to the
// OS process environment, which is shared across vhosts on threaded SAPIs
// like mod_php — so it can leak this app's env vars into a sibling vhost's
// request. Disabling it keeps env vars scoped to $_ENV/$_SERVER per request.
Env::disablePutenv();

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
