<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    public function delete(User $user, Quote $quote): bool
    {
        return $user->isAdmin() && $user->company_id === $quote->company_id;
    }
}
