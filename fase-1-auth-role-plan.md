# Fase 1 — Auth & Role (Detail Implementation Plan)

Estimasi: 1 hari. Prasyarat: Fase 0 selesai (migration jalan, seeder admin dummy ada, login Breeze udah diubah ke NIP).

## 1. Middleware Role

Buat middleware baru buat cek role admin.

```bash
php artisan make:middleware EnsureUserIsAdmin
```

```php
// app/Http/Middleware/EnsureUserIsAdmin.php
public function handle(Request $request, Closure $next)
{
    if (auth()->user()?->role !== 'admin') {
        abort(403, 'Akses ditolak. Halaman ini khusus admin.');
    }
    return $next($request);
}
```

Daftarkan alias di `bootstrap/app.php` (Laravel 11):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
    ]);
})
```

## 2. Proteksi Route

```php
// routes/web.php

Route::middleware('auth')->group(function () {
    Route::get('/', [QrGenerationController::class, 'create']);
    Route::post('/qr/generate', [QrGenerationController::class, 'store']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::resource('signers', SignerController::class)->except(['show']);
        Route::get('/logs', [AuditLogController::class, 'index']);
        Route::resource('users', UserManagementController::class)->except(['show']);
    });
});

// Publik, tanpa auth
Route::get('/verify/{uuid}', [VerificationController::class, 'show']);
```

## 3. Halaman Kelola Akun Staf (Admin Only)

Controller sederhana buat admin tambah/nonaktifkan akun staf.

```bash
php artisan make:controller UserManagementController --resource
```

```php
// app/Http/Controllers/UserManagementController.php
class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::latest()->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'required|string|unique:users,nip',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'role' => 'required|in:staff,admin',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);
        return redirect()->route('users.index')->with('success', 'Akun staf berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:staff,admin',
        ]);
        $user->update($validated);
        return redirect()->route('users.index')->with('success', 'Data akun diperbarui.');
    }

    public function destroy(User $user)
    {
        // nonaktifkan, bukan hapus permanen (jaga integritas FK di qr_generations)
        $user->update(['is_active' => false]);
        return redirect()->route('users.index')->with('success', 'Akun dinonaktifkan.');
    }
}
```

Catatan: kalau mau nonaktifkan (bukan hard delete), tambah kolom `is_active` di migration `users` Fase 0 — kalau belum ada, tambah migration baru:

```bash
php artisan make:migration add_is_active_to_users_table
```

```php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('is_active')->default(true)->after('role');
});
```

Lalu tambahkan cek `is_active` di `LoginRequest::authenticate()` — kalau `false`, gagal login walau NIP+password benar.

## 4. View (Blade, minimal styling dulu)

```
resources/views/admin/users/index.blade.php   -> tabel daftar staf + tombol nonaktifkan
resources/views/admin/users/create.blade.php  -> form tambah staf (NIP, nama, password, role)
resources/views/admin/users/edit.blade.php    -> form edit nama & role
```

Form tambah minimal:

```blade
<form method="POST" action="{{ route('users.store') }}">
    @csrf
    <input type="text" name="nip" placeholder="NIP" required>
    <input type="text" name="name" placeholder="Nama" required>
    <input type="password" name="password" placeholder="Password" required>
    <select name="role">
        <option value="staff">Staff</option>
        <option value="admin">Admin</option>
    </select>
    <button type="submit">Tambah</button>
</form>
```

## 5. Update Login Blade — Tampilkan Pesan Kalau Akun Nonaktif

```php
// app/Http/Requests/Auth/LoginRequest.php
public function authenticate(): void
{
    $this->ensureIsNotRateLimited();

    if (! Auth::attempt($this->only('nip', 'password'), $this->boolean('remember'))) {
        RateLimiter::hit($this->throttleKey());
        throw ValidationException::withMessages([
            'nip' => trans('auth.failed'),
        ]);
    }

    if (! auth()->user()->is_active) {
        Auth::logout();
        throw ValidationException::withMessages([
            'nip' => 'Akun ini sudah dinonaktifkan.',
        ]);
    }

    RateLimiter::clear($this->throttleKey());
}
```

## 6. Seeder Tambahan (Dummy Staf)

Sesuai arahan kamu, semua data dummy. Tambah beberapa akun staf dummy selain admin:

```php
// database/seeders/UserSeeder.php
User::insert([
    ['nip' => '198501012010011001', 'name' => 'Admin TU', 'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ['nip' => '199002022015022002', 'name' => 'Staf TU 1', 'password' => Hash::make('password'), 'role' => 'staff', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ['nip' => '199303032018033003', 'name' => 'Staf TU 2', 'password' => Hash::make('password'), 'role' => 'staff', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
]);
```

## 7. Test Manual (Checklist)

- [ ] Login pakai NIP admin dummy berhasil, masuk ke `/`
- [ ] Login pakai NIP staff dummy berhasil, tapi akses `/admin/users` kena 403
- [ ] Login admin bisa akses `/admin/users`, lihat list, tambah staf baru
- [ ] Nonaktifkan salah satu akun staf, coba login pakai akun itu → gagal dengan pesan "Akun ini sudah dinonaktifkan"
- [ ] NIP salah / password salah → pesan error standar Breeze, tidak bocorkan NIP mana yang valid
- [ ] Akses `/` tanpa login → redirect ke `/login`
- [ ] Akses `/admin/signers` (nanti Fase 4) tanpa login / sebagai staff → 403 atau redirect login

## Output Fase 1
- Login sistem pakai NIP, role staff/admin jalan
- Middleware `admin` siap dipakai di route Fase 4 (CRUD signer, logs)
- CRUD akun staf (dummy) siap dipakai admin
- Siap lanjut Fase 2: core generate & download QR
