<?php

namespace App\Http\Controllers;

use App\Models\LetterType;
use App\Models\QrGeneration;
use App\Models\Signer;
use App\Services\LetterNumberService;
use Illuminate\Http\Request;

class TanpaNomorController extends Controller
{
    public function create()
    {
        abort_unless(config('tte.auto_numbering'), 404);

        $signers = Signer::where('is_active', true)->orderBy('name')->get();
        $types = LetterType::where('is_active', true)
            ->where('numbering', 'none')
            ->orderBy('name')
            ->get();

        return view('tanpa-nomor.create', compact('signers', 'types'));
    }

    public function store(Request $request, LetterNumberService $letterService)
    {
        abort_unless(config('tte.auto_numbering'), 404);

        $validated = $request->validate([
            'letter_type_id' => ['required', 'exists:letter_types,id'],
            'signer_id' => ['required', 'exists:signers,id'],
            'perihal' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ], [
            'letter_type_id.required' => 'Jenis dokumen wajib dipilih.',
            'signer_id.required' => 'Penandatangan wajib dipilih.',
            'perihal.required' => 'Perihal dokumen wajib diisi.',
        ]);

        $type = LetterType::findOrFail($validated['letter_type_id']);

        if (! $type->is_active) {
            return back()->withInput()->withErrors(['letter_type_id' => "Jenis dokumen '{$type->name}' sedang nonaktif."]);
        }

        if ($type->numbering !== 'none') {
            return back()->withInput()->withErrors(['letter_type_id' => 'Jenis surat ini memiliki penomoran, silakan gunakan menu Surat Bernomor.']);
        }

        $signer = Signer::findOrFail($validated['signer_id']);
        if (! $signer->is_active) {
            return back()->withInput()->withErrors(['signer_id' => "Penandatangan '{$signer->name}' sedang nonaktif."]);
        }

        if ($type->reference_label && $type->reference_required && empty(trim((string) $validated['reference']))) {
            return back()->withInput()->withErrors(['reference' => "{$type->reference_label} wajib diisi."]);
        }

        // Cek duplikasi referensi (Spec §6.1b & K10) -> Peringatan konfirmasi, tidak diblok
        $ref = trim((string) ($validated['reference'] ?? ''));
        if ($ref !== '' && ! $request->boolean('confirm_duplicate')) {
            $duplicateExists = QrGeneration::where('letter_type_id', $type->id)
                ->where('reference', $ref)
                ->exists();

            if ($duplicateExists) {
                return back()->withInput()->with('duplicate_warning', "Referensi '{$ref}' sudah pernah diterbitkan pada {$type->name}. Silakan centang kotak konfirmasi jika memang ingin menerbitkan ulang.");
            }
        }

        $generation = $letterService->issue(
            letterType: $type,
            destination: null,
            unit: null,
            signer: $signer,
            perihal: $validated['perihal'],
            user: $request->user(),
            ipAddress: $request->ip(),
            manualNumber: null,
            reference: $ref !== '' ? $ref : null
        );

        return redirect()->route('qr.show', $generation->uuid)
            ->with('success', 'QR TTE Tanpa Nomor berhasil diterbitkan.');
    }
}
