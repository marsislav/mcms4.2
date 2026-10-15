<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

if (! file_exists(__DIR__.'/../vendor/autoload.php')) {
    http_response_code(500);
    exit('vendor/ missing. Run "composer install --no-dev -o" locally and upload the vendor folder.');
}

require __DIR__.'/../vendor/autoload.php';

(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
