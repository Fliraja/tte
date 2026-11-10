<?php

namespace Tests\Unit;

use App\Models\LetterDestination;
use App\Models\LetterType;
use App\Models\LetterUnit;
use App\Support\LetterNumberFormatter;
use App\Support\RomanMonth;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class LetterNumberFormatterTest extends TestCase
{
    public function test_roman_month_conversions(): void
    {
        $expected = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        foreach ($expected as $month => $roman) {
            $this->assertSame($roman, RomanMonth::fromNumber($month));
        }
    }

    public function test_roman_month_invalid_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        RomanMonth::fromNumber(13);
    }

    public function test_format_standard_pattern(): void
    {
        $type = new LetterType(['code' => '03', 'name' => 'Surat Edaran', 'uses_unit' => false]);
        $dest = new LetterDestination(['code' => 'DIR', 'name' => 'Direktur']);
        $date = Carbon::create(2026, 10, 15, 10, 0, 0, 'Asia/Makassar');

        $result = LetterNumberFormatter::format($type, 527, $dest, null, $date);

        $this->assertSame('03.527/DIR/RSUN/X/2026', $result);
    }

    public function test_format_with_unit_pattern(): void
    {
        $type = new LetterType([
            'code' => '16',
            'name' => 'Standar Prosedur Operasional',
            'uses_unit' => true,
            'fixed_peruntukan' => 'SPO',
        ]);
        $unit = new LetterUnit(['code' => 'IBS', 'name' => 'Instalasi Bedah Sentral']);
        $date = Carbon::create(2026, 8, 20, 14, 0, 0, 'Asia/Makassar');

        $result = LetterNumberFormatter::format($type, 508, null, $unit, $date);

        $this->assertSame('16.508/SPO/IBS/RSUN/VIII/2026', $result);
    }

    public function test_format_respects_padding_config(): void
    {
        config(['tte.number_pad' => 3]);

        $type = new LetterType(['code' => '01', 'name' => 'Surat Keputusan', 'uses_unit' => false]);
        $dest = new LetterDestination(['code' => 'DIR', 'name' => 'Direktur']);
        $date = Carbon::create(2026, 1, 5, 10, 0, 0, 'Asia/Makassar');

        $result = LetterNumberFormatter::format($type, 5, $dest, null, $date);

        $this->assertSame('01.005/DIR/RSUN/I/2026', $result);
    }

    public function test_format_uses_wita_timezone_boundary(): void
    {
        $type = new LetterType(['code' => '01', 'name' => 'Surat Keputusan', 'uses_unit' => false]);
        $dest = new LetterDestination(['code' => 'DIR', 'name' => 'Direktur']);

        // 31 Dec 2026 23:30 UTC is 1 Jan 2027 07:30 WITA (+8)
        $dateUtc = Carbon::create(2026, 12, 31, 23, 30, 0, 'UTC');

        $result = LetterNumberFormatter::format($type, 1, $dest, null, $dateUtc);

        $this->assertSame('01.1/DIR/RSUN/I/2027', $result);
    }

    public function test_missing_destination_throws_exception_for_standard(): void
    {
        $type = new LetterType(['code' => '01', 'name' => 'Surat Keputusan', 'uses_unit' => false]);

        $this->expectException(InvalidArgumentException::class);
        LetterNumberFormatter::format($type, 1, null);
    }

    public function test_missing_unit_throws_exception_for_unit_type(): void
    {
        $type = new LetterType(['code' => '16', 'name' => 'SPO', 'uses_unit' => true]);

        $this->expectException(InvalidArgumentException::class);
        LetterNumberFormatter::format($type, 1, null, null);
    }
}
