<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\ShareChannel;
use App\Http\Controllers\Api\V1\Controller;
use App\Jobs\RecomputeRating;
use App\Models\Post;
use App\Models\PostReaction;
use App\Services\RatingCalculator;
use App\Services\VisitorHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ReactionController extends Controller
{
    public function __construct(
        private readonly VisitorHasher $hasher,
        private readonly RatingCalculator $ratings,
    ) {}

    /**
     * Like/unlike/tarik — idempoten (spec 7.3).
     * value: 1 (suka), -1 (tidak suka), 0 (tarik suara).
     */
    public function react(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'integer', Rule::in([1, -1, 0])],
        ]);

        $post = Post::where('slug', $slug)->published()->firstOrFail();
        $visitorHash = $this->hasher->hash($request->cookie('vid'), $request->userAgent());

        $value = (int) $validated['value'];

        $existing = PostReaction::where('post_id', $post->id)
            ->where('visitor_hash', $visitorHash)
            ->first();

        if ($value === 0) {
            // tarik suara
            if ($existing !== null) {
                $existing->delete();
            }
        } else {
            if ($existing === null) {
                PostReaction::create([
                    'post_id' => $post->id,
                    'visitor_hash' => $visitorHash,
                    'value' => $value,
                ]);
            } else {
                $existing->update(['value' => $value]);
            }
        }

        $this->recomputeCounts($post);

        return response()->json([
            'data' => [
                'likes' => $post->likes_count,
                'unlikes' => $post->unlikes_count,
                'viewer_reaction' => $value === 0 ? null : $value,
                'like_percent' => $post->like_percent,
                'stars' => $post->stars,
            ],
        ]);
    }

    /**
     * Catat klik bagikan per kanal (spec 7.3).
     */
    public function share(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['required', 'string', Rule::in(array_column(ShareChannel::cases(), 'value'))],
        ]);

        $post = Post::where('slug', $slug)->published()->firstOrFail();

        $post->shares()->create(['channel' => $validated['channel']]);
        $post->increment('shares_count');

        return response()->json([
            'data' => ['shares' => $post->shares_count],
        ]);
    }

    private function recomputeCounts(Post $post): void
    {
        $likes = PostReaction::where('post_id', $post->id)->where('value', 1)->count();
        $unlikes = PostReaction::where('post_id', $post->id)->where('value', -1)->count();

        $uniqueVisitors = max(1, $post->visits()->distinct()->count('visitor_hash'));
        $ratings = $this->ratings->calculate($likes, $unlikes, $uniqueVisitors);

        $post->update([
            'likes_count' => $likes,
            'unlikes_count' => $unlikes,
            'like_percent' => $ratings['like_percent'],
            'engagement_percent' => $ratings['engagement_percent'],
            'stars' => $ratings['stars'],
        ]);
    }
}
