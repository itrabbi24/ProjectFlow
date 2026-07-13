<?php

namespace App\Services;

use App\Repositories\PurchaseRepositoryInterface;
use App\Models\Purchase;
use Illuminate\Support\Str;

class PurchaseService
{
    protected PurchaseRepositoryInterface $purchaseRepo;
    protected ActivityLogService $activityLogService;

    public function __construct(PurchaseRepositoryInterface $purchaseRepo, ActivityLogService $activityLogService)
    {
        $this->purchaseRepo = $purchaseRepo;
        $this->activityLogService = $activityLogService;
    }

    public function createPurchase(array $data, $file = null): Purchase
    {
        if ($file) {
            $path = $file->storeAs("purchases", Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
            $data['attachment_path'] = $path;
        }

        $purchase = $this->purchaseRepo->create($data);
        $purchase->load('project');

        $this->activityLogService->log(
            'created',
            "Purchase of {$purchase->amount} from '{$purchase->supplier_name}' added to project '{$purchase->project->name}'",
            $purchase
        );

        return $purchase;
    }

    public function updatePurchase(int $id, array $data, $file = null): Purchase
    {
        $purchase = $this->purchaseRepo->findOrFail($id);
        $purchase->load('project');

        if ($file) {
            if ($purchase->attachment_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($purchase->attachment_path);
            }
            $path = $file->storeAs("purchases", Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');
            $data['attachment_path'] = $path;
        }

        $purchase->update($data);

        $this->activityLogService->log(
            'updated',
            "Purchase (Invoice: {$purchase->invoice_no}) updated on project '{$purchase->project->name}'",
            $purchase
        );

        return $purchase;
    }

    public function deletePurchase(int $id): bool
    {
        $purchase = $this->purchaseRepo->findOrFail($id);
        $purchase->load('project');

        $this->activityLogService->log('deleted', "Purchase of {$purchase->amount} from '{$purchase->supplier_name}' deleted", $purchase);
        return $purchase->delete();
    }
}
