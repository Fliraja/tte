<?php

namespace Tests\Feature;

use App\Models\QrGeneration;
use App\Models\Signer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
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

    // --- Signer CRUD ---

    public function test_admin_can_list_signers()
    {
        Signer::factory()->count(3)->create();
        $this->actingAs($this->admin)
            ->get('/admin/signers')
            ->assertOk()
            ->assertSee('Penandatangan');
    }

    public function test_staff_cannot_access_signers()
    {
        $this->actingAs($this->staff)
            ->get('/admin/signers')
            ->assertForbidden();
    }

    public function test_admin_can_create_signer()
    {
        $this->actingAs($this->admin)->post('/admin/signers', [
            'name' => 'dr. Test',
            'position' => 'Dokter Umum',
        ])->assertRedirect();
        $this->assertDatabaseHas('signers', ['name' => 'dr. Test']);
    }

    public function test_admin_can_edit_signer()
    {
        $signer = Signer::factory()->create();
        $this->actingAs($this->admin)->put('/admin/signers/' . $signer->id, [
            'name' => 'dr. Updated',
            'position' => 'Kepala Ruangan',
        ])->assertRedirect();
        $this->assertDatabaseHas('signers', ['name' => 'dr. Updated']);
    }

    public function test_admin_can_deactivate_signer()
    {
        $signer = Signer::factory()->create(['is_active' => true]);
        $this->actingAs($this->admin)->delete('/admin/signers/' . $signer->id)
            ->assertRedirect();
        $this->assertDatabaseHas('signers', ['id' => $signer->id, 'is_active' => false]);
    }

    public function test_admin_can_reactivate_signer()
    {
        $signer = Signer::factory()->create(['is_active' => false]);
        $this->actingAs($this->admin)->patch('/admin/signers/' . $signer->id . '/toggle')
            ->assertRedirect();
        $this->assertDatabaseHas('signers', ['id' => $signer->id, 'is_active' => true]);
    }

    // --- User CRUD ---

    public function test_admin_can_list_users()
    {
        $this->actingAs($this->admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Kelola Akun Staf');
    }

    public function test_staff_cannot_access_users()
    {
        $this->actingAs($this->staff)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_create_user()
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'nip' => '9999888877776666',
            'name' => 'Staf Baru',
            'password' => 'password123',
            'role' => 'staff',
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['nip' => '9999888877776666', 'name' => 'Staf Baru']);
    }

    public function test_admin_can_edit_user_name_and_role()
    {
        $this->actingAs($this->admin)->put('/admin/users/' . $this->staff->id, [
            'name' => 'Nama Diubah',
            'role' => 'admin',
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->staff->id, 'name' => 'Nama Diubah', 'role' => 'admin']);
    }

    public function test_admin_can_reset_other_user_password()
    {
        $this->actingAs($this->admin)->put('/admin/users/' . $this->staff->id, [
            'name' => $this->staff->name,
            'role' => 'staff',
            'password' => 'newpassword123',
        ])->assertRedirect();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $this->staff->fresh()->password));
    }

    public function test_admin_can_deactivate_other_user()
    {
        $this->actingAs($this->admin)->delete('/admin/users/' . $this->staff->id)
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->staff->id, 'is_active' => false]);
    }

    public function test_admin_can_reactivate_user()
    {
        $this->staff->update(['is_active' => false]);
        $this->actingAs($this->admin)->patch('/admin/users/' . $this->staff->id . '/toggle')
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->staff->id, 'is_active' => true]);
    }

    public function test_admin_cannot_deactivate_own_account()
    {
        $this->actingAs($this->admin)->delete('/admin/users/' . $this->admin->id)
            ->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'is_active' => true]);
    }

    public function test_admin_cannot_toggle_own_account_status()
    {
        $this->actingAs($this->admin)->patch('/admin/users/' . $this->admin->id . '/toggle')
            ->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'is_active' => true]);
    }

    public function test_admin_cannot_demote_own_role()
    {
        $this->actingAs($this->admin)->put('/admin/users/' . $this->admin->id, [
            'name' => $this->admin->name,
            'role' => 'staff',
        ])->assertSessionHasErrors('role');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'role' => 'admin']);
    }

    // --- Admin Dashboard ---

    public function test_admin_can_view_dashboard()
    {
        Signer::factory()->count(2)->create(['is_active' => true]);
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('Penandatangan Aktif');
    }

    public function test_staff_cannot_view_dashboard()
    {
        $this->actingAs($this->staff)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_dashboard_shows_correct_counts()
    {
        Signer::factory()->count(2)->create(['is_active' => true]);
        Signer::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['active_signers'] === 2 && $stats['inactive_signers'] === 1;
        });
    }

    // --- Audit Log ---

    public function test_admin_can_view_logs()
    {
        Signer::factory()->count(2)->create();
        $this->actingAs($this->admin)
            ->get('/admin/logs')
            ->assertOk()
            ->assertSee('Log Audit');
    }

    public function test_staff_cannot_view_logs()
    {
        $this->actingAs($this->staff)
            ->get('/admin/logs')
            ->assertForbidden();
    }

    public function test_logs_show_generated_data()
    {
        $signer = Signer::factory()->create(['name' => 'dr. Budi']);
        QrGeneration::factory()->create([
            'signer_id' => $signer->id,
            'generated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/logs')
            ->assertOk()
            ->assertSee('dr. Budi');
    }

    public function test_logs_filter_by_signer_name()
    {
        $signer = Signer::factory()->create(['name' => 'dr. Unik']);
        QrGeneration::factory()->count(3)->create(['signer_id' => $signer->id]);
        QrGeneration::factory()->create();

        $this->actingAs($this->admin)
            ->get('/admin/logs?signer_name=unik')
            ->assertOk()
            ->assertSee('dr. Unik');
    }
}
