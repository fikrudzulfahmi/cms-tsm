<?php

namespace App\Http\Resources\Concerns;

use App\Models\Media;

/**
 * Pembentuk payload media/gambar agar bentuknya konsisten di seluruh API Resource.
 *
 * Hanya atribut aman yang dipublikasikan (url, alt, dimensi, varian webp).
 * Path mentah, nama disk, id pengunggah, dan data internal media lain tidak
 * pernah ikut keluar — baik di API publik maupun admin.
 */
trait FormatsMedia
{
    /**
     * Bentuk lengkap gambar (hero, prestasi, galeri, gambar unggulan berita).
     *
     * @return array{url: string, alt: string|null, width: int|null, height: int|null}|null
     */
    protected function imageShape(mixed $media): ?array
    {
        if ($media instanceof Media) {
            return [
                'url' => $media->url(),
                'alt' => $media->alt,
                'width' => $media->width,
                'height' => $media->height,
            ];
        }

        $url = $this->mediaUrl($media);

        return $url === null ? null : [
            'url' => $url,
            'alt' => data_get($media, 'alt'),
            'width' => data_get($media, 'width'),
            'height' => data_get($media, 'height'),
        ];
    }

    /**
     * Bentuk ringkas gambar untuk kartu/daftar.
     *
     * @return array{url: string, alt: string|null}|null
     */
    protected function imageCard(mixed $media): ?array
    {
        if ($media instanceof Media) {
            return [
                'url' => $media->url(),
                'alt' => $media->alt,
            ];
        }

        $url = $this->mediaUrl($media);

        return $url === null ? null : ['url' => $url, 'alt' => data_get($media, 'alt')];
    }

    /**
     * Varian gambar (mis. webp) bila ada — dipakai SPA untuk srcset.
     *
     * @return array<string, string>
     */
    protected function imageVariants(mixed $media): array
    {
        if ($media instanceof Media) {
            if (! is_array($media->variants)) {
                return [];
            }

            $variants = [];

            foreach ($media->variants as $name => $path) {
                $variants[(string) $name] = $media->url((string) $name);
            }

            return $variants;
        }

        $variants = data_get($media, 'variants');

        if (! is_array($variants)) {
            return [];
        }

        $payload = [];

        foreach ($variants as $name => $path) {
            $url = $this->storageUrl(is_string($path) ? $path : null);

            if ($url !== null) {
                $payload[(string) $name] = $url;
            }
        }

        return $payload;
    }

    /**
     * URL gambar dari model Media maupun payload siap pakai (array/objek).
     *
     * Dipakai LandingResource yang boleh menerima data dari query builder,
     * sehingga `featured_image` bisa saja sudah berbentuk array.
     */
    protected function mediaUrl(mixed $media, ?string $variant = null): ?string
    {
        if ($media instanceof Media) {
            return $media->url($variant);
        }

        if ($variant !== null) {
            $variants = data_get($media, 'variants');
            $path = is_array($variants) ? ($variants[$variant] ?? null) : data_get($variants, $variant);

            return $this->storageUrl(is_string($path) ? $path : null);
        }

        $url = data_get($media, 'url');

        if (is_string($url) && $url !== '') {
            return $url;
        }

        $path = data_get($media, 'path');

        return $this->storageUrl(is_string($path) ? $path : null);
    }

    /**
     * URL untuk kolom yang menyimpan path relatif (mis. `users.avatar`).
     */
    protected function storageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
