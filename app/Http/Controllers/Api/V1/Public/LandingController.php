<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\Public\LandingResource;
use App\Models\Achievement;
use App\Models\AlumniFeedback;
use App\Models\Career;
use App\Models\Category;
use App\Models\Competency;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\LearningStage;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Program;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final class LandingController extends Controller
{
    private const CACHE_TTL = 300; // 5 menit (spec 5.3)

    /**
     * Seluruh data landing page dalam satu panggilan (spec 5.1 & 9.3).
     */
    public function index(): JsonResponse
    {
        $data = $this->landingData();

        return (new LandingResource($data))
            ->response()
            ->withHeaders([
                // Saat development, larang browser menyimpan response agar
                // perubahan data langsung terlihat (hindari "stale landing").
                'Cache-Control' => app()->environment('local')
                    ? 'no-store'
                    : 'public, max-age=300',
            ]);
    }

    /**
     * Kumpul seluruh data landing (fresh, tanpa cache saat development).
     */
    protected function landingData(): array
    {
        return [
            'settings' => Setting::all()->pluck('value', 'key'),
            'sections' => Section::orderBy('sort')->get(),
            'competencies' => Competency::orderBy('sort')->get(),
            'programs' => Program::with('image')->orderBy('sort')->get(),
            'facilities' => Facility::with('image')->orderBy('sort')->get(),
            'stages' => LearningStage::orderBy('sort')->get(),
            'achievements' => Achievement::with('image')->orderByDesc('year')->orderBy('sort')->get(),
            'careers' => Career::orderBy('sort')->get(),
            'partners' => Partner::with('logo')->orderBy('sort')->get(),
            'gallery' => GalleryItem::with('media')->orderBy('sort')->limit(12)->get(),
            'staff' => Staff::with('photo')->orderBy('sort')->get(),
            'faqs' => Faq::orderBy('sort')->get(),
            'feedback' => AlumniFeedback::with('photo')->approved()->orderByDesc('created_at')->get(),
            'posts' => Post::with(['category', 'author', 'featuredMedia'])
                ->published()
                ->orderByDesc('published_at')
                ->limit(6)
                ->get(),
            'categories' => Category::orderBy('sort')->withCount('posts')->get(),
        ];
    }

    public function settings(): JsonResponse
    {
        $settings = Cache::remember('landing:settings', self::CACHE_TTL, fn () => Setting::all()->pluck('value', 'key')->all());

        return response()->json([
            'data' => $settings,
        ])->withHeaders(['Cache-Control' => 'public, max-age=300']);
    }
}
