<?php

namespace App\Repositories;

interface IncomeRepositoryInterface extends BaseRepositoryInterface
{
    public function getTodayIncomesTotal(): float;
}
