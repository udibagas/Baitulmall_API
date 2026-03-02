<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrganizationStructure;
use App\Models\Person;
use App\Models\Assignment;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Event: Ramadhan 1447H
        $event = OrganizationStructure::updateOrCreate(
            ['kode_struktur' => 'RAMADHAN_1447'],
            [
                'nama_struktur' => 'Ramadhan 1447 Hijriah',
                'tipe' => 'Event',
                'tanggal_mulai' => '2026-03-01',
                'tanggal_selesai' => '2026-03-30',
                'is_active' => true
            ]
        );

        $this->command->info("Created Event: " . $event->nama_struktur);

        // 2. Create People (Dummy Staff)
        $peopleNames = ['Ustadz Ahmad', 'Ustadz Budi', 'Pak Charlie', 'Mas Dedi', 'Dik Eko', 'Haji Fajar'];
        $people = [];
        
        foreach ($peopleNames as $name) {
            // Check if exists or create
            $person = Person::firstOrCreate(
                ['nik' => '9999' . rand(1000,9999)],
                [
                    'nama_lengkap' => $name,
                    'tempat_lahir' => 'Semarang',
                    'tanggal_lahir' => '1980-01-01',
                    'jenis_kelamin' => 'L',
                    'alamat_domisili' => 'Jl. Baitulmall No. 1',
                    'no_wa' => '08123456789'
                ]
            );
            $people[] = $person;
        }

        // 3. Create Agendas (Tarawih Malam 1-5)
        for ($i = 1; $i <= 5; $i++) {
            $date = Carbon::parse('2026-03-01')->addDays($i - 1)->format('Y-m-d');
            
            $agenda = OrganizationStructure::updateOrCreate(
                ['kode_struktur' => 'RAMADHAN_1447_TARAWIH_' . $i],
                [
                    'parent_id' => $event->id,
                    'nama_struktur' => "Tarawih Malam Ke-$i",
                    'tipe' => 'Agenda',
                    'tanggal_mulai' => $date,
                    'tanggal_selesai' => $date,
                    'is_active' => true
                ]
            );

            // 4. Assign Roles (Random)
            // Imam
            Assignment::create([
                'structure_id' => $agenda->id,
                'person_id' => $people[array_rand($people)]->id,
                'jabatan' => 'Imam',
                'tipe_sk' => 'Penunjukan Langsung',
                'tanggal_mulai' => $date,
                'status' => 'Aktif'
            ]);

            // Bilal
            Assignment::create([
                'structure_id' => $agenda->id,
                'person_id' => $people[array_rand($people)]->id,
                'jabatan' => 'Bilal',
                'tipe_sk' => 'Penunjukan Langsung',
                'tanggal_mulai' => $date,
                'status' => 'Aktif'
            ]);
            
            // Penceramah (50% chance)
            if (rand(0, 1)) {
                 Assignment::create([
                    'structure_id' => $agenda->id,
                    'person_id' => $people[array_rand($people)]->id,
                    'jabatan' => 'Penceramah',
                    'tipe_sk' => 'Penunjukan Langsung',
                    'tanggal_mulai' => $date,
                    'status' => 'Aktif'
                ]);
            }
        }
        
        $this->command->info("Created 5 Agendas with Assignments.");
    }
}
