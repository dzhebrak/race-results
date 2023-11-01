<?php declare(strict_types=1);

namespace App\Entity;

enum RaceDistance: string
{
    case Long = 'long';
    case Medium = 'medium';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
