<?php
// verify_custom_scoring.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Asnaf;
use App\Services\ScoringService;

$service = new ScoringService();

echo "Running Scoring Verification for Fisabilillah & Amil...\n\n";

// TEST 1: Fisabilillah with all criteria
$asnaf1 = new Asnaf([
    'kategori' => 'Fisabilillah',
    'custom_criteria' => [
        'mengajar_ngaji' => true,
        'mengajar_madrasah' => true,
        'imam_masjid' => true
    ]
]);
$result1 = $service->calculateScore($asnaf1);
echo "TEST 1: Fisabilillah (All Criteria - Expect 100)\n";
echo "Score: " . $result1['total_score'] . "\nDetails: " . json_encode($result1['details']) . "\n";
echo ($result1['total_score'] === 100 ? "PASS" : "FAIL") . "\n\n";

// TEST 2: Amil with 2 criteria (Pengurus Zakat & Kotak Sedekah)
$asnaf2 = new Asnaf([
    'kategori' => 'Amil',
    'custom_criteria' => [
        'pengurus_zakat' => true,
        'pengurus_kotak_sedekah' => true,
        'penyalur_bantuan' => false
    ]
]);
$result2 = $service->calculateScore($asnaf2);
echo "TEST 2: Amil (2 Criteria - Expect 70)\n";
echo "Score: " . $result2['total_score'] . "\nDetails: " . json_encode($result2['details']) . "\n";
echo ($result2['total_score'] === 70 ? "PASS" : "FAIL") . "\n\n";

// TEST 3: Fakir (Old logic should still work)
$asnaf3 = new Asnaf([
    'kategori' => 'Fakir',
    'pendapatan' => 400000,
    'status_rumah_detail' => 'numpang',
    'kondisi_bangunan' => 'tidak_permanen',
    'fasilitas_dasar' => 'keduanya_terbatas'
]);
$result3 = $service->calculateScore($asnaf3);
echo "TEST 3: Fakir (Old Logic - Expect High Score)\n";
echo "Score: " . $result3['total_score'] . "\n";
echo ($result3['total_score'] > 0 ? "PASS (Logic Preserved)" : "FAIL") . "\n";
