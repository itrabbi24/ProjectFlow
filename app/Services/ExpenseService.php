<?php

namespace App\Services;

use App\Repositories\ExpenseRepositoryInterface;
use App\Models\Expense;
use Illuminate\Support\Str;

class ExpenseService
{
    protected ExpenseRepositoryInterface $expenseRepo;
    protected ActivityLogService $activityLogService;

    public function __construct(ExpenseRepositoryInterface $expenseRepo, ActivityLogService $activityLogService)
    {
        $this->expenseRepo = $expenseRepo;
        $this->activityLogService = $activityLogService;
    }

    public function createExpense(array $data, $file = null): Expense
    {
        if ($file) {
            $path = $file->storeAs("expenses", Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
            $data['attachment_path'] = $path;
        }

        $expense = $this->expenseRepo->create($data);
        $expense->load('project');

        $this->activityLogService->log(
            'created',
            "Expense of {$expense->amount} added to project '{$expense->project->name}' (Category: {$expense->category})",
            $expense
        );

        return $expense;
    }

    public function updateExpense(int $id, array $data, $file = null): Expense
    {
        $expense = $this->expenseRepo->findOrFail($id);
        $expense->load('project');

        if ($file) {
            if ($expense->attachment_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($expense->attachment_path);
            }
            $path = $file->storeAs("expenses", Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
            $data['attachment_path'] = $path;
        }

        $expense->update($data);

        $this->activityLogService->log(
            'updated',
            "Expense (ID: {$expense->id}) updated on project '{$expense->project->name}'",
            $expense
        );

        return $expense;
    }

    public function updateStatus(int $id, string $status): Expense
    {
        $expense = $this->expenseRepo->findOrFail($id);
        $expense->load('project');
        
        $expense->update(['status' => $status]);

        $this->activityLogService->log(
            'updated',
            "Expense status updated to {$status} for amount {$expense->amount} on project '{$expense->project->name}'",
            $expense
        );

        return $expense;
    }

    public function deleteExpense(int $id): bool
    {
        $expense = $this->expenseRepo->findOrFail($id);
        $expense->load('project');
        
        $this->activityLogService->log('deleted', "Expense of {$expense->amount} deleted from project '{$expense->project->name}'", $expense);
        return $expense->delete();
    }
}
