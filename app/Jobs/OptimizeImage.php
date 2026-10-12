<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Kompres + buat varian WebP media (spec 12).
 */
final class OptimizeImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const VARIANTS = [
        'thumbnail' => [400, 300],
        'card' => [800, 600],
        'og' => [1200, 630],
    ];

    public function __construct(private readonly int $mediaId) {}

    public function handle(): void
    {
        $media = Media::find($this->mediaId);
        if ($media === null) {
            return;
        }

        $disk = Storage::disk($media->disk);
        if (! $disk->exists($media->path)) {
            return;
        }

        try {
            $manager = new ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            $image = $manager->read($disk->path($media->path));

            $media->update([
                'width' => $image->width(),
                'height' => $image->height(),
            ]);

            $variants = [];
            foreach (self::VARIANTS as $name => [$w, $h]) {
                $variantPath = $this->variantPath($media->path, $name, 'webp');
                $encoded = (clone $image)->cover($w, $h)->encode(new \Intervention\Image\Encoders\WebpEncoder(80));
                $disk->put($variantPath, $encoded->toString());
                $variants[$name] = $variantPath;
            }

            $media->update(['variants' => $variants]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('OptimizeImage gagal: ' . $e->getMessage());
        }
    }

    private function variantPath(string $path, string $name, string $ext): string
    {
        $dir = dirname($path);
        $base = pathinfo($path, PATHINFO_FILENAME);

        return "{$dir}/{$base}-{$name}.{$ext}";
    }
}
