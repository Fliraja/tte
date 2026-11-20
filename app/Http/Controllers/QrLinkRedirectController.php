<?php

namespace App\Http\Controllers;

use App\Models\QrLink;
use App\Support\QrLinkUrlGuard;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Rute publik /l/{code} (spec-qr-link.md §3.2, §4.3, §8).
 *
 * Flag TTE_QR_LINK_ENABLED dijaga middleware 'qr-link.enabled' di level rute.
 * Pola kode dan throttle dijaga di definisi rute.
 */
class QrLinkRedirectController extends Controller
{
    private const SECURITY_HEADERS = [
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
        'Referrer-Policy' => 'no-referrer',
        'X-Robots-Tag' => 'noindex, nofollow',
        'X-Content-Type-Options' => 'nosniff',
    ];

    public function go(string $code, QrLinkUrlGuard $guard): Response|RedirectResponse
    {
        $link = QrLink::where('code', $code)->first();

        if (! $link) {
            return $this->status(404, 'Tautan tidak ditemukan.');
        }

        if ($link->isDisabled()) {
            return $this->status(410, 'Tautan ini sudah dinonaktifkan.');
        }

        // Validasi ulang: whitelist atau data bisa berubah setelah tautan disimpan.
        if (! $guard->isAllowed($link->target_url)) {
            Log::warning('QR Link: tujuan ditolak guard saat redirect', ['code' => $code]);

            return $this->status(404, 'Tautan tidak tersedia.');
        }

        // Update atomik di DB (tanpa baca-ubah-tulis) supaya akses bersamaan tidak saling menimpa.
        // toBase() melewati Eloquent agar updated_at tidak ikut berubah tiap pemindaian.
        QrLink::whereKey($link->id)->toBase()->update([
            'scan_count' => DB::raw('scan_count + 1'),
            'last_scanned_at' => now(),
        ]);

        return redirect()->away($link->target_url, 302)->withHeaders(self::SECURITY_HEADERS);
    }

    private function status(int $httpStatus, string $message): Response
    {
        // Tanpa keterangan, nama pemilik, atau tujuan: pemindai tidak boleh melihat data internal.
        return response()
            ->view('qr-link.unavailable', ['message' => $message], $httpStatus)
            ->withHeaders(self::SECURITY_HEADERS);
    }
}
