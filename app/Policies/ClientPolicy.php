<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Client;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_clients');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->hasPermission('view_clients');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_clients');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->hasPermission('edit_clients');
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->hasPermission('delete_clients');
    }
}
