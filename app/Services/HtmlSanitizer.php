<?php

namespace App\Services;

/**
 * Sanitasi HTML isi berita (spec 12: HTMLPurifier, whitelist tag).
 * Klien tidak dipercaya; sanitasi terjadi di server sebelum disimpan.
 */
final class HtmlSanitizer
{
    /**
     * Tag yang diizinkan untuk isi berita (TipTap output).
     */
    private const ALLOWED = [
        'p', 'br', 'hr', 'strong', 'em', 'u', 's', 'blockquote',
        'h2', 'h3', 'h4',
        'ul', 'ol', 'li',
        'a' => ['href', 'title', 'rel', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'figure', 'figcaption',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'iframe' => ['src', 'width', 'height', 'title', 'loading'],
        'span', 'div', 'mark', 'small', 'code', 'pre',
    ];

    public function purify(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $purifier = $this->makePurifier();

        return $purifier->purify($html);
    }

    private function makePurifier(): \HTMLPurifier
    {
        $config = \HTMLPurifier_Config::createDefault();

        $config->set('HTML.Allowed', $this->allowedString());
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube\.com/embed/|www\.youtube-nocookie\.com/embed/)%i');
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.AutoParagraph', false);
        $config->set('URI.DisableExternalResources', false);
        $config->set('Cache.SerializerPath', storage_path('app/purifier'));

        return new \HTMLPurifier($config);
    }

    private function allowedString(): string
    {
        $parts = [];

        foreach (self::ALLOWED as $tag => $attrs) {
            if (is_int($tag)) {
                $parts[] = $attrs; // tag tanpa atribut
            } else {
                $parts[] = $tag . '[' . implode('|', $attrs) . ']';
            }
        }

        return implode(',', $parts);
    }
}
