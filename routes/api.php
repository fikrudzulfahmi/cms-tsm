<?php

use App\Http\Controllers\Api\V1\Admin\ActivityLogController;
use App\Http\Controllers\Api\V1\Admin\AchievementController as AdminAchievementController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Api\V1\Admin\MediaController;
use App\Http\Controllers\Api\V1\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Api\V1\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\V1\Admin\SeoController;
use App\Http\Controllers\Api\V1\Admin\SectionController;
use App\Http\Controllers\Api\V1\Admin\SettingsController;
use App\Http\Controllers\Api\V1\Admin\StatusController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Api\V1\Public\AchievementController;
use App\Http\Controllers\Api\V1\Public\CommentController;
use App\Http\Controllers\Api\V1\Public\ContactController;
use App\Http\Controllers\Api\V1\Public\FeedbackController;
use App\Http\Controllers\Api\V1\Public\GalleryController;
use App\Http\Controllers\Api\V1\Public\LandingController;
use App\Http\Controllers\Api\V1\Public\PostController;
use App\Http\Controllers\Api\V1\Public\ReactionController;
use App\Http\Controllers\Api\V1\Public\StaffController;
use App\Http\Controllers\Api\V1\Public\TrackController;
use Illuminate\Support\Facades\Route;

/*
 |-----------------------------------------------------------------------
 | API v1 — CMS TSM
 |-----------------------------------------------------------------------
 | Spec bagian 9. Naming resource jamak Inggris di URL, label Indonesia.
 */
