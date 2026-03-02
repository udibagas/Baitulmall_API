<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rules = App\Models\SignatureRule::where('page_name', 'asnaf')
    ->with('rightSigner')
    ->get();

echo "Found " . $rules->count() . " rules for 'asnaf':\n";
foreach($rules as $rule) {
    echo "ID:{$rule->id} | Cat:{$rule->category_filter} | Right:" . ($rule->rightSigner->jabatan ?? 'None') . "\n";
}
