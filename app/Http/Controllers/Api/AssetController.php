<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Income;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Asset::with(['project', 'assignedUser', 'creator']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        $perPage = $request->input('per_page', 15);
        $assets = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $assets
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_code' => 'nullable|string|max:100|unique:assets,asset_code',
            'category' => 'required|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'project_id' => 'nullable|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:in_use,in_stock,maintenance,disposed',
            'warranty_expiry_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if (empty($validated['asset_code'])) {
            $year = now()->format('Y');
            $prefix = "AST-{$year}-";
            $lastAsset = Asset::where('asset_code', 'like', "{$prefix}%")
                ->orderBy('asset_code', 'desc')
                ->first();

            if ($lastAsset) {
                $lastNum = (int) substr($lastAsset->asset_code, -4);
                $nextNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $nextNum = '0001';
            }
            $validated['asset_code'] = $prefix . $nextNum;
        }

        if (!isset($validated['current_value'])) {
            $validated['current_value'] = $validated['purchase_cost'];
        }

        $validated['created_by'] = $request->user()->id;

        $asset = Asset::create($validated);
        $asset->load(['project', 'assignedUser', 'creator']);

        $this->activityLogService->log(
            'created',
            "Fixed Asset created: '{$asset->name}' [Code: {$asset->asset_code}, Cost: \${$asset->purchase_cost}]",
            $asset,
            $asset->toArray()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Asset created successfully',
            'data' => $asset
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $asset = Asset::with(['project', 'assignedUser', 'creator'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $asset
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'asset_code' => 'required|string|max:100|unique:assets,asset_code,' . $asset->id,
            'category' => 'required|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'project_id' => 'nullable|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:in_use,in_stock,maintenance,disposed',
            'warranty_expiry_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'disposal_date' => 'nullable|date',
            'disposal_reason' => 'nullable|string|max:100',
            'scrap_value' => 'nullable|numeric|min:0',
            'disposal_notes' => 'nullable|string',
        ]);

        $oldData = $asset->only(['name', 'status', 'assigned_to', 'current_value']);
        $asset->update($validated);
        $asset->load(['project', 'assignedUser', 'creator']);

        $this->activityLogService->log(
            'updated',
            "Fixed Asset updated: '{$asset->name}' [Code: {$asset->asset_code}]",
            $asset,
            ['old' => $oldData, 'new' => $asset->only(['name', 'status', 'assigned_to', 'current_value'])]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Asset updated successfully',
            'data' => $asset
        ]);
    }

    public function dispose(Request $request, int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'disposal_date' => 'required|date',
            'disposal_reason' => 'required|string|in:damaged,broken,obsolete,sold,lost,other',
            'scrap_value' => 'nullable|numeric|min:0',
            'disposal_notes' => 'nullable|string',
            'record_as_income' => 'nullable|boolean',
        ]);

        $scrapValue = (float) ($validated['scrap_value'] ?? 0);
        $asset->update([
            'status' => 'disposed',
            'current_value' => 0.00,
            'assigned_to' => null,
            'disposal_date' => $validated['disposal_date'],
            'disposal_reason' => $validated['disposal_reason'],
            'scrap_value' => $scrapValue,
            'disposal_notes' => $validated['disposal_notes'] ?? null,
        ]);

        // If scrap value > 0 and requested to record as income
        if (!empty($validated['record_as_income']) && $scrapValue > 0 && $asset->project_id) {
            $project = $asset->project;
            Income::create([
                'income_date' => $validated['disposal_date'],
                'client_id' => $project ? $project->client_id : null,
                'project_id' => $asset->project_id,
                'invoice_number' => 'DISP-' . $asset->asset_code,
                'category' => 'Asset Disposal / Scrap Sale',
                'amount' => $scrapValue,
                'payment_method' => 'Cash',
                'reference_number' => 'Asset: ' . $asset->asset_code,
                'remarks' => "Sale of disposed asset: {$asset->name} ({$validated['disposal_reason']})",
            ]);
        }

        $this->activityLogService->log(
            'disposed',
            "Fixed Asset disposed/written-off: '{$asset->name}' [Reason: {$validated['disposal_reason']}, Scrap Sale: \${$scrapValue}]",
            $asset,
            ['disposal_reason' => $validated['disposal_reason'], 'scrap_value' => $scrapValue]
        );

        $asset->load(['project', 'assignedUser', 'creator']);

        return response()->json([
            'status' => 'success',
            'message' => 'Asset disposed / written-off successfully',
            'data' => $asset
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $name = $asset->name;
        $code = $asset->asset_code;
        $asset->delete();

        $this->activityLogService->log(
            'deleted',
            "Fixed Asset deleted: '{$name}' [Code: {$code}]",
            $asset
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Asset deleted successfully'
        ]);
    }

    public function report(): JsonResponse
    {
        $totalAssets = Asset::count();
        $activeAssets = Asset::where('status', '!=', 'disposed')->count();
        $disposedAssets = Asset::where('status', 'disposed')->count();

        $totalPurchaseCost = (float) Asset::sum('purchase_cost');
        $activeCurrentValue = (float) Asset::where('status', '!=', 'disposed')->sum('current_value');
        $totalScrapRecovered = (float) Asset::where('status', 'disposed')->sum('scrap_value');

        // Total loss from write-offs (purchase cost of disposed - scrap recovered)
        $disposedPurchaseCost = (float) Asset::where('status', 'disposed')->sum('purchase_cost');
        $disposalLoss = max(0, $disposedPurchaseCost - $totalScrapRecovered);

        $statusBreakdown = Asset::selectRaw('status, count(*) as count, sum(purchase_cost) as total_cost, sum(current_value) as total_value')
            ->groupBy('status')
            ->get();

        $categoryBreakdown = Asset::selectRaw('category, count(*) as count, sum(purchase_cost) as total_cost, sum(current_value) as total_value')
            ->groupBy('category')
            ->get();

        $disposedList = Asset::with('project')
            ->where('status', 'disposed')
            ->latest('disposal_date')
            ->take(10)
            ->get();

        $recentAssets = Asset::with(['project', 'assignedUser'])
            ->where('status', '!=', 'disposed')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_assets' => $totalAssets,
                'active_assets' => $activeAssets,
                'disposed_assets' => $disposedAssets,
                'total_purchase_cost' => $totalPurchaseCost,
                'active_current_value' => $activeCurrentValue,
                'total_scrap_recovered' => $totalScrapRecovered,
                'disposal_loss' => $disposalLoss,
                'status_breakdown' => $statusBreakdown,
                'category_breakdown' => $categoryBreakdown,
                'disposed_list' => $disposedList,
                'recent_assets' => $recentAssets,
            ]
        ]);
    }
}
