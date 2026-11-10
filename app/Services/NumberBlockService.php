<?php

namespace App\Services;

use App\Models\LetterNumberBlock;
use App\Models\QrGeneration;
use Illuminate\Validation\ValidationException;

class NumberBlockService
{
    /**
     * Get the active number block for the given year.
     * The active block is the one with the smallest start_number that still has remaining numbers.
     */
    public function activeBlock(int $year, bool $lock = false): ?LetterNumberBlock
    {
        $query = LetterNumberBlock::where('year', $year)
            ->orderBy('start_number');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->first(function (LetterNumberBlock $block) {
            return $block->end_number === null || $block->next_number <= $block->end_number;
        });
    }

    /**
     * Validate a new block according to Spec §4 Rules 1-4.
     *
     * 1. start_number >= 1, end_number >= start_number (if specified).
     * 2. Cannot overlap with another block in the same year.
     * 3. If an unlimited block exists, no block after it can be created.
     * 4. start_number must be greater than the maximum sequence number issued that year.
     *
     * @throws ValidationException
     */
    public function validateNewBlock(int $year, int $startNumber, ?int $endNumber = null): void
    {
        // Rule 1: start_number >= 1, end_number >= start_number
        if ($startNumber < 1) {
            throw ValidationException::withMessages([
                'start_number' => 'Nomor awal harus minimal 1.',
            ]);
        }

        if ($endNumber !== null && $endNumber < $startNumber) {
            throw ValidationException::withMessages([
                'end_number' => 'Nomor akhir harus lebih besar atau sama dengan nomor awal.',
            ]);
        }

        $existingBlocks = LetterNumberBlock::where('year', $year)->get();

        // Rule 3: Jika ada blok tanpa batas akhir, blok setelahnya tidak boleh dibuat
        $unlimitedBlock = $existingBlocks->firstWhere('end_number', null);
        if ($unlimitedBlock && $startNumber >= $unlimitedBlock->start_number) {
            throw ValidationException::withMessages([
                'start_number' => "Tidak dapat membuat blok setelah blok tanpa batas akhir (mulai {$unlimitedBlock->start_number}).",
            ]);
        }

        if ($endNumber === null) {
            $blockAfter = $existingBlocks->first(fn ($b) => $b->start_number >= $startNumber);
            if ($blockAfter) {
                throw ValidationException::withMessages([
                    'end_number' => "Blok tanpa batas akhir hanya dapat dibuat di akhir urutan (ada blok lain mulai {$blockAfter->start_number}).",
                ]);
            }
        }

        // Rule 2: Tidak boleh tumpang tindih dengan blok lain di tahun yang sama
        foreach ($existingBlocks as $existing) {
            $eStart = $existing->start_number;
            $eEnd = $existing->end_number;

            if ($eEnd === null) {
                if ($endNumber === null || $endNumber >= $eStart) {
                    throw ValidationException::withMessages([
                        'start_number' => "Rentang nomor tumpang tindih dengan blok yang sudah ada ({$eStart}-seterusnya).",
                    ]);
                }
            } else {
                if ($endNumber === null) {
                    if ($startNumber <= $eEnd) {
                        throw ValidationException::withMessages([
                            'start_number' => "Rentang nomor tumpang tindih dengan blok yang sudah ada ({$eStart}-{$eEnd}).",
                        ]);
                    }
                } else {
                    if (max($startNumber, $eStart) <= min($endNumber, $eEnd)) {
                        throw ValidationException::withMessages([
                            'start_number' => "Rentang nomor ({$startNumber}-{$endNumber}) tumpang tindih dengan blok yang sudah ada ({$eStart}-{$eEnd}).",
                        ]);
                    }
                }
            }
        }

        // Rule 4: start_number harus lebih besar dari nomor terbesar yang sudah diterbitkan tahun itu
        $maxIssued = QrGeneration::where('number_year', $year)
            ->whereNotNull('sequence_number')
            ->max('sequence_number');

        if ($maxIssued !== null && $startNumber <= $maxIssued) {
            throw ValidationException::withMessages([
                'start_number' => "Nomor awal ({$startNumber}) harus lebih besar dari nomor terbesar yang sudah diterbitkan tahun {$year} ({$maxIssued}).",
            ]);
        }
    }

    /**
     * Create a validated number block.
     *
     * @throws ValidationException
     */
    public function createBlock(int $year, int $startNumber, ?int $endNumber, int $userId): LetterNumberBlock
    {
        $this->validateNewBlock($year, $startNumber, $endNumber);

        return LetterNumberBlock::create([
            'year' => $year,
            'start_number' => $startNumber,
            'end_number' => $endNumber,
            'next_number' => $startNumber,
            'created_by' => $userId,
        ]);
    }
}

