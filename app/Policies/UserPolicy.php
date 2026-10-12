<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Policy user: hanya Super Admin yang mengelola akun admin.
 */
class UserPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->role === Role::SuperAdmin) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->role === Role::SuperAdmin;
    }

    public function view(User $user, User $target): bool
    {
        // boleh lihat diri sendiri
        if ($user->id === $target->id) {
            return true;
        }

        return $user->role === Role::SuperAdmin;
    }

    public function create(User $user): bool
    {
        return $user->role === Role::SuperAdmin;
    }

    public function update(User $user, User $target): bool
    {
        // boleh ubah diri sendiri (profil sendiri)
        if ($user->id === $target->id) {
            return true;
        }

        return $user->role === Role::SuperAdmin;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->role === Role::SuperAdmin && $user->id !== $target->id;
    }
}
