<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Policy generik untuk konten landing & admin CMS (spec 6.1).
 *
 * Dipakai untuk model-model yang aturannya sederhana:
 * Editor ke atas boleh kelola; Super Admin selalu diizinkan via before().
 *
 * Model-model: Category, Section, Competency, Program, Facility,
 * LearningStage, Achievement, Career, Partner, GalleryItem, Staff, Faq,
 * AlumniFeedback, ContactMessage, Media, User(hanya super_admin), Setting.
 */
class CmsContentPolicy
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

    public function view(User $user, mixed $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [Role::SuperAdmin, Role::Editor], true);
    }

    public function update(User $user, mixed $model): bool
    {
        return in_array($user->role, [Role::SuperAdmin, Role::Editor], true);
    }

    public function delete(User $user, mixed $model): bool
    {
        return in_array($user->role, [Role::SuperAdmin, Role::Editor], true);
    }

    public function reorder(User $user, mixed $model = null): bool
    {
        return in_array($user->role, [Role::SuperAdmin, Role::Editor], true);
    }

    public function publish(User $user, mixed $model = null): bool
    {
        return in_array($user->role, [Role::SuperAdmin, Role::Editor], true);
    }
}