Route::prefix('v1')->group(function (): void {

    // ---------------- AUTH (Sanctum SPA, cookie) ----------------
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');
    Route::post('auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:interact');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:interact');

    // ---------------- PUBLIK ----------------
    Route::name('public.')->group(function (): void {
        Route::get('site/landing', [LandingController::class, 'index'])
            ->name('landing')->middleware('throttle:public');
        Route::get('site/settings', [LandingController::class, 'settings'])
            ->name('settings')->middleware('throttle:public');

        Route::get('posts', [PostController::class, 'index'])
            ->name('posts.index')->middleware('throttle:public');
        Route::get('categories', [PostController::class, 'categories'])
            ->name('categories.index')->middleware('throttle:public');
        Route::get('posts/{slug}', [PostController::class, 'show'])
            ->name('posts.show')->middleware('throttle:public');

        Route::put('posts/{slug}/reaction', [ReactionController::class, 'react'])
            ->name('posts.react')->middleware('throttle:interact');
        Route::post('posts/{slug}/share', [ReactionController::class, 'share'])
            ->name('posts.share')->middleware('throttle:interact');

        Route::get('posts/{slug}/comments', [CommentController::class, 'index'])
            ->name('comments.index')->middleware('throttle:public');
        Route::post('posts/{slug}/comments', [CommentController::class, 'store'])
            ->name('comments.store')->middleware('throttle:comments');
        Route::put('comments/{id}/like', [CommentController::class, 'like'])
            ->name('comments.like')->middleware('throttle:interact');
        Route::post('comments/{id}/report', [CommentController::class, 'report'])
            ->name('comments.report')->middleware('throttle:interact');

        Route::get('achievements', [AchievementController::class, 'index'])
            ->name('achievements.index')->middleware('throttle:public');
        Route::get('gallery', [GalleryController::class, 'index'])
            ->name('gallery.index')->middleware('throttle:public');
        Route::get('staff', [StaffController::class, 'index'])
            ->name('staff.index')->middleware('throttle:public');

        Route::post('feedback', [FeedbackController::class, 'store'])
            ->name('feedback.store')->middleware('throttle:interact');
        Route::post('contact', [ContactController::class, 'store'])
            ->name('contact.store')->middleware('throttle:interact');
        Route::post('track', [TrackController::class, 'store'])
            ->name('track')->middleware('throttle:public');
    });

    // SEO publik: sitemap, robots, RSS feed (dilindungi throttle:public)
    Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
    Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');
    Route::get('feed.xml', [FeedController::class, 'index'])->name('feed');

    // ---------------- ADMIN ----------------
    Route::prefix('admin')->name('admin.')->middleware([
        'auth:sanctum',
        'log.admin',
    ])->group(function (): void {

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('status', [StatusController::class, 'index'])->name('status');
        Route::post('seo/analyze', [SeoController::class, 'analyze'])->name('seo.analyze');

        // berita
        Route::apiResource('posts', AdminPostController::class);
        Route::post('posts/{id}/publish', [AdminPostController::class, 'publish'])->name('posts.publish');
        Route::post('posts/{id}/unpublish', [AdminPostController::class, 'unpublish'])->name('posts.unpublish');
        Route::post('posts/{id}/archive', [AdminPostController::class, 'archive'])->name('posts.archive');
        Route::get('posts/{id}/revisions', [AdminPostController::class, 'revisions'])->name('posts.revisions');
        Route::post('posts/{id}/revisions/{rev}/restore', [AdminPostController::class, 'restoreRevision'])->name('posts.restore');

        Route::apiResource('categories', CategoryController::class);
        Route::patch('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');

        // komentar moderasi
        Route::get('comments', [AdminCommentController::class, 'index'])->name('comments.index');
        Route::patch('comments/{id}', [AdminCommentController::class, 'update'])->name('comments.update');
        Route::post('comments/{id}/reply', [AdminCommentController::class, 'reply'])->name('comments.reply');
        Route::delete('comments/{id}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');
        Route::post('comments/bulk', [AdminCommentController::class, 'bulk'])->name('comments.bulk');

        // konten situs
        Route::get('sections', [SectionController::class, 'index'])->name('sections.index');
        Route::get('sections/{key}', [SectionController::class, 'show'])->name('sections.show');
        Route::put('sections/{key}', [SectionController::class, 'update'])->name('sections.update');
        Route::patch('sections/order', [SectionController::class, 'order'])->name('sections.order');

        // daftar item landing (reorder bersama)
        Route::apiResource('competencies', \App\Http\Controllers\Api\V1\Admin\CompetencyController::class);
        Route::patch('competencies/reorder', [\App\Http\Controllers\Api\V1\Admin\CompetencyController::class, 'reorder'])->name('competencies.reorder');
        Route::apiResource('programs', \App\Http\Controllers\Api\V1\Admin\ProgramController::class);
        Route::patch('programs/reorder', [\App\Http\Controllers\Api\V1\Admin\ProgramController::class, 'reorder'])->name('programs.reorder');
        Route::apiResource('facilities', \App\Http\Controllers\Api\V1\Admin\FacilityController::class);
        Route::patch('facilities/reorder', [\App\Http\Controllers\Api\V1\Admin\FacilityController::class, 'reorder'])->name('facilities.reorder');
        Route::apiResource('stages', \App\Http\Controllers\Api\V1\Admin\LearningStageController::class);
        Route::patch('stages/reorder', [\App\Http\Controllers\Api\V1\Admin\LearningStageController::class, 'reorder'])->name('stages.reorder');
        Route::apiResource("achievements", AdminAchievementController::class);
        Route::patch('achievements/reorder', [\App\Http\Controllers\Api\V1\Admin\AchievementController::class, 'reorder'])->name('achievements.reorder');
        Route::apiResource('careers', \App\Http\Controllers\Api\V1\Admin\CareerController::class);
        Route::patch('careers/reorder', [\App\Http\Controllers\Api\V1\Admin\CareerController::class, 'reorder'])->name('careers.reorder');
        Route::apiResource('partners', \App\Http\Controllers\Api\V1\Admin\PartnerController::class);
        Route::patch('partners/reorder', [\App\Http\Controllers\Api\V1\Admin\PartnerController::class, 'reorder'])->name('partners.reorder');
        Route::apiResource('gallery', \App\Http\Controllers\Api\V1\Admin\GalleryItemController::class);
        Route::patch('gallery/reorder', [\App\Http\Controllers\Api\V1\Admin\GalleryItemController::class, 'reorder'])->name('gallery.reorder');
        Route::apiResource('staff', \App\Http\Controllers\Api\V1\Admin\StaffController::class);
        Route::patch('staff/reorder', [\App\Http\Controllers\Api\V1\Admin\StaffController::class, 'reorder'])->name('staff.reorder');
        Route::apiResource('faqs', \App\Http\Controllers\Api\V1\Admin\FaqController::class);
        Route::patch('faqs/reorder', [\App\Http\Controllers\Api\V1\Admin\FaqController::class, 'reorder'])->name('faqs.reorder');

        // umpan balik & pesan
        Route::get('feedback', [\App\Http\Controllers\Api\V1\Admin\FeedbackController::class, 'index'])->name('feedback.index');
        Route::patch('feedback/{id}', [\App\Http\Controllers\Api\V1\Admin\FeedbackController::class, 'update'])->name('feedback.update');
        Route::get('messages', [\App\Http\Controllers\Api\V1\Admin\MessageController::class, 'index'])->name('messages.index');
        Route::patch('messages/{id}', [\App\Http\Controllers\Api\V1\Admin\MessageController::class, 'update'])->name('messages.update');

        // media
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::patch('media/{id}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');

        // pengaturan & pengguna & log
        Route::get('settings/{group}', [SettingsController::class, 'show'])->name('settings.show');
        Route::put('settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
        Route::apiResource('users', UserController::class);
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/security', [ActivityLogController::class, 'security'])
            ->name('activity-logs.security');
        Route::post('activity-logs/{id}/unban', [ActivityLogController::class, 'unban'])
            ->name('activity-logs.unban');
    });
});

// sitemap, robots & feed dirender server dari routes/web.php (spec 5.4 poin 6)
