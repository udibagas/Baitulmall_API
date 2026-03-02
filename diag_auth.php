<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- ROLES ---\n";
$roles = \App\Models\Role::all();
foreach ($roles as $role) {
    echo "ID: {$role->id}, Name: '{$role->name}', Permissions: " . json_encode($role->permissions) . "\n";
}

echo "\n--- USERS & ASSIGNMENTS ---\n";
$users = \App\Models\User::with('person.assignments')->get();
foreach ($users as $user) {
    echo "User: {$user->name} ({$user->email})\n";
    if ($user->person && $user->person->assignments) {
        foreach ($user->person->assignments as $a) {
            echo "  - Assignment: Jabatan: '{$a->jabatan}', Status: '{$a->status}'\n";
            // Check if relationship works
            $r = \App\Models\Role::where('name', $a->jabatan)->first();
            if ($r) {
                echo "    -> Linked Role found: ID {$r->id}\n";
            } else {
                echo "    -> ERROR: No Role found for name '{$a->jabatan}'\n";
            }
        }
    } else {
        echo "  - No Person/Assignments found.\n";
    }
}
