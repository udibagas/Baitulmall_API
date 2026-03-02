<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\API\SignatureController();
$req = new \Illuminate\Http\Request();

echo "Testing resolution for 'Amil':\n";
$req->replace(['page' => 'zakat_fitrah', 'category' => 'Amil', 'rt' => 'ALL']);
$res = $controller->resolveSignature($req);
$data = $res->getData();
if ($data->success && $data->data->rule_id == 20) {
    echo "SUCCESS: Resolved to Rule 20 (Global). Right Signer: " . ($data->data->right->jabatan ?? 'N/A') . "\n";
} else {
    echo "FAILED: Resolved to Rule " . ($data->data->rule_id ?? 'None') . "\n";
}

echo "Testing resolution for 'Fisabilillah':\n";
$req->replace(['page' => 'zakat_fitrah', 'category' => 'Fisabilillah', 'rt' => 'ALL']);
$res = $controller->resolveSignature($req);
$data = $res->getData();
if ($data->success && $data->data->rule_id == 20) {
    echo "SUCCESS: Resolved to Rule 20 (Global). Right Signer: " . ($data->data->right->jabatan ?? 'N/A') . "\n";
} else {
    echo "FAILED: Resolved to Rule " . ($data->data->rule_id ?? 'None') . "\n";
}
