<?php

namespace App\Services;

use App\Repositories\IncomeRepositoryInterface;
use App\Models\Income;
use Illuminate\Support\Str;

class IncomeService
{
    protected IncomeRepositoryInterface $incomeRepo;
    protected ActivityLogService $activityLogService;

    public function __construct(IncomeRepositoryInterface $incomeRepo, ActivityLogService $activityLogService)
    {
        $this->incomeRepo = $incomeRepo;
        $this->activityLogService = $activityLogService;
    }

    public function createIncome(array $data, $file = null): Income
    {
        if ($file) {
            $path = $file->storeAs("incomes", Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
            $data['attachment_path'] = $path;
        }

        $income = $this->incomeRepo->create($data);
        $income->load('project');

        $this->activityLogService->log(
            'created',
            "Income of {$income->amount} (Invoice: {$income->invoice_number}) received for project '{$income->project->name}'",
            $income
        );

        return $income;
    }

    public function updateIncome(int $id, array $data, $file = null): Income
    {
        $income = $this->incomeRepo->findOrFail($id);
        $income->load('project');

        if ($file) {
            if ($income->attachment_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($income->attachment_path);
            }
            $path = $file->storeAs("incomes", Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
            $data['attachment_path'] = $path;
        }

        $income->update($data);

        $this->activityLogService->log(
            'updated',
            "Income (Invoice: {$income->invoice_number}) updated on project '{$income->project->name}'",
            $income
        );

        return $income;
    }

    public function deleteIncome(int $id): bool
    {
        $income = $this->incomeRepo->findOrFail($id);
        $income->load('project');

        $this->activityLogService->log('deleted', "Income of {$income->amount} (Invoice: {$income->invoice_number}) deleted", $income);
        return $income->delete();
    }
}
