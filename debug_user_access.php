<?php

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'fajarmaqbulkandri@gmail.com';
$user = User::where('email', $email)->with('roles', 'assignments.structure', 'people')->first();

if (!$user) {
    echo "User not found: $email\n";
    exit;
}

echo "User Found:\n";
echo "ID: " . $user->id . "\n";
echo "Name: " . ($user->people->name ?? 'N/A') . "\n";
echo "Email: " . $user->email . "\n";

echo "\nRoles:\n";
if ($user->roles->isEmpty()) {
    echo "- No roles assigned directly.\n";
} else {
    foreach ($user->roles as $role) {
        echo "- " . $role->name . "\n";
    }
}

echo "\nAssignments:\n";
if ($user->assignments->isEmpty()) {
    echo "- No assignments found.\n";
} else {
    foreach ($user->assignments as $assignment) {
        echo "- Structure: " . ($assignment->structure->name ?? 'Unknown') . " (Role: " . ($assignment->role ?? 'N/A') . ")\n";
    }
}
