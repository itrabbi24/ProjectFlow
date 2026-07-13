<?php

namespace App\Repositories;

use App\Models\Project;

class ProjectRepository extends BaseRepository implements ProjectRepositoryInterface
{
    public function __construct(Project $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code): ?Project
    {
        return $this->model->where('code', $code)->first();
    }

    public function getRunningProjectsCount(): int
    {
        return $this->model->where('status', 'running')->count();
    }

    public function getCompletedProjectsCount(): int
    {
        return $this->model->where('status', 'completed')->count();
    }
}
