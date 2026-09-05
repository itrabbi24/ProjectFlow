<?php

namespace App\Services;

use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Carbon\Carbon;

class AuthService
{
    protected UserRepositoryInterface $userRepo;
    protected ActivityLogService $activityLogService;

    public function __construct(UserRepositoryInterface $userRepo, ActivityLogService $activityLogService)
    {
        $this->userRepo = $userRepo;
        $this->activityLogService = $activityLogService;
    }

    public function login(string $username, string $password): array
    {
        $user = $this->userRepo->findByUsername($username);

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials do not match our records.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'username' => ['Your account is currently inactive. Contact your administrator.'],
            ]);
        }

        // Update last login & activity
        $user->update([
            'last_login_at' => Carbon::now(),
            'last_seen_at' => Carbon::now(),
        ]);

        // Generate Sanctum token
        $user->load('role.permissions');
        $token = $user->createToken('auth_token')->plainTextToken;

        // Log activity
        $this->activityLogService->log('login', "User {$user->name} logged in successfully");

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
        // Since logging out resets the auth context, we log it before clearing tokens
        $this->activityLogService->log('logout', "User {$user->name} logged out");
    }
}
