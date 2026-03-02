<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ScoringService;
use App\Models\Asnaf;

$service = new ScoringService();

$cases = [
    ['name' => 'Pak Fakir A (0 Income, Numpang, 2 Jiwa)', 'data' => ['pendapatan' => 0, 'jumlah_jiwa' => 2, 'kondisi_rumah' => 'numpang']],
    ['name' => 'Pak Fakir B (400k Income, Sewa, 4 Jiwa)', 'data' => ['pendapatan' => 400000, 'jumlah_jiwa' => 4, 'kondisi_rumah' => 'sewa']],
    ['name' => 'Pak Miskin A (1.5M Income, Milik Semi, 4 Jiwa)', 'data' => ['pendapatan' => 1500000, 'jumlah_jiwa' => 4, 'kondisi_rumah' => 'milik_sendiri_semi']],
    ['name' => 'Pak Miskin B (2.1M Income, Milik Permanen, 3 Jiwa)', 'data' => ['pendapatan' => 2100000, 'jumlah_jiwa' => 3, 'kondisi_rumah' => 'milik_sendiri_permanen']],
];

echo "Current Scoring Logic Simulation:\n";
echo "---------------------------------\n";

foreach ($cases as $case) {
    $asnaf = new Asnaf($case['data']);
    $result = $service->calculateScore($asnaf);
    
    echo "Case: {$case['name']}\n";
    echo "  Total Score: {$result['total_score']}\n";
    echo "  Details:\n";
    foreach ($result['details'] as $k => $v) {
        echo "    - " . ucfirst($k) . ": {$v['points']} pts (Val: {$v['value']})\n";
    }
    echo "---------------------------------\n";
}
