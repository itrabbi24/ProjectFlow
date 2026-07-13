<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Income;

class IncomePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_incomes');
    }

    public function view(User $user, Income $income): bool
    {
        return $user->hasPermission('view_incomes');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_incomes');
    }

    public function update(User $user, Income $income): bool
    {
        return $user->hasPermission('edit_incomes');
    }

    public function delete(User $user, Income $income): bool
    {
        return $user->hasPermission('delete_incomes');
    }
}
