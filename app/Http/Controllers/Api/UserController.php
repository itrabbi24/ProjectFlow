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

        $query = User::with('role');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function store(UserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = $this->userRepo->create($data);
        $user->load('role');

        $this->activityLogService->log('created', "User profile created for '{$user->name}' (Username: {$user->username})", $user);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userRepo->findOrFail($id, ['role']);
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
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $user->load('role');

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
