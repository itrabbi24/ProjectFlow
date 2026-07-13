<?php

namespace App\Repositories;

use App\Models\Income;
use Carbon\Carbon;

class IncomeRepository extends BaseRepository implements IncomeRepositoryInterface
{
    public function __construct(Income $model)
    {
        parent::__construct($model);
    }

    public function getTodayIncomesTotal(): float
    {
        return (float) $this->model
            ->whereDate('income_date', Carbon::today())
            ->sum('amount');
    }
}
