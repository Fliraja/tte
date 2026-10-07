# Spec & Implementation Plan — QR Generator Tanda Tangan Elektronik (Internal Hospital)

## 1. Ringkasan

Website internal berbasis Laravel untuk generate QR code yang mewakili tanda tangan elektronik penanggung jawab surat internal rumah sakit. User memilih nama penandatangan dari dropdown, sistem generate QR code, lalu file gambar (PNG) langsung terdownload.

## 2. Catatan Penting Sebelum Mulai (Baca Dulu)

Ini bagian paling krusial karena menyangkut "tanda tangan elektronik" yang dipakai institusi resmi (rumah sakit):

**Risiko keamanan desain naif**: Kalau QR hanya encode nama/teks statis per orang (misal QR untuk "dr. Budi" selalu sama persis), maka QR itu bisa di-screenshot dan ditempel ulang di surat mana pun oleh siapa pun. Itu bukan tanda tangan, itu stempel yang gampang dipalsukan. Untuk kebutuhan internal rumah sakit, hal ini sebaiknya dihindari.

**Rekomendasi**: QR tidak boleh berisi nama statis saja. QR sebaiknya berisi URL unik (`https://domain/verify/{kode-unik}`) yang di-generate setiap kali tombol "Generate" ditekan. Kode unik ini disimpan di database beserta: nama penandatangan, jabatan, waktu generate, dan (kalau ada) nomor surat. Siapa pun yang scan QR akan diarahkan ke halaman verifikasi yang menampilkan info tersebut — sehingga QR bisa dicek keasliannya, bukan sekadar gambar generik.

**Soal legalitas**: Di Indonesia, tanda tangan elektronik yang punya kekuatan hukum penuh (UU ITE) umumnya perlu tersertifikasi lewat Penyelenggara Sertifikasi Elektronik (PSrE) resmi seperti Peruri/BSSN/PrivyID/dsb — bukan sekadar QR buatan sendiri. Kalau tujuannya hanya *stempel digital praktis untuk surat internal* (bukan dokumen yang butuh kekuatan hukum di pengadilan), pendekatan QR + halaman verifikasi di spec ini cukup memadai. Kalau ke depannya dibutuhkan kekuatan hukum formal, sebaiknya dikonsultasikan ke bagian legal rumah sakit dan pertimbangkan integrasi PSrE resmi. Poin ini saya taruh di Open Questions di bawah.

## 3. Goals (v1)

1. Admin bisa kelola daftar penandatangan (nama, jabatan) lewat panel sederhana.
2. User memilih penandatangan dari dropdown, generate QR unik, dan langsung download PNG.
3. QR yang di-generate bisa diverifikasi keasliannya lewat halaman publik (scan QR → tampil info penandatangan & waktu generate).
4. Semua histori generate QR tercatat (audit trail) — penting untuk institusi seperti rumah sakit.

## 4. Non-Goals (v1)

- **Bukan** e-signature bersertifikat resmi (PSrE) — hanya stempel digital terverifikasi untuk keperluan internal.
- **Bukan** integrasi otomatis ke sistem manajemen surat/dokumen (PDF surat tetap ditempel QR manual dulu untuk v1).
- **Bukan** sistem login/autentikasi individual untuk tiap penandatangan generate QR sendiri (v1: cukup dikelola admin/staff TU yang punya akses).
- **Bukan** fitur revoke/cabut QR yang sudah digenerate (dipertimbangkan di v2 kalau dibutuhkan).

## 5. User Stories

- Sebagai staf TU, saya ingin memilih nama penandatangan dari dropdown supaya proses generate QR cepat dan tidak salah ketik nama.
- Sebagai staf TU, saya ingin QR langsung terdownload dalam format gambar supaya bisa langsung ditempel ke file surat (Word/PDF).
- Sebagai penerima surat, saya ingin bisa scan QR dan melihat halaman yang mengonfirmasi surat itu memang ditandatangani oleh orang yang tertera, supaya saya percaya keasliannya.
- Sebagai admin, saya ingin melihat daftar riwayat QR yang pernah digenerate (siapa, kapan) supaya ada jejak audit.
- Sebagai admin, saya ingin menambah/mengedit/menonaktifkan data penandatangan tanpa perlu ubah kode.

## 6. Requirements

### Must-Have (P0)
- [ ] CRUD data penandatangan (`signers`): nama, jabatan, status aktif.
- [ ] Halaman generate: dropdown pilih penandatangan → tombol Generate → download PNG otomatis.
- [ ] Setiap generate membuat record unik di DB (kode UUID, signer_id, timestamp).
- [ ] QR encode URL verifikasi (bukan teks nama statis).
- [ ] Halaman verifikasi publik `/verify/{kode}` menampilkan nama, jabatan, dan waktu generate.
- [ ] Format output PNG (alasan di bagian teknis).

### Nice-to-Have (P1)
- [ ] Input opsional nomor surat saat generate, ikut tersimpan & tampil di halaman verifikasi.
- [ ] Halaman admin daftar riwayat generate (log audit) dengan filter tanggal/nama.
- [ ] Autentikasi login sederhana (Laravel Breeze) supaya hanya staf berwenang yang bisa akses halaman generate & admin.

### Future Considerations (P2)
- [ ] Auto-embed QR ke template surat (generate PDF langsung berisi QR).
- [ ] Fitur revoke / expiry QR.
- [ ] Integrasi ke sistem PSrE resmi untuk surat yang butuh kekuatan hukum formal.
- [ ] Role-based access (tiap dokter/pejabat generate QR miliknya sendiri lewat login masing-masing).

## 7. Desain Teknis

