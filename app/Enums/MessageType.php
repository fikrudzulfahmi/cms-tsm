<?php

namespace App\Enums;

enum MessageType: string
{
    case Pendaftaran = 'pendaftaran';
    case Pertanyaan = 'pertanyaan';

    public function label(): string
    {
        return match ($this) {
            self::Pendaftaran => 'Pendaftaran',
            self::Pertanyaan => 'Pertanyaan',
        };
    }
}
