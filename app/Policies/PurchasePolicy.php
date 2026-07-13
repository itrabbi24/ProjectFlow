<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Purchase;

class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_purchases');
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->hasPermission('view_purchases');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_purchases');
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $user->hasPermission('edit_purchases');
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $user->hasPermission('delete_purchases');
    }
}
