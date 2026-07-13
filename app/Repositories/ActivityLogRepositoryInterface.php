<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;

interface ActivityLogRepositoryInterface extends BaseRepositoryInterface
{
    public function getLatestActivities(int $limit = 10): Collection;
}
