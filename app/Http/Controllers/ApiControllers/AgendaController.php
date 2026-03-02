<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\OrganizationStructure;
use App\Models\Assignment;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /**
     * Create a new Agenda under an Event
     */
    public function store(Request $request)
    {
        $request->validate([
            'parent_id' => 'required|exists:organization_structures,id', // Event ID
            'nama_struktur' => 'required|string', // e.g. "Tarawih Malam 1"
            'tanggal_mulai' => 'required|date',
        ]);

        // Auto-generate code if not provided
        $parent = OrganizationStructure::find($request->parent_id);
        
        $count = OrganizationStructure::where('parent_id', $request->parent_id)->count();
        do {
            $count++;
            $kode = $parent->kode_struktur . '_AGENDA_' . $count;
        } while (OrganizationStructure::where('kode_struktur', $kode)->exists());

        $agenda = OrganizationStructure::create([
            'parent_id' => $request->parent_id,
            'nama_struktur' => $request->nama_struktur,
            'kode_struktur' => $kode,
            'tipe' => 'Agenda',
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_mulai, // Usually single day
            'is_active' => true
        ]);

        return response()->json(['success' => true, 'data' => $agenda], 201);
    }

    /**
     * Assign a person to an Agenda (Imam, Bilal, etc)
     */
    public function assignPerson(Request $request, $id)
    {
        $request->validate([
             'person_id' => 'required|exists:people,id',
             'jabatan' => 'required|string', // Imam, Bilal, Kultum
        ]);

        $agenda = OrganizationStructure::find($id);
        if (!$agenda || $agenda->tipe !== 'Agenda') {
            return response()->json(['message' => 'Invalid Agenda ID'], 404);
        }

        // Prevent duplicate assignment for same role in same agenda
        $exists = Assignment::where('structure_id', $id)
            ->where('person_id', $request->person_id)
            ->where('jabatan', $request->jabatan)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Person already assigned to this role'], 422);
        }

        $assignment = Assignment::create([
            'structure_id' => $id,
            'person_id' => $request->person_id,
            'jabatan' => $request->jabatan,
            'tipe_sk' => 'Penunjukan Langsung',
            'tanggal_mulai' => $agenda->tanggal_mulai, // Sync with agenda date
            'tanggal_selesai' => $agenda->tanggal_selesai,
            'status' => 'Aktif'
        ]);

        return response()->json(['success' => true, 'data' => $assignment]);
    }

    /**
     * Remove assignment
     */
    public function removeAssignment($assignmentId)
    {
        $assignment = Assignment::find($assignmentId);
        if (!$assignment) return response()->json(['message' => 'Not found'], 404);
        
        $assignment->delete();
        return response()->json(['success' => true]);
    }
}
