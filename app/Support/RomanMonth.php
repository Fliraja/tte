<?php

namespace App\Support;

use InvalidArgumentException;

class RomanMonth
{
    private const MAP = [
        1 => 'I',
        2 => 'II',
        3 => 'III',
        4 => 'IV',
        5 => 'V',
        6 => 'VI',
        7 => 'VII',
        8 => 'VIII',
        9 => 'IX',
        10 => 'X',
        11 => 'XI',
        12 => 'XII',
    ];

    public static function fromNumber(int $month): string
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException("Bulan harus di antara 1 dan 12, diberikan: {$month}");
        }

        return self::MAP[$month];
    }
}

