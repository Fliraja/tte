<?php

namespace App\Http\Controllers;

use App\Models\QrGeneration;
use App\Models\QrGenerationEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class QrDocumentController extends Controller
{
    /**
     * Upload the final PDF document for an issued QR generation.
     */
    public function upload(Request $request, string $uuid)
    {
        if (! config('tte.documents_enabled')) {
            abort(404, 'Fitur upload dokumen belum diaktifkan.');
        }

        $generation = QrGeneration::where('uuid', $uuid)->firstOrFail();
        Gate::authorize('uploadDocument', $generation);

        $maxKb = config('tte.document_max_kb', 5120);

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf', 'max:' . $maxKb],
        ], [
            'document.required' => 'File dokumen PDF wajib dipilih.',
            'document.mimes' => 'Format file wajib PDF.',
            'document.max' => "Ukuran file tidak boleh melebihi {$maxKb} KB.",
        ]);

        $file = $request->file('document');
        $this->validatePdfHeader($file);

        $year = $generation->number_year ?? $generation->created_at->format('Y');
        $disk = config('tte.document_disk', 'local');
        $targetFilename = "{$generation->uuid}.pdf";
        $directory = "letters/{$year}";

        $hash = hash_file('sha256', $file->getRealPath());
        $storedPath = Storage::disk($disk)->putFileAs($directory, $file, $targetFilename);

        $generation->update([
            'document_path' => $storedPath,
            'document_hash' => $hash,
            'document_uploaded_at' => now(),
            'document_uploaded_by' => $request->user()->id,
            'status' => 'final',
        ]);

        QrGenerationEvent::create([
            'qr_generation_id' => $generation->id,
            'user_id' => $request->user()->id,
            'event' => 'document_uploaded',
            'note' => 'Dokumen final diunggah (SHA-256: ' . substr($hash, 0, 12) . '...)',
        ]);

        return redirect()->route('qr.show', $generation->uuid)
            ->with('success', 'Dokumen final berhasil diunggah. Surat telah dikunci (Final).');
    }

    /**
     * Replace the final PDF document (Admin only).
     */
    public function replace(Request $request, string $uuid)
    {
        if (! config('tte.documents_enabled')) {
            abort(404, 'Fitur upload dokumen belum diaktifkan.');
        }

        $generation = QrGeneration::where('uuid', $uuid)->firstOrFail();
        Gate::authorize('replaceDocument', $generation);

        $maxKb = config('tte.document_max_kb', 5120);

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf', 'max:' . $maxKb],
            'reason' => ['required', 'string', 'min:5'],
        ], [
            'document.required' => 'File dokumen PDF pengganti wajib dipilih.',
            'document.mimes' => 'Format file wajib PDF.',
            'document.max' => "Ukuran file tidak boleh melebihi {$maxKb} KB.",
            'reason.required' => 'Alasan penggantian dokumen wajib diisi.',
            'reason.min' => 'Alasan penggantian minimal 5 karakter.',
        ]);

        $file = $request->file('document');
        $this->validatePdfHeader($file);

        $year = $generation->number_year ?? $generation->created_at->format('Y');
        $disk = config('tte.document_disk', 'local');
        $targetFilename = "{$generation->uuid}.pdf";
        $directory = "letters/{$year}";

        $oldHash = $generation->document_hash ?? '-';
        $newHash = hash_file('sha256', $file->getRealPath());

        $storedPath = Storage::disk($disk)->putFileAs($directory, $file, $targetFilename);

        $generation->update([
            'document_path' => $storedPath,
            'document_hash' => $newHash,
            'document_uploaded_at' => now(),
            'document_uploaded_by' => $request->user()->id,
        ]);

        QrGenerationEvent::create([
            'qr_generation_id' => $generation->id,
            'user_id' => $request->user()->id,
            'event' => 'document_replaced',
            'note' => 'Dokumen diganti oleh admin: ' . $request->input('reason') .
                      ' (Hash lama: ' . substr($oldHash, 0, 12) . '..., Hash baru: ' . substr($newHash, 0, 12) . '...)',
        ]);

        return redirect()->route('qr.show', $generation->uuid)
            ->with('success', 'Dokumen final berhasil diperbarui oleh Admin.');
    }

    /**
     * Preview / download the stored private document for authenticated users (Staff / Admin).
     */
    public function show(string $uuid): Response
    {
        $generation = QrGeneration::where('uuid', $uuid)->firstOrFail();
        Gate::authorize('viewDocument', $generation);

        $disk = Storage::disk(config('tte.document_disk', 'local'));

        if (! $generation->document_path || ! $disk->exists($generation->document_path)) {
            abort(404, 'File dokumen tidak ditemukan.');
        }

        return response($disk->get($generation->document_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="dokumen-' . $generation->uuid . '.pdf"',
        ]);
    }

    /**
     * Stream document for public verification page (/verify/{uuid}/document).
     */
    public function publicStream(string $uuid): Response
    {
        if (! config('tte.documents_enabled')) {
            abort(404);
        }

        $generation = QrGeneration::with('letterType')
            ->where('uuid', $uuid)
            ->firstOrFail();

        // Security check per Spec §7 & task 10.2:
        // Must be final, not void, show_content = true
        if ($generation->isVoid() || $generation->status !== 'final') {
            abort(404);
        }

        if ($generation->letterType && ! $generation->letterType->show_content) {
            abort(404);
        }

        $disk = Storage::disk(config('tte.document_disk', 'local'));

        if (! $generation->document_path || ! $disk->exists($generation->document_path)) {
            abort(404);
        }

        return response($disk->get($generation->document_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="dokumen-' . $generation->uuid . '.pdf"',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Ensure the uploaded file starts with the %PDF- magic bytes.
     */
    protected function validatePdfHeader($file): void
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $header = fread($handle, 5);
        fclose($handle);

        if ($header !== '%PDF-') {
            throw ValidationException::withMessages([
                'document' => 'File yang diunggah tidak valid atau bukan berkas PDF asli.',
            ]);
        }
    }
}

