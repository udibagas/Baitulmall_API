<?php
use App\Models\User;
use App\Models\Person;
use App\Models\Assignment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::transaction(function() {
    $users = [
        ['name' => 'Mas Yazid', 'email' => 'masyazid@baitulmal.com'],
        ['name' => 'Idi', 'email' => 'idi@baitulmal.com']
    ];

    foreach ($users as $data) {
        $user = User::updateOrCreate(
            ['email' => $data['email']],
            ['name' => $data['name'], 'password' => Hash::make('password123')]
        );

        $person = Person::updateOrCreate(
            ['user_id' => $user->id],
            ['nama_lengkap' => $data['name'], 'status_hidup' => 'Hidup']
        );

        Assignment::updateOrCreate(
            ['person_id' => $person->id, 'structure_id' => 1, 'jabatan' => 'Ketua Umum'],
            ['tipe_sk' => 'SK Resmi', 'tanggal_mulai' => now(), 'status' => 'Aktif']
        );
        echo "Created user: {$data['email']}\n";
    }
});
