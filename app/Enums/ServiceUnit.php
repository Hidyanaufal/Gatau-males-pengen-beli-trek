<?php

namespace App\Enums;

enum ServiceUnit: string
{
    case Kg = 'kg';
    case Item = 'item';

    public function label(): string
    {
        return match ($this) {
            self::Kg => 'Per kilogram',
            self::Item => 'Per item',
        };
    }
}