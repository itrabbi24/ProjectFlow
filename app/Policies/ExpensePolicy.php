<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Expense;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_expenses');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->hasPermission('view_expenses');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_expenses');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->hasPermission('edit_expenses');
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->hasPermission('delete_expenses');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission('approve_expenses');
    }
}
