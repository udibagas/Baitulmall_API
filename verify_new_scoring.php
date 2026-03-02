<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ScoringService;
use App\Models\Asnaf;

$service = new ScoringService();

$scenarios = [
    [
        'name' => 'Scenario 1: Fakir Maksimal',
        'data' => [
            'pendapatan' => 450000, // <= 500k -> 60 pts
            'status_rumah_detail' => 'numpang', // 30 pts
            'kondisi_bangunan' => 'tidak_permanen', // 10 pts
            'fasilitas_dasar' => 'keduanya_terbatas', // 10 pts
            'jumlah_jiwa' => 5 // Ignored
        ],
        'expected' => 110
    ],
    [
        'name' => 'Scenario 2: Miskin Minimal',
        'data' => [
            'pendapatan' => 1200000, // 1M-1.5M -> 30 pts
            'status_rumah_detail' => 'sewa', // 20 pts
            'kondisi_bangunan' => 'semi_permanen', // 5 pts
            'fasilitas_dasar' => 'salah_satu_terbatas', // 5 pts
             'jumlah_jiwa' => 4 // Ignored
        ],
        'expected' => 60 // 30 + 30
    ],
    [
        'name' => 'Scenario 3: Orang Kaya',
        'data' => [
            'pendapatan' => 3500000, // > 3M -> 0 pts
            'status_rumah_detail' => 'milik_layak', // 0 pts
            'kondisi_bangunan' => 'permanen_baik', // 0 pts
            'fasilitas_dasar' => 'layak', // 0 pts
             'jumlah_jiwa' => 2 // Ignored
        ],
        'expected' => 0
    ],
    [
         'name' => 'Scenario 4: Perbatasan Fakir',
         'data' => [
             'pendapatan' => 900000, // 500k-1M -> 45 pts
             'status_rumah_detail' => 'sewa', // 20 pts
             'kondisi_bangunan' => 'tidak_permanen', // 10 pts
             'fasilitas_dasar' => 'salah_satu_terbatas' // 5 pts
         ],
         // Total: 45 + 35 = 80 (Fakir Threshold)
         'expected' => 80
     ]
];

echo "Verifying New Point-Based Scoring System...\n\n";

foreach ($scenarios as $case) {
    $asnaf = new Asnaf($case['data']);
    $result = $service->calculateScore($asnaf);
    $total = $result['total_score'];
    
    echo "{$case['name']}\n";
    echo "  Expected: {$case['expected']} | Actual: {$total}\n";
    
    if ($total == $case['expected']) {
        echo "  [PASS]\n";
    } else {
        echo "  [FAIL] Difference: " . ($total - $case['expected']) . "\n";
        print_r($result['details']);
    }
    echo "---------------------------------\n";
}
