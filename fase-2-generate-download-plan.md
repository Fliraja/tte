# Fase 2 — Core Generate & Download QR (Detail Implementation Plan)

Estimasi: 1 hari. Prasyarat: Fase 0 & 1 selesai (migration, model, auth+role jalan).

## 1. Controller

```bash
php artisan make:controller QrGenerationController
```

```php
// app/Http/Controllers/QrGenerationController.php
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use App\Models\Signer;
use App\Models\QrGeneration;
use Illuminate\Support\Str;

class QrGenerationController extends Controller
{
    public function create()
    {
        $signers = Signer::where('is_active', true)->orderBy('name')->get();
        return view('qr.create', compact('signers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'signer_id' => 'required|exists:signers,id',
            'letter_number' => 'nullable|string|max:100',
        ]);

        $uuid = Str::uuid();

        QrGeneration::create([
            'uuid' => $uuid,
            'signer_id' => $validated['signer_id'],
            'letter_number' => $validated['letter_number'] ?? null,
            'generated_by' => auth()->id(),
            'ip_address' => $request->ip(),
        ]);

        $qrCode = new QrCode(url('/verify/' . $uuid));
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return response($result->getString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="qr-ttd-' . $uuid . '.png"',
        ]);
    }
}
```

Catatan: `signer_id` divalidasi `exists:signers,id`, tapi belum cek `is_active`. Tambah rule custom biar signer nonaktif gak bisa dipilih paksa lewat request manual (bukan cuma disembunyikan di dropdown):

```php
'signer_id' => 'required|exists:signers,id,is_active,1',
```

## 2. Route

```php
// routes/web.php, di dalam group middleware('auth')
Route::get('/', [QrGenerationController::class, 'create'])->name('qr.create');
Route::post('/qr/generate', [QrGenerationController::class, 'store'])->name('qr.generate');
```

## 3. View — Form Generate

```blade
{{-- resources/views/qr/create.blade.php --}}
<x-app-layout>
    <div class="max-w-xl mx-auto py-8">
        <h1 class="text-xl font-semibold mb-4">Generate QR Tanda Tangan</h1>

        @if ($errors->any())
            <div class="mb-4 text-red-600">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('qr.generate') }}">
            @csrf

            <label for="signer_id">Nama Penandatangan</label>
            <select name="signer_id" id="signer_id" required>
                <option value="">-- Pilih --</option>
                @foreach ($signers as $signer)
                    <option value="{{ $signer->id }}">{{ $signer->name }} — {{ $signer->position }}</option>
                @endforeach
            </select>

            <label for="letter_number">Nomor Surat (opsional)</label>
            <input type="text" name="letter_number" id="letter_number" placeholder="Contoh: 001/RS/VII/2026">

            <button type="submit">Generate & Download QR</button>
        </form>
    </div>
</x-app-layout>
```

Dropdown cuma tampilkan signer aktif (query udah filter di controller), jadi gak perlu validasi tambahan di frontend.

## 4. Kenapa Stream Langsung, Bukan Simpan File Fisik

Sesuai spec awal: `response($result->getString(), ...)` generate PNG on-the-fly di memory, langsung dikirim sebagai download. Tidak ada file tersimpan di server (`storage/app/...`). Alasan: hemat storage, karena QR bisa di-generate ulang kapan saja dari data DB (`uuid` udah cukup buat rebuild QR image via URL verify). Kalau butuh, image bisa selalu di-regenerate dari DB.

## 5. Test Manual (Checklist)

- [ ] Buka `/` dalam kondisi login, dropdown cuma tampilkan signer `is_active=true`
- [ ] Signer nonaktif (`is_active=false`) tidak muncul di dropdown
- [ ] Submit tanpa pilih signer → validasi gagal, pesan error muncul
- [ ] Submit dengan signer valid, tanpa isi nomor surat → berhasil, file PNG kedownload otomatis
- [ ] Submit dengan nomor surat diisi → tersimpan di `qr_generations.letter_number`
- [ ] Cek DB: setiap generate bikin row baru dengan `uuid` unik, `generated_by` keisi NIP user yang login (bukan null)
- [ ] Generate berkali-kali (misal 5x untuk signer sama) → tetap 5 `uuid` beda, tidak collision
- [ ] Scan QR hasil download pakai HP → link mengarah ke `https://domain-kamu/verify/{uuid}` (halaman verify-nya baru jalan di Fase 3, jadi sementara 404 dulu — itu wajar, bukan bug)
- [ ] File PNG kebuka normal, bukan corrupt, tampil sebagai QR valid (bisa dites pakai QR scanner app apapun)
- [ ] Coba akses `/qr/generate` (POST) tanpa login → redirect ke `/login`, tidak bisa generate tanpa auth

## Output Fase 2
- Form generate QR jalan, dropdown ambil data signer aktif
- Setiap generate bikin record audit di `qr_generations` (uuid, signer, siapa generate, kapan, IP)
- File PNG download otomatis, tanpa nyimpen file fisik di server
- Siap lanjut Fase 3: halaman verifikasi publik (`/verify/{uuid}`)
