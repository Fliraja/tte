<?php

namespace App\Http\Controllers;

use App\Models\QrGeneration;

class VerificationController extends Controller
{
    public function show(string $uuid)
    {
        $generation = QrGeneration::with('signer')
            ->where('uuid', $uuid)
            ->first();

        if (! $generation) {
            return view('verify.not-found');
        }

        return view('verify.show', compact('generation'));
    }
}
