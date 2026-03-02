<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Role;
use App\Models\User;

$roles = Role::all();
echo "ROLES:\n";
foreach($roles as $role) {
    echo "ID: {$role->id}, Name: {$role->name}, Permissions: " . json_encode($role->permissions) . "\n";
}

$users = User::with('person.assignments.role')->get();
echo "\nUSERS & ASSIGNMENTS:\n";
foreach($users as $user) {
    echo "User: {$user->email}\n";
    if ($user->person && $user->person->assignments) {
        foreach($user->person->assignments as $a) {
            echo "  Assignment Jabatan: {$a->jabatan}, Role: " . ($a->role ? $a->role->name : 'N/A') . ", Status: {$a->status}\n";
        }
    }
}
