# Implementation Plan — QR Tanda Tangan Elektronik (Final, Post Open Questions)

## Keputusan dari Open Questions

1. **Auth wajib, pakai NIP Pegawai.** Login jadi bagian MVP (bukan opsional/P1 lagi). Fase 4 (auth) naik jadi Fase 1, karena semua route generate & admin butuh middleware auth dari awal. Field `generated_by` di `qr_generations` diisi NIP user login (bukan nullable lagi).
2. **Nomor surat: opsional.** Tetap P1, field `letter_number` nullable, tidak wajib diisi di form generate.
3. **Legal: aman.** Tujuan hanya kurangi kertas untuk manajemen internal RS, bukan dokumen kekuatan hukum pengadilan. Non-Goals & catatan PSrE di spec tetap berlaku, tidak perlu integrasi PSrE di v1.

## Perubahan Skema

**Table: `users`** (Laravel default + kustom)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| nip | string, unique | Login pakai NIP, bukan email |
| name | string | |
| password | string | hashed |
| role | enum('staff','admin') | staff = akses generate, admin = akses CRUD signer + log |
| timestamps | | |

**Table: `qr_generations`** (update)
| Kolom | Tipe | Keterangan |
|---|---|---|
| ... | | field lain sama seperti spec awal |
| generated_by | FK ke users.id, **not nullable** | otomatis dari `auth()->id()` saat generate, bukan input manual |

`signers` table tidak berubah dari spec awal.

## Routes (update)

```
-- Auth (Breeze, custom login pakai NIP) --
GET  /login                 -> form login (field: NIP + password)
POST /login
POST /logout

-- Semua route di bawah wajib middleware auth --
GET  /                      -> form pilih signer (staff & admin)
POST /qr/generate           -> proses generate + download PNG, generated_by = auth user
GET  /verify/{uuid}         -> PUBLIK, tanpa auth (biar penerima surat luar bisa scan & cek)

-- Admin only (middleware auth + role:admin) --
GET    /admin/signers
POST   /admin/signers
PUT    /admin/signers/{id}
DELETE /admin/signers/{id}
GET    /admin/logs          -> filter tanggal/nama/NIP staf
GET    /admin/users         -> kelola akun staf (tambah NIP baru, reset password)
```

Catatan: `/verify/{uuid}` tetap publik tanpa login — itu tujuan awal QR, supaya siapapun penerima surat (internal/eksternal RS) bisa scan tanpa akun.

## Fase (revisi urutan & estimasi)

**Fase 0 — Setup (0.5 hari)**
- `composer require endroid/qr-code`
- `composer require laravel/breeze` (auth scaffolding)
- Migration: `users` (custom, tambah kolom `nip`, `role`), `signers`, `qr_generations`
- Seeder: 1 akun admin awal (NIP + password default), data signer awal

**Fase 1 — Auth & role (1 hari)** *(naik prioritas, dulunya Fase 4)*
- Setup Breeze, ubah login form: field `NIP` menggantikan `email`
- Middleware `role:admin` untuk route admin
- Seeder / form admin untuk tambah akun staf baru (NIP + nama + password)

**Fase 2 — Core generate & download (1 hari)**
- Route + controller `QrGenerationController`, wajib login (`auth` middleware)
- View pilih signer (Blade + dropdown, ambil `signers` where `is_active=true`)
- Input opsional nomor surat di form
- Logic: generate UUID, simpan `qr_generations` dengan `generated_by = auth()->id()`, `ip_address = $request->ip()`, stream PNG
- Test: generate berkali-kali, pastikan uuid unik, file valid QR

**Fase 3 — Halaman verifikasi (0.5 hari)**
- Route `/verify/{uuid}`, publik (tidak pakai middleware auth)
- Tampilkan nama, jabatan, waktu generate, nomor surat (kalau ada)
- Pesan "kode tidak ditemukan" kalau uuid invalid

**Fase 4 — Admin CRUD signer & log audit (1 hari)**
- Resource controller `signers` (list, tambah, edit, nonaktifkan)
- Halaman `/admin/logs`: tabel riwayat generate, filter tanggal/nama signer/NIP staf yang generate
- Halaman `/admin/users` sederhana: tambah/nonaktifkan akun staf

**Total estimasi MVP: 3.5–4 hari kerja** (naik dari estimasi awal karena auth masuk wajib, bukan opsional).

## Yang Perlu Disiapkan Sebelum Mulai
- Daftar NIP awal staf TU yang berhak akses (untuk seeder akun).
- Konfirmasi: 1 role admin cukup, atau perlu granular (misal admin RS vs admin TU)? Kalau cukup 2 role (staff/admin) seperti di atas, bisa langsung jalan Fase 0.

## Tidak Berubah dari Spec Awal
- PNG sebagai format output (alasan lossless, tidak ada artefak blur di modul QR).
- Struktur `signers` table.
- Non-Goals: bukan PSrE resmi, bukan auto-embed ke PDF surat, bukan fitur revoke QR (tetap masuk Future Considerations).
