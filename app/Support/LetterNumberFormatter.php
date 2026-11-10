<?php

namespace App\Support;

use App\Models\LetterDestination;
use App\Models\LetterType;
use App\Models\LetterUnit;
use Carbon\Carbon;
use DateTimeInterface;
use InvalidArgumentException;

class LetterNumberFormatter
{
    /**
     * Format a letter number according to RSU Nirwana specification.
     *
     * Standard: {code}.{seq}/{destination}/RSUN/{roman_month}/{year}
     * With unit: {code}.{seq}/{fixed_peruntukan}/{unit}/RSUN/{roman_month}/{year}
     */
    public static function format(
        LetterType $type,
        int $sequence,
        LetterDestination|string|null $destination = null,
        LetterUnit|string|null $unit = null,
        ?DateTimeInterface $dateTime = null
    ): string {
        $tz = config('app.timezone', 'Asia/Makassar');
        $date = $dateTime ? Carbon::instance($dateTime)->copy()->setTimezone($tz) : Carbon::now($tz);

        $pad = (int) config('tte.number_pad', 0);
        $seqFormatted = $pad > 0 ? str_pad((string) $sequence, $pad, '0', STR_PAD_LEFT) : (string) $sequence;

        $typeCode = $type->code;
        $romanMonth = RomanMonth::fromNumber((int) $date->format('n'));
        $year = $date->format('Y');

        if ($type->uses_unit) {
            $destCode = $type->fixed_peruntukan ?: 'SPO';
            $unitCode = $unit instanceof LetterUnit ? $unit->code : (string) $unit;

            if (trim($unitCode) === '') {
                throw new InvalidArgumentException("Unit wajib diisi untuk jenis surat {$type->name}.");
            }

            return "{$typeCode}.{$seqFormatted}/{$destCode}/{$unitCode}/RSUN/{$romanMonth}/{$year}";
        }

        $destCode = $destination instanceof LetterDestination ? $destination->code : (string) $destination;

        if (trim($destCode) === '') {
            throw new InvalidArgumentException("Peruntukan wajib diisi untuk jenis surat {$type->name}.");
        }

        return "{$typeCode}.{$seqFormatted}/{$destCode}/RSUN/{$romanMonth}/{$year}";
    }
}
