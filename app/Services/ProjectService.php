<?php

namespace App\Services;

use App\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProjectService
{
    protected ProjectRepositoryInterface $projectRepo;
    protected ActivityLogService $activityLogService;

    public function __construct(ProjectRepositoryInterface $projectRepo, ActivityLogService $activityLogService)
    {
        $this->projectRepo = $projectRepo;
        $this->activityLogService = $activityLogService;
    }

    public function generateProjectCode(): string
    {
        $year = Carbon::now()->format('Y');
        $prefix = "PRJ-{$year}-";
        
        $lastProject = Project::where('code', 'like', "{$prefix}%")
            ->orderBy('code', 'desc')
            ->first();

        if ($lastProject) {
            $lastNum = (int) substr($lastProject->code, -4);
            $nextNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNum = '0001';
        }

        return $prefix . $nextNum;
    }

    public function createProject(array $data): Project
    {
        $data['code'] = $this->generateProjectCode();
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_map('trim', explode(',', $data['tags']));
        }
        
        $project = $this->projectRepo->create($data);
        
        $this->activityLogService->log('created', "Project '{$project->name}' was created with code {$project->code}", $project);
        
        return $project;
    }

    public function updateProject(int $id, array $data): Project
    {
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_map('trim', explode(',', $data['tags']));
        }

        $project = $this->projectRepo->findOrFail($id);
        $project->update($data);

        $this->activityLogService->log('updated', "Project '{$project->name}' was updated", $project);

        return $project;
    }

    public function deleteProject(int $id): bool
    {
        $project = $this->projectRepo->findOrFail($id);
        $this->activityLogService->log('deleted', "Project '{$project->name}' was deleted", $project);
        return $project->delete();
    }

    public function uploadFile(Project $project, $file, int $userId): ProjectFile
    {
        $filename = $file->getClientOriginalName();
        $path = $file->storeAs("projects/{$project->id}", Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
        
        $projectFile = $project->files()->create([
            'filename' => $filename,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'uploaded_by' => $userId
        ]);

        $this->activityLogService->log('updated', "Uploaded file '{$filename}' to project '{$project->name}'", $project);

        return $projectFile;
    }

    public function getProjectFinancials(Project $project): array
    {
        $budget = (float) $project->budget;

        $totalExpenses = (float) $project->expenses()->where('status', 'approved')->sum('amount');

        $totalSpent = $totalExpenses;
        $remainingBudget = $budget - $totalSpent;
        $budgetPercentage = $budget > 0 ? round(($totalSpent / $budget) * 100, 2) : 0;

        $totalIncome = (float) $project->incomes()->sum('amount');
        $actualNetProfit = $totalIncome - $totalSpent;

        return [
            'budget' => $budget,
            'total_expenses' => $totalExpenses,
            'total_spent' => $totalSpent,
            'remaining_budget' => $remainingBudget,
            'budget_percentage' => $budgetPercentage,
            'total_income' => $totalIncome,
            'actual_net_profit' => $actualNetProfit,
            'budget_alert' => $budgetPercentage > 90,
        ];
    }
}
