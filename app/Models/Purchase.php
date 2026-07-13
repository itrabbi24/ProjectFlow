<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'purchase_date', 'supplier_name', 'invoice_no', 'category',
    'amount', 'payment_method', 'project_id', 'remarks', 'attachment_path'
])]
class Purchase extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
