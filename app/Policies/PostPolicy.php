<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Post;
use App\Models\User;

/**
 * Hak akses berita (spec 6.1 + 7.1).
 *
 * - Penulis: hanya berita miliknya.
 * - Editor: semua berita + terbit/turunkan.
 * - Super Admin: semua.
 */
class PostPolicy
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
        return in_array($user->role, [Role::SuperAdmin, Role::Editor, Role::Writer], true);
    }

    public function view(User $user, Post $post): bool
    {
        if ($user->role === Role::Editor) {
            return true;
        }

        return $post->author_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [Role::SuperAdmin, Role::Editor, Role::Writer], true);
    }

    public function update(User $user, Post $post): bool
    {
        if ($user->role === Role::Editor) {
            return true;
        }

        return $post->author_id === $user->id;
    }

    public function delete(User $user, Post $post): bool
    {
        if ($user->role === Role::Editor) {
            return true;
        }

        return $post->author_id === $user->id;
    }

    public function publish(User $user, ?Post $post = null): bool
    {
        return in_array($user->role, [Role::SuperAdmin, Role::Editor], true);
    }

    public function restore(User $user, Post $post): bool
    {
        return $this->delete($user, $post);
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->role === Role::SuperAdmin;
    }
}
