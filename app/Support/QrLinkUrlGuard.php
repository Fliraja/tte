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

        // Whitelist kosong = mode bebas: situs https publik apa pun boleh,
        // tetapi bukan alamat IP atau nama lokal (localhost, intranet, dst).
        if ($allowed === [] && ! $this->isPublicHostName(strtolower($parts['host']))) {
            return 'Tautan harus memakai nama domain situs, bukan alamat IP atau nama lokal.';
        }

        if ($allowed !== [] && ! in_array(strtolower($parts['host']), $allowed, true)) {
            return 'Domain tujuan belum diizinkan. Domain yang diizinkan: ' . implode(', ', $allowed) . '.';
        }

        return null;
    }

    /**
     * Nama domain publik: minimal dua label, tiap label huruf/angka/tanda hubung
     * (tidak diawali atau diakhiri tanda hubung), label terakhir bukan angka murni.
     * Menolak IPv4/IPv6 literal, "localhost", dan nama satu label seperti "intranet".
     */
    private function isPublicHostName(string $host): bool
    {
        if ($host === '' || strlen($host) > 253) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return false;
        }

        $label = '[\p{L}\p{N}](?:[\p{L}\p{N}-]*[\p{L}\p{N}])?';

        if (preg_match('/^(?:' . $label . '\.)+' . $label . '$/u', $host) !== 1) {
            return false;
        }

        $labels = explode('.', $host);

        return ! ctype_digit((string) end($labels));
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
