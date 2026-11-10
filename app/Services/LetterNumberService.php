<?php

namespace App\Services;

use App\Exceptions\NumberBlockUnavailableException;
use App\Models\LetterDestination;
use App\Models\LetterNumberBlock;
use App\Models\LetterType;
use App\Models\LetterUnit;
use App\Models\QrGeneration;
use App\Models\QrGenerationEvent;
use App\Models\Signer;
use App\Models\User;
use App\Support\LetterNumberFormatter;
use App\Support\RomanMonth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class LetterNumberService
{
    /**
     * Issue a new letter record with automatic, manual, override, or no numbering.
     *
     * @throws NumberBlockUnavailableException
     * @throws ValidationException
     * @throws InvalidArgumentException
     */
    public function issue(
        LetterType $letterType,
        ?LetterDestination $destination,
        ?LetterUnit $unit,
        Signer $signer,
        string $perihal,
        User $user,
        ?string $ipAddress = null,
        ?string $manualNumber = null,
        ?string $overrideReason = null,
        ?string $reference = null
    ): QrGeneration {
        if (! $letterType->is_active) {
            throw new InvalidArgumentException("Jenis surat {$letterType->name} sedang nonaktif.");
        }

        if (! $signer->is_active) {
            throw new InvalidArgumentException("Penandatangan {$signer->name} sedang nonaktif.");
        }

        $tz = config('app.timezone', 'Asia/Makassar');
        $now = Carbon::now($tz);
        $year = (int) $now->format('Y');
        $romanMonth = RomanMonth::fromNumber((int) $now->format('n'));

        // Cabang 6.4b: TTE Tanpa Nomor (mis. Hasil MCU)
        if ($letterType->numbering === 'none') {
            if ($manualNumber !== null || $overrideReason !== null) {
                throw new InvalidArgumentException("Jenis surat tanpa nomor tidak boleh menyertakan nomor surat.");
            }

            return DB::transaction(function () use (
                $letterType, $signer, $perihal, $user, $ipAddress, $reference
            ) {
                $generation = QrGeneration::create([
                    'uuid' => (string) Str::uuid(),
                    'signer_id' => $signer->id,
                    'letter_type_id' => $letterType->id,
                    'destination_id' => null,
                    'unit_id' => null,
                    'letter_number' => null,
                    'perihal' => $perihal,
                    'sequence_number' => null,
                    'number_year' => null,
                    'number_month' => null,
                    'number_source' => 'none',
                    'override_reason' => null,
                    'reference' => $reference,
                    'signer_name' => $signer->name,
                    'signer_position' => $signer->position,
                    'status' => 'issued',
                    'generated_by' => $user->id,
                    'ip_address' => $ipAddress,
                ]);

                QrGenerationEvent::create([
                    'qr_generation_id' => $generation->id,
                    'user_id' => $user->id,
                    'event' => 'issued',
                    'note' => null,
                    'created_at' => now(),
                ]);

                return $generation;
            });
        }

        // Cabang 6.4: Nomor Manual (mis. SDM)
        if ($letterType->numbering === 'manual') {
            if ($manualNumber === null || trim($manualNumber) === '') {
                throw ValidationException::withMessages([
                    'letter_number' => 'Nomor surat wajib diisi untuk jenis surat ini.',
                ]);
            }

            $cleanNumber = $this->normalizeNumber($manualNumber);

            if (mb_strlen($cleanNumber) > 100) {
                throw ValidationException::withMessages([
                    'letter_number' => 'Nomor surat maksimal 100 karakter.',
                ]);
            }

            $this->assertUniqueManualNumber($cleanNumber);

            return DB::transaction(function () use (
                $letterType, $destination, $unit, $signer, $perihal, $user, $ipAddress, $cleanNumber, $reference
            ) {
                $generation = QrGeneration::create([
                    'uuid' => (string) Str::uuid(),
                    'signer_id' => $signer->id,
                    'letter_type_id' => $letterType->id,
                    'destination_id' => $destination?->id,
                    'unit_id' => $unit?->id,
                    'letter_number' => $cleanNumber,
                    'perihal' => $perihal,
                    'sequence_number' => null,
                    'number_year' => null,
                    'number_month' => null,
                    'number_source' => 'manual',
                    'override_reason' => null,
                    'reference' => $reference,
                    'signer_name' => $signer->name,
                    'signer_position' => $signer->position,
                    'status' => 'issued',
                    'generated_by' => $user->id,
                    'ip_address' => $ipAddress,
                ]);

                QrGenerationEvent::create([
                    'qr_generation_id' => $generation->id,
                    'user_id' => $user->id,
                    'event' => 'issued',
                    'note' => null,
                    'created_at' => now(),
                ]);

                return $generation;
            });
        }

        // Cabang 6.3: Nomor Otomatis (dengan opsi Override)
        if ($manualNumber !== null && trim($manualNumber) !== '') {
            // Override nomor oleh admin
            if ($overrideReason === null || trim($overrideReason) === '') {
                throw ValidationException::withMessages([
                    'override_reason' => 'Alasan override nomor wajib diisi.',
                ]);
            }

            $cleanNumber = $this->normalizeNumber($manualNumber);

            if (mb_strlen($cleanNumber) > 100) {
                throw ValidationException::withMessages([
                    'letter_number' => 'Nomor surat maksimal 100 karakter.',
                ]);
            }

            $this->assertUniqueManualNumber($cleanNumber);

            return DB::transaction(function () use (
                $letterType, $destination, $unit, $signer, $perihal, $user, $ipAddress,
                $cleanNumber, $overrideReason, $year, $romanMonth, $reference
            ) {
                $generation = QrGeneration::create([
                    'uuid' => (string) Str::uuid(),
                    'signer_id' => $signer->id,
                    'letter_type_id' => $letterType->id,
                    'destination_id' => $destination?->id,
                    'unit_id' => $unit?->id,
                    'letter_number' => $cleanNumber,
                    'perihal' => $perihal,
                    'sequence_number' => null,
                    'number_year' => $year,
                    'number_month' => $romanMonth,
                    'number_source' => 'override',
                    'override_reason' => trim($overrideReason),
                    'reference' => $reference,
                    'signer_name' => $signer->name,
                    'signer_position' => $signer->position,
                    'status' => 'issued',
                    'generated_by' => $user->id,
                    'ip_address' => $ipAddress,
                ]);

                QrGenerationEvent::create([
                    'qr_generation_id' => $generation->id,
                    'user_id' => $user->id,
                    'event' => 'number_overridden',
                    'note' => trim($overrideReason),
                    'created_at' => now(),
                ]);

                return $generation;
            });
        }

        // Standar Otomatis: Blok & Counter
        return DB::transaction(function () use (
            $letterType, $destination, $unit, $signer, $perihal, $user, $ipAddress, $year, $romanMonth, $now, $reference
        ) {
            $block = LetterNumberBlock::where('year', $year)
                ->orderBy('start_number')
                ->lockForUpdate()
                ->get()
                ->first(fn ($b) => $b->end_number === null || $b->next_number <= $b->end_number);

            if (! $block) {
                throw new NumberBlockUnavailableException($year);
            }

            $seq = $block->next_number;
            $block->increment('next_number');

            $formattedNumber = LetterNumberFormatter::format(
                $letterType,
                $seq,
                $destination,
                $unit,
                $now
            );

            $generation = QrGeneration::create([
                'uuid' => (string) Str::uuid(),
                'signer_id' => $signer->id,
                'letter_type_id' => $letterType->id,
                'destination_id' => $destination?->id,
                'unit_id' => $unit?->id,
                'letter_number' => $formattedNumber,
                'perihal' => $perihal,
                'sequence_number' => $seq,
                'number_year' => $year,
                'number_month' => $romanMonth,
                'number_source' => 'auto',
                'override_reason' => null,
                'reference' => $reference,
                'signer_name' => $signer->name,
                'signer_position' => $signer->position,
                'status' => 'issued',
                'generated_by' => $user->id,
                'ip_address' => $ipAddress,
            ]);

            QrGenerationEvent::create([
                'qr_generation_id' => $generation->id,
                'user_id' => $user->id,
                'event' => 'issued',
                'note' => null,
                'created_at' => now(),
            ]);

            return $generation;
        }, attempts: 3);
    }

    /**
     * Normalize letter number by trimming and collapsing multiple spaces.
     */
    public function normalizeNumber(string $number): string
    {
        return preg_replace('/\s+/', ' ', trim($number));
    }

    /**
     * Check if a manual or override letter number is unique across all existing letter numbers,
     * comparing without whitespace and case-insensitively.
     *
     * @throws ValidationException
     */
    public function assertUniqueManualNumber(string $number, ?int $exceptId = null): void
    {
        $normalized = preg_replace('/\s+/', '', mb_strtolower($number));

        $query = DB::table('qr_generations')
            ->whereNotNull('letter_number')
            ->whereRaw("REPLACE(LOWER(letter_number), ' ', '') = ?", [$normalized]);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'letter_number' => "Nomor surat '{$number}' sudah pernah digunakan.",
            ]);
        }
    }
}
