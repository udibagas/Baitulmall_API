<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'admin@baitulmall.com'],
            [
                'name' => 'Admin Baitulmall',
                'email' => 'admin@baitulmall.com',
                'password' => bcrypt('password'),
                'remember_token' => \Illuminate\Support\Str::random(10),
            ]
        );

        $this->call([
            RTSeeder::class,
            AsnafSeeder::class,
            SDMSeeder::class,
            SignatureSeeder::class,
            ZakatFitrahSeeder::class,
            SettingSeeder::class,
            // RequestedUsersSeeder::class, // Disabled to prevent restore of deleted users
            TransactionalDataSeeder::class,
            // UserAccountSeeder::class, // Disabled to prevent restore of deleted users
            // NewUsersSeeder::class, // Disabled to prevent restore of deleted users
        ]);
    }
}
