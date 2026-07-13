<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        // Require user permissions view access to assign roles
        Gate::authorize('view_users');

        $roles = Role::with('permissions')->get();
        return response()->json([
            'status' => 'success',
            'data' => $roles
        ]);
    }

    public function permissions(): JsonResponse
    {
        Gate::authorize('assign_roles');

        $permissions = Permission::all()->groupBy('category');
        return response()->json([
            'status' => 'success',
            'data' => $permissions
        ]);
    }

    public function updatePermissions(Request $request, int $id): JsonResponse
    {
        Gate::authorize('assign_roles');

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = Role::findOrFail($id);
        $role->permissions()->sync($request->input('permissions'));

        return response()->json([
            'status' => 'success',
            'message' => 'Role permissions updated successfully',
            'data' => $role->load('permissions')
        ]);
    }
}
