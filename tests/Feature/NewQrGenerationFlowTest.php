<?php

namespace Tests\Feature;

use App\Models\LetterDestination;
use App\Models\LetterNumberBlock;
use App\Models\LetterType;
use App\Models\LetterUnit;
use App\Models\QrGeneration;
use App\Models\Signer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewQrGenerationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff1;
    private User $staff2;
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

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->staff1 = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->staff2 = User::factory()->create(['role' => 'staff', 'is_active' => true]);

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
            'reference_required' => false,
            'is_active' => true,
        ]);
    }

    public function test_legacy_flow_remains_active_when_flag_is_false(): void
    {
        config(['tte.auto_numbering' => false]);

        $response = $this->actingAs($this->staff1)->post('/qr/generate', [
            'signer_id' => $this->signer->id,
            'letter_number' => '001/RS/VII/2026',
            'perihal' => 'Pemberitahuan Cuti',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertDatabaseHas('qr_generations', [
            'letter_number' => '001/RS/VII/2026',
            'generated_by' => $this->staff1->id,
        ]);
    }

    public function test_new_flow_auto_standard_generates_and_redirects_to_show(): void
    {
        config(['tte.auto_numbering' => true]);
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 100,
            'end_number' => 200,
            'next_number' => 100,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->staff1)->post('/qr/generate', [
            'letter_type_id' => $this->autoType->id,
            'destination_id' => $this->destination->id,
            'signer_id' => $this->signer->id,
            'perihal' => 'Rapat Direksi',
        ]);

        $generation = QrGeneration::latest()->first();
        $this->assertNotNull($generation);

        $response->assertRedirect(route('qr.show', $generation->uuid));

        $this->assertSame(100, $generation->sequence_number);
        $this->assertSame('auto', $generation->number_source);

        // Verify show page
        $showResponse = $this->actingAs($this->staff1)->get(route('qr.show', $generation->uuid));
        $showResponse->assertOk()
            ->assertSee($generation->letter_number)
            ->assertSee('Rapat Direksi')
            ->assertSee($this->signer->name);
    }

    public function test_new_flow_auto_unit_requires_unit_id(): void
    {
        config(['tte.auto_numbering' => true]);
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 10,
            'end_number' => 20,
            'next_number' => 10,
            'created_by' => $this->admin->id,
        ]);

        // Missing unit_id
        $this->actingAs($this->staff1)->post('/qr/generate', [
            'letter_type_id' => $this->unitType->id,
            'signer_id' => $this->signer->id,
            'perihal' => 'SPO Operasi',
        ])->assertSessionHasErrors('unit_id');

        // With unit_id
        $this->actingAs($this->staff1)->post('/qr/generate', [
            'letter_type_id' => $this->unitType->id,
            'unit_id' => $this->unit->id,
            'signer_id' => $this->signer->id,
            'perihal' => 'SPO Operasi',
        ])->assertSessionHasNoErrors();

        $gen = QrGeneration::latest()->first();
        $this->assertStringContainsString('/SPO/IBS/RSUN/', $gen->letter_number);
    }

    public function test_new_flow_manual_numbering(): void
    {
        config(['tte.auto_numbering' => true]);

        $this->actingAs($this->staff1)->post('/qr/generate', [
            'letter_type_id' => $this->manualType->id,
            'signer_id' => $this->signer->id,
            'perihal' => 'Surat Tugas SDM',
            'manual_number' => '077/HRD-RSUN/2026',
        ])->assertSessionHasNoErrors();

        $gen = QrGeneration::latest()->first();
        $this->assertSame('077/HRD-RSUN/2026', $gen->letter_number);
        $this->assertSame('manual', $gen->number_source);
        $this->assertNull($gen->sequence_number);
    }

    public function test_new_flow_rejects_none_type_on_numbered_menu(): void
    {
        config(['tte.auto_numbering' => true]);

        $response = $this->actingAs($this->staff1)->post('/qr/generate', [
            'letter_type_id' => $this->noneType->id,
            'signer_id' => $this->signer->id,
            'perihal' => 'Hasil MCU',
        ]);

        $response->assertSessionHasErrors('letter_type_id');
    }

    public function test_new_flow_fails_when_block_unavailable(): void
    {
        config(['tte.auto_numbering' => true]);

        $response = $this->actingAs($this->staff1)->post('/qr/generate', [
            'letter_type_id' => $this->autoType->id,
            'destination_id' => $this->destination->id,
            'signer_id' => $this->signer->id,
            'perihal' => 'Surat Gagal Blok',
        ]);

        $response->assertSessionHasErrors('letter_type_id');
    }

    public function test_qr_show_and_png_authorization(): void
    {
        config(['tte.auto_numbering' => true]);

        $gen = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'generated_by' => $this->staff1->id,
            'letter_number' => '01.10/DIR/RSUN/XI/2026',
        ]);

        // Creator can view
        $this->actingAs($this->staff1)->get(route('qr.show', $gen->uuid))->assertOk();
        $this->actingAs($this->staff1)->get(route('qr.png', $gen->uuid))->assertOk();

        // Admin can view
        $this->actingAs($this->admin)->get(route('qr.show', $gen->uuid))->assertOk();
        $this->actingAs($this->admin)->get(route('qr.png', $gen->uuid))->assertOk();

        // Other staff forbidden
        $this->actingAs($this->staff2)->get(route('qr.show', $gen->uuid))->assertForbidden();
        $this->actingAs($this->staff2)->get(route('qr.png', $gen->uuid))->assertForbidden();
    }

    public function test_tanpa_nomor_flow_for_mcu(): void
    {
        config(['tte.auto_numbering' => true]);

        $this->actingAs($this->staff1)->get('/tanpa-nomor')->assertOk()->assertSee('Hasil MCU');

        $response = $this->actingAs($this->staff1)->post('/tanpa-nomor', [
            'letter_type_id' => $this->noneType->id,
            'signer_id' => $this->signer->id,
            'perihal' => 'Hasil MCU Calon Karyawan',
            'reference' => 'MCU-2026-999',
        ]);

        $gen = QrGeneration::latest()->first();
        $this->assertNotNull($gen);

        $response->assertRedirect(route('qr.show', $gen->uuid));
        $this->assertNull($gen->letter_number);
        $this->assertSame('none', $gen->number_source);
        $this->assertSame('MCU-2026-999', $gen->reference);
    }

    public function test_tanpa_nomor_rejects_numbered_type(): void
    {
        config(['tte.auto_numbering' => true]);

        $response = $this->actingAs($this->staff1)->post('/tanpa-nomor', [
            'letter_type_id' => $this->autoType->id,
            'signer_id' => $this->signer->id,
            'perihal' => 'Surat Salah Menu',
        ]);

        $response->assertSessionHasErrors('letter_type_id');
    }

    public function test_tanpa_nomor_warns_on_duplicate_reference_and_accepts_confirmation(): void
    {
        config(['tte.auto_numbering' => true]);

        // First issue
        $this->actingAs($this->staff1)->post('/tanpa-nomor', [
            'letter_type_id' => $this->noneType->id,
            'signer_id' => $this->signer->id,
            'perihal' => 'Hasil MCU 1',
            'reference' => 'MCU-DUP-01',
        ])->assertSessionHasNoErrors();

        // Second issue with same reference -> should trigger warning
        $response = $this->actingAs($this->staff1)->post('/tanpa-nomor', [
            'letter_type_id' => $this->noneType->id,
            'signer_id' => $this->signer->id,
            'perihal' => 'Hasil MCU Cetak Ulang',
            'reference' => 'MCU-DUP-01',
        ]);

        $response->assertSessionHas('duplicate_warning');

        // Third issue with confirmation checkbox -> succeeds
        $confirmResponse = $this->actingAs($this->staff1)->post('/tanpa-nomor', [
            'letter_type_id' => $this->noneType->id,
            'signer_id' => $this->signer->id,
            'perihal' => 'Hasil MCU Cetak Ulang',
            'reference' => 'MCU-DUP-01',
            'confirm_duplicate' => 1,
        ]);

        $confirmResponse->assertSessionHasNoErrors();
    }

    public function test_history_shows_own_for_staff_and_all_for_admin(): void
    {
        config(['tte.auto_numbering' => true]);

        $genStaff1 = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'generated_by' => $this->staff1->id,
            'perihal' => 'Surat Rahasia Staff 1',
        ]);

        $genStaff2 = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'generated_by' => $this->staff2->id,
            'perihal' => 'Surat Rahasia Staff 2',
        ]);

        // Staff 1 only sees own
        $this->actingAs($this->staff1)
            ->get('/qr/history')
            ->assertOk()
            ->assertSee('Surat Rahasia Staff 1')
            ->assertDontSee('Surat Rahasia Staff 2');

        // Admin sees both
        $this->actingAs($this->admin)
            ->get('/qr/history')
            ->assertOk()
            ->assertSee('Surat Rahasia Staff 1')
            ->assertSee('Surat Rahasia Staff 2');
    }

    public function test_history_search_filter(): void
    {
        config(['tte.auto_numbering' => true]);

        QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'generated_by' => $this->admin->id,
            'perihal' => 'Undangan Workshop Akreditasi',
            'letter_number' => '05.100/DIR/RSUN/XI/2026',
        ]);

        QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'generated_by' => $this->admin->id,
            'perihal' => 'Surat Tugas Pelatihan Medis',
            'letter_number' => '05.101/DIR/RSUN/XI/2026',
        ]);

        $this->actingAs($this->admin)
            ->get('/qr/history?search=Akreditasi')
            ->assertOk()
            ->assertSee('Undangan Workshop Akreditasi')
            ->assertDontSee('Surat Tugas Pelatihan Medis');
    }
}

