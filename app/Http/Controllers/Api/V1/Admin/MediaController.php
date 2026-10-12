<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\Admin\MediaResource;
use App\Jobs\OptimizeImage;
use App\Models\Media;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

final class MediaController extends Controller
{
    private const MAX_UPLOAD_MB = 8;

    public function __construct() {}

    /**
     * Daftar media (paginasi, cari).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Media::latest();

        if (! empty($validated['q'])) {
            $search = $validated['q'];
            $query->where('alt', 'like', "%{$search}%")
                ->orWhere('caption', 'like', "%{$search}%")
                ->orWhere('path', 'like', "%{$search}%");
        }

        $result = $query->paginate((int) ($validated['per_page'] ?? 24));

        return MediaResource::collection($result)->response();
    }

    /**
     * Unggah media — hanya jpg/png/webp/svg-bersih, cek MIME sebenarnya (spec 12).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', File::image()->max(self::MAX_UPLOAD_MB * 1024)],
            'alt' => ['nullable', 'string', 'max:200'],
            'caption' => ['nullable', 'string', 'max:300'],
        ]);

        $file = $request->file('file');

        // cek MIME sebenarnya, jangan percaya ekstensi
        $mime = $file->getMimeType();
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'];
        if (! in_array($mime, $allowed, true)) {
            return response()->json([
                'message' => 'Tipe file tidak diizinkan.',
                'errors' => ['file' => ['Hanya JPG, PNG, WebP, GIF, dan SVG yang diizinkan.']],
            ], 422);
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'svg',
        };

        // nama acak + path berdasarkan tanggal
        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $path = $file->storeAs('media/' . date('Y/m'), $name, 'public');

        $media = Media::create([
            'path' => $path,
            'disk' => 'public',
            'mime' => $mime,
            'size' => $file->getSize(),
            'width' => null,
            'height' => null,
            'alt' => $validated['alt'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'uploaded_by' => $request->user()?->id,
        ]);

        // optimasi (resize + webp variant) lewat antrean
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            OptimizeImage::dispatch($media->id);
        }

        ActivityLogger::log($request->user()?->id, 'admin.media.upload', 'Media', $media->id);

        return (new MediaResource($media->fresh()))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'alt' => ['nullable', 'string', 'max:200'],
            'caption' => ['nullable', 'string', 'max:300'],
        ]);

        $media = Media::findOrFail($id);
        $media->update($validated);

        ActivityLogger::log($request->user()?->id, 'admin.media.update', 'Media', $media->id);

        return (new MediaResource($media->fresh()))->response();
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $media = Media::findOrFail($id);

        if (Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->delete($media->path);
        }
        foreach (($media->variants ?? []) as $variant) {
            if (Storage::disk($media->disk)->exists($variant)) {
                Storage::disk($media->disk)->delete($variant);
            }
        }

        $media->delete();
        ActivityLogger::log($request->user()?->id, 'admin.media.delete', 'Media', $media->id);

        return response()->json(['message' => 'Media berhasil dihapus.']);
    }
}
