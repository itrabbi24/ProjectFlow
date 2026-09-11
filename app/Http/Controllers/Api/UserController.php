<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    protected UserRepositoryInterface $userRepo;
    protected ActivityLogService $activityLogService;

    public function __construct(
        UserRepositoryInterface $userRepo,
        ActivityLogService $activityLogService
    ) {
        $this->userRepo = $userRepo;
        $this->activityLogService = $activityLogService;
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $query = User::with(['role', 'assignedProjects:id,name,code']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $users = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function options(): JsonResponse
    {
        $users = User::with('role:id,name,slug')
            ->select('id', 'name', 'username', 'email', 'role_id')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function store(UserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();
        $projectIds = $data['project_ids'] ?? null;
        unset($data['project_ids']);

        $data['password'] = Hash::make($data['password']);

        $user = $this->userRepo->create($data);
        if ($projectIds !== null) {
            $user->assignedProjects()->sync($projectIds);
        }
        $user->load(['role', 'assignedProjects:id,name,code']);

        $this->activityLogService->log('created', "User profile created for '{$user->name}' (Username: {$user->username})", $user);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userRepo->findOrFail($id, ['role', 'assignedProjects:id,name,code']);
        Gate::authorize('view', $user);

        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    public function update(UserRequest $request, int $id): JsonResponse
    {
        $user = $this->userRepo->findOrFail($id);
        Gate::authorize('update', $user);

        $data = $request->validated();
        $projectIds = $data['project_ids'] ?? null;
        unset($data['project_ids']);

        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        if ($projectIds !== null) {
            $user->assignedProjects()->sync($projectIds);
        }
        $user->load(['role', 'assignedProjects:id,name,code']);

        $this->activityLogService->log('updated', "User profile for '{$user->name}' was updated", $user);

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->userRepo->findOrFail($id);
        Gate::authorize('delete', $user);

        $this->activityLogService->log('deleted', "User profile for '{$user->name}' was deleted", $user);
        $this->userRepo->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }
}
