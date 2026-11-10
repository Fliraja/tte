<?php

namespace Tests\Feature;

use App\Exceptions\NumberBlockUnavailableException;
use App\Models\LetterDestination;
use App\Models\LetterNumberBlock;
use App\Models\LetterType;
use App\Models\LetterUnit;
use App\Models\QrGeneration;
use App\Models\Signer;
use App\Models\User;
use App\Services\LetterNumberService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LetterNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private LetterNumberService $service;
    private User $user;
    private Signer $signer;
    private LetterDestination $destination;
    private LetterUnit $unit;
    private LetterType $autoType;
    private LetterType $unitType;
    private LetterType $manualType;
    private LetterType $noneType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LetterNumberService();

        $this->user = User::factory()->create();
        $this->signer = Signer::factory()->create([
            'name' => 'dr. Direktur',
            'position' => 'Direktur Utama',
            'is_active' => true,
        ]);
        $this->destination = LetterDestination::factory()->create(['code' => 'DIR', 'is_active' => true]);
        $this->unit = LetterUnit::factory()->create(['code' => 'IBS', 'is_active' => true]);

        $this->autoType = LetterType::factory()->create([
            'code' => '03',
            'name' => 'Surat Edaran',
            'numbering' => 'auto',
            'uses_unit' => false,
            'is_active' => true,
        ]);

        $this->unitType = LetterType::factory()->create([
            'code' => '16',
            'name' => 'Standar Prosedur Operasional',
            'numbering' => 'auto',
            'uses_unit' => true,
            'fixed_peruntukan' => 'SPO',
            'is_active' => true,
        ]);

        $this->manualType = LetterType::factory()->create([
            'code' => null,
            'name' => 'Surat SDM',
            'numbering' => 'manual',
            'is_active' => true,
        ]);

        $this->noneType = LetterType::factory()->create([
            'code' => null,
            'name' => 'Hasil MCU',
            'numbering' => 'none',
            'show_content' => false,
            'expects_document' => false,
            'reference_label' => 'Nomor Pemeriksaan',
            'is_active' => true,
        ]);
    }

    public function test_issue_auto_standard_format_and_increments_next_number(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        $block = LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 10,
            'end_number' => 20,
            'next_number' => 10,
            'created_by' => $this->user->id,
        ]);

        $gen = $this->service->issue(
            $this->autoType,
            $this->destination,
            null,
            $this->signer,
            'Pemberitahuan',
            $this->user,
            '127.0.0.1'
        );

        $this->assertNotNull($gen);
        $this->assertSame(10, $gen->sequence_number);
        $this->assertSame($year, $gen->number_year);
        $this->assertStringStartsWith('03.10/DIR/RSUN/', $gen->letter_number);
        $this->assertSame('issued', $gen->status);
        $this->assertSame('auto', $gen->number_source);
        $this->assertSame('dr. Direktur', $gen->signer_name);
        $this->assertSame('Direktur Utama', $gen->signer_position);

        // Check block incremented
        $block->refresh();
        $this->assertSame(11, $block->next_number);

        // Check event created
        $this->assertDatabaseHas('qr_generation_events', [
            'qr_generation_id' => $gen->id,
            'event' => 'issued',
        ]);
    }

    public function test_issue_auto_with_unit_format(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 1,
            'end_number' => 10,
            'next_number' => 5,
            'created_by' => $this->user->id,
        ]);

        $gen = $this->service->issue(
            $this->unitType,
            null,
            $this->unit,
            $this->signer,
            'SPO Cuci Tangan',
            $this->user
        );

        $this->assertSame(5, $gen->sequence_number);
        $this->assertStringStartsWith('16.5/SPO/IBS/RSUN/', $gen->letter_number);
    }

    public function test_issue_sequential_numbers(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 1,
            'end_number' => 10,
            'next_number' => 1,
            'created_by' => $this->user->id,
        ]);

        $gen1 = $this->service->issue($this->autoType, $this->destination, null, $this->signer, 'Surat 1', $this->user);
        $gen2 = $this->service->issue($this->autoType, $this->destination, null, $this->signer, 'Surat 2', $this->user);

        $this->assertSame(1, $gen1->sequence_number);
        $this->assertSame(2, $gen2->sequence_number);
    }

    public function test_issue_fails_and_creates_no_record_when_block_exhausted(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 1,
            'end_number' => 5,
            'next_number' => 6, // Exhausted
            'created_by' => $this->user->id,
        ]);

        $initialCount = QrGeneration::count();

        $this->expectException(NumberBlockUnavailableException::class);
        $this->service->issue($this->autoType, $this->destination, null, $this->signer, 'Surat Gagal', $this->user);

        $this->assertSame($initialCount, QrGeneration::count());
    }

    public function test_issue_jumps_to_next_active_block_when_first_is_exhausted(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        // Block 1 exhausted
        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 10,
            'end_number' => 12,
            'next_number' => 13,
            'created_by' => $this->user->id,
        ]);

        // Block 2 active
        $block2 = LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 50,
            'end_number' => 60,
            'next_number' => 50,
            'created_by' => $this->user->id,
        ]);

        $gen = $this->service->issue($this->autoType, $this->destination, null, $this->signer, 'Surat Lompat', $this->user);

        $this->assertSame(50, $gen->sequence_number);
        $block2->refresh();
        $this->assertSame(51, $block2->next_number);
    }

    public function test_issue_manual_numbering_success(): void
    {
        $gen = $this->service->issue(
            $this->manualType,
            null,
            null,
            $this->signer,
            'SK Karyawan',
            $this->user,
            '127.0.0.1',
            ' 050/SDM-RSUN/2026 '
        );

        $this->assertSame('050/SDM-RSUN/2026', $gen->letter_number);
        $this->assertSame('manual', $gen->number_source);
        $this->assertNull($gen->sequence_number);
    }

    public function test_issue_manual_numbering_duplicate_is_rejected(): void
    {
        $this->service->issue(
            $this->manualType,
            null,
            null,
            $this->signer,
            'Surat A',
            $this->user,
            null,
            '001/HRD/2026'
        );

        $this->expectException(ValidationException::class);

        // Reject even with different spacing or casing
        $this->service->issue(
            $this->manualType,
            null,
            null,
            $this->signer,
            'Surat B',
            $this->user,
            null,
            ' 001/hrd/2026 '
        );
    }

    public function test_issue_override_does_not_increment_counter(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        $block = LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 10,
            'end_number' => 20,
            'next_number' => 10,
            'created_by' => $this->user->id,
        ]);

        $gen = $this->service->issue(
            $this->autoType,
            $this->destination,
            null,
            $this->signer,
            'Surat Override',
            $this->user,
            '127.0.0.1',
            '03.999/DIR/RSUN/X/2026',
            'Surat perbaikan dari tanggal kemarin'
        );

        $this->assertSame('03.999/DIR/RSUN/X/2026', $gen->letter_number);
        $this->assertSame('override', $gen->number_source);
        $this->assertSame('Surat perbaikan dari tanggal kemarin', $gen->override_reason);
        $this->assertNull($gen->sequence_number);

        // Next number is UNCHANGED
        $block->refresh();
        $this->assertSame(10, $block->next_number);

        $this->assertDatabaseHas('qr_generation_events', [
            'qr_generation_id' => $gen->id,
            'event' => 'number_overridden',
        ]);
    }

    public function test_issue_none_numbering_mcu_does_not_touch_blocks_or_counter(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        $block = LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 10,
            'end_number' => 20,
            'next_number' => 10,
            'created_by' => $this->user->id,
        ]);

        $gen = $this->service->issue(
            $this->noneType,
            null,
            null,
            $this->signer,
            'Hasil MCU Karyawan PT ABC',
            $this->user,
            '127.0.0.1',
            null,
            null,
            'MCU-2026-001'
        );

        $this->assertNull($gen->letter_number);
        $this->assertNull($gen->sequence_number);
        $this->assertSame('none', $gen->number_source);
        $this->assertSame('MCU-2026-001', $gen->reference);

        // Block next_number completely untouched
        $block->refresh();
        $this->assertSame(10, $block->next_number);
    }

    public function test_unique_constraint_on_year_and_sequence(): void
    {
        $year = 2026;

        QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'generated_by' => $this->user->id,
            'number_year' => $year,
            'sequence_number' => 100,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        // Force insert duplicate (year, sequence)
        QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'generated_by' => $this->user->id,
            'number_year' => $year,
            'sequence_number' => 100,
        ]);
    }
}
