<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Signer;
use App\Models\SignatureRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SignatureController extends Controller
{
    // --- Master Signers ---

    public function getSigners()
    {
        $signers = Signer::orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $signers]);
    }

    public function createSigner(Request $request)
    {
        $validated = $request->validate([
            'nama_pejabat' => 'required|string',
            'jabatan' => 'required|string',
            'nip' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $signer = Signer::create($validated);
        return response()->json(['success' => true, 'data' => $signer]);
    }

    public function updateSigner(Request $request, $id)
    {
        $signer = Signer::find($id);
        if (!$signer) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $validated = $request->validate([
            'nama_pejabat' => 'string',
            'jabatan' => 'string',
            'nip' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $signer->update($validated);
        return response()->json(['success' => true, 'data' => $signer]);
    }

    public function deleteSigner($id)
    {
        $signer = Signer::find($id);
        if (!$signer) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $signer->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }

    // --- Rules ---

    public function getRules()
    {
        $rules = SignatureRule::with(['leftSigner', 'rightSigner'])
            ->orderBy('priority', 'desc') // Higher priority first
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['success' => true, 'data' => $rules]);
    }

    public function createRule(Request $request)
    {
        $validated = $request->validate([
            'page_name' => 'required|string',
            'category_filter' => 'required|string',
            'rt_filter' => 'required|string',
            'left_signer_id' => 'nullable|exists:signers,id',
            'right_signer_id' => 'nullable|exists:signers,id',
            'priority' => 'integer'
        ]);

        $rule = SignatureRule::create($validated);
        // Load relations
        $rule->load(['leftSigner', 'rightSigner']);
        return response()->json(['success' => true, 'data' => $rule]);
    }

    public function updateRule(Request $request, $id)
    {
        $rule = SignatureRule::find($id);
        if (!$rule) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $validated = $request->validate([
            'page_name' => 'string',
            'category_filter' => 'string',
            'rt_filter' => 'string',
            'left_signer_id' => 'nullable|exists:signers,id',
            'right_signer_id' => 'nullable|exists:signers,id',
            'priority' => 'integer'
        ]);

        $rule->update($validated);
        // Load relations
        $rule->load(['leftSigner', 'rightSigner']);
        return response()->json(['success' => true, 'data' => $rule]);
    }

    public function deleteRule($id)
    {
        $rule = SignatureRule::find($id);
        if (!$rule) return response()->json(['success' => false, 'message' => 'Not found'], 404);
        $rule->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }

    // --- Resolution Logic ---

    public function resolveSignature(Request $request)
    {
        $page = $request->input('page');
        $category = $request->input('category', 'ALL');
        $rt = $request->input('rt', 'ALL');

        try {
            $rules = SignatureRule::where('page_name', $page)
                ->with(['leftSigner', 'rightSigner'])
                ->orderBy('priority', 'desc')
                ->get();

            $bestMatch = null;
            $matchScore = -1;

            foreach ($rules as $rule) {
                $currentScore = 0;

                if ($rule->category_filter === 'ALL') {
                    $currentScore += 1;
                } elseif ($rule->category_filter === $category) {
                    $currentScore += 10;
                } else {
                    continue;
                }

                if ($rule->rt_filter === 'ALL') {
                    $currentScore += 1;
                } elseif ($rule->rt_filter === $rt) {
                    $currentScore += 10;
                } else {
                    continue;
                }

                if ($currentScore > $matchScore) {
                    $matchScore = $currentScore;
                    $bestMatch = $rule;
                }
            }

            if ($bestMatch) {
                $left = $bestMatch->leftSigner;
                $right = $bestMatch->rightSigner;

                if (!$left) {
                    $left = $this->lookupDynamicSigner($category, $rt, 'Ketua');
                }
                if (!$right) {
                    $right = $this->lookupDynamicSigner($category, $rt, 'Bendahara');
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'left' => $left,
                        'right' => $right,
                        'rule_id' => $bestMatch->id,
                        'is_dynamic' => true
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            // Table may not exist yet — return empty result gracefully
        }

        return response()->json([
            'success' => true,
            'data' => [
                'left' => null,
                'right' => null,
                'message' => 'No matching rule found'
            ]
        ]);
    }

    /**
     * Helper to find a person in a specific role based on context
     */
    private function lookupDynamicSigner($category, $rt, $targetRole)
    {
        // Guess structure code based on category or RT
        // Optimization: In a real system, we'd have a mapping table
        $structCode = 'BAITULMALL_2023'; // Default
        if ($category === 'Amil' || $category === 'TAKMIR') $structCode = 'TAKMIR_2023';
        if ($rt !== 'ALL') $structCode = "RT_{$rt}_2023";

        $assignment = \App\Models\Assignment::with('person')
            ->where('status', 'Aktif')
            ->where('jabatan', 'like', "%{$targetRole}%")
            ->whereHas('structure', function($q) use ($structCode) {
                $q->where('kode_struktur', $structCode);
            })
            ->first();

        if ($assignment && $assignment->person) {
            return (object)[
                'nama_pejabat' => $assignment->person->nama_lengkap,
                'jabatan' => $assignment->jabatan,
                'nip' => $assignment->no_sk // Use SK as fallback for ID/NIP
            ];
        }

        return null;
    }
}
