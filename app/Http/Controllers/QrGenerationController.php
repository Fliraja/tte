<?php

namespace App\Http\Controllers;

use App\Exceptions\NumberBlockUnavailableException;
use App\Http\Requests\StoreQrGenerationRequest;
use App\Models\LetterDestination;
use App\Models\LetterType;
use App\Models\LetterUnit;
use App\Models\QrGeneration;
use App\Models\Signer;
use App\Services\LetterNumberService;
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

        if (! config('tte.auto_numbering')) {
            return view('qr.create', compact('signers'));
        }

        $types = LetterType::where('is_active', true)
            ->where('numbering', '!=', 'none')
            ->orderByRaw("CASE WHEN numbering = 'manual' THEN 1 ELSE 0 END, code ASC")
            ->get();

        $destinations = LetterDestination::where('is_active', true)->orderBy('code')->get();
        $units = LetterUnit::where('is_active', true)->orderBy('code')->get();

        return view('qr.create', compact('signers', 'types', 'destinations', 'units'));
    }

    public function store(Request $request, LetterNumberService $letterService)
    {
        // Legacy flow jika auto_numbering dimatikan
        if (! config('tte.auto_numbering')) {
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
                'status' => 'issued',
            ]);

            $pngData = $this->qrCodeService->generate(url('/verify/' . $uuid));

            return response($pngData, 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'attachment; filename="qr-ttd-' . $uuid . '.png"',
            ]);
        }

        // Flow Penomoran Otomatis / Manual Baru (Fase 8)
        $formRequest = app(StoreQrGenerationRequest::class);
        $validated = $formRequest->validated();

        $letterType = LetterType::findOrFail($validated['letter_type_id']);
        $signer = Signer::findOrFail($validated['signer_id']);
        $destination = ! empty($validated['destination_id']) ? LetterDestination::find($validated['destination_id']) : null;
        $unit = ! empty($validated['unit_id']) ? LetterUnit::find($validated['unit_id']) : null;

        try {
            $generation = $letterService->issue(
                letterType: $letterType,
                destination: $destination,
                unit: $unit,
                signer: $signer,
                perihal: $validated['perihal'],
                user: $request->user(),
                ipAddress: $request->ip(),
                manualNumber: $validated['manual_number'] ?? null,
                reference: $validated['reference'] ?? null
            );
        } catch (NumberBlockUnavailableException $e) {
            return back()->withInput()->withErrors([
                'letter_type_id' => $e->getMessage(),
            ]);
        }

        return redirect()->route('qr.show', $generation->uuid)
            ->with('success', 'Nomor surat dan QR berhasil diterbitkan.');
    }

    public function show(string $uuid)
    {
        $generation = QrGeneration::with(['signer', 'letterType', 'destination', 'unit', 'generator'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        // Otorisasi: hanya pembuat atau admin
        if (auth()->user()->role !== 'admin' && $generation->generated_by !== auth()->id()) {
            abort(403);
        }

        return view('qr.show', compact('generation'));
    }

    public function png(string $uuid)
    {
        $generation = QrGeneration::where('uuid', $uuid)->firstOrFail();

        if (auth()->user()->role !== 'admin' && $generation->generated_by !== auth()->id()) {
            abort(403);
        }

        $pngData = $this->qrCodeService->generate(url('/verify/' . $generation->uuid));

        return response($pngData, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-ttd-' . $generation->uuid . '.png"',
        ]);
    }

    public function history(Request $request)
    {
        $query = QrGeneration::with(['signer', 'letterType', 'generator']);

        // Staf hanya melihat miliknya sendiri, admin melihat semua (K7)
        if (auth()->user()->role !== 'admin') {
            $query->where('generated_by', auth()->id());
        }

        // Filter keyword pencarian (Task 8.9)
        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('letter_number', 'like', $term)
                  ->orWhere('perihal', 'like', $term)
                  ->orWhere('reference', 'like', $term);
            });
        }

        if ($request->filled('letter_type_id')) {
            $query->where('letter_type_id', $request->input('letter_type_id'));
        }

        if ($request->filled('signer_id')) {
            $query->where('signer_id', $request->input('signer_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $generations = $query->latest()->paginate(20)->withQueryString();

        $types = LetterType::where('is_active', true)->orderBy('name')->get();
        $signers = Signer::where('is_active', true)->orderBy('name')->get();

        return view('qr.history', compact('generations', 'types', 'signers'));
    }
}
