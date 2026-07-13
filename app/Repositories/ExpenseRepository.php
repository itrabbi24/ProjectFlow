<?php

namespace App\Repositories;

use App\Models\Expense;
use Carbon\Carbon;

class ExpenseRepository extends BaseRepository implements ExpenseRepositoryInterface
{
    public function __construct(Expense $model)
    {
        parent::__construct($model);
    }

    public function getTodayExpensesTotal(): float
    {
        return (float) $this->model
            ->whereDate('expense_date', Carbon::today())
            ->where('status', 'approved')
            ->sum('amount');
    }
}
