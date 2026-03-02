<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\OrganizationStructure;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * List all Events
     */
    public function index()
    {
        $events = OrganizationStructure::where('tipe', 'Event')
            ->orderBy('tanggal_mulai', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Show Event details and its Agendas
     */
    public function show($id)
    {
        $event = OrganizationStructure::where('id', $id)
            ->where('tipe', 'Event')
            ->first();

        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        // Fetch children (Agendas)
        $agendas = OrganizationStructure::where('parent_id', $id)
            ->where('tipe', 'Agenda')
            ->orderBy('tanggal_mulai', 'asc')
            ->with(['assignments.person']) // Eager load assignments & people
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'event' => $event,
                'agendas' => $agendas
            ]
        ]);
    }

    /**
     * Create a new Event
     */
    /**
     * Create a new Event
     */
    public function store(Request $request)
    {
        // Map Frontend fields to Backend fields
        if ($request->has('nama_event') && !$request->has('nama_struktur')) {
            $request->merge(['nama_struktur' => $request->nama_event]);
        }
        
        // Auto-generate code if missing
        if (!$request->has('kode_struktur')) {
            $timestamp = time(); // Use timestamp
            $random = rand(100, 999);
            $request->merge(['kode_struktur' => "EVT-{$timestamp}-{$random}"]);
        }

        // Default title for blank drafts
        if (!$request->has('nama_struktur') && !$request->has('nama_event')) {
             $request->merge(['nama_struktur' => 'Draft Acara Baru']);
        }

        $request->validate([
            'nama_struktur' => 'required|string', // Now effectively optional due to merge above
            'kode_struktur' => 'required|string|unique:organization_structures,kode_struktur',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'deskripsi' => 'nullable|string',
            'lokasi' => 'nullable|string',
            'status' => 'nullable|string',
            'rundown' => 'nullable', 
            'anggaran' => 'nullable',
            'pemasukan' => 'nullable',
            'checklist' => 'nullable',
            'panitia' => 'nullable'
        ]);

        $event = OrganizationStructure::create([
            'nama_struktur' => $request->nama_struktur,
            'kode_struktur' => $request->kode_struktur,
            'tipe' => 'Event',
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'deskripsi' => $request->deskripsi,
            'lokasi' => $request->lokasi,
            'status' => $request->status ?? 'Draft',
            'rundown' => $request->rundown,
            'anggaran' => $request->anggaran,
            'pemasukan' => $request->pemasukan,
            'checklist' => $request->checklist,
            'panitia' => $request->panitia,
            'is_active' => true
        ]);

        return response()->json(['success' => true, 'data' => $event], 201);
    }

    /**
     * Update an Event
     */
    public function update(Request $request, $id)
    {
        $event = OrganizationStructure::where('id', $id)
            ->where('tipe', 'Event')
            ->first();

        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        // Map Frontend fields to Backend fields
        if ($request->has('nama_event') && !$request->has('nama_struktur')) {
            $request->merge(['nama_struktur' => $request->nama_event]);
        }

        $request->validate([
            'nama_struktur' => 'sometimes|required|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'deskripsi' => 'nullable|string',
            'lokasi' => 'nullable|string',
            'status' => 'nullable|string',
            'rundown' => 'nullable',
            'anggaran' => 'nullable',
            'pemasukan' => 'nullable',
            'checklist' => 'nullable',
            'panitia' => 'nullable'
        ]);

        // Filter out null/empty values to prevent accidental data loss
        // Only update fields that are explicitly provided and not null (unless strictly intended)
        $dataToUpdate = array_filter($request->only([
            'nama_struktur', 'tanggal_mulai', 'tanggal_selesai',
            'deskripsi', 'lokasi', 'status',
            'rundown', 'anggaran', 'pemasukan', 'checklist', 'panitia'
        ]), function ($value) {
            return $value !== null;
        });

        // Use fill() and save() for model events or just update()
        $event->update($dataToUpdate);

        return response()->json(['success' => true, 'data' => $event]);
    }

    /**
     * Delete an Event
     */
    public function destroy($id)
    {
        $event = OrganizationStructure::where('id', $id)
            ->where('tipe', 'Event')
            ->first();

        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }

        $event->delete();

        return response()->json(['success' => true, 'message' => 'Event deleted']);
    }
}
