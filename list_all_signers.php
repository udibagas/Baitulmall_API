<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$signers = App\Models\Signer::orderBy('id')->get();
foreach($signers as $s) {
    echo "ID: {$s->id} | Name: {$s->nama_pejabat} | Jabatan: {$s->jabatan}\n";
}
