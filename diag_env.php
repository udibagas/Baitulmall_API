<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- ASSIGNMENT JABATAN DISTRINCT ---\n";
foreach (\App\Models\Assignment::distinct()->pluck('jabatan') as $j) {
    echo "Jabatan: '$j'\n";
}

echo "\n--- ROLE NAMES ---\n";
foreach (\App\Models\Role::all() as $r) {
    echo "Role Name: '{$r->name}' (Permissions: ".count($r->permissions??[]).")\n";
    if (count($r->permissions??[]) > 0) {
        echo "  - First 5: ".implode(', ', array_slice($r->permissions, 0, 5))."\n";
    }
}

echo "\n--- PERMISSION CHECK FOR ADMIN ZAKAT ---\n";
$role = \App\Models\Role::where('name', 'Admin Zakat')->first();
if ($role) {
    echo "Found role 'Admin Zakat'. Permissions array: " . json_encode($role->permissions) . "\n";
} else {
    echo "ERROR: Role 'Admin Zakat' not found!\n";
}
unlink(__FILE__);
