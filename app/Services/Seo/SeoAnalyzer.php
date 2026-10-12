<?php

namespace App\Services\Seo;

use App\Models\Post;
use Illuminate\Support\Str;

/**
 * Analisis SEO ketat (spec 7.2). SATU sumber kebenaran di server.
 * Dipakai editor real-time + laporan → skor tidak pernah berbeda.
 */
final class SeoAnalyzer
{
    private const TRANSITION_WORDS = [
        'selain', 'kemudian', 'selanjutnya', 'akhirnya', 'namun', 'tetapi',
        'sebaliknya', 'sementara', 'oleh karena itu', 'sehingga', 'maka',
        'pada', 'sedangkan', 'berikut', 'misalnya', 'yaitu', 'antara lain',
        'pertama', 'kedua', 'ketiga', 'selain itu', 'tak hanya', 'bahkan',
    ];

    private const PASSIVE_PREFIXES = ['di', 'ter', 'ke'];

    /**
     * @return array{score: int, checks: array<int, array{group: string, label: string, status: string, text: string}>, summary: array<string, int>}
     */
    public function analyze(array $input): array
    {
        $keyword = (string) ($input['focus_keyword'] ?? '');
        $seoTitle = (string) ($input['seo_title'] ?? ($input['title'] ?? ''));
        $meta = (string) ($input['seo_description'] ?? '');
        $slug = (string) ($input['slug'] ?? '');
        $body = (string) ($input['body'] ?? '');
        $excerpt = (string) ($input['excerpt'] ?? '');
        $hasFeatured = ! empty($input['featured_media_id']);
        $featuredAlt = (string) ($input['featured_alt'] ?? '');
        $wordCount = $this->wordCount($body);

        $checks = [];
        $weightSum = 0.0;
        $weightOk = 0.0;

        $add = function (string $group, string $label, string $status, string $text, float $weight) use (&$checks, &$weightSum, &$weightOk): void {
            $checks[] = compact('group', 'label', 'status', 'text');
            $weightSum += $weight;
            if ($status === 'ok') {
                $weightOk += $weight;
            } elseif ($status === 'warning') {
                $weightOk += $weight * 0.5;
            }
        };

        // ---------- KATA KUNCI ----------
        if ($keyword === '') {
            $add('Kata kunci', 'Kata kunci fokus', 'error', 'Kata kunci fokus wajib diisi agar analisis berjalan.', 10);
        } else {
            $kw = mb_strtolower($keyword);

            // di judul SEO (sebaiknya di awal)
            $titlePos = $kw !== '' ? mb_strpos(mb_strtolower($seoTitle), $kw) : false;
            if ($titlePos === false) {
                $add('Kata kunci', 'Ada di judul SEO', 'error', "Tambahkan kata kunci \"$keyword\" ke judul SEO.", 8);
            } else {
                $atStart = $titlePos < 10;
                $add('Kata kunci', 'Ada di judul SEO' . ($atStart ? ' (di awal)' : ''), 'ok', $atStart
                    ? 'Kata kunci berada di awal judul SEO.'
                    : 'Kata kunci ada di judul. Lebih baik jika diletakkan di awal.', 8);
            }

            // di paragraf pertama
            $firstPara = $this->firstParagraph($body);
            $this->contains($firstPara, $kw)
                ? $add('Kata kunci', 'Ada di paragraf pertama', 'ok', 'Kata kunci muncul di paragraf pertama.', 6)
                : $add('Kata kunci', 'Ada di paragraf pertama', 'error', 'Masukkan kata kunci di paragraf pertama isi berita.', 6);

            // di subjudul H2/H3
            $headings = $this->headings($body);
            $inHeading = false;
            foreach ($headings as $h) {
                if ($this->contains($h, $kw)) {
                    $inHeading = true;
                    break;
                }
            }
            $inHeading
                ? $add('Kata kunci', 'Ada di subjudul H2/H3', 'ok', 'Kata kunci ditemukan di minimal satu subjudul.', 5)
                : $add('Kata kunci', 'Ada di subjudul H2/H3', 'error', 'Tambahkan kata kunci ke salah satu subjudul H2 atau H3.', 5);

            // di slug
            $slugKw = Str::slug($keyword);
            $slugKw !== '' && str_contains(mb_strtolower($slug), $slugKw)
                ? $add('Kata kunci', 'Ada di slug', 'ok', 'Kata kunci ada di slug.', 4)
                : $add('Kata kunci', 'Ada di slug', 'error', 'Slug sebaiknya mengandung kata kunci.', 4);

            // di meta description
            $this->contains($meta, $kw)
                ? $add('Kata kunci', 'Ada di meta description', 'ok', 'Kata kunci ada di meta description.', 5)
                : $add('Kata kunci', 'Ada di meta description', 'error', 'Tambahkan kata kunci ke meta description.', 5);

            // di alt gambar utama
            if (! $hasFeatured) {
                $add('Kata kunci', 'Ada di alt gambar utama', 'error', 'Berita belum punya gambar utama.', 4);
            } elseif ($this->contains($featuredAlt, $kw)) {
                $add('Kata kunci', 'Ada di alt gambar utama', 'ok', 'Kata kunci ada di teks alt gambar utama.', 4);
            } else {
                $add('Kata kunci', 'Ada di alt gambar utama', 'error', 'Tambahkan kata kunci ke teks alt gambar utama.', 4);
            }

            // kepadatan
            $density = $this->keywordDensity($body, $kw);
            if ($density < 0.5) {
                $add('Kata kunci', 'Kepadatan kata kunci', 'warning', "Kepadatan {$density}%. Terlalu rendah — munculkan kata kunci beberapa kali lagi.", 3);
            } elseif ($density > 2.5) {
                $add('Kata kunci', 'Kepadatan kata kunci', 'warning', "Kepadatan {$density}%. Terlalu tinggi — kurangi pengulangan kata kunci.", 3);
            } else {
                $add('Kata kunci', 'Kepadatan kata kunci', 'ok', "Kepadatan {$density}% — dalam rentang ideal 0,5%–2,5%.", 3);
            }

            // kanibalisasi: kata kunci dipakai berita lain
            $dup = Post::where('focus_keyword', $keyword)
                ->when(! empty($input['id']), fn ($q) => $q->where('id', '!=', $input['id']))
                ->exists();
            $dup
                ? $add('Kata kunci', 'Belum dipakai berita lain', 'error', "Kata kunci \"$keyword\" sudah dipakai berita lain. Ubah agar tidak saling merebut peringkat.", 5)
                : $add('Kata kunci', 'Belum dipakai berita lain', 'ok', 'Kata kunci belum dipakai berita lain.', 5);
        }

        // ---------- JUDUL ----------
        $titleLen = mb_strlen($seoTitle);
        if ($titleLen < 30 || $titleLen > 60) {
            $add('Judul', 'Panjang judul SEO', 'warning', "Judul $titleLen karakter. Idealnya 30–60 karakter.", 4);
        } else {
            $add('Judul', 'Panjang judul SEO', 'ok', "Judul $titleLen karakter.", 4);
        }

        $uniqueTitle = ! Post::where('seo_title', $seoTitle)
            ->when(! empty($input['id']), fn ($q) => $q->where('id', '!=', $input['id']))
            ->exists();
        $uniqueTitle
            ? $add('Judul', 'Judul SEO unik', 'ok', 'Judul SEO belum dipakai di situs.', 4)
            : $add('Judul', 'Judul SEO unik', 'error', 'Judul SEO sudah dipakai berita lain. Ganti judul.', 4);

        // ---------- META ----------
        $metaLen = mb_strlen($meta);
        if ($metaLen < 120 || $metaLen > 160) {
            $add('Meta description', 'Panjang meta description', 'warning', "Meta description $metaLen karakter. Idealnya 120–160 karakter.", 4);
        } else {
            $add('Meta description', 'Panjang meta description', 'ok', "Meta description $metaLen karakter.", 4);
        }

        // ---------- SLUG ----------
        $slugOk = preg_match('/^[a-z0-9-]+$/', $slug) && strlen($slug) <= 75;
        $slugOk
            ? $add('Slug', 'Format slug', 'ok', 'Slug bersih: huruf kecil, tanda hubung, maks 75 karakter.', 3)
            : $add('Slug', 'Format slug', 'error', 'Slug harus huruf kecil, tanda hubung, maks 75 karakter.', 3);

        // ---------- KONTEN ----------
        if ($wordCount < 300) {
            $add('Konten', 'Minimal 300 kata', 'error', "Isi baru $wordCount kata. Minimal 300 kata agar SEO optimal.", 8);
        } else {
            $add('Konten', 'Minimal 300 kata', 'ok', "Isi $wordCount kata.", 8);
        }

        // satu H1 saja (judul) + urutan heading
        $h1Count = $this->countTag($body, 'h1');
        if ($h1Count === 0) {
            $add('Konten', 'Satu H1 saja', 'ok', 'Isi berita tidak punya H1; judul menjadi H1 tunggal.', 3);
        } else {
            $add('Konten', 'Satu H1 saja', 'error', 'Isi mengandung ' . $h1Count . ' tag H1. H1 cukup judul saja.', 3);
        }

        // subjudul tiap 300 kata
        $hCount = $this->countTag($body, 'h2') + $this->countTag($body, 'h3');
        $need = max(1, intdiv($wordCount, 300));
        if ($hCount >= $need) {
            $add('Konten', 'Subjudul tiap 300 kata', 'ok', "Ada $hCount subjudul.", 3);
        } else {
            $add('Konten', 'Subjudul tiap 300 kata', 'warning', "Isi $wordCount kata tapi baru $hCount subjudul. Tambah minimal $need subjudul.", 3);
        }

        // paragraf maks 150 kata
        $longPara = $this->longestParagraphWords($body);
        if ($longPara > 150) {
            $add('Konten', 'Paragraf maksimal 150 kata', 'warning', "Paragraf terpanjang $longPara kata. Pecah jadi beberapa paragraf.", 2);
        } else {
            $add('Konten', 'Paragraf maksimal 150 kata', 'ok', "Paragraf terpanjang $longPara kata.", 2);
        }

        // ---------- KETERBACAAN ----------
        $longSentencePct = $this->longSentencePercent($body);
        if ($longSentencePct > 25) {
            $add('Keterbacaan', 'Kalimat panjang ≤ 25%', 'warning', round($longSentencePct) . "% kalimat lebih dari 25 kata. Pendekkan kalimat.", 3);
        } else {
            $add('Keterbacaan', 'Kalimat panjang ≤ 25%', 'ok', round($longSentencePct) . '% kalimat panjang.', 3);
        }

        $transitionPct = $this->transitionPercent($body);
        if ($transitionPct < 20) {
            $add('Keterbacaan', 'Kata transisi ≥ 20%', 'suggestion', round($transitionPct) . '% kalimat memakai kata transisi. Tambahkan kata seperti "selanjutnya", "kemudian".', 2);
        } else {
            $add('Keterbacaan', 'Kata transisi ≥ 20%', 'ok', round($transitionPct) . '% kalimat memakai kata transisi.', 2);
        }

        $passivePct = $this->passivePercent($body);
        if ($passivePct > 15) {
            $add('Keterbacaan', 'Kalimat pasif ≤ 15%', 'suggestion', round($passivePct) . "% kalimat terlihat pasif. Ubah ke kalimat aktif.", 2);
        } else {
            $add('Keterbacaan', 'Kalimat pasif ≤ 15%', 'ok', round($passivePct) . '% kalimat pasif.', 2);
        }

        // ---------- Tautan ----------
        $internal = $this->countInternalLinks($body);
        $internal >= 1
            ? $add('Tautan', 'Minimal 1 tautan internal', 'ok', "Ada $internal tautan internal.", 3)
            : $add('Tautan', 'Minimal 1 tautan internal', 'error', 'Tambahkan minimal satu tautan ke berita atau halaman lain di situs.', 3);

        $external = $this->countExternalLinks($body);
        $external >= 1
            ? $add('Tautan', 'Minimal 1 tautan keluar', 'suggestion', "Ada $external tautan keluar.", 1)
            : $add('Tautan', 'Minimal 1 tautan keluar', 'suggestion', 'Tautan keluar ke sumber tepercaya memperkuat kredibilitas.', 1);

        // ---------- Gambar ----------
        $imgs = $this->imagesWithoutAlt($body);
        $imgs === 0
            ? $add('Gambar', 'Semua gambar punya alt', 'ok', 'Semua gambar di isi punya teks alt.', 3)
            : $add('Gambar', 'Semua gambar punya alt', 'error', "$imgs gambar belum punya teks alt.", 3);

        if ($hasFeatured) {
            $add('Gambar', 'Ada gambar utama', 'ok', 'Gambar utama sudah dipasang.', 3);
        } else {
            $add('Gambar', 'Ada gambar utama', 'error', 'Pasang gambar utama agar tampil menarik di Google dan WhatsApp.', 3);
        }

        // ---------- Media sosial ----------
        ! empty($input['og_media_id'])
            ? $add('Media sosial', 'Gambar OG 1200x630', 'ok', 'Gambar Open Graph sudah dipasang.', 3)
            : $add('Media sosial', 'Gambar OG 1200x630', 'error', 'Pasang gambar Open Graph 1200x630 untuk pratinjau WhatsApp/Facebook.', 3);

        // ---------- skor ----------
        $score = $weightSum > 0 ? (int) round(($weightOk / $weightSum) * 100) : 0;

        $summary = [
            'ok' => count(array_filter($checks, fn ($c) => $c['status'] === 'ok')),
            'warning' => count(array_filter($checks, fn ($c) => $c['status'] === 'warning')),
            'error' => count(array_filter($checks, fn ($c) => $c['status'] === 'error')),
            'suggestion' => count(array_filter($checks, fn ($c) => $c['status'] === 'suggestion')),
            'word_count' => $wordCount,
        ];

        return [
            'score' => $score,
            'checks' => $checks,
            'summary' => $summary,
        ];
    }

