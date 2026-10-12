<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skema CMS TSM (spec bagian 10) + kolom keamanan tambahan.
     * Prefix `cms_` menghindari bentrokan dengan tabel bawaan Laravel.
     */
    public function up(): void
    {
        // ---------- users (perpanjang tabel bawaan) ----------
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('writer')->after('email');
            $table->string('avatar')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->json('two_factor')->nullable()->after('last_login_at');
        });

        // ---------- settings (key-value global) ----------
        Schema::create('cms_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('group')->default('umum');
            $table->timestamps();
        });

        // ---------- media ----------
        Schema::create('cms_media', function (Blueprint $table): void {
            $table->id();
            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt')->nullable();
            $table->string('caption')->nullable();
            $table->json('variants')->nullable(); // webp variants
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('created_at');
        });

        // ---------- sections (blok landing page) ----------
        Schema::create('cms_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique(); // hero, statistik, kompetensi, ...
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('cms_media')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->json('extra')->nullable();
            $table->timestamps();
        });

        // ---------- daftar item landing ----------
        Schema::create('cms_competencies', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_highlight')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_programs', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('cms_media')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_facilities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('spec')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('image_id')->nullable()->constrained('cms_media')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('cms_learning_stages', function (Blueprint $table): void {
            $table->id();
            $table->string('class_label'); // X, XI, XII
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('cms_achievements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('level')->nullable(); // kota, provinsi, nasional
            $table->string('rank')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('student_name')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('image_id')->nullable()->constrained('cms_media')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index('year');
        });

        Schema::create('cms_careers', function (Blueprint $table): void {
            $table->id();
            $table->string('label');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('cms_partners', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('logo_id')->nullable()->constrained('cms_media')->nullOnDelete();
            $table->string('url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('cms_gallery_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')->constrained('cms_media')->cascadeOnDelete();
            $table->string('caption')->nullable();
            $table->string('album')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->index('album');
        });

        Schema::create('cms_staff', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable();
            $table->foreignId('photo_id')->nullable()->constrained('cms_media')->nullOnDelete();
            $table->text('bio')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('cms_faqs', function (Blueprint $table): void {
            $table->id();
            $table->text('question');
            $table->text('answer');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('cms_alumni_feedback', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('occupation')->nullable();
            $table->text('message');
            $table->foreignId('photo_id')->nullable()->constrained('cms_media')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('cms_contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('message');
            $table->string('type')->default('pertanyaan'); // pendaftaran|pertanyaan
            $table->string('status')->default('baru');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        // ---------- berita ----------
        Schema::create('cms_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('cms_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('cms_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->foreignId('featured_media_id')->nullable()->constrained('cms_media')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('cms_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draf')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->boolean('allow_comments')->default(true);
            $table->string('focus_keyword')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->foreignId('og_media_id')->nullable()->constrained('cms_media')->nullOnDelete();
            $table->unsignedSmallInteger('seo_score')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->unsignedBigInteger('unlikes_count')->default(0);
            $table->unsignedBigInteger('comments_count')->default(0);
            $table->unsignedBigInteger('shares_count')->default(0);
            $table->decimal('like_percent', 5, 2)->default(0);
            $table->decimal('engagement_percent', 5, 2)->default(0);
            $table->decimal('stars', 3, 1)->default(0);
            $table->timestamps();
            $table->index(['status', 'published_at']);
            $table->index('published_at');
        });

        Schema::create('cms_post_tag', function (Blueprint $table): void {
            $table->foreignId('post_id')->constrained('cms_posts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('cms_tags')->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });

        Schema::create('cms_post_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('cms_posts')->cascadeOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('post_id');
        });

        Schema::create('cms_post_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('cms_posts')->cascadeOnDelete();
            $table->string('visitor_hash');
            $table->smallInteger('value'); // +1 / -1 / 0
            $table->timestamps();
            $table->unique(['post_id', 'visitor_hash']);
            $table->index('post_id');
        });

        Schema::create('cms_post_shares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('cms_posts')->cascadeOnDelete();
            $table->string('channel');
            $table->timestamps();
            $table->index('post_id');
        });

        // ---------- komentar ----------
        Schema::create('cms_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('cms_posts')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('cms_comments')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->text('body');
            $table->string('status')->default('menunggu')->index();
            $table->string('visitor_hash');
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->boolean('is_admin_reply')->default(false);
            $table->timestamps();
            $table->index(['post_id', 'status']);
        });

        Schema::create('cms_comment_likes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comment_id')->constrained('cms_comments')->cascadeOnDelete();
            $table->string('visitor_hash');
            $table->timestamps();
            $table->unique(['comment_id', 'visitor_hash']);
        });

        Schema::create('cms_comment_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comment_id')->constrained('cms_comments')->cascadeOnDelete();
            $table->string('reason');
            $table->string('visitor_hash');
            $table->timestamps();
        });

        // ---------- analitik ----------
        Schema::create('cms_visits', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('visited_at');
            $table->string('path');
            $table->foreignId('post_id')->nullable()->constrained('cms_posts')->nullOnDelete();
            $table->string('visitor_hash');
            $table->string('referrer_source')->nullable();
            $table->string('referrer_url')->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->index('visited_at');
            $table->index(['post_id', 'visited_at']);
            $table->index('visitor_hash');
        });

        Schema::create('cms_daily_stats', function (Blueprint $table): void {
            $table->date('date')->primary();
            $table->unsignedBigInteger('visits')->default(0);
            $table->unsignedBigInteger('unique_visitors')->default(0);
            $table->unsignedBigInteger('pageviews')->default(0);
        });

        Schema::create('cms_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('file_path')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ---------- activity log (keamanan) ----------
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index('created_at');
            $table->index(['action', 'created_at']);
        });

        // ---------- login attempts (brute-force) ----------
        Schema::create('cms_login_attempts', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->index();
            $table->ipAddress('ip')->nullable()->index();
            $table->boolean('success')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_login_attempts');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('cms_reports');
        Schema::dropIfExists('cms_daily_stats');
        Schema::dropIfExists('cms_visits');
        Schema::dropIfExists('cms_comment_reports');
        Schema::dropIfExists('cms_comment_likes');
        Schema::dropIfExists('cms_comments');
        Schema::dropIfExists('cms_post_shares');
        Schema::dropIfExists('cms_post_reactions');
        Schema::dropIfExists('cms_post_revisions');
        Schema::dropIfExists('cms_post_tag');
        Schema::dropIfExists('cms_posts');
        Schema::dropIfExists('cms_tags');
        Schema::dropIfExists('cms_categories');
        Schema::dropIfExists('cms_contact_messages');
        Schema::dropIfExists('cms_alumni_feedback');
        Schema::dropIfExists('cms_faqs');
        Schema::dropIfExists('cms_staff');
        Schema::dropIfExists('cms_gallery_items');
        Schema::dropIfExists('cms_partners');
        Schema::dropIfExists('cms_careers');
        Schema::dropIfExists('cms_achievements');
        Schema::dropIfExists('cms_learning_stages');
        Schema::dropIfExists('cms_facilities');
        Schema::dropIfExists('cms_programs');
        Schema::dropIfExists('cms_competencies');
        Schema::dropIfExists('cms_sections');
        Schema::dropIfExists('cms_media');
        Schema::dropIfExists('cms_settings');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'avatar', 'is_active', 'last_login_at', 'two_factor']);
        });
    }
};
