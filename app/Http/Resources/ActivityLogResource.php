<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Log aktivitas admin (`GET /admin/activity-logs`, spec bagian 9.4 & 12).
 *
 * Dipakai panel "Log aktivitas" sekaligus bahan deteksi pola serangan.
 * Baris bisa berasal dari model Eloquent maupun `DB::table('activity_logs')`,
 * jadi pembacaan field memakai `data_get()` yang aman untuk keduanya.
 *
 * `user_agent` mentah tidak dikirim (cukup dipakai internal untuk deteksi).
 */
class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) data_get($this->resource, 'id'),

            // Relasi opsional: hanya tampil bila controller eager-load `user`.
            'user' => $this->when($this->userLoaded(), fn () => $this->userPayload()),

            'action' => data_get($this->resource, 'action'),
            'subject_type' => data_get($this->resource, 'subject_type'),
            'subject_label' => $this->subjectLabel(),
            'subject_id' => $this->intOrNull(data_get($this->resource, 'subject_id')),
            'meta' => $this->metaPayload(),
            'ip' => data_get($this->resource, 'ip'),
            'created_at' => $this->iso(data_get($this->resource, 'created_at')),
        ];
    }

    protected function userLoaded(): bool
    {
        return is_object($this->resource)
            && method_exists($this->resource, 'relationLoaded')
            && $this->resource->relationLoaded('user');
    }

    /**
     * @return array{name: string|null, email: string|null}|null
     */
    protected function userPayload(): ?array
    {
        $user = data_get($this->resource, 'user');

        if ($user === null) {
            return null;
        }

        return [
            'name' => data_get($user, 'name'),
            'email' => data_get($user, 'email'),
        ];
    }

    /**
     * Nama pendek kelas subjek, mis. `App\Models\Post` -> `Post`.
     */
    protected function subjectLabel(): ?string
    {
        $type = data_get($this->resource, 'subject_type');

        return is_string($type) && $type !== '' ? class_basename($type) : null;
    }

    /**
     * `meta` bisa berupa array (cast Eloquent) atau string JSON (query builder).
     */
    protected function metaPayload(): mixed
    {
        $meta = data_get($this->resource, 'meta');

        if (is_string($meta)) {
            if ($meta === '') {
                return null;
            }

            $decoded = json_decode($meta, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $meta;
        }

        if (is_object($meta)) {
            return (array) $meta;
        }

        return $meta;
    }

    protected function intOrNull(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    protected function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface || is_string($value)) {
            try {
                return Carbon::parse($value)->toISOString();
            } catch (\Throwable) {
                return is_string($value) ? $value : null;
            }
        }

        return null;
    }
}
