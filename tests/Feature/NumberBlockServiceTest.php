<?php

namespace Tests\Feature;

use App\Models\LetterNumberBlock;
use App\Models\QrGeneration;
use App\Models\Signer;
use App\Models\User;
use App\Services\NumberBlockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NumberBlockServiceTest extends TestCase
{
    use RefreshDatabase;

    private NumberBlockService $service;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NumberBlockService();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_active_block_returns_smallest_start_number_with_remaining(): void
    {
        $year = 2026;

        // Block 1: 100-110, exhausted (next_number = 111)
        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 100,
            'end_number' => 110,
            'next_number' => 111,
            'created_by' => $this->admin->id,
        ]);

        // Block 2: 200-250, active (next_number = 200)
        $block2 = LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 200,
            'end_number' => 250,
            'next_number' => 200,
            'created_by' => $this->admin->id,
        ]);

        // Block 3: 300-350, pending (next_number = 300)
        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 300,
            'end_number' => 350,
            'next_number' => 300,
            'created_by' => $this->admin->id,
        ]);

        $active = $this->service->activeBlock($year);

        $this->assertNotNull($active);
        $this->assertSame($block2->id, $active->id);
    }

    public function test_active_block_returns_null_when_all_exhausted_or_none(): void
    {
        $this->assertNull($this->service->activeBlock(2026));

        LetterNumberBlock::factory()->create([
            'year' => 2026,
            'start_number' => 10,
            'end_number' => 20,
            'next_number' => 21,
            'created_by' => $this->admin->id,
        ]);

        $this->assertNull($this->service->activeBlock(2026));
    }

    public function test_active_block_handles_unlimited_block(): void
    {
        $block = LetterNumberBlock::factory()->create([
            'year' => 2026,
            'start_number' => 500,
            'end_number' => null,
            'next_number' => 9999,
            'created_by' => $this->admin->id,
        ]);

        $active = $this->service->activeBlock(2026);

        $this->assertNotNull($active);
        $this->assertSame($block->id, $active->id);
    }

    public function test_rule_1_start_number_must_be_at_least_1(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->createBlock(2026, 0, 10, $this->admin->id);
    }

    public function test_rule_1_end_number_must_be_greater_or_equal_to_start(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->createBlock(2026, 50, 40, $this->admin->id);
    }

    public function test_rule_2_overlapping_blocks_are_rejected(): void
    {
        LetterNumberBlock::factory()->create([
            'year' => 2026,
            'start_number' => 100,
            'end_number' => 200,
            'next_number' => 100,
            'created_by' => $this->admin->id,
        ]);

        // Overlap: 150-250
        $this->expectException(ValidationException::class);
        $this->service->createBlock(2026, 150, 250, $this->admin->id);
    }

    public function test_rule_3_cannot_create_block_after_unlimited_block(): void
    {
        LetterNumberBlock::factory()->create([
            'year' => 2026,
            'start_number' => 500,
            'end_number' => null,
            'next_number' => 500,
            'created_by' => $this->admin->id,
        ]);

        $this->expectException(ValidationException::class);
        $this->service->createBlock(2026, 600, 700, $this->admin->id);
    }

    public function test_rule_4_start_number_must_be_greater_than_max_issued_sequence(): void
    {
        $signer = Signer::factory()->create();

        QrGeneration::factory()->create([
            'signer_id' => $signer->id,
            'generated_by' => $this->admin->id,
            'number_year' => 2026,
            'sequence_number' => 550,
        ]);

        // Trying to create a block starting at 500 (<= 550) should fail
        $this->expectException(ValidationException::class);
        $this->service->createBlock(2026, 500, 600, $this->admin->id);
    }

    public function test_valid_block_creation_succeeds(): void
    {
        $block = $this->service->createBlock(2026, 1000, 1100, $this->admin->id);

        $this->assertDatabaseHas('letter_number_blocks', [
            'id' => $block->id,
            'year' => 2026,
            'start_number' => 1000,
            'end_number' => 1100,
            'next_number' => 1000,
            'created_by' => $this->admin->id,
        ]);
    }
}

