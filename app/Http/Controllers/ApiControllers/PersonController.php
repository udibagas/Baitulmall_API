<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    public function index(Request $request)
    {
        $query = Person::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('nama_lengkap')->paginate(20)
        ]);
    }

    public function overview(Request $request)
    {
        $people = Person::with(['assignments.structure'])
            ->get()
            ->map(function($person) {
                $activeAssignments = $person->assignments->where('status', 'Aktif');
                $roleCount = $activeAssignments->count();
                
                // Burnout Score calculation
                // High level roles (Ketua, Sekretaris, Bendahara) = 10, Others = 4
                $burnoutScore = $activeAssignments->reduce(function($carry, $a) {
                    $isHighLevel = preg_match('/Ketua|Sekretaris|Bendahara/i', $a->jabatan);
                    return $carry + ($isHighLevel ? 10 : 4);
                }, 0);

                return [
                    'id' => $person->id,
                    'nama' => $person->nama_lengkap,
                    'nik' => $person->nik,
                    'no_wa' => $person->no_wa,
                    'skills' => $person->skills,
                    'role_count' => $roleCount,
                    'burnout_score' => $burnoutScore,
                    'status_burnout' => $burnoutScore > 20 ? 'Overloaded' : ($burnoutScore > 10 ? 'Busy' : 'Available'),
                    'roles' => $activeAssignments->map(fn($a) => [
                        'jabatan' => $a->jabatan,
                        'organisasi' => $a->structure->nama_struktur,
                        'kode' => $a->structure->kode_struktur
                    ])->values()
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $people
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'nik' => 'nullable|string|unique:people,nik',
            'no_wa' => 'nullable|string',
        ]);

        $person = Person::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Person created successfully',
            'data' => $person
        ], 201);
    }

    public function show($id)
    {
        $person = Person::with('assignments.structure')->find($id);
        if (!$person) return response()->json(['message' => 'Not found'], 404);
        return response()->json(['success' => true, 'data' => $person]);
    }

    public function update(Request $request, $id)
    {
        $person = Person::find($id);
        if (!$person) return response()->json(['message' => 'Not found'], 404);

        $person->update($request->all());
        return response()->json(['success' => true, 'data' => $person]);
    }

    public function destroy($id)
    {
        $person = Person::find($id);
        if (!$person) return response()->json(['message' => 'Not found'], 404);
        
        $person->delete(); // Soft delete
        return response()->json(['success' => true, 'message' => 'Person deactivated']);
    }
}
