<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use App\Repositories\ExpenseRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ExpenseController extends Controller
{
    protected ExpenseRepositoryInterface $expenseRepo;
    protected ExpenseService $expenseService;

    public function __construct(
        ExpenseRepositoryInterface $expenseRepo,
        ExpenseService $expenseService
    ) {
        $this->expenseRepo = $expenseRepo;
        $this->expenseService = $expenseService;
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Expense::class);

        $query = Expense::with('project', 'paidBy');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $expenses = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $expenses
        ]);
    }

    public function store(ExpenseRequest $request): JsonResponse
    {
        Gate::authorize('create', Expense::class);

        $data = $request->validated();
        $user = $request->user();

        // Enforce project assignment check
        if ($user && $user->role && $user->role->slug !== 'administrator') {
            $assignedCount = $user->assignedProjects()->count();
            if ($assignedCount > 0 && !$user->assignedProjects()->where('projects.id', $data['project_id'])->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to log expenses for this project.'
                ], 403);
            }
        }

        $expense = $this->expenseService->createExpense(
            $data,
            $request->file('attachment')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Expense logged successfully',
            'data' => $expense
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $expense = $this->expenseRepo->findOrFail($id, ['project', 'paidBy']);
        Gate::authorize('view', $expense);

        return response()->json([
            'status' => 'success',
            'data' => $expense
        ]);
    }

    public function update(ExpenseRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (isset($data['project_id']) && $user && $user->role && $user->role->slug !== 'administrator') {
            $assignedCount = $user->assignedProjects()->count();
            if ($assignedCount > 0 && !$user->assignedProjects()->where('projects.id', $data['project_id'])->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to assign expenses to this project.'
                ], 403);
            }
        }

        $expense = $this->expenseRepo->findOrFail($id);
        Gate::authorize('update', $expense);

        $updatedExpense = $this->expenseService->updateExpense(
            $id,
            $request->validated(),
            $request->file('attachment')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Expense updated successfully',
            'data' => $updatedExpense
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $expense = $this->expenseRepo->findOrFail($id);
        Gate::authorize('delete', $expense);

        $this->expenseService->deleteExpense($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Expense deleted successfully'
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $expense = $this->expenseRepo->findOrFail($id);
        Gate::authorize('approve', $expense);

        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $updatedExpense = $this->expenseService->updateStatus($id, $request->input('status'));

        return response()->json([
            'status' => 'success',
            'message' => 'Expense status updated successfully',
            'data' => $updatedExpense
        ]);
    }
}
