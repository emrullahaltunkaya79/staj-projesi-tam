<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Kullanıcı yönetimi (liste/ekleme) yalnızca yöneticilere açıktır.
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin()
            && $user->company_id === $target->company_id
            && $user->id !== $target->id;
    }
}
