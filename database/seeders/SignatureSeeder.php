<?php

namespace Database\Seeders;

use App\Models\Signer;
use App\Models\SignatureRule;
use Illuminate\Database\Seeder;

class SignatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clear existing data
        SignatureRule::query()->delete();
        Signer::query()->delete();

        // 2. Create Master Signers
        $ketua = Signer::create([
            'nama_pejabat' => 'H. Sulaiman',
            'jabatan' => 'Ketua Umum Baitulmall'
        ]);

        $bendahara = Signer::create([
            'nama_pejabat' => 'Ahmad Bendahara',
            'jabatan' => 'Bendahara Baitulmall'
        ]);

        $ketuaTakmir = Signer::create([
            'nama_pejabat' => 'Drs. KH. Ahmad',
            'jabatan' => 'Ketua Takmir Masjid'
        ]);

        // 3. Create Default Rules for various pages
        $pages = [
            'zakat-fitrah',
            'zakat-mall',
            'sedekah',
            'asnaf',
            'mustahik',
            'muzaki',
            'distribusi',
            'santunan'
        ];

        foreach ($pages as $page) {
            SignatureRule::create([
                'page_name' => $page,
                'category_filter' => 'ALL',
                'rt_filter' => 'ALL',
                'left_signer_id' => $bendahara->id,
                'right_signer_id' => $ketua->id,
                'priority' => 0
            ]);
        }

        // 4. Specific Rule example: Asnaf often needs Takmir + Ketua
        SignatureRule::updateOrCreate(
            ['page_name' => 'asnaf', 'category_filter' => 'ALL', 'rt_filter' => 'ALL'],
            [
                'left_signer_id' => $ketuaTakmir->id,
                'right_signer_id' => $ketua->id,
                'priority' => 1
            ]
        );

        $this->command->info('✅ Signature signers and rules seeded successfully.');
    }
}
