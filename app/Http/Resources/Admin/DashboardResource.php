<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Public\PostCardResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ringkasan dashboard admin (`GET /admin/dashboard?range=`, spec bagian 6.2).
 *
 * Resource ini menerima payload dari `DashboardController` berupa array atau
 * objek dengan kunci berikut (semua opsional, nilai hilang jadi 0/[]):
 *
 *   range ("7d"|"30d"|"12m"), from, to,
 *   cards   => {visits, unique_visitors, pageviews,
 *               posts:{total,published,draft,scheduled},
 *               achievements, competencies, gallery,
 *               comparison:{visits_percent, unique_visitors_percent, pageviews_percent}},
 *   chart   => {labels:[], series:{visits:[], unique_visitors:[], pageviews:[]}},
 *   traffic_sources => [{source, label, visits, percent}],
 *   devices         => [{device, label, visits, percent}],
 *   popular_posts   => koleksi model Post (atau array kartu siap pakai),
 *   pending => {comments, feedback, messages, reports},
 *   alerts  => [{type, severity, message, count, url}]
 */
class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cards = $this->arr('cards');
        $chart = $this->arr('chart');
        $series = $this->arr('chart.series');
        $pending = $this->arr('pending');

        return [
            'range' => (string) $this->find('range', '7d'),
            'period' => [
                'from' => $this->iso($this->find('from')),
                'to' => $this->iso($this->find('to')),
            ],

            'cards' => [
                'visits' => (int) ($cards['visits'] ?? 0),
                'unique_visitors' => (int) ($cards['unique_visitors'] ?? 0),
                'pageviews' => (int) ($cards['pageviews'] ?? 0),
                'posts' => [
                    'total' => (int) ($cards['posts']['total'] ?? 0),
                    'published' => (int) ($cards['posts']['published'] ?? 0),
                    'draft' => (int) ($cards['posts']['draft'] ?? 0),
                    'scheduled' => (int) ($cards['posts']['scheduled'] ?? 0),
                ],
                'achievements' => (int) ($cards['achievements'] ?? 0),
                'competencies' => (int) ($cards['competencies'] ?? 0),
                'gallery' => (int) ($cards['gallery'] ?? 0),
                'comparison' => [
                    'visits_percent' => round((float) data_get($cards, 'comparison.visits_percent', 0), 1),
                    'unique_visitors_percent' => round((float) data_get($cards, 'comparison.unique_visitors_percent', 0), 1),
                    'pageviews_percent' => round((float) data_get($cards, 'comparison.pageviews_percent', 0), 1),
                ],
            ],

            // Grafik kunjungan harian/bulanan (garis tren).
            'chart' => [
                'labels' => array_values((array) ($chart['labels'] ?? [])),
                'series' => [
                    'visits' => array_values((array) ($series['visits'] ?? [])),
                    'unique_visitors' => array_values((array) ($series['unique_visitors'] ?? [])),
                    'pageviews' => array_values((array) ($series['pageviews'] ?? [])),
                ],
            ],

            // Donat sumber trafik dan perangkat.
            'traffic_sources' => $this->labelled('traffic_sources', 'source'),
            'devices' => $this->labelled('devices', 'device'),

            // Berita terpopuler (model Post dibungkus PostCardResource).
            'popular_posts' => $this->posts($this->find('popular_posts', [])),

            // Antrean moderasi + laporan.
            'pending' => [
                'comments' => (int) ($pending['comments'] ?? 0),
                'feedback' => (int) ($pending['feedback'] ?? 0),
                'messages' => (int) ($pending['messages'] ?? 0),
                'reports' => (int) ($pending['reports'] ?? 0),
            ],

            // Peringatan: skor SEO rendah, backup terakhir, antrean gagal.
            'alerts' => collect($this->arr('alerts'))->map(fn ($alert) => [
                'type' => (string) data_get($alert, 'type', 'info'),
                'severity' => (string) data_get($alert, 'severity', 'warning'),
                'message' => (string) data_get($alert, 'message', ''),
                'count' => (int) data_get($alert, 'count', 0),
                'url' => data_get($alert, 'url'),
            ])->values()->all(),
        ];
    }

    /**
     * Ambil nilai dari payload (mendukung notasi titik).
     */
    protected function find(string $key, mixed $default = null): mixed
    {
        return data_get($this->resource, $key) ?? $default;
    }

    /**
     * Paksa nilai menjadi array.
     *
     * @return array<array-key, mixed>
     */
    protected function arr(string $key): array
    {
        $value = $this->find($key, []);

        if ($value instanceof Collection) {
            return $value->all();
        }

        if (is_object($value)) {
            return (array) $value;
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Seragamkan daftar sumber trafik/perangkat.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function labelled(string $key, string $typeKey): array
    {
        return collect($this->arr($key))
            ->map(fn ($item) => [
                $typeKey => (string) data_get($item, $typeKey, 'tidak_diketahui'),
                'label' => (string) data_get($item, 'label', data_get($item, $typeKey, 'Tidak diketahui')),
                'visits' => (int) data_get($item, 'visits', 0),
                'percent' => round((float) data_get($item, 'percent', 0), 1),
            ])
            ->values()
            ->all();
    }

    /**
     * Berita terpopuler: model Post dibungkus PostCardResource, array diteruskan.
     *
     * @return array<int, mixed>
     */
    protected function posts(mixed $items): array
    {
        return collect($items)
            ->map(function ($item) {
                if ($item instanceof Model) {
                    return PostCardResource::make($item)->resolve();
                }

                return is_array($item) ? $item : (array) $item;
            })
            ->values()
            ->all();
    }

    /**
     * Tanggal menjadi ISO 8601 (menerima Carbon maupun string).
     */
    protected function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value)->toISOString();
            } catch (\Throwable) {
                return $value;
            }
        }

        return null;
    }
}
