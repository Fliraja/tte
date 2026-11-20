<?php

namespace App\Http\Controllers;

/**
 * Rute publik /l/{code}.
 *
 * Sementara masih kerangka: nama rute `qr-link.go` dibutuhkan Fase Q2 untuk isi QR,
 * tetapi redirect sebenarnya dikerjakan di Fase Q3 (validasi ulang guard, hitung akses,
 * 302, header keamanan, halaman status). Sampai Q3 selesai, semua kode menghasilkan 404.
 */
class QrLinkRedirectController extends Controller
{
    public function go(string $code)
    {
        abort(404);
    }
}
