<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'income_date', 'client_id', 'project_id', 'invoice_number', 'category',
    'amount', 'payment_method', 'reference_number', 'remarks', 'attachment_path'
])]
class Income extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'income_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
