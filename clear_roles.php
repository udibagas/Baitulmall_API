<?php
require __DIR__.'/vendor/autoload.php';

// Force load .env.local for database settings
if (file_exists(__DIR__.'/.env.local')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '.env.local');
    $dotenv->load();
}

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Role;
use App\Models\Assignment;
use Illuminate\Support\Facades\DB;

try {
    echo "Starting role cleanup...\n";
    
    // Check connection
    $connection = config('database.default');
    echo "Using connection: $connection\n";

    // Delete all roles
    $count = Role::count();
    Role::query()->delete();
    echo "Deleted $count roles.\n";

    echo "Cleanup complete successfully.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
