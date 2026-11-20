<?php

namespace App\Support;

/**
 * Validasi tautan tujuan QR Link (spec-qr-link.md §4).
 *
 * Dipakai di tiga titik: saat membuat, saat mengganti tujuan, dan sekali lagi
 * saat redirect publik, supaya redirect di domain RS tidak bisa disalahgunakan.
 */
class QrLinkUrlGuard
{
    public const MAX_LENGTH = 2048;

    /**
     * Rapikan input sebelum divalidasi/disimpan. Path dan query tidak diubah.
     */
    public function normalize(string $url): string
    {
        return trim($url);
    }

    /**
     * Pesan kesalahan (bahasa Indonesia) atau null bila tautan lolos.
     */
    public function reason(string $url): ?string
    {
        $url = $this->normalize($url);

        if ($url === '') {
            return 'Tautan tujuan wajib diisi.';
        }

        if (strlen($url) > self::MAX_LENGTH) {
            return 'Tautan tujuan maksimal ' . self::MAX_LENGTH . ' karakter.';
        }

        // Spasi, tab, newline, karakter kontrol, dan backslash (trik "https://evil.com\@drive.google.com").
        if (preg_match('/[\s\x00-\x1F\x7F\\\\]/', $url) === 1) {
            return 'Tautan tidak boleh mengandung spasi atau karakter khusus.';
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return 'Tautan tidak valid.';
        }

        if (! isset($parts['scheme']) || strtolower($parts['scheme']) !== 'https') {
            return 'Tautan harus diawali https://';
        }

        if (! isset($parts['host']) || $parts['host'] === '') {
            return 'Tautan tidak valid.';
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return 'Tautan tidak boleh memuat nama pengguna atau kata sandi.';
        }

        if (isset($parts['port']) && $parts['port'] !== 443) {
            return 'Tautan tidak boleh memakai port khusus.';
        }

        $allowed = $this->allowedHosts();

        if (! in_array(strtolower($parts['host']), $allowed, true)) {
            return 'Domain tujuan belum diizinkan. Domain yang diizinkan: ' . implode(', ', $allowed) . '.';
        }

        return null;
    }

    public function isAllowed(string $url): bool
    {
        return $this->reason($url) === null;
    }

    /**
     * Whitelist host dari config, dinormalisasi (trim + huruf kecil) setiap dibaca
     * agar tahan terhadap config yang diisi manual.
     *
     * @return array<int, string>
     */
    public function allowedHosts(): array
    {
        $hosts = (array) config('tte.qr_link_allowed_hosts', []);

        return array_values(array_filter(array_map(
            fn ($host) => strtolower(trim((string) $host)),
            $hosts
        )));
    }
}
