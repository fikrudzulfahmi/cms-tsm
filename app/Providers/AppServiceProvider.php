<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ---- Gate tunggal berbasis peran (spec 6.1) ----
        // Hak akses dihitung dari kolom users.role, tanpa tabel ACL terpisah.
        Gate::define('manage-cms', function ($user, string $ability = 'access') {
            return in_array($user->role, ['super_admin', 'editor'], true);
        });

        Gate::define('admin-access', fn ($user) => in_array($user->role, ['super_admin', 'editor', 'writer'], true));

        Gate::define('super-admin', fn ($user) => $user->role === 'super_admin');

        Gate::define('publish-post', function ($user) {
            return in_array($user->role, ['super_admin', 'editor'], true);
        });

        // ---- Pembatas laju (spec 5.3 & 12) ----
        RateLimiter::for('public', fn ($request) => Limit::perMinute(120)->by(self::key($request)));

        RateLimiter::for('interact', fn ($request) => Limit::perMinute(30)->by(self::key($request)));

        RateLimiter::for('comments', fn ($request) => Limit::perMinute(10)->by(self::key($request)));

        RateLimiter::for('contact', fn ($request) => Limit::perHour(5)->by(self::key($request)));

        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('upload', fn ($request) => Limit::perMinute(20)->by($request->ip()));
    }

    /**
     * Kunci pembatas: hash pengunjung bila ada (anonim), selainnya id user.
     */
    private static function key($request): string
    {
        $user = $request->user();
        if ($user !== null) {
            return 'u' . $user->id;
        }

        $hash = $request->attributes->get('visitor_hash');
        if (is_string($hash) && $hash !== '') {
            return 'v' . $hash;
        }

        return 'i' . $request->ip();
    }
}
