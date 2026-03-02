<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Person;
use App\Models\Assignment;
use App\Models\OrganizationStructure;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class NewUsersSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Creating new requested user accounts...');

        // 1. Ensure Roles exist with robust permissions
        $superAdminRole = Role::updateOrCreate(
            ['name' => 'Super Admin'],
            [
                'description' => 'Akses penuh ke semua sistem', 
                'permissions' => ['*']
            ]
        );

        $adminZakatRole = Role::updateOrCreate(
            ['name' => 'Admin Zakat'],
            [
                'description' => 'Akses pengelolaan Zakat, Asnaf, dan Tanda Tangan', 
                'permissions' => ['zakat.*', 'asnaf.*', 'mustahik.*', 'muzaki.*', 'signature.*', 'reports.*']
            ]
        );

        // 2. Ensure Structure exists
        $structure = OrganizationStructure::firstOrCreate(
            ['kode_struktur' => 'BAITULMALL_2023'],
            [
                'nama_struktur' => 'Pengurus Baitulmall', 
                'tipe' => 'Struktural', 
                'tanggal_mulai' => '2023-01-01'
            ]
        );

        $password = Hash::make('password123'); // Default password for new users

        $newUsers = [
            [
                'name' => 'Mas Yazid',
                'email' => 'masyazid@baitulmall.com',
                'jabatan' => 'Super Admin'
            ],
            [
                'name' => 'Fani',
                'email' => 'fani@baitulmall.com',
                'jabatan' => 'Super Admin'
            ],
            [
                'name' => 'Lutfi',
                'email' => 'lutfi@baitulmal.com',
                'jabatan' => 'Admin Zakat'
            ],
            [
                'name' => 'Nuha',
                'email' => 'nuha@baitulmall.com',
                'jabatan' => 'Admin Zakat'
            ],
        ];

        foreach ($newUsers as $u) {
            try {
                $user = User::updateOrCreate(
                    ['email' => strtolower($u['email'])],
                    [
                        'name' => $u['name'],
                        'password' => $password,
                    ]
                );

                $person = Person::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama_lengkap' => $u['name'],
                        'jenis_kelamin' => 'L',
                        'alamat_domisili' => 'Kandri',
                        'no_wa' => '08123456789'
                    ]
                );

                Assignment::updateOrCreate(
                    [
                        'person_id' => $person->id,
                        'structure_id' => $structure->id,
                        'jabatan' => $u['jabatan']
                    ],
                    [
                        'tipe_sk' => 'SK Penugasan',
                        'tanggal_mulai' => '2023-01-01',
                        'status' => 'Aktif'
                    ]
                );

                $this->command->info("✅ Created/Updated: {$u['name']} ({$u['email']}) as {$u['jabatan']}");
            } catch (\Exception $e) {
                $this->command->error("❌ FAILED for: {$u['name']} (" . $e->getMessage() . ")");
            }
        }
    }
}
