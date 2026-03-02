<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$defaults = [
    [
        'key_name' => 'nama_instansi',
        'value' => 'BAITULMALL FAJAR MAQBUL',
        'type' => 'string',
        'description' => 'Nama instansi yang muncul di kop surat laporan.'
    ],
    [
        'key_name' => 'alamat_instansi',
        'value' => 'MASJID KANDRI NO. 45, SEMARANG',
        'type' => 'string',
        'description' => 'Alamat lengkap instansi untuk kop surat.'
    ],
    [
        'key_name' => 'kontak_instansi',
        'value' => 'Telepon: 0812-3456-7890 | Email: baitulmall@fajarmaqbul.org',
        'type' => 'string',
        'description' => 'Informasi kontak (Telepon/Email) di kop surat.'
    ],
    [
        'key_name' => 'logo_url',
        'value' => '/logo-masjid.png',
        'type' => 'string',
        'description' => 'URL atau path gambar logo instansi.'
    ],
    [
        'key_name' => 'kota_instansi',
        'value' => 'Semarang',
        'type' => 'string',
        'description' => 'Kota domisili untuk tanggal surat/laporan.'
    ]
];

foreach ($defaults as $setting) {
    if (!Setting::where('key_name', $setting['key_name'])->exists()) {
        Setting::create($setting);
        echo "Created setting: {$setting['key_name']}\n";
    } else {
        echo "Setting exists: {$setting['key_name']}\n";
    }
}

echo "Seeding completed.\n";
