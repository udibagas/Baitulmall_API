<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array'
        ]);

        $role = Role::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => $role
        ], 201);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|unique:roles,name,' . $id,
            'description' => 'nullable|string',
            'permissions' => 'nullable|array'
        ]);

        $role->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy($id)
    {
        $this->authorizeAdmin();

        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Simple Super Admin check.
     * In a full system, this would be a middleware or policy.
     */
    private function authorizeAdmin()
    {
        $user = Auth::user();
        
        // As defined in UserAccountSeeder, Super Admin use this email
        // We allow both 'baitulmal.com' and 'baitulmall.com' typo variants
        // AND we allow the primary developer/user:
        $allowedEmails = [
            'admin@baitulmall.com', 
            'admin@baitulmal.com',
            'fajarmaqbulkandri@gmail.com',
            'masyazid@baitulmall.com',
            'fani@baitulmall.com'
        ];
        
        $userEmail = $user ? strtolower(trim($user->email)) : '';
        
        if (!$user || !in_array($userEmail, $allowedEmails)) {
            abort(403, 'Unauthorized. Only Super Admin can perform this action.');
        }
    }
}
