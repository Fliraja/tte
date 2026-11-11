<?php

namespace Tests\Feature;

use App\Models\LetterNumberBlock;
use App\Models\QrGeneration;
use App\Models\Signer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNumberBlockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/blocks')->assertRedirect('/login');
        $this->get('/admin/blocks/create')->assertRedirect('/login');
        $this->post('/admin/blocks', [])->assertRedirect('/login');
    }

    public function test_staff_cannot_access_blocks(): void
    {
        $this->actingAs($this->staff)->get('/admin/blocks')->assertForbidden();
        $this->actingAs($this->staff)->get('/admin/blocks/create')->assertForbidden();
        $this->actingAs($this->staff)->post('/admin/blocks', [
            'year' => 2026,
            'start_number' => 100,
            'end_number' => 200,
        ])->assertForbidden();
    }

    public function test_admin_can_view_blocks_index(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 100,
            'end_number' => 200,
            'next_number' => 100,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/blocks')
            ->assertOk()
            ->assertSee('Kelola Blok Nomor Surat')
            ->assertSee('100 – 200')
            ->assertSee('Aktif');
    }

    public function test_admin_can_view_create_block_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/blocks/create')
            ->assertOk()
            ->assertSee('Tambah Blok Nomor Surat');
    }

    public function test_admin_can_create_valid_block(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        $response = $this->actingAs($this->admin)->post('/admin/blocks', [
            'year' => $year,
            'start_number' => 1000,
            'end_number' => 1100,
        ]);

        $response->assertRedirect(route('blocks.index', ['year' => $year]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('letter_number_blocks', [
            'year' => $year,
            'start_number' => 1000,
            'end_number' => 1100,
            'next_number' => 1000,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_cannot_create_overlapping_block(): void
    {
        $year = 2026;

        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 100,
            'end_number' => 200,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/blocks', [
            'year' => $year,
            'start_number' => 150,
            'end_number' => 250,
        ]);

        $response->assertSessionHasErrors('start_number');
    }

    public function test_admin_cannot_create_block_with_start_less_than_max_issued(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');
        $signer = Signer::factory()->create();

        QrGeneration::factory()->create([
            'signer_id' => $signer->id,
            'generated_by' => $this->admin->id,
            'number_year' => $year,
            'sequence_number' => 800,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/blocks', [
            'year' => $year,
            'start_number' => 700,
            'end_number' => 900,
        ]);

        $response->assertSessionHasErrors('start_number');
    }

    public function test_dashboard_shows_block_warning_when_no_blocks_exist_for_current_year(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee("Blok nomor tahun {$year} belum diatur!");
    }

    public function test_dashboard_shows_block_warning_when_all_blocks_exhausted(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');

        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 10,
            'end_number' => 20,
            'next_number' => 21, // Exhausted
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee("Semua blok nomor tahun {$year} sudah habis!");
    }

    public function test_dashboard_shows_active_block_and_last_issued(): void
    {
        $year = (int) Carbon::now('Asia/Makassar')->format('Y');
        $signer = Signer::factory()->create();

        LetterNumberBlock::factory()->create([
            'year' => $year,
            'start_number' => 100,
            'end_number' => 150,
            'next_number' => 105,
            'created_by' => $this->admin->id,
        ]);

        QrGeneration::factory()->create([
            'signer_id' => $signer->id,
            'generated_by' => $this->admin->id,
            'number_year' => $year,
            'sequence_number' => 104,
            'letter_number' => "01.104/DIR/RSUN/XI/{$year}",
        ]);

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('#104')
            ->assertSee("01.104/DIR/RSUN/XI/{$year}")
            ->assertSee('Rentang 100–150');
    }
}

