<?php

namespace Database\Seeders;

use App\Models\Muzaki;
use App\Models\ZakatFitrah;
use App\Models\RT;
use App\Models\Asnaf;
use App\Models\Distribusi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZakatFitrahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clear existing Fitrah data
        ZakatFitrah::query()->delete();
        Distribusi::query()->delete();
        Muzaki::query()->delete();

        $rtMap = RT::pluck('id', 'kode')->toArray();
        $tahun = 2026;

        // 2. Create Muzakis & Payments (Penerimaan)
        // Some sample data for the new year
        $muzakis = [
            ['nama' => 'H. Ahmad Syarif', 'rt' => '01', 'jiwa' => 4, 'kg' => 10.00],
            ['nama' => 'Bp. Sunardi', 'rt' => '01', 'jiwa' => 2, 'kg' => 5.00],
            ['nama' => 'Ibu Rahayu', 'rt' => '02', 'jiwa' => 5, 'kg' => 12.50],
            ['nama' => 'Bp. Mulyono', 'rt' => '03', 'jiwa' => 3, 'kg' => 7.50],
            ['nama' => 'Hj. Siti Aminah', 'rt' => '04', 'jiwa' => 6, 'kg' => 15.00],
            ['nama' => 'Bp. Kuswoyo', 'rt' => '05', 'jiwa' => 4, 'kg' => 10.00],
            ['nama' => 'Ibu Lestari', 'rt' => '06', 'jiwa' => 3, 'kg' => 7.50],
            ['nama' => 'Bp. Bambang', 'rt' => '07', 'jiwa' => 2, 'kg' => 5.00],
            ['nama' => 'Muhammad Rizky', 'rt' => '01', 'jiwa' => 1, 'kg' => 2.50],
            ['nama' => 'Keluarga Bp. Slamet', 'rt' => '02', 'jiwa' => 4, 'kg' => 10.00],
        ];

        foreach ($muzakis as $m) {
            $muzaki = Muzaki::create([
                'rt_id' => $rtMap[$m['rt']],
                'nama' => $m['nama'],
                'jumlah_jiwa' => $m['jiwa'],
                'jumlah_beras_kg' => $m['kg'],
                'status_bayar' => 'lunas',
                'tahun' => $tahun,
                'tanggal_bayar' => now(),
            ]);

            ZakatFitrah::create([
                'muzaki_id' => $muzaki->id,
                'rt_id' => $muzaki->rt_id,
                'jumlah_jiwa' => $m['jiwa'],
                'jumlah_kg' => $m['kg'],
                'jenis_bayar' => 'beras',
                'tanggal' => now(),
                'tahun' => $tahun,
            ]);
        }

        // 3. Create some Distributions (Penyaluran)
        // Link to some existing Asnaf (Fakir/Miskin)
        $asnafs = Asnaf::whereIn('kategori', ['Fakir', 'Miskin'])->take(30)->get();
        foreach ($asnafs as $asnaf) {
            Distribusi::create([
                'asnaf_id' => $asnaf->id,
                'kategori_asnaf' => $asnaf->kategori,
                'jumlah_kg' => $asnaf->kategori === 'Fakir' ? 5.00 : 3.00,
                'tahun' => $tahun,
                'status' => 'distributed',
                'tanggal' => now(),
                'distributed_by' => 'Panitia Ramadhan 1447H',
                'catatan' => 'Penyaluran Zakat Fitrah Tahap 1'
            ]);
        }

        $this->command->info('✅ Zakat Fitrah (Muzaki and Distribution) seeded successfully.');
    }
}
