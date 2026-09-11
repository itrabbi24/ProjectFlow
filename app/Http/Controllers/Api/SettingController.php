<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SettingController extends Controller
{
    protected SettingService $settingService;
    protected ActivityLogService $activityLogService;

    public function __construct(
        SettingService $settingService,
        ActivityLogService $activityLogService
    ) {
        $this->settingService = $settingService;
        $this->activityLogService = $activityLogService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = $this->settingService->getAllSettings();

        // If user doesn't have view_settings permission, only return public appearance/formatting settings
        if (!$user || (!$user->hasPermission('view_settings') && $user->role?->slug !== 'administrator')) {
            $publicKeys = ['company_name', 'currency', 'currency_symbol', 'timezone', 'date_format', 'theme'];
            $settings = array_intersect_key($settings, array_flip($publicKeys));
        }

        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        Gate::authorize('edit_settings');

        $this->settingService->updateSettings($request->all());

        $this->activityLogService->log('updated', 'System settings were updated');

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully',
            'data' => $this->settingService->getAllSettings()
        ]);
    }
}
