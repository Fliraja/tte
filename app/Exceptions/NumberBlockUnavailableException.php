<?php

namespace App\Exceptions;

use RuntimeException;

class NumberBlockUnavailableException extends RuntimeException
{
    public function __construct(int $year)
    {
        parent::__construct("Blok nomor tahun {$year} belum diatur atau sudah habis. Silakan hubungi administrator.");
    }
}
