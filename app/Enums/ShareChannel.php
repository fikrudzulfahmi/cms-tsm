<?php

namespace App\Enums;

enum ShareChannel: string
{
    case Whatsapp = 'whatsapp';
    case Facebook = 'facebook';
    case X = 'x';
    case Telegram = 'telegram';
    case Copy = 'copy';
    case Native = 'native';

    public function label(): string
    {
        return match ($this) {
            self::Whatsapp => 'WhatsApp',
            self::Facebook => 'Facebook',
            self::X => 'X',
            self::Telegram => 'Telegram',
            self::Copy => 'Salin tautan',
            self::Native => 'Web Share',
        };
    }
}
