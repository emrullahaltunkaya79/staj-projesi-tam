<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function delete(User $user, Expense $expense): bool
    {
        return $user->isAdmin() && $user->company_id === $expense->company_id;
    }
}
