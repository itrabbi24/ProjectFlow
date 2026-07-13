<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use App\Services\PurchaseService;
use App\Repositories\PurchaseRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PurchaseController extends Controller
{
    protected PurchaseRepositoryInterface $purchaseRepo;
    protected PurchaseService $purchaseService;

    public function __construct(
        PurchaseRepositoryInterface $purchaseRepo,
        PurchaseService $purchaseService
    ) {
        $this->purchaseRepo = $purchaseRepo;
        $this->purchaseService = $purchaseService;
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Purchase::class);

        $query = Purchase::with('project');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                  ->orWhere('invoice_no', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%");
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $purchases = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $purchases
        ]);
    }

    public function store(PurchaseRequest $request): JsonResponse
    {
        Gate::authorize('create', Purchase::class);

        $purchase = $this->purchaseService->createPurchase(
            $request->validated(),
            $request->file('attachment')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase logged successfully',
            'data' => $purchase
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $purchase = $this->purchaseRepo->findOrFail($id, ['project']);
        Gate::authorize('view', $purchase);

        return response()->json([
            'status' => 'success',
            'data' => $purchase
        ]);
    }

    public function update(PurchaseRequest $request, int $id): JsonResponse
    {
        $purchase = $this->purchaseRepo->findOrFail($id);
        Gate::authorize('update', $purchase);

        $updatedPurchase = $this->purchaseService->updatePurchase(
            $id,
            $request->validated(),
            $request->file('attachment')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase updated successfully',
            'data' => $updatedPurchase
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $purchase = $this->purchaseRepo->findOrFail($id);
        Gate::authorize('delete', $purchase);

        $this->purchaseService->deletePurchase($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase deleted successfully'
        ]);
    }
}
