<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $users = DB::table('users')
        ->leftJoin('people', 'users.id', '=', 'people.user_id')
        ->leftJoin('assignments', 'people.id', '=', 'assignments.person_id')
        ->select('users.email', 'users.name', 'assignments.jabatan')
        ->get();

    foreach ($users as $u) {
        echo "Email: {$u->email} | Nama: {$u->name} | Jabatan: " . ($u->jabatan ?? 'N/A') . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
