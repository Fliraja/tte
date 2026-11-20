<?php

namespace Tests\Feature;

use App\Models\QrLink;
use App\Models\QrLinkEvent;
use App\Models\User;
use App\Services\QrLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QrLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    private QrLinkService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tte.qr_link_allowed_hosts' => ['drive.google.com', 'docs.google.com']]);

        $this->service = app(QrLinkService::class);
        $this->user = User::factory()->create();
    }

    public function test_create_makes_active_link_with_valid_code_and_created_event(): void
    {
        $link = $this->service->create(
            $this->user,
            '  https://drive.google.com/file/d/ABC123/view?usp=sharing  ',
            '  SPO Cuci Tangan 2026  '
        );

        $this->assertMatchesRegularExpression('/^[a-hjkmnp-z2-9]{10}$/', $link->code);
        $this->assertSame('https://drive.google.com/file/d/ABC123/view?usp=sharing', $link->target_url);
        $this->assertSame('SPO Cuci Tangan 2026', $link->label);
        $this->assertSame(QrLink::STATUS_ACTIVE, $link->status);
        $this->assertTrue($link->isActive());
        $this->assertSame(0, $link->scan_count);
        $this->assertNull($link->last_scanned_at);
        $this->assertSame($this->user->id, $link->created_by);

        $this->assertDatabaseHas('qr_link_events', [
            'qr_link_id' => $link->id,
            'user_id' => $this->user->id,
            'event' => 'created',
        ]);
    }

    public function test_create_generates_unique_codes(): void
    {
        $codes = [];

        for ($i = 0; $i < 40; $i++) {
            $codes[] = $this->service->create(
                $this->user,
                'https://drive.google.com/file/d/ABC' . $i . '/view',
                'Dokumen ' . $i
            )->code;
        }

        $this->assertCount(40, array_unique($codes));
    }

    public function test_create_with_invalid_url_throws_and_stores_nothing(): void
    {
        try {
            $this->service->create($this->user, 'https://evil.com/phishing', 'Label');
            $this->fail('Seharusnya melempar ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('target_url', $e->errors());
        }

        $this->assertSame(0, QrLink::count());
        $this->assertSame(0, QrLinkEvent::count());
    }

    public function test_create_with_empty_or_too_long_label_throws(): void
    {
        foreach (['', '   ', str_repeat('a', 256)] as $label) {
            try {
                $this->service->create($this->user, 'https://drive.google.com/file/d/abc', $label);
                $this->fail('Seharusnya melempar ValidationException untuk label: ' . strlen($label));
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('label', $e->errors());
            }
        }

        $this->assertSame(0, QrLink::count());
    }

    public function test_generate_code_uses_configured_length(): void
    {
        config(['tte.qr_link_code_length' => 12]);

        $this->assertMatchesRegularExpression('/^[a-hjkmnp-z2-9]{12}$/', $this->service->generateCode());
    }

    public function test_update_target_records_old_and_new_url_and_keeps_code(): void
    {
        $link = $this->service->create($this->user, 'https://drive.google.com/file/d/OLD/view', 'Lama');
        $code = $link->code;

        $other = User::factory()->create();
        $this->service->updateTarget($link, $other, 'https://docs.google.com/document/d/NEW/edit', 'Baru');

        $link->refresh();

        $this->assertSame($code, $link->code);
        $this->assertSame('https://docs.google.com/document/d/NEW/edit', $link->target_url);
        $this->assertSame('Baru', $link->label);

        $event = QrLinkEvent::where('qr_link_id', $link->id)->where('event', 'target_changed')->first();
        $this->assertNotNull($event);
        $this->assertSame($other->id, $event->user_id);
        $this->assertStringContainsString('https://drive.google.com/file/d/OLD/view', $event->note);
        $this->assertStringContainsString('https://docs.google.com/document/d/NEW/edit', $event->note);
    }

    public function test_label_only_change_does_not_record_target_changed_event(): void
    {
        $link = $this->service->create($this->user, 'https://drive.google.com/file/d/abc/view', 'Lama');

        $this->service->updateTarget($link, $this->user, 'https://drive.google.com/file/d/abc/view', 'Keterangan baru');

        $this->assertSame('Keterangan baru', $link->fresh()->label);
        $this->assertSame(0, QrLinkEvent::where('event', 'target_changed')->count());
    }

    public function test_update_target_with_invalid_url_keeps_old_data(): void
    {
        $link = $this->service->create($this->user, 'https://drive.google.com/file/d/abc/view', 'Lama');

        try {
            $this->service->updateTarget($link, $this->user, 'http://drive.google.com/file/d/abc/view', 'Baru');
            $this->fail('Seharusnya melempar ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('target_url', $e->errors());
        }

        $link->refresh();
        $this->assertSame('https://drive.google.com/file/d/abc/view', $link->target_url);
        $this->assertSame('Lama', $link->label);
        $this->assertSame(0, QrLinkEvent::where('event', 'target_changed')->count());
    }

    public function test_disable_requires_reason_and_records_state_and_event(): void
    {
        $link = $this->service->create($this->user, 'https://drive.google.com/file/d/abc/view', 'Label');
        $admin = User::factory()->create(['role' => 'admin']);

        try {
            $this->service->disable($link, $admin, 'abc');
            $this->fail('Alasan pendek seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('disable_reason', $e->errors());
        }
        $this->assertTrue($link->fresh()->isActive());

        $this->service->disable($link, $admin, '  QR salah sebar ke pihak luar  ');

        $link->refresh();
        $this->assertTrue($link->isDisabled());
        $this->assertSame($admin->id, $link->disabled_by);
        $this->assertNotNull($link->disabled_at);
        $this->assertSame('QR salah sebar ke pihak luar', $link->disable_reason);

        $this->assertDatabaseHas('qr_link_events', [
            'qr_link_id' => $link->id,
            'user_id' => $admin->id,
            'event' => 'disabled',
            'note' => 'QR salah sebar ke pihak luar',
        ]);
    }

    public function test_disable_twice_is_rejected(): void
    {
        $link = $this->service->create($this->user, 'https://drive.google.com/file/d/abc/view', 'Label');
        $this->service->disable($link, $this->user, 'Alasan yang cukup');

        $this->expectException(ValidationException::class);
        $this->service->disable($link->fresh(), $this->user, 'Alasan lagi');
    }

    public function test_enable_clears_disable_columns_and_records_event(): void
    {
        $link = $this->service->create($this->user, 'https://drive.google.com/file/d/abc/view', 'Label');
        $this->service->disable($link, $this->user, 'Dinonaktifkan sementara');

        $this->service->enable($link->fresh(), $this->user);

        $link->refresh();
        $this->assertTrue($link->isActive());
        $this->assertNull($link->disabled_at);
        $this->assertNull($link->disabled_by);
        $this->assertNull($link->disable_reason);

        $this->assertDatabaseHas('qr_link_events', [
            'qr_link_id' => $link->id,
            'event' => 'enabled',
        ]);
        $this->assertSame(1, QrLinkEvent::where('event', 'disabled')->count());
    }

    public function test_enable_on_active_link_is_rejected(): void
    {
        $link = $this->service->create($this->user, 'https://drive.google.com/file/d/abc/view', 'Label');

        $this->expectException(ValidationException::class);
        $this->service->enable($link, $this->user);
    }

    public function test_model_helpers_and_relations(): void
    {
        $link = QrLink::factory()->create();
        $disabled = QrLink::factory()->disabled('Salah sebar')->create();

        $this->assertSame('code', $link->getRouteKeyName());
        $this->assertTrue($link->isActive());
        $this->assertTrue($disabled->isDisabled());
        $this->assertSame($link->created_by, $link->creator->id);
        $this->assertNotNull($disabled->disabler);
    }
}
