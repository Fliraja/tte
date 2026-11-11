<?php

namespace Tests\Feature;

use App\Models\LetterType;
use App\Models\QrGeneration;
use App\Models\Signer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoidLetterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff1;
    private User $staff2;
    private Signer $signer;
    private LetterType $letterType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->staff1 = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->staff2 = User::factory()->create(['role' => 'staff', 'is_active' => true]);

        $this->signer = Signer::factory()->create(['is_active' => true]);
        $this->letterType = LetterType::factory()->create(['code' => '01', 'is_active' => true]);
    }

    public function test_creator_can_void_own_issued_letter(): void
    {
        $gen = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'letter_type_id' => $this->letterType->id,
            'generated_by' => $this->staff1->id,
            'letter_number' => '01.500/DIR/RSUN/XI/2026',
            'status' => 'issued',
        ]);

        $response = $this->actingAs($this->staff1)->post(route('qr.void', $gen->uuid), [
            'void_reason' => 'Salah tanggal dan perihal surat fisik',
        ]);

        $response->assertRedirect(route('qr.show', $gen->uuid));
        $response->assertSessionHas('success');

        $gen->refresh();
        $this->assertTrue($gen->isVoid());
        $this->assertSame($this->staff1->id, $gen->voided_by);
        $this->assertNotNull($gen->voided_at);
        $this->assertSame('Salah tanggal dan perihal surat fisik', $gen->void_reason);

        // Verify event logged
        $this->assertDatabaseHas('qr_generation_events', [
            'qr_generation_id' => $gen->id,
            'user_id' => $this->staff1->id,
            'event' => 'voided',
            'note' => 'Salah tanggal dan perihal surat fisik',
        ]);
    }

    public function test_admin_can_void_any_letter(): void
    {
        $gen = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'letter_type_id' => $this->letterType->id,
            'generated_by' => $this->staff1->id,
            'letter_number' => '01.501/DIR/RSUN/XI/2026',
            'status' => 'issued',
        ]);

        $response = $this->actingAs($this->admin)->post(route('qr.void', $gen->uuid), [
            'void_reason' => 'Dibatalkan atas arahan direktur',
        ]);

        $response->assertRedirect(route('qr.show', $gen->uuid));
        $gen->refresh();
        $this->assertTrue($gen->isVoid());
        $this->assertSame($this->admin->id, $gen->voided_by);
    }

    public function test_other_staff_cannot_void_letter(): void
    {
        $gen = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'letter_type_id' => $this->letterType->id,
            'generated_by' => $this->staff1->id,
            'status' => 'issued',
        ]);

        $this->actingAs($this->staff2)
            ->post(route('qr.void', $gen->uuid), [
                'void_reason' => 'Iseng membatalkan surat rekan kerja',
            ])
            ->assertForbidden();

        $gen->refresh();
        $this->assertFalse($gen->isVoid());
    }

    public function test_cannot_void_already_voided_letter(): void
    {
        $gen = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'generated_by' => $this->staff1->id,
            'status' => 'void',
            'void_reason' => 'Alasan pertama',
        ]);

        $this->actingAs($this->admin)
            ->post(route('qr.void', $gen->uuid), [
                'void_reason' => 'Pembatalan kedua kali',
            ])
            ->assertForbidden();
    }

    public function test_void_requires_reason_with_min_5_chars(): void
    {
        $gen = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'generated_by' => $this->staff1->id,
            'status' => 'issued',
        ]);

        // Empty reason
        $this->actingAs($this->staff1)
            ->post(route('qr.void', $gen->uuid), ['void_reason' => ''])
            ->assertSessionHasErrors('void_reason');

        // Less than 5 characters
        $this->actingAs($this->staff1)
            ->post(route('qr.void', $gen->uuid), ['void_reason' => 'bad'])
            ->assertSessionHasErrors('void_reason');
    }

    public function test_staff_cannot_void_final_letter_but_admin_can(): void
    {
        $gen = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'generated_by' => $this->staff1->id,
            'status' => 'final',
        ]);

        // Staff cannot void final letter (Rule K5)
        $this->actingAs($this->staff1)
            ->post(route('qr.void', $gen->uuid), [
                'void_reason' => 'Surat salah setelah final',
            ])
            ->assertForbidden();

        // Admin CAN void final letter (Rule K5)
        $this->actingAs($this->admin)
            ->post(route('qr.void', $gen->uuid), [
                'void_reason' => 'Pembatalan darurat oleh admin setelah final',
            ])
            ->assertRedirect(route('qr.show', $gen->uuid));

        $gen->refresh();
        $this->assertTrue($gen->isVoid());
    }
}

