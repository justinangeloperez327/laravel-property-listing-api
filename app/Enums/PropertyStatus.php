<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case Available = 'available';
    case Pending = 'pending';
    case Sold = 'sold';
    case Rented = 'rented';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
