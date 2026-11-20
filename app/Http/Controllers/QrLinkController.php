<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQrLinkRequest;
use App\Http\Requests\UpdateQrLinkRequest;
use App\Models\QrLink;
use App\Services\QrCodeService;
use App\Services\QrLinkService;
use App\Support\QrLinkUrlGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * UI staf untuk QR Link (spec-qr-link.md §3, §6, §7).
 * Flag TTE_QR_LINK_ENABLED dijaga middleware 'qr-link.enabled' di level rute.
 */
class QrLinkController extends Controller
{
    public function __construct(
        private QrCodeService $qrCodeService,
        private QrLinkService $qrLinkService,
    ) {
    }

    public function index(Request $request)
    {
        $query = QrLink::with('creator')->latest();

        // Staf hanya melihat miliknya sendiri, admin melihat semua (K4)
        if ($request->user()->role !== 'admin') {
            $query->where('created_by', $request->user()->id);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('label', 'like', $term)
                  ->orWhere('target_url', 'like', $term);
            });
        }

        $links = $query->paginate(20)->withQueryString();

        return view('qr-link.index', compact('links'));
    }

    public function create(QrLinkUrlGuard $guard)
    {
        return view('qr-link.create', ['allowedHosts' => $guard->allowedHosts()]);
    }

    public function store(StoreQrLinkRequest $request)
    {
        $link = $this->qrLinkService->create(
            $request->user(),
            $request->validated('target_url'),
            $request->validated('label'),
        );

        return redirect()->route('qr-link.show', $link)
            ->with('success', 'QR Link berhasil dibuat. Unduh gambar QR di bawah ini.');
    }

    public function show(QrLink $link)
    {
        Gate::authorize('view', $link);

        $link->load([
            'creator',
            'disabler',
            'events' => fn ($q) => $q->orderByDesc('created_at')->orderByDesc('id'),
            'events.user',
        ]);

        return view('qr-link.show', compact('link'));
    }

    public function png(QrLink $link)
    {
        Gate::authorize('view', $link);

        // QR tetap bisa diunduh walau status nonaktif (catatan ditampilkan di halaman detail).
        $pngData = $this->qrCodeService->generate(route('qr-link.go', $link->code));

        return response($pngData, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-link-' . $link->code . '.png"',
        ]);
    }

    public function edit(QrLink $link, QrLinkUrlGuard $guard)
    {
        Gate::authorize('update', $link);

        return view('qr-link.edit', [
            'link' => $link,
            'allowedHosts' => $guard->allowedHosts(),
        ]);
    }

    public function update(UpdateQrLinkRequest $request, QrLink $link)
    {
        $this->qrLinkService->updateTarget(
            $link,
            $request->user(),
            $request->validated('target_url'),
            $request->validated('label'),
        );

        return redirect()->route('qr-link.show', $link)
            ->with('success', 'QR Link diperbarui. Perubahan tujuan langsung berlaku pada QR yang sama.');
    }

    /**
     * Nonaktifkan (alasan wajib, min. 5 karakter) atau aktifkan kembali (K5, K6).
     * Pelanggaran aturan dilempar service sebagai ValidationException.
     */
    public function toggle(Request $request, QrLink $link)
    {
        Gate::authorize('toggle', $link);

        if ($link->isActive()) {
            $this->qrLinkService->disable($link, $request->user(), (string) $request->input('disable_reason', ''));
            $message = 'QR Link dinonaktifkan. Pemindai kini melihat halaman "dinonaktifkan".';
        } else {
            $this->qrLinkService->enable($link, $request->user());
            $message = 'QR Link diaktifkan kembali. QR yang sudah beredar berfungsi lagi.';
        }

        return redirect()->route('qr-link.show', $link)->with('success', $message);
    }
}
