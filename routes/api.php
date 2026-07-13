<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\CategoryController;

// Public Routes
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (Sanctum Authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth status & profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard & Analytical Reports
    Route::get('/dashboard-stats', [ReportController::class, 'dashboard']);
    Route::get('/reports/profit', [ReportController::class, 'projectProfit']);
    Route::get('/reports/expense', [ReportController::class, 'expense']);
    Route::get('/reports/income', [ReportController::class, 'income']);
    Route::get('/reports/purchase-summary', [ReportController::class, 'purchaseSummary']);

    // Application Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);

    // Project files & Custom Actions
    Route::post('/projects/{id}/files', [ProjectController::class, 'uploadFile']);
    Route::post('/expenses/{id}/approve', [ExpenseController::class, 'approve']);

    // Roles & Permissions management
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
    Route::post('/roles/{id}/permissions', [RoleController::class, 'updatePermissions']);

    // Core REST Resource Controllers
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('expenses', ExpenseController::class);
    Route::apiResource('purchases', PurchaseController::class);
    Route::apiResource('incomes', IncomeController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('categories', CategoryController::class)->except(['show']);
});
