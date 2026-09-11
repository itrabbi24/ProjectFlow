<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncomeRequest;
use App\Models\Income;
use App\Services\IncomeService;
use App\Repositories\IncomeRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class IncomeController extends Controller
{
    protected IncomeRepositoryInterface $incomeRepo;
    protected IncomeService $incomeService;

    public function __construct(
        IncomeRepositoryInterface $incomeRepo,
        IncomeService $incomeService
    ) {
        $this->incomeRepo = $incomeRepo;
        $this->incomeService = $incomeService;
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Income::class);

        $query = Income::with('project', 'client');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%");
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $incomes = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $incomes
        ]);
    }

    public function store(IncomeRequest $request): JsonResponse
    {
        Gate::authorize('create', Income::class);

        $data = $request->validated();
        $user = $request->user();

        // Enforce project assignment check
        if ($user && $user->role && $user->role->slug !== 'administrator') {
            $assignedCount = $user->assignedProjects()->count();
            if ($assignedCount > 0 && !$user->assignedProjects()->where('projects.id', $data['project_id'])->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to log income for this project.'
                ], 403);
            }
        }

        $income = $this->incomeService->createIncome(
            $data,
            $request->file('attachment')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Income logged successfully',
            'data' => $income
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $income = $this->incomeRepo->findOrFail($id, ['project', 'client']);
        Gate::authorize('view', $income);

        return response()->json([
            'status' => 'success',
            'data' => $income
        ]);
    }

    public function update(IncomeRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (isset($data['project_id']) && $user && $user->role && $user->role->slug !== 'administrator') {
            $assignedCount = $user->assignedProjects()->count();
            if ($assignedCount > 0 && !$user->assignedProjects()->where('projects.id', $data['project_id'])->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to assign income to this project.'
                ], 403);
            }
        }
        $income = $this->incomeRepo->findOrFail($id);
        Gate::authorize('update', $income);

        $updatedIncome = $this->incomeService->updateIncome(
            $id,
            $request->validated(),
            $request->file('attachment')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Income updated successfully',
            'data' => $updatedIncome
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $income = $this->incomeRepo->findOrFail($id);
        Gate::authorize('delete', $income);

        $this->incomeService->deleteIncome($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Income deleted successfully'
        ]);
    }
}
