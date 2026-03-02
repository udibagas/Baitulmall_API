<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\AgendaPost;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgendaPostController extends Controller
{
    /**
     * List all posts for an event
     */
    public function index(Request $request)
    {
        $query = AgendaPost::withCount('assignments');

        if ($request->has('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('schedule_date', 'asc')->get()
        ]);
    }

    /**
     * Create a new post
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:organization_structures,id',
            'title' => 'required|string',
            'schedule_date' => 'required|date',
        ]);

        $slug = Str::slug($request->title);
        $count = AgendaPost::where('slug', $slug)->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        $post = AgendaPost::create([
            'event_id' => $request->event_id,
            'title' => $request->title,
            'slug' => $slug,
            'content' => $request->content,
            'schedule_date' => $request->schedule_date,
            'location' => $request->location,
            'status' => $request->status ?? 'draft'
        ]);

        return response()->json(['success' => true, 'data' => $post], 201);
    }

    /**
     * Show post details
     */
    public function show($id)
    {
        $post = AgendaPost::with(['assignments.person'])->find($id);
        if (!$post) return response()->json(['message' => 'Not found'], 404);

        return response()->json(['success' => true, 'data' => $post]);
    }

    /**
     * Update post
     */
    public function update(Request $request, $id)
    {
        $post = AgendaPost::find($id);
        if (!$post) return response()->json(['message' => 'Not found'], 404);

        $post->update($request->except(['slug', 'event_id'])); // Prevent changing structural data
        return response()->json(['success' => true, 'data' => $post]);
    }

    /**
     * Delete post
     */
    public function destroy($id)
    {
        $post = AgendaPost::find($id);
        if (!$post) return response()->json(['message' => 'Not found'], 404);

        $post->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Assign person to agenda post
     */
    public function assignPerson(Request $request, $id)
    {
        $request->validate([
             'person_id' => 'required|exists:people,id',
             'jabatan' => 'required|string',
        ]);

        $post = AgendaPost::find($id);
        if (!$post) return response()->json(['message' => 'Not found'], 404);

        // Check duplicate
        $exists = Assignment::where('agenda_id', $id)
            ->where('person_id', $request->person_id)
            ->where('jabatan', $request->jabatan)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Person already assigned to this role'], 422);
        }

        // Create assignment (structure_id is null or optional, we link via agenda_id)
        $assignment = Assignment::create([
            'agenda_id' => $id,
            'person_id' => $request->person_id,
            'jabatan' => $request->jabatan,
            'tipe_sk' => 'Penunjukan Langsung',
            'tanggal_mulai' => $post->schedule_date,
            'status' => 'Aktif'
        ]);

        return response()->json(['success' => true, 'data' => $assignment]);
    }
}
