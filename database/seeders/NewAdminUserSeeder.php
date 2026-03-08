<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class NewAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'masyazid@baitulmall.com',
                'name' => 'Masyazid',
                'password' => Hash::make('password123'),
            ],
            [
                'email' => 'fani@baitulmall.com',
                'name' => 'Fani',
                'password' => Hash::make('password123'),
            ],
            [
                'email' => 'lutfi@baitulmal.com',
                'name' => 'Lutfi',
                'password' => Hash::make('password123'),
            ],
            [
                'email' => 'arif@baitulmal.com',
                'name' => 'Arif',
                'password' => Hash::make('password123'),
            ],
            [
                'email' => 'pandu@baitulmal.com',
                'name' => 'Pandu',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                ]
            );
            $this->command->info('Created or updated user: ' . $userData['email']);
        }
    }
}
