<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rule = App\Models\SignatureRule::find(20);
if ($rule) {
    $right = $rule->rightSigner;
    echo "Rule 20 Right Signer:\n";
    echo "ID: " . ($right ? $right->id : 'NULL') . "\n";
    echo "Name: " . ($right ? $right->nama_pejabat : 'NULL') . "\n";
    echo "Jabatan: " . ($right ? $right->jabatan : 'NULL') . "\n";
} else {
    echo "Rule 20 NOT FOUND\n";
}
