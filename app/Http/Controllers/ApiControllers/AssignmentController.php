<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        // Use eager load count to fix N+1 issue
        $query = Assignment::with([
            'structure',
            'person' => function($q) {
                $q->withCount(['assignments as active_roles_count' => function($sq) {
                    $sq->where('status', 'Aktif');
                }]);
            }
        ]);

        if ($request->has('structure_id')) {
            $query->where('structure_id', $request->structure_id);
        }

        $assignments = $query->get()->map(function($a) {
            if ($a->person) {
                // Flatten the count for easier UI access
                $a->active_roles_count = $a->person->active_roles_count ?? 1;
            }
            return $a;
        });

        return response()->json([
            'success' => true,
            'data' => $assignments
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'person_id' => 'required|exists:people,id',
                'structure_id' => 'required|exists:organization_structures,id',
                'jabatan' => 'required|string',
                'tanggal_mulai' => 'required|date',
            ]);

            $assignment = Assignment::create($request->all());
            return response()->json(['success' => true, 'data' => $assignment], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $assignment = Assignment::find($id);
        if (!$assignment) return response()->json(['message' => 'Not found'], 404);

        $assignment->update($request->only(['jabatan', 'structure_id', 'status', 'no_sk', 'tanggal_mulai', 'tanggal_selesai', 'keterangan']));

        $personAttributes = $request->only(['nama', 'nama_lengkap', 'no_wa', 'alamat_domisili', 'nik', 'jenis_kelamin']);
        
        // Map 'nama' to 'nama_lengkap' if the frontend is using 'nama'
        if (isset($personAttributes['nama']) && !isset($personAttributes['nama_lengkap'])) {
            $personAttributes['nama_lengkap'] = $personAttributes['nama'];
        }

        if ($assignment->person) {
            if (!empty($personAttributes)) {
                $assignment->person->update($personAttributes);
            }
        } else if (!empty($personAttributes['nama_lengkap'])) {
            // Self-healing orphaned assignments
            $person = \App\Models\Person::create($personAttributes);
            $assignment->update(['person_id' => $person->id]);
            $assignment->setRelation('person', $person);
        }

        return response()->json([
            'success' => true, 
            'message' => 'Data pengurus berhasil diperbarui',
            'data' => $assignment->load('person', 'structure')
        ]);
    }

    public function destroy($id)
    {
        $assignment = Assignment::find($id);
        if (!$assignment) return response()->json(['message' => 'Not found'], 404);

        $assignment->delete();
        return response()->json(['success' => true, 'message' => 'Assignment deleted']);
    }

    /**
     * Legacy view helper for existing Kepengurusan UI
     * Flattens the assignments for a specific structure (e.g. Baitulmall)
     */
    public function kepengurusanView()
    {
        // Ideally we filter by structure code, e.g. BAITULMALL_2024
        // Fixed N+1 in legacy view helper
        $assignments = Assignment::with([
            'structure',
            'person' => function($q) {
                $q->withCount(['assignments as active_roles_count' => function($sq) {
                    $sq->where('status', 'Aktif');
                }]);
            }
        ])->get();

        $flattened = $assignments->map(function($a) {
            $activeCount = $a->person->active_roles_count ?? 1;

            return [
                'id' => $a->id,
                'person_id' => $a->person_id,
                'nama' => $a->person->nama_lengkap ?? 'Unknown',
                'jabatan' => $a->jabatan,
                'divisi' => $a->structure->nama_struktur ?? '-',
                'alamat' => $a->person->alamat_domisili ?? '-',
                'no_wa' => $a->person->no_wa ?? '-',
                'status' => $a->status,
                'periode_mulai' => substr($a->tanggal_mulai, 0, 4),
                'periode_selesai' => $a->tanggal_selesai ? substr($a->tanggal_selesai, 0, 4) : 'Sekarang',
                'job_desk' => $a->keterangan,
                'is_double_role' => $activeCount > 1,
                'active_roles_count' => $activeCount
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $flattened
        ]);
    }
    public function getActiveSigner(Request $request)
    {
        $validated = $request->validate([
            'kode_struktur' => 'required|string', // e.g. BAITULMALL_2023
            'jabatan' => 'required|string',       // e.g. Ketua Umum
        ]);

        $query = Assignment::with(['person', 'structure'])
            ->where('status', 'Aktif')
            ->where(function($q) {
                $q->whereNull('tanggal_selesai')
                  ->orWhere('tanggal_selesai', '>=', now());
            })
            ->whereHas('structure', function($q) use ($request) {
                $q->where('kode_struktur', $request->kode_struktur);
            })
            ->where('jabatan', $request->jabatan);

        // Optional: Filter by Authority (if filter_zakat=true)
        if ($request->has('filter_zakat') && $request->filter_zakat == 'true') {
            $query->where('kewenangan->can_sign_zakat', true);
        }

        $signer = $query->first();

        if (!$signer) {
            // Return 200 with null data (Safe Fail)
            return response()->json([
                'success' => false,
                'message' => 'Signer not found',
                'data' => null
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nama_lengkap' => $signer->person->nama_lengkap,
                'jabatan' => $signer->jabatan,
                'structure' => $signer->structure->nama_struktur,
                'no_sk' => $signer->no_sk,
                'person_id' => $signer->person_id
            ]
        ]);
    }
}
