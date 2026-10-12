<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Comment;
use App\Models\User;

/**
 * Moderasi komentar (spec 7.4). Editor ke atas.
 */
class CommentPolicy
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
        return in_array($user->role, [Role::SuperAdmin, Role::Editor], true);
    }

    public function view(User $user, Comment $comment): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $this->viewAny($user);
    }

    public function reply(User $user, Comment $comment): bool
    {
        return $this->viewAny($user);
    }
}
