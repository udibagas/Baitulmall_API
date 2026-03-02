<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
die(json_encode([
    'status' => 'ok',
    'php' => PHP_VERSION,
    'key' => !empty(getenv('APP_KEY')),
    'storage' => is_writable('/tmp'),
    'vendor' => is_dir(__DIR__ . '/../vendor')
]));
