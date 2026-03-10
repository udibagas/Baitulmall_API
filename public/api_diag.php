<?php
// api_diag.php - Debugging User 17 Role Update
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Person;
use App\Models\Assignment;
use App\Models\OrganizationStructure;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

header('Content-Type: application/json');

try {
    $userId = 17;
    $structureId = 1; // Assuming 1 exists, adjust if needed
    $jabatan = 'Ketua';

    echo json_encode([
        'step' => 'start',
        'debug_info' => 'Checking User 17'
    ]) . "\n";

    $user = User::with('person')->find($userId);
    if (!$user) {
        die(json_encode(['error' => "User $userId not found"]));
    }

    echo json_encode([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'has_person' => !!$user->person
        ]
    ]) . "\n";

    // 1. Check People Table Email Conflict
    $conflict = Person::where('email', $user->email)->where('user_id', '!=', $user->id)->first();
    if ($conflict) {
        echo json_encode(['conflict' => [
            'msg' => 'Email exists in people table but belongs to different user',
            'person_id' => $conflict->id,
            'user_id' => $conflict->user_id
        ]]) . "\n";
    }

    // 2. Try Simulate Role Update
    echo json_encode(['step' => 'simulating_person_creation']) . "\n";
    if (!$user->person) {
        try {
            $person = Person::create([
                'user_id' => $user->id,
                'nama_lengkap' => $user->name,
                'email' => $user->email,
                'jenis_kelamin' => 'L'
            ]);
            echo json_encode(['msg' => 'Person created successfully', 'id' => $person->id]) . "\n";
        } catch (\Exception $e) {
            echo json_encode(['error' => 'Person creation failed', 'msg' => $e->getMessage()]) . "\n";
        }
    }

    echo json_encode(['step' => 'simulating_assignment_update']) . "\n";
    try {
        $personId = $user->person ? $user->person->id : $person->id;
        
        // Find a valid structure
        $struct = OrganizationStructure::find($structureId) ?: OrganizationStructure::first();
        if (!$struct) {
            die(json_encode(['error' => 'No organization structure found in DB']));
        }
        
        $assignment = Assignment::updateOrCreate(
            [
                'person_id' => $personId,
                'structure_id' => $struct->id,
                'status' => 'Aktif'
            ],
            [
                'jabatan' => $jabatan,
                'tipe_sk' => 'SK Resmi',
                'tanggal_mulai' => now(),
            ]
        );
        echo json_encode(['msg' => 'Assignment success', 'id' => $assignment->id]) . "\n";
    } catch (\Exception $e) {
        echo json_encode(['error' => 'Assignment updateOrCreate failed', 'msg' => $e->getMessage()]) . "\n";
    }

} catch (\Exception $e) {
    echo json_encode(['fatal_error' => $e->getMessage()]) . "\n";
}
