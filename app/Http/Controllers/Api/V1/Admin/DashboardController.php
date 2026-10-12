<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\CommentStatus;
use App\Enums\PostStatus;
use App\Http\Controllers\Api\V1\Controller;
use App\Models\AlumniFeedback;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class DashboardController extends Controller
{
    /**
     * Beranda admin (spec 6.2). Periode: 7 hari, 30 hari, 12 bulan.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'range' => ['nullable', 'string', 'in:7d,30d,12m'],
        ]);

        $range = $validated['range'] ?? '30d';
        $days = $range === '7d' ? 7 : ($range === '12m' ? 365 : 30);
        $from = now()->subDays($days);

        // ---- statistik kunjungan ----
        $visits = Visit::where('visited_at', '>=', $from);
        $totalVisits = $visits->count();
        $uniqueVisitors = (clone $visits)->distinct()->count('visitor_hash');
        $pageviews = (clone $visits)->count();

        // grafik harian
        $daily = DB::table('cms_visits')
            ->selectRaw('DATE(visited_at) as date, COUNT(*) as visits, COUNT(DISTINCT visitor_hash) as unique_visitors')
            ->where('visited_at', '>=', $from)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ---- berita ----
        $postsPublished = Post::where('status', PostStatus::Published->value)->count();
        $postsDraft = Post::where('status', PostStatus::Draft->value)->count();
        $postsScheduled = Post::where('status', PostStatus::Scheduled->value)->count();
        $achievements = \App\Models\Achievement::count();
        $competencies = \App\Models\Competency::count();
        $galleryPhotos = \App\Models\GalleryItem::count();

        // ---- berita terpopuler ----
        $popular = Post::published()
            ->orderByDesc('views_count')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'views_count', 'likes_count', 'comments_count', 'stars']);

        // ---- menunggu moderasi ----
        $commentsPending = Comment::where('status', CommentStatus::Pending->value)->count();
        $feedbackPending = AlumniFeedback::where('status', 'pending')->count();
        $messagesNew = ContactMessage::where('status', 'baru')->count();

        // ---- sumber trafik ----
        $sources = DB::table('cms_visits')
            ->selectRaw('COALESCE(NULLIF(referrer_source, ""), "direct") as source, COUNT(*) as total')
            ->where('visited_at', '>=', $from)
            ->groupBy('source')
            ->orderByDesc('total')
            ->get();

        // ---- perangkat ----
        $devices = DB::table('cms_visits')
            ->selectRaw('device, COUNT(*) as total')
            ->where('visited_at', '>=', $from)
            ->groupBy('device')
            ->get();

        // ---- peringatan ----
        $lowSeoPosts = Post::published()->where('seo_score', '<', 60)->count();
        $queueFailed = DB::table('failed_jobs')->count();

        return response()->json([
            'data' => [
                'range' => $range,
                'visits' => [
                    'total' => $totalVisits,
                    'unique_visitors' => $uniqueVisitors,
                    'pageviews' => $pageviews,
                    'chart' => $daily,
                ],
                'content' => [
                    'posts_published' => $postsPublished,
                    'posts_draft' => $postsDraft,
                    'posts_scheduled' => $postsScheduled,
                    'achievements' => $achievements,
                    'competencies' => $competencies,
                    'gallery_photos' => $galleryPhotos,
                ],
                'popular_posts' => $popular,
                'pending' => [
                    'comments' => $commentsPending,
                    'feedback' => $feedbackPending,
                    'messages' => $messagesNew,
                ],
                'traffic' => [
                    'sources' => $sources,
                    'devices' => $devices,
                ],
                'alerts' => [
                    'low_seo_posts' => $lowSeoPosts,
                    'queue_failed' => $queueFailed,
                ],
            ],
        ]);
    }
}
