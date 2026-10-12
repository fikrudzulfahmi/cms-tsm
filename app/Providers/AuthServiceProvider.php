<?php

namespace App\Providers;

use App\Models\Achievement;
use App\Models\AlumniFeedback;
use App\Models\Career;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Competency;
use App\Models\ContactMessage;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\LearningStage;
use App\Models\Media;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\User;
use App\Policies\CmsContentPolicy;
use App\Policies\CommentPolicy;
use App\Policies\PostPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Pemetaan model → policy (spec 6.1).
     *
     * Konten landing pakai CmsContentPolicy generik; Post & Comment
     * punya policy khusus karena aturan kepemilikan lebih kompleks.
     */
    protected $policies = [
        // berita & interaksi
        Post::class => PostPolicy::class,
        Comment::class => CommentPolicy::class,
        Category::class => CmsContentPolicy::class,

        // konten landing
        Section::class => CmsContentPolicy::class,
        Setting::class => CmsContentPolicy::class,
        Media::class => CmsContentPolicy::class,
        Competency::class => CmsContentPolicy::class,
        Program::class => CmsContentPolicy::class,
        Facility::class => CmsContentPolicy::class,
        LearningStage::class => CmsContentPolicy::class,
        Achievement::class => CmsContentPolicy::class,
        Career::class => CmsContentPolicy::class,
        Partner::class => CmsContentPolicy::class,
        GalleryItem::class => CmsContentPolicy::class,
        Staff::class => CmsContentPolicy::class,
        Faq::class => CmsContentPolicy::class,

        // inbox
        AlumniFeedback::class => CmsContentPolicy::class,
        ContactMessage::class => CmsContentPolicy::class,

        // akun
        User::class => UserPolicy::class,
    ];

    public function boot(): void {}
}
