<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// On Vercel: prepare writable paths BEFORE anything else
$isVercel = getenv('VERCEL') === '1' || isset($_ENV['VERCEL']);
if ($isVercel) {
    $basePath = dirname(__DIR__);
    $tmpStorage = '/tmp/storage';
    $tmpBootstrap = '/tmp/bootstrap';
    
    // Create all required directories in /tmp
    $dirs = [
        $tmpStorage,
        $tmpStorage . '/framework/sessions',
        $tmpStorage . '/framework/views',
        $tmpStorage . '/framework/cache',
        $tmpStorage . '/framework/cache/data',
        $tmpStorage . '/app/public',
        $tmpStorage . '/logs',
        $tmpBootstrap . '/cache',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }
    
    // Set cache path env vars BEFORE Laravel loads
    putenv("APP_SERVICES_CACHE={$tmpBootstrap}/cache/services.php");
    putenv("APP_PACKAGES_CACHE={$tmpBootstrap}/cache/packages.php");
    putenv("APP_CONFIG_CACHE={$tmpBootstrap}/cache/config.php");
    putenv("APP_ROUTES_CACHE={$tmpBootstrap}/cache/routes-v7.php");
    putenv("APP_EVENTS_CACHE={$tmpBootstrap}/cache/events.php");
    
    // Create empty .env in /tmp (Vercel filesystem is read-only)
    $tmpEnv = '/tmp/.env';
    if (!file_exists($tmpEnv)) {
        file_put_contents($tmpEnv, "# Vercel runtime - env vars set via dashboard\n");
    }
    
    // Tell Laravel to use /tmp/.env by setting DOTENV_PATH
    // Laravel's DetectEnvironment uses $app->environmentPath() which defaults to basePath
    // We need to override this AFTER app creation
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Override paths AFTER app is created but BEFORE handling request
if ($isVercel) {
    $app->useStoragePath('/tmp/storage');
    $app->useEnvironmentPath('/tmp'); // Point .env loading to /tmp where we created dummy .env
}

$app->handleRequest(Request::capture());
