<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'code', 'name', 'client_id', 'manager_id', 'start_date', 'end_date',
    'status', 'priority', 'budget', 'estimated_profit', 'description',
    'color_label', 'tags', 'progress', 'notes'
])]
class Project extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'tags' => 'array',
            'budget' => 'decimal:2',
            'estimated_profit' => 'decimal:2',
            'progress' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }
}
