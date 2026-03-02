<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SignatureRule;

$rules = SignatureRule::where('page_name', 'asnaf')
    ->with(['leftSigner', 'rightSigner'])
    ->get();

echo $rules->toJson(JSON_PRETTY_PRINT);
