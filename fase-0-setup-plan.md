# Fase 0 — Setup (Detail Implementation Plan)

Estimasi: 0.5 hari. Tujuan: base project siap, migration jalan, seeder jalan, sebelum masuk logic Fase 1.

## 1. Install Package

```bash
composer require endroid/qr-code
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build
```

Pilih `blade` stack (bukan react/vue) — sesuai spec, cukup Blade + Alpine.js.

## 2. Migration `users` (custom)

Breeze generate migration `users` default. Edit tambah kolom `nip` dan `role`, hapus/ubah `email` jadi nullable (login pakai NIP, bukan email).

```php
// database/migrations/xxxx_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('nip')->unique();
    $table->string('name');
    $table->string('email')->nullable()->unique(); // simpan optional, tidak dipakai login
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->enum('role', ['staff', 'admin'])->default('staff');
    $table->rememberToken();
    $table->timestamps();
});
```

## 3. Migration `signers`

```php
Schema::create('signers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('position');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

## 4. Migration `qr_generations`

```php
Schema::create('qr_generations', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('signer_id')->constrained('signers');
    $table->string('letter_number')->nullable();
    $table->foreignId('generated_by')->constrained('users'); // not nullable
    $table->string('ip_address')->nullable();
    $table->timestamps();
});
```

Jalankan:

```bash
php artisan migrate
```

## 5. Model + Relasi

```php
// app/Models/User.php — tambah $fillable, dan override username login
protected $fillable = ['nip', 'name', 'email', 'password', 'role'];

// app/Models/Signer.php
class Signer extends Model {
    protected $fillable = ['name', 'position', 'is_active'];
    public function generations() {
        return $this->hasMany(QrGeneration::class);
    }
}

// app/Models/QrGeneration.php
class QrGeneration extends Model {
    protected $fillable = ['uuid', 'signer_id', 'letter_number', 'generated_by', 'ip_address'];
    public function signer() {
        return $this->belongsTo(Signer::class);
    }
    public function generator() {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
```

## 6. Ubah Breeze Login Pakai NIP

Breeze default login pakai `email`. Ganti jadi `nip`:

- `resources/views/auth/login.blade.php`: ganti input `email` jadi `nip`.
- `app/Http/Requests/Auth/LoginRequest.php`: ganti `'email' => ...` jadi `'nip' => ...`, dan di method `authenticate()` ganti `Auth::attempt(['email' => ..., 'password' => ...])` jadi `Auth::attempt(['nip' => ..., 'password' => ...])`.

## 7. Seeder

**Seeder admin awal:**
```php
// database/seeders/UserSeeder.php
User::create([
    'nip' => '198501012010011001', // ganti sesuai NIP admin asli
    'name' => 'Admin TU',
    'password' => Hash::make('ganti-password-ini'),
    'role' => 'admin',
]);
```

**Seeder signer awal:**
```php
// database/seeders/SignerSeeder.php
Signer::insert([
    ['name' => 'dr. Budi Santoso', 'position' => 'Direktur Utama', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ['name' => 'dr. Siti Aminah', 'position' => 'Wakil Direktur', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
]);
```

Daftarkan di `DatabaseSeeder.php`, lalu jalankan:

```bash
php artisan db:seed
```

## 8. Checklist Selesai Fase 0

- [ ] Migration jalan tanpa error, 3 table baru ada (`users` custom, `signers`, `qr_generations`)
- [ ] Breeze login form pakai field NIP, bukan email
- [ ] Login pakai akun admin seeder berhasil
- [ ] Seeder signer keisi minimal 2 data
- [ ] `endroid/qr-code` kebaca di `composer.json`, tidak ada error saat `composer require`

## Yang Perlu dari Kamu Sebelum Jalan
- Daftar NIP + nama staf TU yang perlu akun awal (minimal 1 admin untuk testing).
- Daftar nama + jabatan signer real (atau pakai dummy dulu, ganti nanti pas Fase 4 admin CRUD jalan).
