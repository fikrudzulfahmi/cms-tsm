<?php

namespace App\Services;

/**
 * Menghitung rating berita (spec 7.3).
 *
 * - rating_suka        = like / (like + unlike) x 100%
 * - tingkat_keterlibatan = (like + unlike) / pengunjung unik x 100%
 * - skor_bintang       = 5 x batas bawah interval Wilson
 *   (lower bound) dari like/(like+unlike), agar suara sedikit
 *   tidak menyesatkan.
 *
 * Rumus interval Wilson (Wilson score interval, 95% confidence):
 *   p + z²/2n ± z·sqrt(p(1-p)/n + z²/4n²)
 *   ---------------------
 *        1 + z²/n
 * dengan z = 1.96 (95%), p = proporsi suka, n = total suara.
 */
final class RatingCalculator
{
    private const Z = 1.96; // 95% confidence

    /**
     * @param  array{likes: int, unlikes: int, unique_visitors?: int}  $data
     * @return array{like_percent: float, engagement_percent: float, stars: float}
     */
    public function calculate(int $likes, int $unlikes, int $uniqueVisitors = 0): array
    {
        $total = $likes + $unlikes;

        $likePercent = $total > 0
            ? round(($likes / $total) * 100, 2)
            : 0.0;

        $engagement = ($uniqueVisitors > 0 && $total > 0)
            ? round(($total / $uniqueVisitors) * 100, 2)
            : 0.0;

        $stars = $total > 0
            ? round(5 * $this->wilsonLowerBound($likes, $total), 1)
            : 0.0;

        return [
            'like_percent' => $likePercent,
            'engagement_percent' => $engagement,
            'stars' => $stars,
        ];
    }

    /**
     * Batas bawah interval Wilson untuk proporsi suka.
     */
    public function wilsonLowerBound(int $positive, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        $p = $positive / $total;
        $z2 = self::Z * self::Z;
        $denominator = 1 + $z2 / $total;
        $center = $p + $z2 / (2 * $total);
        $spread = self::Z * sqrt(
            ($p * (1 - $p) / $total) + ($z2 / (4 * $total * $total))
        );

        $lower = ($center - $spread) / $denominator;

        return max(0.0, min(1.0, $lower));
    }
}
