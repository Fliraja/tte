# Fase 3 — Halaman Verifikasi Publik (Detail Implementation Plan)

Estimasi: 0.5 hari. Prasyarat: Fase 2 selesai (generate QR jalan, `qr_generations` keisi).

## 1. Controller

```bash
php artisan make:controller VerificationController
```

```php
// app/Http/Controllers/VerificationController.php
use App\Models\QrGeneration;

class VerificationController extends Controller
{
    public function show(string $uuid)
    {
        $generation = QrGeneration::with('signer', 'generator')
            ->where('uuid', $uuid)
            ->first();

        if (! $generation) {
            return view('verify.not-found');
        }

        return view('verify.show', compact('generation'));
    }
}
```

## 2. Route (Publik, Tanpa Auth)

```php
// routes/web.php, di luar group middleware('auth')
Route::get('/verify/{uuid}', [VerificationController::class, 'show'])->name('verify.show');
```

Pastikan route ini benar-benar di luar group `auth` — kalau kebawa masuk group, penerima surat luar RS gak bisa scan tanpa akun, itu bikin fitur verifikasi gagal fungsi.

## 3. View — Halaman Verifikasi Valid

```blade
{{-- resources/views/verify/show.blade.php --}}
<x-guest-layout>
    <div class="max-w-md mx-auto py-12 text-center">
        <div class="text-green-600 text-3xl mb-2">✓</div>
        <h1 class="text-lg font-semibold">Dokumen Terverifikasi</h1>

        <div class="mt-6 text-left border rounded p-4">
            <p><strong>Nama:</strong> {{ $generation->signer->name }}</p>
            <p><strong>Jabatan:</strong> {{ $generation->signer->position }}</p>
            @if ($generation->letter_number)
                <p><strong>Nomor Surat:</strong> {{ $generation->letter_number }}</p>
            @endif
            <p><strong>Waktu Generate:</strong> {{ $generation->created_at->translatedFormat('d F Y, H:i') }} WIB</p>
        </div>

        <p class="mt-6 text-sm text-gray-500">
            QR ini digenerate lewat sistem internal Rumah Sakit dan tercatat di sistem audit.
        </p>
    </div>
</x-guest-layout>
```

## 4. View — Kode Tidak Ditemukan

```blade
{{-- resources/views/verify/not-found.blade.php --}}
<x-guest-layout>
    <div class="max-w-md mx-auto py-12 text-center">
        <div class="text-red-600 text-3xl mb-2">✕</div>
        <h1 class="text-lg font-semibold">Kode Tidak Ditemukan</h1>
        <p class="mt-4 text-gray-600">
            QR ini tidak terdaftar di sistem. Kemungkinan kode tidak valid atau sudah tidak berlaku.
        </p>
    </div>
</x-guest-layout>
```

## 5. Pertimbangan Keamanan Halaman Publik

- Jangan tampilkan `generated_by` (nama/NIP staf yang generate) di halaman publik — itu data internal, tidak perlu diketahui penerima surat luar. Cukup nama signer, jabatan, waktu, nomor surat.
- `ip_address` juga tidak ditampilkan di halaman verify — itu buat audit internal admin, bukan konsumsi publik.
- Route ini rawan di-enumerate kalau UUID gampang ditebak — tapi karena pakai UUID v4 (128-bit random), praktis tidak bisa di-brute-force. Tidak perlu proteksi tambahan (misal rate limit) untuk v1, tapi bisa dipertimbangkan kalau nanti banyak trafik aneh masuk log.

## 6. Test Manual (Checklist)

- [ ] Generate QR baru di Fase 2, scan pakai HP → langsung terbuka halaman verifikasi (bukan 404 lagi)
- [ ] Halaman verifikasi tampilkan nama, jabatan, waktu generate dengan benar
- [ ] Kalau nomor surat diisi saat generate → muncul di halaman verify; kalau tidak diisi → baris nomor surat tidak muncul (bukan tampil kosong/null)
- [ ] Akses `/verify/{uuid-asal-ketik}` (uuid ngasal, tidak ada di DB) → tampil halaman "Kode Tidak Ditemukan", bukan error 500
- [ ] Akses `/verify/{uuid}` tanpa login sama sekali (logout dulu / browser mode incognito) → tetap bisa dibuka, tidak diarahkan ke `/login`
- [ ] Cek halaman verify tidak menampilkan `generated_by` atau `ip_address` (data internal tidak bocor ke publik)
- [ ] Tampilan mobile-friendly, karena mayoritas akses dari scan HP (bukan desktop)

## Output Fase 3
- QR yang digenerate sekarang benar-benar berfungsi sebagai verifikasi, bukan cuma gambar dekoratif
- Halaman publik, bisa diakses siapa saja (internal/eksternal RS) tanpa login
- Data sensitif (siapa staf yang generate, IP) tetap tersembunyi dari publik, hanya bisa dilihat admin lewat log (Fase 4)
- MVP inti (Fase 0–3) sudah lengkap: generate → simpan → verifikasi. Tinggal Fase 4: admin CRUD signer + log audit
