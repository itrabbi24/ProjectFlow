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

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('assign_roles');

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $slug = \Illuminate\Support\Str::slug($validated['name'], '_');
        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Role::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}_{$counter}";
            $counter++;
        }

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Role created successfully',
            'data' => $role->load('permissions')
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        Gate::authorize('assign_roles');

        $role = Role::withCount('users')->findOrFail($id);

        if (in_array($role->slug, ['administrator', 'project_manager'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'System default roles cannot be deleted.'
            ], 422);
        }

        if ($role->users_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot delete role because it is assigned to {$role->users_count} user(s)."
            ], 422);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully.'
        ]);
    }
}
