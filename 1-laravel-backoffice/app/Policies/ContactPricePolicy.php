<?php

namespace App\Policies;

use App\Models\ContactPrice;
use App\Models\User;

class ContactPricePolicy
{
    public function delete(User $user, ContactPrice $price): bool
    {
        return $user->isAdmin() && $user->company_id === $price->company_id;
    }
}
