<?php

namespace App\Services;

use App\Repositories\ActivityLogRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    protected ActivityLogRepositoryInterface $activityLogRepo;

    public function __construct(ActivityLogRepositoryInterface $activityLogRepo)
    {
        $this->activityLogRepo = $activityLogRepo;
    }

    public function log(string $action, string $description, ?Model $loggable = null, ?array $properties = null): void
    {
        $this->activityLogRepo->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'loggable_type' => $loggable ? get_class($loggable) : null,
            'loggable_id' => $loggable ? $loggable->getKey() : null,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
