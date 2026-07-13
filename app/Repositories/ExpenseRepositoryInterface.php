<?php

namespace App\Repositories;

interface ExpenseRepositoryInterface extends BaseRepositoryInterface
{
    public function getTodayExpensesTotal(): float;
}
