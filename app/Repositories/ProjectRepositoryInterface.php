<?php

namespace App\Repositories;

use App\Models\Project;

interface ProjectRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCode(string $code): ?Project;
    public function getRunningProjectsCount(): int;
    public function getCompletedProjectsCount(): int;
}
