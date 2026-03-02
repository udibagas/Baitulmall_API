<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SignatureRule;
use App\Models\Signer;

echo "Starting fix for Asnaf signature...\n";

// Find signers - prefer IDs 1 & 4 (from previous debug), fallback to active
$left = Signer::find(1);
$right = Signer::find(4);

if (!$left || !$right) {
    echo "Warning: Specific signers (1, 4) not found. Fetching any active signers...\n";
    $signers = Signer::where('is_active', true)->take(2)->get();
    if ($signers->count() < 2) {
        die("Error: Not enough signers in database to create a rule.\n");
    }
    $left = $signers[0];
    $right = $signers[1];
}

echo "Using Left Signer: {$left->nama_pejabat} (ID: {$left->id})\n";
echo "Using Right Signer: {$right->nama_pejabat} (ID: {$right->id})\n";

// Check if default rule exists
$exists = SignatureRule::where('page_name', 'asnaf')
    ->where('category_filter', 'ALL')
    ->where('rt_filter', 'ALL')
    ->first();

if ($exists) {
    echo "Rule already exists (ID: {$exists->id}). Updating matching signers...\n";
    $exists->update([
        'left_signer_id' => $left->id,
        'right_signer_id' => $right->id
    ]);
    echo "Rule updated.\n";
} else {
    echo "Creating new default rule for Asnaf (ALL/ALL)...\n";
    // Priority 0 ensures specifics (priority 0 + match bonuses) still win
    SignatureRule::create([
        'page_name' => 'asnaf',
        'category_filter' => 'ALL',
        'rt_filter' => 'ALL',
        'left_signer_id' => $left->id,
        'right_signer_id' => $right->id,
        'priority' => 0
    ]);
    echo "Rule created successfully.\n";
}