    private function wordCount(string $body): int
    {
        $text = trim(strip_tags($body));

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text));
    }

    private function firstParagraph(string $body): string
    {
        if (preg_match('/<p[^>]*>([\s\S]*?)<\/p>/i', $body, $m)) {
            return strip_tags($m[1]);
        }
        $parts = preg_split('/\n\n+/', strip_tags($body));

        return (string) ($parts[0] ?? '');
    }

    private function headings(string $body): array
    {
        preg_match_all('/<h[23][^>]*>([\s\S]*?)<\/h[23]>/i', $body, $m);

        return array_map('strip_tags', $m[1] ?? []);
    }

    private function contains(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return false;
        }

        return mb_strpos(mb_strtolower($haystack), mb_strtolower($needle)) !== false;
    }

    private function countTag(string $body, string $tag): int
    {
        return preg_match_all('/<' . $tag . '[^>]*>/i', $body);
    }

    private function keywordDensity(string $body, string $keyword): float
    {
        $text = mb_strtolower(strip_tags($body));
        $words = preg_split('/\s+/u', $text);
        $total = count($words);
        if ($total === 0) {
            return 0.0;
        }
        $count = 0;
        foreach ($words as $w) {
            if (str_contains($w, $keyword)) {
                $count++;
            }
        }

        return round(($count / $total) * 100, 2);
    }

    private function sentences(string $body): array
    {
        $text = strip_tags($body);
        $parts = preg_split('/(?<=[.!?])\s+/u', $text);

        return array_values(array_filter(array_map('trim', $parts), fn ($s) => $s !== ''));
    }

    private function longestParagraphWords(string $body): int
    {
        $text = strip_tags($body);
        $paras = preg_split('/\n\s*\n+/u', $text);
        $max = 0;
        foreach ($paras as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            $max = max($max, count(explode(' ', $p)));
        }

        return $max;
    }

    private function longSentencePercent(string $body): float
    {
        $sentences = $this->sentences($body);
        if (count($sentences) === 0) {
            return 0.0;
        }
        $long = 0;
        foreach ($sentences as $s) {
            if (count(explode(' ', $s)) > 25) {
                $long++;
            }
        }

        return round(($long / count($sentences)) * 100, 1);
    }

    private function transitionPercent(string $body): float
    {
        $sentences = $this->sentences($body);
        if (count($sentences) === 0) {
            return 0.0;
        }
        $hit = 0;
        foreach ($sentences as $s) {
            $low = mb_strtolower($s);
            foreach (self::TRANSITION_WORDS as $w) {
                if (str_contains($low, $w)) {
                    $hit++;
                    break;
                }
            }
        }

        return round(($hit / count($sentences)) * 100, 1);
    }

    private function passivePercent(string $body): float
    {
        $sentences = $this->sentences($body);
        if (count($sentences) === 0) {
            return 0.0;
        }
        $passive = 0;
        foreach ($sentences as $s) {
            $words = explode(' ', mb_strtolower($s));
            foreach ($words as $w) {
                $w = trim($w, " ,.;:!?()[]{}\"'");
                if (in_array($w, self::PASSIVE_PREFIXES, true)) {
                    $passive++;
                    break;
                }
            }
        }

        return round(($passive / count($sentences)) * 100, 1);
    }

    private function countInternalLinks(string $body): int
    {
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $body, $m);
        $count = 0;
        foreach ($m[1] ?? [] as $href) {
            if (str_starts_with($href, '/') || str_starts_with($href, url('/'))) {
                $count++;
            }
        }

        return $count;
    }

    private function countExternalLinks(string $body): int
    {
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $body, $m);
        $count = 0;
        foreach ($m[1] ?? [] as $href) {
            if (str_starts_with($href, 'http') && ! str_starts_with($href, url('/'))) {
                $count++;
            }
        }

        return $count;
    }

    private function imagesWithoutAlt(string $body): int
    {
        preg_match_all('/<img[^>]*>/i', $body, $m);
        $count = 0;
        foreach ($m[0] ?? [] as $img) {
            if (! preg_match('/alt\s*=\s*["\'][^"\']+["\']/i', $img)) {
                $count++;
            }
        }

        return $count;
    }
}
