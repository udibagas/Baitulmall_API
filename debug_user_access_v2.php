<?php

use App\Models\User;
use App\Models\Person;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'fajarmaqbulkandri@gmail.com';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "User not found: $email\n";
    exit;
}

echo "User Found:\n";
echo "ID: " . $user->id . "\n";
echo "Email: " . $user->email . "\n";

// Check Person
$person = Person::where('user_id', $user->id)->first();
if ($person) {
    echo "Person ID: " . $person->id . "\n";
    echo "Name: " . $person->name . "\n";
    
    // Check Assignments manually if relationship is missing
    $assignments = DB::table('assignments')->where('person_id', $person->id)->get();
    echo "\nAssignments (Raw DB Check):\n";
    foreach ($assignments as $assignment) {
        echo "- Role: " . $assignment->role . " (Structure ID: " . $assignment->structure_id . ")\n";
    }

} else {
    echo "No Person record associated.\n";
}
