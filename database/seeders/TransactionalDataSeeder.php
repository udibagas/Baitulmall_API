<?php

namespace Database\Seeders;

use App\Models\Sedekah;
use App\Models\ZakatMall;
use App\Models\RT;
use Illuminate\Database\Seeder;

class TransactionalDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahun = 2026;
        $rtMap = RT::pluck('id', 'kode')->toArray();

        // 1. Sedekah Data
        $sedekahData = [
            ['nama' => 'Hamba Allah', 'jumlah' => 150000, 'rt' => '01'],
            ['nama' => 'Bp. Agus', 'jumlah' => 200000, 'rt' => '02'],
            ['nama' => 'Ibu Maria', 'jumlah' => 500000, 'rt' => '03'],
            ['nama' => 'Bp. Handoko', 'jumlah' => 1000000, 'rt' => '01'],
            ['nama' => 'Kel. Bp. Syarif', 'jumlah' => 750000, 'rt' => '04'],
        ];

        foreach ($sedekahData as $s) {
            Sedekah::create([
                'rt_id' => $rtMap[$s['rt']],
                'jumlah' => $s['jumlah'],
                'jenis' => 'penerimaan',
                'tanggal' => now(),
                'tahun' => $tahun,
                'nama_donatur' => $s['nama'],
            ]);
        }

        // 2. Zakat Mal Data
        ZakatMall::create([
            'nama_muzaki' => 'Bp. H. Sulaiman',
            'rt_id' => $rtMap['01'],
            'jumlah' => 5000000,
            'kategori' => 'Tabungan',
            'tanggal' => now(),
        ]);

        $this->command->info('✅ Transactional data (Sedekah, Zakat Mal) seeded successfully.');
    }
}
