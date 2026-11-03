<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->isAdmin() && $user->company_id === $warehouse->company_id;
    }
}
