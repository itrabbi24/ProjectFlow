<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    public function getAllSettings(): array
    {
        return Setting::pluck('value', 'key')->toArray();
    }

    public function updateSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            // Filter out null values to maintain schema integrity, or store as empty strings
            Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        }
    }
}
