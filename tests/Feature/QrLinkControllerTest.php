<?php

namespace Tests\Feature;

use App\Models\QrLink;
use App\Models\QrLinkEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrLinkControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff1;
    private User $staff2;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tte.qr_link_enabled' => true,
            'tte.qr_link_allowed_hosts' => ['drive.google.com', 'docs.google.com'],
        ]);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff1 = User::factory()->create(['role' => 'staff']);
        $this->staff2 = User::factory()->create(['role' => 'staff']);
    }

    private function linkOf(User $owner, array $attributes = []): QrLink
    {
        return QrLink::factory()->create(array_merge(['created_by' => $owner->id], $attributes));
    }

    // ------------------------------------------------------------------ buat

    public function test_staff_can_create_valid_qr_link(): void
    {
        $response = $this->actingAs($this->staff1)->post(route('qr-link.store'), [
            'target_url' => 'https://drive.google.com/file/d/ABC123/view?usp=sharing',
            'label' => 'SPO Cuci Tangan 2026',
        ]);

        $link = QrLink::firstOrFail();

        $response->assertRedirect(route('qr-link.show', $link));
        $response->assertSessionHas('success');

        $this->assertSame($this->staff1->id, $link->created_by);
        $this->assertSame('https://drive.google.com/file/d/ABC123/view?usp=sharing', $link->target_url);
        $this->assertDatabaseHas('qr_link_events', [
            'qr_link_id' => $link->id,
            'user_id' => $this->staff1->id,
            'event' => 'created',
        ]);
    }

    public function test_invalid_url_is_rejected_with_message_and_nothing_is_stored(): void
    {
        $response = $this->actingAs($this->staff1)->from(route('qr-link.create'))->post(route('qr-link.store'), [
            'target_url' => 'https://evil.com/phishing',
            'label' => 'Label',
        ]);

        $response->assertRedirect(route('qr-link.create'));
        $response->assertSessionHasErrors(['target_url' => 'Domain tujuan belum diizinkan. Domain yang diizinkan: drive.google.com, docs.google.com.']);
        $this->assertSame(0, QrLink::count());
    }

    public function test_label_is_required_and_limited_to_255_characters(): void
    {
        $this->actingAs($this->staff1)->post(route('qr-link.store'), [
            'target_url' => 'https://drive.google.com/file/d/abc/view',
            'label' => '',
        ])->assertSessionHasErrors('label');

        $this->actingAs($this->staff1)->post(route('qr-link.store'), [
            'target_url' => 'https://drive.google.com/file/d/abc/view',
            'label' => str_repeat('a', 256),
        ])->assertSessionHasErrors('label');

        $this->assertSame(0, QrLink::count());
    }

    public function test_create_form_shows_warning_and_allowed_hosts(): void
    {
        $this->actingAs($this->staff1)->get(route('qr-link.create'))
            ->assertOk()
            ->assertSee('Siapa saja yang memiliki link')
            ->assertSee('UU PDP')
            ->assertSee('drive.google.com, docs.google.com');
    }

    // ----------------------------------------------------------------- flag

    public function test_all_routes_are_404_when_flag_is_off(): void
    {
        config(['tte.qr_link_enabled' => false]);

        $link = $this->linkOf($this->staff1);

        $this->actingAs($this->staff1);

        $this->get(route('qr-link.index'))->assertNotFound();
        $this->get(route('qr-link.create'))->assertNotFound();
        $this->post(route('qr-link.store'), [
            'target_url' => 'https://drive.google.com/file/d/abc/view',
            'label' => 'Label',
        ])->assertNotFound();
        $this->get(route('qr-link.show', $link))->assertNotFound();
        $this->get(route('qr-link.png', $link))->assertNotFound();
        $this->get(route('qr-link.edit', $link))->assertNotFound();
        $this->patch(route('qr-link.update', $link), [
            'target_url' => 'https://drive.google.com/file/d/new/view',
            'label' => 'Baru',
        ])->assertNotFound();
        $this->patch(route('qr-link.toggle', $link), ['disable_reason' => 'Alasan cukup'])->assertNotFound();

        $this->assertSame(0, QrLinkEvent::count());
        $this->assertTrue($link->fresh()->isActive());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $link = $this->linkOf($this->staff1);

        $this->get(route('qr-link.index'))->assertRedirect(route('login'));
        $this->get(route('qr-link.show', $link))->assertRedirect(route('login'));
    }

    // ------------------------------------------------------------ otorisasi

    public function test_other_staff_gets_403_on_every_detail_route(): void
    {
        $link = $this->linkOf($this->staff1);

        $this->actingAs($this->staff2);

        $this->get(route('qr-link.show', $link))->assertForbidden();
        $this->get(route('qr-link.png', $link))->assertForbidden();
        $this->get(route('qr-link.edit', $link))->assertForbidden();
        $this->patch(route('qr-link.update', $link), [
            'target_url' => 'https://drive.google.com/file/d/new/view',
            'label' => 'Dibajak',
        ])->assertForbidden();
        $this->patch(route('qr-link.toggle', $link), ['disable_reason' => 'Iseng menonaktifkan'])->assertForbidden();

        $link->refresh();
        $this->assertTrue($link->isActive());
        $this->assertNotSame('Dibajak', $link->label);
    }

    public function test_owner_and_admin_can_open_every_detail_route(): void
    {
        $link = $this->linkOf($this->staff1);

        foreach ([$this->staff1, $this->admin] as $user) {
            $this->actingAs($user);

            $this->get(route('qr-link.show', $link))->assertOk();
            $this->get(route('qr-link.png', $link))->assertOk();
            $this->get(route('qr-link.edit', $link))->assertOk();
        }
    }

    public function test_unknown_code_is_404(): void
    {
        $this->actingAs($this->admin)->get('/qr-link/zzzzzzzzzz')->assertNotFound();
    }

    // -------------------------------------------------------------- riwayat

    public function test_staff_history_only_lists_own_links_and_admin_sees_all(): void
    {
        $mine = $this->linkOf($this->staff1, ['label' => 'Milik staf satu']);
        $theirs = $this->linkOf($this->staff2, ['label' => 'Milik staf dua']);

        $this->actingAs($this->staff1)->get(route('qr-link.index'))
            ->assertOk()
            ->assertSee('Milik staf satu')
            ->assertDontSee('Milik staf dua');

        $this->actingAs($this->admin)->get(route('qr-link.index'))
            ->assertOk()
            ->assertSee('Milik staf satu')
            ->assertSee('Milik staf dua');
    }

    public function test_history_search_matches_label_and_target_url(): void
    {
        $this->linkOf($this->staff1, [
            'label' => 'SPO Cuci Tangan',
            'target_url' => 'https://drive.google.com/file/d/AAA111/view',
        ]);
        $this->linkOf($this->staff1, [
            'label' => 'Panduan Gizi',
            'target_url' => 'https://docs.google.com/document/d/BBB222/edit',
        ]);

        $this->actingAs($this->staff1);

        $this->get(route('qr-link.index', ['search' => 'Cuci']))
            ->assertSee('SPO Cuci Tangan')
            ->assertDontSee('Panduan Gizi');

        $this->get(route('qr-link.index', ['search' => 'BBB222']))
            ->assertSee('Panduan Gizi')
            ->assertDontSee('SPO Cuci Tangan');
    }

    public function test_history_shows_empty_state(): void
    {
        $this->actingAs($this->staff1)->get(route('qr-link.index'))
            ->assertOk()
            ->assertSee('Belum ada QR Link');

        $this->get(route('qr-link.index', ['search' => 'tidak-ada']))
            ->assertSee('Tidak ada QR Link yang cocok');
    }

    public function test_history_is_paginated_by_20(): void
    {
        QrLink::factory()->count(25)->create(['created_by' => $this->staff1->id]);

        $this->actingAs($this->staff1)->get(route('qr-link.index'))
            ->assertOk()
            ->assertViewHas('links', fn ($links) => $links->count() === 20 && $links->total() === 25);
    }

    // ---------------------------------------------------------------- detail

    public function test_show_displays_warning_short_url_and_test_open_button(): void
    {
        $link = $this->linkOf($this->staff1, [
            'target_url' => 'https://drive.google.com/file/d/ABC123/view',
        ]);

        $this->actingAs($this->staff1)->get(route('qr-link.show', $link))
            ->assertOk()
            ->assertSee('Siapa saja yang memiliki link')
            ->assertSee('UU PDP')
            ->assertSee(route('qr-link.go', $link->code), false)
            ->assertSee('Uji Buka Tautan')
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('Unduh PNG');
    }

    // ------------------------------------------------------------------- png

    public function test_png_returns_valid_png_image(): void
    {
        $link = $this->linkOf($this->staff1);

        $response = $this->actingAs($this->staff1)->get(route('qr-link.png', $link));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
        $this->assertNotFalse(imagecreatefromstring($response->getContent()));
    }

    public function test_png_is_still_available_for_disabled_link(): void
    {
        $link = QrLink::factory()->disabled()->create(['created_by' => $this->staff1->id]);

        $this->actingAs($this->staff1)->get(route('qr-link.png', $link))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    // ----------------------------------------------------------------- ubah

    public function test_owner_can_change_target_and_event_records_old_and_new(): void
    {
        $link = $this->linkOf($this->staff1, ['target_url' => 'https://drive.google.com/file/d/OLD/view']);
        $code = $link->code;

        $this->actingAs($this->staff1)->patch(route('qr-link.update', $link), [
            'target_url' => 'https://docs.google.com/document/d/NEW/edit',
            'label' => 'Judul baru',
        ])->assertRedirect(route('qr-link.show', $link))->assertSessionHas('success');

        $link->refresh();
        $this->assertSame($code, $link->code);
        $this->assertSame('https://docs.google.com/document/d/NEW/edit', $link->target_url);
        $this->assertSame('Judul baru', $link->label);

        $event = QrLinkEvent::where('qr_link_id', $link->id)->where('event', 'target_changed')->firstOrFail();
        $this->assertStringContainsString('https://drive.google.com/file/d/OLD/view', $event->note);
        $this->assertStringContainsString('https://docs.google.com/document/d/NEW/edit', $event->note);
    }

    public function test_admin_can_change_target_of_staff_link(): void
    {
        $link = $this->linkOf($this->staff1);

        $this->actingAs($this->admin)->patch(route('qr-link.update', $link), [
            'target_url' => 'https://drive.google.com/drive/folders/ADMIN',
            'label' => 'Diubah admin',
        ])->assertRedirect(route('qr-link.show', $link));

        $this->assertSame('https://drive.google.com/drive/folders/ADMIN', $link->fresh()->target_url);
        $this->assertDatabaseHas('qr_link_events', [
            'qr_link_id' => $link->id,
            'user_id' => $this->admin->id,
            'event' => 'target_changed',
        ]);
    }

    public function test_update_with_invalid_url_keeps_old_target(): void
    {
        $link = $this->linkOf($this->staff1, ['target_url' => 'https://drive.google.com/file/d/OLD/view']);

        $this->actingAs($this->staff1)->patch(route('qr-link.update', $link), [
            'target_url' => 'http://drive.google.com/file/d/NEW/view',
            'label' => 'Label',
        ])->assertSessionHasErrors('target_url');

        $this->assertSame('https://drive.google.com/file/d/OLD/view', $link->fresh()->target_url);
        $this->assertSame(0, QrLinkEvent::where('event', 'target_changed')->count());
    }

    // ----------------------------------------------------------------- toggle

    public function test_disable_without_reason_is_rejected(): void
    {
        $link = $this->linkOf($this->staff1);

        $this->actingAs($this->staff1)->patch(route('qr-link.toggle', $link))
            ->assertSessionHasErrors('disable_reason');

        $this->actingAs($this->staff1)->patch(route('qr-link.toggle', $link), ['disable_reason' => 'abc'])
            ->assertSessionHasErrors('disable_reason');

        $this->assertTrue($link->fresh()->isActive());
        $this->assertSame(0, QrLinkEvent::where('event', 'disabled')->count());
    }

    public function test_owner_can_disable_with_reason_then_enable_again(): void
    {
        $link = $this->linkOf($this->staff1);

        $this->actingAs($this->staff1)->patch(route('qr-link.toggle', $link), [
            'disable_reason' => 'QR salah sebar ke pihak luar',
        ])->assertRedirect(route('qr-link.show', $link))->assertSessionHas('success');

        $link->refresh();
        $this->assertTrue($link->isDisabled());
        $this->assertSame($this->staff1->id, $link->disabled_by);
        $this->assertSame('QR salah sebar ke pihak luar', $link->disable_reason);
        $this->assertDatabaseHas('qr_link_events', [
            'qr_link_id' => $link->id,
            'event' => 'disabled',
            'note' => 'QR salah sebar ke pihak luar',
        ]);

        $this->get(route('qr-link.show', $link))
            ->assertOk()
            ->assertSee('QR Link nonaktif')
            ->assertSee('QR salah sebar ke pihak luar')
            ->assertSee('Aktifkan Kembali');

        $this->patch(route('qr-link.toggle', $link))
            ->assertRedirect(route('qr-link.show', $link))
            ->assertSessionHas('success');

        $link->refresh();
        $this->assertTrue($link->isActive());
        $this->assertNull($link->disabled_at);
        $this->assertNull($link->disable_reason);
        $this->assertDatabaseHas('qr_link_events', ['qr_link_id' => $link->id, 'event' => 'enabled']);
    }

    public function test_admin_can_disable_staff_link(): void
    {
        $link = $this->linkOf($this->staff1);

        $this->actingAs($this->admin)->patch(route('qr-link.toggle', $link), [
            'disable_reason' => 'Dinonaktifkan oleh admin',
        ])->assertRedirect(route('qr-link.show', $link));

        $this->assertTrue($link->fresh()->isDisabled());
        $this->assertSame($this->admin->id, $link->fresh()->disabled_by);
    }

    // ------------------------------------------------------------------ menu

    public function test_menu_is_visible_only_when_flag_is_on(): void
    {
        $this->actingAs($this->staff1);

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(route('qr-link.index'), false);

        config(['tte.qr_link_enabled' => false]);

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee(route('qr-link.index'), false);
    }

    // ------------------------------------------------------- mode bebas (tanpa whitelist)

    public function test_staff_can_create_link_to_any_public_https_site_when_whitelist_is_empty(): void
    {
        config(['tte.qr_link_allowed_hosts' => []]);

        $this->actingAs($this->staff1)->post(route('qr-link.store'), [
            'target_url' => 'https://example.org/menu-kantin?hari=senin',
            'label' => 'Menu kantin',
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://example.org/menu-kantin?hari=senin', QrLink::firstOrFail()->target_url);
    }

    public function test_ip_address_target_is_rejected_when_whitelist_is_empty(): void
    {
        config(['tte.qr_link_allowed_hosts' => []]);

        $this->actingAs($this->staff1)->post(route('qr-link.store'), [
            'target_url' => 'https://192.168.1.10/rahasia',
            'label' => 'IP lokal',
        ])->assertSessionHasErrors('target_url');

        $this->assertSame(0, QrLink::count());
    }

    public function test_forms_tell_that_any_https_site_is_allowed_when_whitelist_is_empty(): void
    {
        config(['tte.qr_link_allowed_hosts' => []]);
        $link = $this->linkOf($this->staff1);

        $this->actingAs($this->staff1);

        $this->get(route('qr-link.create'))->assertOk()->assertSee('Situs apa pun dengan alamat https:// boleh dipakai.');
        $this->get(route('qr-link.edit', $link))->assertOk()->assertSee('Situs apa pun dengan alamat https:// boleh dipakai.');
    }

    // ------------------------------------------------------- isi QR

    public function test_qr_content_is_the_absolute_short_url(): void
    {
        $link = $this->linkOf($this->staff1);

        $this->assertSame(url('/l/' . $link->code), route('qr-link.go', $link->code));
    }
}
