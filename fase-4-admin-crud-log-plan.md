# Fase 4 — Admin CRUD Signer & Log Audit (Detail Implementation Plan)

Estimasi: 1 hari. Prasyarat: Fase 0-3 selesai (auth+role jalan, generate+verify jalan).

## 1. Controller CRUD Signer

```bash
php artisan make:controller SignerController --resource
```

```php
// app/Http/Controllers/SignerController.php
class SignerController extends Controller
{
    public function index()
    {
        $signers = Signer::latest()->paginate(15);
        return view('admin.signers.index', compact('signers'));
    }

    public function create()
    {
        return view('admin.signers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ]);
        Signer::create($validated);
        return redirect()->route('signers.index')->with('success', 'Penandatangan berhasil ditambahkan.');
    }

    public function edit(Signer $signer)
    {
        return view('admin.signers.edit', compact('signer'));
    }

    public function update(Request $request, Signer $signer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ]);
        $signer->update($validated);
        return redirect()->route('signers.index')->with('success', 'Data diperbarui.');
    }

    public function destroy(Signer $signer)
    {
        // nonaktifkan, bukan hapus permanen — jaga integritas FK qr_generations.signer_id
        $signer->update(['is_active' => false]);
        return redirect()->route('signers.index')->with('success', 'Signer dinonaktifkan.');
    }
}
```

Penting: `destroy()` gak hard delete. Kalau signer dihapus permanen padahal udah punya histori `qr_generations`, halaman verify lama bakal error pas nampilin `$generation->signer->name` (relasi null). Nonaktifkan aman, histori tetap utuh.

## 2. Route

```php
// routes/web.php, di dalam group middleware(['auth', 'admin'])
Route::resource('signers', SignerController::class)->except(['show']);
```

## 3. View CRUD Signer

```blade
{{-- resources/views/admin/signers/index.blade.php --}}
<x-app-layout>
    <div class="max-w-3xl mx-auto py-8">
        <div class="flex justify-between mb-4">
            <h1 class="text-xl font-semibold">Kelola Penandatangan</h1>
            <a href="{{ route('signers.create') }}" class="btn">+ Tambah</a>
        </div>

        @if (session('success'))
            <p class="text-green-600 mb-4">{{ session('success') }}</p>
        @endif

        <table class="w-full border">
            <thead>
                <tr><th>Nama</th><th>Jabatan</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @foreach ($signers as $signer)
                <tr>
                    <td>{{ $signer->name }}</td>
                    <td>{{ $signer->position }}</td>
                    <td>{{ $signer->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>
                        <a href="{{ route('signers.edit', $signer) }}">Edit</a>
                        @if ($signer->is_active)
                        <form action="{{ route('signers.destroy', $signer) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Nonaktifkan signer ini?')">Nonaktifkan</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $signers->links() }}
    </div>
</x-app-layout>
```

Form `create.blade.php` dan `edit.blade.php` sama pola dengan Fase 1 (input `name`, `position`).

## 4. Controller Log Audit

```bash
php artisan make:controller AuditLogController
```

```php
// app/Http/Controllers/AuditLogController.php
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = QrGeneration::with('signer', 'generator')->latest();

        if ($request->filled('signer_name')) {
            $query->whereHas('signer', fn ($q) =>
                $q->where('name', 'like', '%' . $request->signer_name . '%'));
        }

        if ($request->filled('nip')) {
            $query->whereHas('generator', fn ($q) =>
                $q->where('nip', 'like', '%' . $request->nip . '%'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.logs.index', compact('logs'));
    }
}
```

## 5. Route Log

```php
// routes/web.php, di dalam group middleware(['auth', 'admin'])
Route::get('/logs', [AuditLogController::class, 'index'])->name('logs.index');
```

## 6. View Log Audit

```blade
{{-- resources/views/admin/logs/index.blade.php --}}
<x-app-layout>
    <div class="max-w-5xl mx-auto py-8">
        <h1 class="text-xl font-semibold mb-4">Log Audit Generate QR</h1>

        <form method="GET" class="flex gap-2 mb-4">
            <input type="text" name="signer_name" placeholder="Nama signer" value="{{ request('signer_name') }}">
            <input type="text" name="nip" placeholder="NIP staf" value="{{ request('nip') }}">
            <input type="date" name="date_from" value="{{ request('date_from') }}">
            <input type="date" name="date_to" value="{{ request('date_to') }}">
            <button type="submit">Filter</button>
        </form>

        <table class="w-full border text-sm">
            <thead>
                <tr>
                    <th>Waktu</th><th>Signer</th><th>Nomor Surat</th>
                    <th>Digenerate Oleh</th><th>IP</th><th>UUID</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                <tr>
                    <td>{{ $log->created_at->translatedFormat('d M Y, H:i') }}</td>
                    <td>{{ $log->signer->name }}</td>
                    <td>{{ $log->letter_number ?? '-' }}</td>
                    <td>{{ $log->generator->name }} ({{ $log->generator->nip }})</td>
                    <td>{{ $log->ip_address }}</td>
                    <td class="font-mono text-xs">{{ $log->uuid }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{ $logs->links() }}
    </div>
</x-app-layout>
```

Halaman ini yang boleh tampilkan `generated_by` + `ip_address` — beda dari halaman `/verify` publik di Fase 3 yang sengaja sembunyikan data ini.

## 7. Test Manual (Checklist)

- [ ] Login admin, akses `/admin/signers` → list signer dummy Fase 0 muncul
- [ ] Tambah signer baru → langsung muncul di list, langsung bisa dipilih di dropdown generate (`/`)
- [ ] Edit nama/jabatan signer → berubah di list, tapi histori `qr_generations` lama tetap simpan nama versi lama? *(catatan di bawah)*
- [ ] Nonaktifkan signer → hilang dari dropdown generate (Fase 2), tapi histori generate lama tetap tampil normal di log & verify
- [ ] Login staff (bukan admin), coba akses `/admin/signers` → 403
- [ ] Akses `/admin/logs`, filter by nama signer → hasil sesuai
- [ ] Filter by NIP staf → hasil sesuai
- [ ] Filter tanggal range → hasil sesuai, kosongin filter → tampil semua data
- [ ] Kolom `ip_address` dan NIP staf muncul di log, tidak muncul di halaman `/verify` publik

## Catatan Penting: Edit Nama Signer vs Histori

Karena `qr_generations.signer_id` itu FK (bukan snapshot nama), kalau nama/jabatan signer diedit, semua histori lama (termasuk QR yang udah ditempel di surat lama) bakal ikut tampil nama BARU pas di-scan ulang — bukan nama yang berlaku saat surat itu ditandatangani dulu. Ini works as intended untuk v1 (spec tidak minta snapshot data), tapi worth didiskusikan: kalau jabatan sering berubah (misal ganti Direktur), histori surat lama bisa jadi "salah tampil" di halaman verify. Kalau ini jadi masalah nyata nanti, solusinya snapshot `signer_name` + `signer_position` langsung di tabel `qr_generations` saat generate (denormalisasi), bukan cuma FK. Taruh di Open Question buat didiskusikan, tidak perlu diubah sekarang kalau belum jadi concern.

## Output Fase 4
- Admin bisa kelola signer (tambah/edit/nonaktifkan) tanpa sentuh kode
- Log audit lengkap dengan filter, siap dipakai buat investigasi kalau ada surat dipertanyakan keasliannya
- **MVP (Fase 0-4) selesai total.** Sistem generate QR + verifikasi + audit + admin panel, siap masuk tahap testing menyeluruh / UAT sebelum dipakai staf TU beneran.
