<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'email', 'phone', 'company', 'address', 'remarks'])]
class Client extends Model
{
    use SoftDeletes;

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }
}
