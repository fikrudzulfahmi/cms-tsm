<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'avatar', 'is_active',
        'last_login_at', 'two_factor',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor' => 'array',
            'role' => Role::class,
        ];
    }

    // ---------- role helpers ----------

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function hasAbility(string $ability): bool
    {
        return $this->role?->has($ability) ?? false;
    }

    // ---------- relationships ----------

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'uploaded_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class, 'user_id');
    }
}