### 7.1 Kenapa PNG, bukan JPEG
QR code adalah pola hitam-putih tegas (bitonal). JPEG pakai kompresi lossy yang bisa memunculkan artefak/blur di tepi modul QR, terutama saat ukuran kecil — ini bisa bikin QR gagal discan. PNG lossless, ukuran file QR kecil (biasanya <20KB), jadi tidak ada alasan pakai JPEG di sini. **Keputusan: PNG.**

### 7.2 Stack
- Laravel 11.x (sesuai environment Laragon yang sudah dipakai)
- Package QR: `endroid/qr-code` (aktif maintained, langsung support Laravel, output PNG native tanpa dependency `imagick` wajib — pakai driver GD yang biasanya sudah ada di XAMPP/Laragon). Alternatif: `simplesoftwareio/simple-qrcode` (lebih populer tapi update-nya lebih jarang).
- Tidak perlu JS framework berat — Blade + sedikit Alpine.js cukup (sesuai yang sedang dipelajari di project HRIS).

### 7.3 Skema Database

**Table: `signers`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | string | Nama penandatangan |
| position | string | Jabatan (mis. "Direktur Utama") |
| is_active | boolean, default true | Nonaktif = tidak muncul di dropdown |
| timestamps | | |

**Table: `qr_generations`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| uuid | string, unique, indexed | Kode unik, dipakai di URL verifikasi |
| signer_id | FK ke signers | |
| letter_number | string, nullable | Nomor surat (opsional, P1) |
| generated_by | string, nullable | Nama/username staf yang generate (kalau sudah ada auth) |
| ip_address | string, nullable | Untuk audit |
| timestamps | | `created_at` = waktu generate |

### 7.4 Routes

```
GET  /                      -> form pilih signer (halaman generate)
POST /qr/generate           -> proses generate + trigger download PNG
GET  /verify/{uuid}         -> halaman publik verifikasi QR

-- Admin (P1, sebaiknya di-wrap middleware auth) --
GET    /admin/signers            -> list signer
POST   /admin/signers            -> tambah signer
PUT    /admin/signers/{id}       -> edit signer
DELETE /admin/signers/{id}       -> nonaktifkan signer
GET    /admin/logs               -> riwayat generate
```

### 7.5 Alur Generate (flow inti)

1. User buka `/`, pilih nama dari dropdown (data dari `signers` where `is_active=true`).
2. Submit form ke `POST /qr/generate`.
3. Server generate `uuid` baru, simpan row baru di `qr_generations`.
4. Server generate QR image yang encode `https://domain-kamu/verify/{uuid}`.
5. Response langsung stream file PNG dengan header `Content-Disposition: attachment` (download otomatis, tanpa perlu simpan file fisik di server — generate on-the-fly lebih efisien storage).
6. User buka file PNG, tempel ke surat.
7. Penerima surat scan QR → diarahkan ke `/verify/{uuid}` → lihat nama, jabatan, waktu generate → yakin surat sah.

### 7.6 Contoh generate QR (endroid/qr-code)

```php
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$uuid = Str::uuid();

QrGeneration::create([
    'uuid' => $uuid,
    'signer_id' => $request->signer_id,
]);

$qrCode = new QrCode(url('/verify/' . $uuid));
$writer = new PngWriter();
$result = $writer->write($qrCode);

return response($result->getString(), 200, [
    'Content-Type' => 'image/png',
    'Content-Disposition' => 'attachment; filename="qr-ttd-' . $uuid . '.png"',
]);
```

## 8. Implementation Plan (Fase)

**Fase 0 — Setup (0.5 hari)**
- `composer require endroid/qr-code`
- Migration `signers` & `qr_generations`
- Seeder data signer awal

**Fase 1 — Core generate & download (1 hari)**
- Route + controller `QrGenerationController`
- View pilih signer (Blade + dropdown)
- Logic generate UUID + simpan DB + stream PNG
- Test: generate berkali-kali, pastikan uuid selalu unik & file kebaca sebagai QR valid

**Fase 2 — Halaman verifikasi (0.5 hari)**
- Route `/verify/{uuid}`
- Tampilkan data signer + waktu generate, atau pesan "kode tidak ditemukan" kalau uuid invalid
- Ini bagian yang bikin QR ini beneran berguna sebagai verifikasi, jangan diskip

**Fase 3 — Admin CRUD signer (0.5–1 hari)**
- Resource controller sederhana untuk `signers`
- List, tambah, edit, nonaktifkan

**Fase 4 — Auth & audit log (opsional tapi disarankan, 1 hari)**
- Laravel Breeze untuk login staf TU
- Middleware auth di route generate & admin
- Halaman log riwayat generate

**Total estimasi MVP (Fase 0–3): 2.5–3 hari kerja. Dengan Fase 4: +1 hari.**

## 9. Open Questions

- **[Blocking — perlu keputusan sebelum Fase 1]** Apakah halaman generate boleh diakses siapa saja (tanpa login), atau harus dibatasi staf TU tertentu? Ini menentukan apakah Fase 4 (auth) masuk MVP atau boleh nyusul.
- **[Non-blocking]** Apakah nomor surat wajib diisi tiap generate, atau opsional (P1)?
- **[Non-blocking, untuk pihak legal RS]** Apakah QR + halaman verifikasi ini cukup untuk kebutuhan internal, atau ke depan perlu kekuatan hukum formal (PSrE)? Sebaiknya dikonfirmasi ke bagian legal sebelum dipakai untuk surat-surat yang sifatnya kritis.

## 10. Yang Harus Dihindari

- Jangan bikin QR statis per nama yang bisa dipakai berulang tanpa batas — itu yang bikin desain ini gampang dipalsukan.
- Jangan skip halaman verifikasi (Fase 2) meski kelihatan "opsional" — tanpa itu, QR cuma dekorasi, bukan verifikasi.
