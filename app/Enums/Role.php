<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Editor = 'editor';
    case Writer = 'writer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Editor => 'Editor',
            self::Writer => 'Penulis',
        };
    }

    /**
     * Izin yang melekat pada role (tanpa package tambahan, dipakai Gate).
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                'posts.*', 'categories.*', 'tags.*', 'comments.*', 'sections.*',
                'competencies.*', 'programs.*', 'facilities.*', 'stages.*',
                'achievements.*', 'careers.*', 'partners.*', 'gallery.*',
                'staff.*', 'faqs.*', 'feedback.*', 'messages.*', 'media.*',
                'reports.*', 'settings.*', 'users.*', 'backups.*', 'logs.*',
                'seo.analyze', 'dashboard.view',
            ],
            self::Editor => [
                'posts.*', 'categories.*', 'tags.*', 'comments.*', 'sections.*',
                'competencies.*', 'programs.*', 'facilities.*', 'stages.*',
                'achievements.*', 'careers.*', 'partners.*', 'gallery.*',
                'staff.*', 'faqs.*', 'feedback.*', 'messages.*', 'media.*',
                'reports.*', 'seo.analyze', 'dashboard.view',
            ],
            self::Writer => [
                'posts.create', 'posts.update.own', 'posts.edit.own',
                'media.upload', 'seo.analyze', 'dashboard.view',
            ],
        };
    }

    public function has(string $ability): bool
    {
        foreach ($this->permissions() as $perm) {
            if ($perm === $ability) {
                return true;
            }
            // wildcard: "posts.*" cocok "posts.create"
            if (str_ends_with($perm, '.*') && str_starts_with($ability, substr($perm, 0, -1))) {
                return true;
            }
        }

        return false;
    }
}
