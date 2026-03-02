<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrganizationStructure;
use App\Models\Person;
use App\Models\Assignment;

class SDMSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Structures - Removing is_active to see if it fixes PostgreSQL type mismatch
        $baitulmall = OrganizationStructure::updateOrCreate(
            ['kode_struktur' => 'BAITULMALL_2023'],
            [
                'nama_struktur' => 'Pengurus Baitulmall',
                'tipe' => 'Struktural',
                'tanggal_mulai' => '2023-01-01',
                'tanggal_selesai' => '2028-12-31'
            ]
        );

        $takmir = OrganizationStructure::updateOrCreate(
            ['kode_struktur' => 'TAKMIR_2023'],
            [
                'nama_struktur' => 'Pengurus Takmir Masjid',
                'tipe' => 'Struktural',
                'tanggal_mulai' => '2023-01-01'
            ]
        );

        $rw = OrganizationStructure::updateOrCreate(
            ['kode_struktur' => 'RW_01_2023'],
            [
                'nama_struktur' => 'Pengurus RW 01',
                'tipe' => 'Struktural',
                'tanggal_mulai' => '2023-01-01'
            ]
        );

        $rt = OrganizationStructure::updateOrCreate(
            ['kode_struktur' => 'RT_01_2023'],
            [
                'nama_struktur' => 'Pengurus RT 01',
                'tipe' => 'Struktural',
                'tanggal_mulai' => '2023-01-01'
            ]
        );

        // 2. Create People & Assignments
        $members = [
            [
                'nama' => 'H. Sulaiman',
                'jabatan' => 'Ketua Umum',
                'no_wa' => '081234567890',
                'alamat' => 'Jl. Merpati No. 10',
                'status' => 'Aktif',
                'structure_id' => $baitulmall->id
            ],
            [
                'nama' => 'Drs. KH. Ahmad',
                'jabatan' => 'Ketua Takmir',
                'no_wa' => '08212345678',
                'alamat' => 'Jl. Masjid Kandri',
                'status' => 'Aktif',
                'structure_id' => $takmir->id
            ],
            [
                'nama' => 'Bp. Bambang',
                'jabatan' => 'Ketua RW',
                'no_wa' => '08567891234',
                'alamat' => 'RW 01 Kandri',
                'status' => 'Aktif',
                'structure_id' => $rw->id
            ],
            [
                'nama' => 'Bp. Joko',
                'jabatan' => 'Ketua RT',
                'no_wa' => '085789012345',
                'alamat' => 'RT 01 Kandri',
                'status' => 'Aktif',
                'structure_id' => $rt->id
            ]
        ];

        foreach ($members as $m) {
            $person = Person::updateOrCreate(
                ['nama_lengkap' => $m['nama']],
                [
                    'no_wa' => $m['no_wa'],
                    'alamat_domisili' => $m['alamat'],
                    'jenis_kelamin' => 'L'
                ]
            );

            Assignment::updateOrCreate(
                [
                    'person_id' => $person->id,
                    'structure_id' => $m['structure_id'],
                    'jabatan' => $m['jabatan']
                ],
                [
                    'tipe_sk' => 'SK Resmi',
                    'tanggal_mulai' => '2023-01-01',
                    'status' => $m['status']
                ]
            );
        }
    }
}
