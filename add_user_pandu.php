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

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

try {
    echo "Creating user pandu@baitulmall.com...\n";
    
    // Check if user exists
    $user = User::where('email', 'pandu@baitulmall.com')->first();
    if ($user) {
        echo "User already exists. Updating password...\n";
    } else {
        $user = new User();
        $user->email = 'pandu@baitulmall.com';
        $user->name = 'Pandu';
        echo "New user created.\n";
    }
    
    $user->password = Hash::make('password123');
    $user->save();
    
    echo "User pandu@baitulmall.com (password: password123) is ready.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
