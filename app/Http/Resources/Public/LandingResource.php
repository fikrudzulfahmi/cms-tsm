<?php

namespace App\Http\Resources\Public;

use App\Http\Resources\Concerns\NullSafeObject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Seluruh data landing page dalam satu panggilan (`GET /api/v1/site/landing`,
 * spec bagian 5.1 & 9.3).
 *
 * Resource ini menerima payload pilihan controller berupa array atau objek
 * dengan kunci berikut (alias bahasa Indonesia/Inggris diterima):
 *
 *   sections, settings, statistik, competencies|kompetensi,
 *   programs|program, facilities|sarana, stages|tahap,
 *   achievements|prestasi, careers|karier, partners|mitra,
 *   gallery|galeri, staff|staf, faqs, feedback|umpan_balik, posts|berita
 *
 * Setiap item boleh berupa model Eloquent maupun array asosiatif. `sections`
 * selalu dikirim terurut berdasarkan kolom `sort`; `posts` dipotong ke 6 item
 * (berita terbaru versi ringkas).
 */
class LandingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sections' => SectionResource::collection(
                $this->listing(['sections'])->sortBy('sort')->values()
            ),

            'settings' => $this->assoc(['settings', 'pengaturan']),
            'statistik' => $this->assoc(['statistik', 'stats']),

            'kompetensi' => CompetencyResource::collection(
                $this->listing(['kompetensi', 'competencies'])->sortBy('sort')->values()
            ),
            'program' => ProgramResource::collection(
                $this->listing(['program', 'programs'])->sortBy('sort')->values()
            ),
            'facilities' => FacilityResource::collection(
                $this->listing(['facilities', 'sarana'])->sortBy('sort')->values()
            ),
            'stages' => StageResource::collection(
                $this->listing(['stages', 'tahap'])->sortBy('sort')->values()
            ),
            'achievements' => AchievementResource::collection(
                $this->listing(['achievements', 'prestasi'])->sortBy('sort')->values()
            ),
            'careers' => CareerResource::collection(
                $this->listing(['careers', 'karier'])->sortBy('sort')->values()
            ),
            'partners' => PartnerResource::collection(
                $this->listing(['partners', 'mitra'])->sortBy('sort')->values()
            ),
            'gallery' => GalleryResource::collection(
                $this->listing(['gallery', 'galeri'])->sortBy('sort')->values()
            ),
            'staff' => StaffResource::collection(
                $this->listing(['staff', 'staf'])->sortBy('sort')->values()
            ),
            'faqs' => FaqResource::collection(
                $this->listing(['faqs', 'faq'])->sortBy('sort')->values()
            ),
            // Umpan balik lulusan: controller WAJIB mengirim yang sudah disetujui
            // saja (`feedback.status = disetujui`); resource hanya membungkus.
            'feedback' => FeedbackResource::collection(
                $this->listing(['feedback', 'umpan_balik'])
            ),

            // 6 berita terbaru (versi ringkas) — batas ditegakkan di sini juga.
            'posts' => PostCardResource::collection(
                $this->listing(['posts', 'berita'])->take(6)
            ),
        ];
    }

    /**
     * Ambil nilai pertama yang ada dari daftar alias kunci.
     */
    protected function find(array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($this->resource, $key);

            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Kumpulan item (list) sebagai Collection berisi objek, agar item berupa
     * array asosiatif tetap bisa dibungkus JsonResource.
     */
    protected function listing(array $keys, mixed $default = []): Collection
    {
        $value = $this->find($keys, $default);

        if ($value instanceof Collection) {
            return $value->map(fn ($item) => $this->toObject($item))->values();
        }

        if (is_array($value)) {
            return collect($value)->map(fn ($item) => $this->toObject($item))->values();
        }

        return collect();
    }

    /**
     * Nilai peta key => value (settings, statistik) sebagai objek JSON.
     */
    protected function assoc(array $keys): object
    {
        $value = $this->find($keys, []);

        if (is_object($value)) {
            return $value;
        }

        return (object) (is_array($value) ? $value : []);
    }

    /**
     * Ubah array asosiatif menjadi objek null-safe; nilai lain diteruskan apa adanya.
     */
    protected function toObject(mixed $item): mixed
    {
        return NullSafeObject::wrap($item);
    }
}
