<?php

namespace Database\Seeders;

use App\Models\RT;
use Illuminate\Database\Seeder;

class RTSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rts = [
            [
                'kode' => '01',
                'rw' => '01',
                'ketua' => 'Ketua RT 01',
                'latitude' => -7.042583,
                'longitude' => 110.352222,
            ],
            [
                'kode' => '02',
                'rw' => '01',
                'ketua' => 'Ketua RT 02',
                'latitude' => -7.042583,
                'longitude' => 110.351222,
            ],
            [
                'kode' => '03',
                'rw' => '01',
                'ketua' => 'Ketua RT 03',
                'latitude' => -7.041583,
                'longitude' => 110.351222,
            ],
            [
                'kode' => '04',
                'rw' => '01',
                'ketua' => 'Ketua RT 04',
                'latitude' => -7.041583,
                'longitude' => 110.352222,
            ],
            [
                'kode' => '05',
                'rw' => '01',
                'ketua' => 'Ketua RT 05',
                'latitude' => -7.043083,
                'longitude' => 110.351722,
            ],
            [
                'kode' => '06',
                'rw' => '01',
                'ketua' => 'Ketua RT 06',
                'latitude' => -7.042083,
                'longitude' => 110.352722,
            ],
            [
                'kode' => '07',
                'rw' => '01',
                'ketua' => 'Ketua RT 07',
                'latitude' => -7.041083,
                'longitude' => 110.351722,
            ],
        ];

        foreach ($rts as $rt) {
            RT::updateOrCreate(['kode' => $rt['kode']], $rt);
        }

        $this->command->info('✅ Successfully seeded 7 RTs for Desa Kandri, RW 01');
    }
}
