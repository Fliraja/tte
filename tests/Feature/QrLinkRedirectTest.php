<?php

namespace Tests\Feature;

use App\Models\QrLink;
use App\Models\User;
use App\Services\QrLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class QrLinkRedirectTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tte.qr_link_enabled' => true,
            'tte.qr_link_allowed_hosts' => ['drive.google.com', 'docs.google.com'],
        ]);

        $this->owner = User::factory()->create();
    }

    private function link(array $attributes = []): QrLink
    {
        return QrLink::factory()->create(array_merge(['created_by' => $this->owner->id], $attributes));
    }

    private function assertSecurityHeaders($response): void
    {
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', $response->headers->get('Cache-Control'));
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_active_link_redirects_with_302_to_exact_target_and_counts_the_access(): void
    {
        $target = 'https://drive.google.com/file/d/ABC123/view?usp=sharing';
        $link = $this->link(['target_url' => $target]);

        $response = $this->get(route('qr-link.go', $link->code));

        $response->assertStatus(302);
        $response->assertHeader('Location', $target);
        $this->assertSecurityHeaders($response);

        $link->refresh();
        $this->assertSame(1, $link->scan_count);
        $this->assertNotNull($link->last_scanned_at);
    }

    public function test_every_active_access_adds_one_to_the_counter(): void
    {
        $link = $this->link();

        $this->get(route('qr-link.go', $link->code));
        $this->get(route('qr-link.go', $link->code));
        $this->get(route('qr-link.go', $link->code));

        $this->assertSame(3, $link->fresh()->scan_count);
    }

    public function test_scan_does_not_touch_updated_at(): void
    {
        $link = $this->link();
        $before = $link->fresh()->updated_at;

        $this->travel(5)->minutes();
        $this->get(route('qr-link.go', $link->code))->assertStatus(302);

        $this->assertTrue($link->fresh()->updated_at->equalTo($before));
    }

    public function test_no_login_is_needed(): void
    {
        $link = $this->link();

        $this->assertGuest();
        $this->get(route('qr-link.go', $link->code))->assertStatus(302);
    }

    public function test_disabled_link_returns_410_without_location_and_without_counting(): void
    {
        $link = QrLink::factory()->disabled()->create([
            'created_by' => $this->owner->id,
            'label' => 'Rahasia internal RS',
        ]);

        $response = $this->get(route('qr-link.go', $link->code));

        $response->assertStatus(410);
        $response->assertHeaderMissing('Location');
        $response->assertSee('Tautan ini sudah dinonaktifkan.');
        $response->assertDontSee('Rahasia internal RS');
        $response->assertDontSee($link->target_url);
        $this->assertSecurityHeaders($response);

        $link->refresh();
        $this->assertSame(0, $link->scan_count);
        $this->assertNull($link->last_scanned_at);
    }

    public function test_unknown_code_returns_404_page(): void
    {
        $response = $this->get(route('qr-link.go', 'abcdefghjk'));

        $response->assertStatus(404);
        $response->assertHeaderMissing('Location');
        $response->assertSee('Tautan tidak ditemukan.');
        $this->assertSecurityHeaders($response);
    }

    public function test_code_outside_pattern_is_404_and_changes_nothing(): void
    {
        $link = $this->link();

        foreach (['ABC', 'abcdefghj', 'abcdefghjkm', 'ABCDEFGHJK', '0000000000', 'abcdefghj1', 'abcdefghj-'] as $code) {
            $this->get('/l/' . $code)->assertStatus(404);
        }

        $this->assertSame(0, $link->fresh()->scan_count);
    }

    public function test_target_no_longer_allowed_by_whitelist_does_not_redirect_and_is_logged(): void
    {
        $link = $this->link(['target_url' => 'https://drive.google.com/file/d/ABC123/view']);

        config(['tte.qr_link_allowed_hosts' => ['docs.google.com']]);
        Log::spy();

        $response = $this->get(route('qr-link.go', $link->code));

        $response->assertStatus(404);
        $response->assertHeaderMissing('Location');
        $response->assertSee('Tautan tidak tersedia.');
        $response->assertDontSee('drive.google.com');
        $this->assertSecurityHeaders($response);
        $this->assertSame(0, $link->fresh()->scan_count);

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_tampered_target_in_database_is_not_followed(): void
    {
        $link = $this->link();

        foreach ([
            'http://drive.google.com/file/d/abc',
            'https://drive.google.com.evil.com/x',
            'https://evil.com\\@drive.google.com/x',
            'javascript:alert(1)',
        ] as $bad) {
            $link->forceFill(['target_url' => $bad])->save();

            $response = $this->get(route('qr-link.go', $link->code));

            $response->assertStatus(404);
            $response->assertHeaderMissing('Location');
        }

        $this->assertSame(0, $link->fresh()->scan_count);
    }

    public function test_empty_whitelist_redirects_to_non_google_target(): void
    {
        config(['tte.qr_link_allowed_hosts' => []]);
        $link = $this->link(['target_url' => 'https://example.org/menu?x=1']);

        $this->get(route('qr-link.go', $link->code))
            ->assertStatus(302)
            ->assertHeader('Location', 'https://example.org/menu?x=1');

        $this->assertSame(1, $link->fresh()->scan_count);
    }

    public function test_empty_whitelist_does_not_follow_ip_or_local_targets_in_database(): void
    {
        config(['tte.qr_link_allowed_hosts' => []]);
        $link = $this->link();

        foreach (['https://127.0.0.1/x', 'https://localhost/x', 'https://intranet/x', 'http://example.org/x'] as $bad) {
            $link->forceFill(['target_url' => $bad])->save();

            $this->get(route('qr-link.go', $link->code))
                ->assertStatus(404)
                ->assertHeaderMissing('Location');
        }

        $this->assertSame(0, $link->fresh()->scan_count);
    }

    public function test_flag_off_makes_the_public_route_404_and_does_not_count(): void
    {
        $link = $this->link();

        config(['tte.qr_link_enabled' => false]);

        $this->get(route('qr-link.go', $link->code))
            ->assertStatus(404)
            ->assertHeaderMissing('Location');

        $this->assertSame(0, $link->fresh()->scan_count);
    }

    public function test_changing_the_target_applies_to_the_same_code_immediately(): void
    {
        $service = app(QrLinkService::class);
        $link = $service->create($this->owner, 'https://drive.google.com/file/d/OLD/view', 'Lama');

        $this->get(route('qr-link.go', $link->code))
            ->assertHeader('Location', 'https://drive.google.com/file/d/OLD/view');

        $service->updateTarget($link->fresh(), $this->owner, 'https://docs.google.com/document/d/NEW/edit', 'Baru');

        $this->get(route('qr-link.go', $link->code))
            ->assertStatus(302)
            ->assertHeader('Location', 'https://docs.google.com/document/d/NEW/edit');
    }

    public function test_disable_then_enable_stops_and_restores_the_redirect(): void
    {
        $service = app(QrLinkService::class);
        $link = $service->create($this->owner, 'https://drive.google.com/file/d/ABC/view', 'Label');

        $service->disable($link->fresh(), $this->owner, 'QR salah sebar');
        $this->get(route('qr-link.go', $link->code))->assertStatus(410);
        $this->assertSame(0, $link->fresh()->scan_count);

        $service->enable($link->fresh(), $this->owner);
        $this->get(route('qr-link.go', $link->code))
            ->assertStatus(302)
            ->assertHeader('Location', 'https://drive.google.com/file/d/ABC/view');
        $this->assertSame(1, $link->fresh()->scan_count);
    }

    public function test_status_page_is_noindex_and_has_no_internal_links(): void
    {
        $response = $this->get(route('qr-link.go', 'abcdefghjk'));

        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertDontSee('/login', false);
        $response->assertDontSee('/qr-link', false);
    }

    public function test_public_route_is_throttled_to_60_requests_per_minute(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->get(route('qr-link.go', 'abcdefghjk'))->assertStatus(404);
        }

        $this->get(route('qr-link.go', 'abcdefghjk'))->assertStatus(429);
    }
}
