<?php

namespace Tests\Unit;

use App\Support\QrLinkUrlGuard;
use Tests\TestCase;

class QrLinkUrlGuardTest extends TestCase
{
    private QrLinkUrlGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tte.qr_link_allowed_hosts' => ['drive.google.com', 'docs.google.com']]);
        $this->guard = new QrLinkUrlGuard();
    }

    public function test_valid_urls_are_accepted(): void
    {
        $valid = [
            'https://drive.google.com/file/d/ABC123/view?usp=sharing',
            'https://drive.google.com/drive/folders/ABC123',
            'https://docs.google.com/document/d/ABC123/edit',
            'HTTPS://DRIVE.GOOGLE.COM/file/d/ABC123/view',
            'https://drive.google.com:443/file/d/ABC123/view',
            '  https://drive.google.com/file/d/ABC123/view  ',
        ];

        foreach ($valid as $url) {
            $this->assertTrue($this->guard->isAllowed($url), "Seharusnya diterima: {$url}");
            $this->assertNull($this->guard->reason($url));
        }
    }

    public function test_invalid_urls_are_rejected(): void
    {
        $invalid = [
            'http://drive.google.com/file/d/abc',
            'javascript:alert(1)',
            'data:text/html;base64,PHNjcmlwdD4=',
            'file:///C:/Windows/win.ini',
            'https://drive.google.com.evil.com/x',
            'https://evil.com/?u=https://drive.google.com',
            'https://drive.google.com@evil.com/x',
            'https://user:pass@drive.google.com/x',
            'https://evil.com\\@drive.google.com/x',
            'https://drive.google.com:8443/x',
            'https://sites.google.com/x',
            'https://sub.drive.google.com/x',
            'https://www.docs.google.com/x',
            'https://drive.google.com./x',
            'drive.google.com/file/d/abc',
            'https:///drive.google.com/x',
            'https://drive.google.com/file/d/a b',
            "https://drive.google.com/x\nSet-Cookie: a=b",
            '',
            '   ',
        ];

        foreach ($invalid as $url) {
            $this->assertFalse($this->guard->isAllowed($url), 'Seharusnya ditolak: ' . json_encode($url));
            $this->assertIsString($this->guard->reason($url));
        }
    }

    public function test_length_limit_is_2048_characters(): void
    {
        $prefix = 'https://drive.google.com/file/d/';

        $exact = $prefix . str_repeat('a', QrLinkUrlGuard::MAX_LENGTH - strlen($prefix));
        $tooLong = $exact . 'a';

        $this->assertSame(QrLinkUrlGuard::MAX_LENGTH, strlen($exact));
        $this->assertTrue($this->guard->isAllowed($exact));
        $this->assertFalse($this->guard->isAllowed($tooLong));
    }

    public function test_whitelist_is_normalized_when_read(): void
    {
        config(['tte.qr_link_allowed_hosts' => ['  Drive.Google.COM  ', '']]);

        $this->assertSame(['drive.google.com'], $this->guard->allowedHosts());
        $this->assertTrue($this->guard->isAllowed('https://drive.google.com/file/d/abc'));
        $this->assertFalse($this->guard->isAllowed('https://docs.google.com/document/d/abc'));
    }

    public function test_empty_whitelist_allows_any_public_https_host(): void
    {
        config(['tte.qr_link_allowed_hosts' => []]);

        foreach ([
            'https://drive.google.com/file/d/abc',
            'https://example.org/page',
            'https://sub.example.co.id/a/b?c=1#d',
            'https://forms.gle/AbC123',
            'https://www.canva.com/design/x/view',
            'https://xn--bcher-kva.example/x',
            'https://EXAMPLE.org/Case',
        ] as $url) {
            $this->assertTrue($this->guard->isAllowed($url), $url);
            $this->assertNull($this->guard->reason($url), $url);
        }
    }

    public function test_any_host_mode_still_rejects_ip_local_names_and_unsafe_urls(): void
    {
        config(['tte.qr_link_allowed_hosts' => []]);

        foreach ([
            'http://example.org/x',
            'ftp://example.org/x',
            'javascript:alert(1)',
            'https://localhost/x',
            'https://intranet/x',
            'https://127.0.0.1/x',
            'https://192.168.1.10/x',
            'https://10.0.0.5/x',
            'https://[::1]/x',
            'https://2130706433/x',
            'https://example.123/x',
            'https://-bad.example/x',
            'https://bad-.example/x',
            'https://exa_mple.org/x',
            'https://example.org:8443/x',
            'https://user:pass@example.org/x',
            'https://example.org/a b',
            'https://example.org\\@evil.com/x',
        ] as $url) {
            $this->assertFalse($this->guard->isAllowed($url), $url);
        }
    }

    public function test_any_host_mode_reports_ip_and_local_reason_in_indonesian(): void
    {
        config(['tte.qr_link_allowed_hosts' => []]);

        $this->assertSame(
            'Tautan harus memakai nama domain situs, bukan alamat IP atau nama lokal.',
            $this->guard->reason('https://127.0.0.1/x')
        );
    }

    public function test_normalize_only_trims(): void
    {
        $this->assertSame(
            'https://drive.google.com/file/d/Ab?x=1#frag',
            $this->guard->normalize("  https://drive.google.com/file/d/Ab?x=1#frag \n")
        );
    }
}
