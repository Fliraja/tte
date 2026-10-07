<?php

namespace Tests\Feature;

use App\Models\QrGeneration;
use App\Models\Signer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    private QrGeneration $generation;

    protected function setUp(): void
    {
        parent::setUp();
        $signer = Signer::factory()->create(['is_active' => true]);
        $user = User::factory()->create(['is_active' => true]);
        $this->generation = QrGeneration::factory()->create([
            'signer_id' => $signer->id,
            'generated_by' => $user->id,
        ]);
    }

    public function test_verify_valid_uuid_shows_signer_info()
    {
        $this->get('/verify/' . $this->generation->uuid)
            ->assertOk()
            ->assertSee($this->generation->signer->name)
            ->assertSee($this->generation->signer->position);
    }

    public function test_verify_shows_letter_number_if_present()
    {
        $signer = Signer::factory()->create();
        $user = User::factory()->create();
        $gen = QrGeneration::factory()->create([
            'signer_id' => $signer->id,
            'generated_by' => $user->id,
            'letter_number' => '001/RS/VII/2026',
        ]);

        $this->get('/verify/' . $gen->uuid)
            ->assertOk()
            ->assertSee('001/RS/VII/2026');
    }

    public function test_verify_hides_letter_number_row_if_null()
    {
        $this->get('/verify/' . $this->generation->uuid)
            ->assertDontSee('Nomor Surat');
    }

    public function test_verify_invalid_uuid_shows_not_found()
    {
        $this->get('/verify/invalid-uuid-12345')
            ->assertOk()
            ->assertSee('Kode Tidak Ditemukan');
    }

    public function test_verify_accessible_without_login()
    {
        $this->get('/verify/' . $this->generation->uuid)
            ->assertOk();
    }

    public function test_verify_does_not_leak_internal_data()
    {
        $this->get('/verify/' . $this->generation->uuid)
            ->assertOk()
            ->assertDontSee($this->generation->generator->name)
            ->assertDontSee($this->generation->ip_address ?? '');
    }
}
