<?php

namespace App\Policies;

use App\Models\RecurringInvoice;
use App\Models\User;

class RecurringInvoicePolicy
{
    public function delete(User $user, RecurringInvoice $rule): bool
    {
        return $user->isAdmin() && $user->company_id === $rule->company_id;
    }
}
