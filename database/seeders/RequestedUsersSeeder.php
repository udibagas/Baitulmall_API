<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Person;
use App\Models\Assignment;
use App\Models\OrganizationStructure;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RequestedUsersSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // 1. Get Main Structure
            $structure = OrganizationStructure::where('kode_struktur', 'BAITULMALL_2023')->first();
            
            if (!$structure) {
                $this->command->error("Structure BAITULMALL_2023 not found. Please run SDMSeeder first.");
                return;
            }

            $password = Hash::make('password123'); // Default password

            // 2. Admin Super (1 User)
            $this->createUser(
                'Super Admin', 
                'admin@baitulmall.com', 
                $password, 
                'Ketua Umum', 
                $structure->id
            );

            // 3. Bendahara (3 Users)
            for ($i = 1; $i <= 3; $i++) {
                $this->createUser(
                    "Bendahara $i", 
                    "bendahara$i@baitulmall.com", 
                    $password, 
                    'Bendahara', 
                    $structure->id
                );
            }

            // 4. Koordinator RT (7 Users)
            for ($i = 1; $i <= 7; $i++) {
                $rtCode = str_pad($i, 2, '0', STR_PAD_LEFT); // 01, 02...
                $this->createUser(
                    "Koordinator RT $rtCode", 
                    "rt$rtCode@baitulmall.com", 
                    $password, 
                    'Koordinator RT', 
                    $structure->id,
                    "RT $rtCode" // Keterangan
                );
            }

            // 5. User Khusus
            $this->createUser(
                'Fajar Maqbul Kandri',
                'fajarmaqbulkandri@gmail.com',
                $password,
                'Admin Utama',
                $structure->id
            );
        });
    }

    private function createUser($name, $email, $password, $jabatan, $structureId, $keterangan = null)
    {
        // 1. Create User
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now()
            ]
        );

        // 2. Create Person linked to User
        $person = Person::firstOrCreate(
            ['user_id' => $user->id], // Link via user_id
            [
                'nama_lengkap' => $name,
                'jenis_kelamin' => 'L',
                'status_hidup' => 'Hidup'
            ]
        );

        // 3. Create Assignment
        Assignment::updateOrCreate(
            [
                'person_id' => $person->id,
                'structure_id' => $structureId,
                'jabatan' => $jabatan
            ],
            [
                'tipe_sk' => 'SK Resmi',
                'tanggal_mulai' => now(),
                'status' => 'Aktif',
                'keterangan' => $keterangan
            ]
        );

        $this->command->info("Created: $name ($email)");
    }
}
