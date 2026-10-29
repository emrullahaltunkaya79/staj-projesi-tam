<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    /**
     * Cari silme yalnızca aynı şirketin yöneticisine açıktır.
     */
    public function delete(User $user, Contact $contact): bool
    {
        return $user->isAdmin() && $user->company_id === $contact->company_id;
    }
}
