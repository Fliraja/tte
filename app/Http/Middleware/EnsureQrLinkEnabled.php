<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Semua rute fitur QR Link menjadi 404 selama flag TTE_QR_LINK_ENABLED mati.
 * Dipasang di level rute (bukan di controller) supaya berlaku sebelum validasi FormRequest.
 */
class EnsureQrLinkEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('tte.qr_link_enabled'), 404);

        return $next($request);
    }
}
