<?php

namespace App\Http\Controllers;

use App\Models\QrGeneration;
use Illuminate\Http\Response;

class VerificationController extends Controller
{
    public function show(string $uuid): Response
    {
        $generation = QrGeneration::with(['signer', 'letterType'])
            ->where('uuid', $uuid)
            ->first();

        $securityHeaders = [
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (! $generation) {
            return response()
                ->view('verify.not-found', [], 200)
                ->withHeaders($securityHeaders);
        }

        return response()
            ->view('verify.show', compact('generation'))
            ->withHeaders($securityHeaders);
    }
}
