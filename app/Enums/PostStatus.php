<?php

namespace App\Enums;

enum PostStatus: string
{
    case Draft = 'draf';
    case PendingReview = 'menunggu_review';
    case Scheduled = 'terjadwal';
    case Published = 'terbit';
    case Archived = 'arsip';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::PendingReview => 'Menunggu review',
            self::Scheduled => 'Terjadwal',
            self::Published => 'Terbit',
            self::Archived => 'Arsip',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Published => 'green',
            self::Draft => 'gray',
            self::PendingReview => 'amber',
            self::Scheduled => 'blue',
            self::Archived => 'gray',
        };
    }

    public static function publicVisible(): array
    {
        return [self::Published->value];
    }
}
