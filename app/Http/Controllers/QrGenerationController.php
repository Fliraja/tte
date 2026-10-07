<?php

namespace App\Http\Controllers;

use App\Models\Signer;
use App\Models\QrGeneration;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrGenerationController extends Controller
{
    public function __construct(private QrCodeService $qrCodeService)
    {
    }

    public function create()
    {
        $signers = Signer::where('is_active', true)->orderBy('name')->get();
        return view('qr.create', compact('signers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'signer_id' => 'required|exists:signers,id,is_active,1',
            'letter_number' => 'required|string|max:100',
            'perihal' => 'required|string|max:255',
        ]);

        $uuid = Str::uuid();

        QrGeneration::create([
            'uuid' => $uuid,
            'signer_id' => $validated['signer_id'],
            'letter_number' => $validated['letter_number'],
            'perihal' => $validated['perihal'],
            'generated_by' => auth()->id(),
            'ip_address' => $request->ip(),
        ]);

        $pngData = $this->qrCodeService->generate(url('/verify/' . $uuid));

        return response($pngData, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="qr-ttd-' . $uuid . '.png"',
        ]);
    }
}
