<?php

namespace App\Http\Resources\Concerns;

/**
 * Menyematkan data sisi-pengunjung (reaksi, berita terkait) pada resource.
 *
 * Dipakai PostResource untuk menampilkan apakah pengunjung sudah menyukai
 * berita sebelumnya, tanpa mengandalkan state server.
 */
trait HasVisitorData
{
    protected ?array $visitorData = null;

    public function setVisitorData(array $data): static
    {
        $this->visitorData = $data;

        return $this;
    }

    protected function visitorData(): ?array
    {
        return $this->visitorData;
    }
}
