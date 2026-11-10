<?php

namespace App\Services;

use App\Models\User;

class AuthorizationService
{
    public function allows(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission);
    }
}
