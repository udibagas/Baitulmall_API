<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Assignment;
use App\Models\OrganizationStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['person.assignments.structure']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20)
        ]);
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'jabatan' => 'required|string',
            'structure_id' => 'required|exists:organization_structures,id'
        ]);

        $user = User::with('person')->findOrFail($id);
        
        if (!$user->person) {
            // Create a default Person profile if it doesn't exist so we can add assignments
            $person = \App\Models\Person::create([
                'user_id' => $user->id,
                'nama_lengkap' => $user->name,
                'email' => $user->email,
                'jenis_kelamin' => 'L', // Default, can be updated later
            ]);
            $user->setRelation('person', $person);
        }

        // Update or Create Assignment
        // For simplicity in this "Edit Role" feature, we assume one main active assignment per person per structure for now
        // Or we just add a new assignment (history) and set others to inactive? 
        // Request implies "Editing Role", so we update the existing active assignment or create if none.

        $assignment = Assignment::updateOrCreate(
            [
                'person_id' => $user->person->id,
                'structure_id' => $request->structure_id,
                'status' => 'Aktif'
            ],
            [
                'jabatan' => $request->jabatan,
                'tipe_sk' => 'SK Resmi', // Default
                'tanggal_mulai' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $user->load('person.assignments.structure')
        ]);
    }

    // Optional: CRUD if needed
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'password' => 'nullable|min:6'
            ]);

            $user->name = $request->name;
            $user->email = $request->email;
            
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Remove the specified user from storage.
     */
    public function destroy($id)
    {
        try {
            $user = User::with('person')->findOrFail($id);

            DB::transaction(function() use ($user) {
                if ($user->person) {
                    // Manual delete linked assignments if not on cascade delete
                    if (method_exists($user->person, 'assignments')) {
                        $user->person->assignments()->delete();
                    }
                    $user->person->delete();
                }
                $user->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Akun pengguna dan data terkait berhasil diapus permanen.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus user: ' . $e->getMessage()
            ], 500);
        }
    }
}
