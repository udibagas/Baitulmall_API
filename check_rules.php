<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rules = App\Models\SignatureRule::where('page_name', 'zakat_fitrah')
    ->with('rightSigner')
    ->orderBy('id')
    ->get();

foreach($rules as $rule) {
    $right = $rule->rightSigner ? $rule->rightSigner->jabatan : 'NULL';
    echo "ID:{$rule->id} | Cat:{$rule->category_filter} | Right:{$right}\r\n";
}
