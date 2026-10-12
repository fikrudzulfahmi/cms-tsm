<?php

use App\Http\Controllers\SeoShellController;
use Illuminate\Support\Facades\Route;

/*
 |-----------------------------------------------------------------------
 | Web routes — shell SPA + SEO (spec 5.4)
 |-----------------------------------------------------------------------
 */

// admin shell (noindex via middleware)
Route::get('/admin/{any?}', [SeoShellController::class, 'admin'])
    ->where('any', '.*')
    ->middleware([\App\Http\Middleware\NoIndexAdmin::class]);

// catch-all publik: semua URL non-api, non-admin → shell SPA publik
// Slug berita terbit menghasilkan 404 sungguhan bila tidak ditemukan.
Route::get('/{any?}', [SeoShellController::class, 'public'])
    ->where('any', '^(?!api).*$');
