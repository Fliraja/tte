<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\NumberBlockController;
use App\Http\Controllers\QrDocumentController;
use App\Http\Controllers\QrGenerationController;
use App\Http\Controllers\QrLinkController;
use App\Http\Controllers\QrLinkRedirectController;
use App\Http\Controllers\SignerController;
use App\Http\Controllers\TanpaNomorController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', [QrGenerationController::class, 'create'])->name('qr.create');
    Route::post('/qr/generate', [QrGenerationController::class, 'store'])->name('qr.generate');
    Route::get('/qr/history', [QrGenerationController::class, 'history'])->name('qr.history');
    Route::get('/qr/{uuid}', [QrGenerationController::class, 'show'])->name('qr.show');
    Route::get('/qr/{uuid}/png', [QrGenerationController::class, 'png'])->name('qr.png');
    Route::post('/qr/{uuid}/void', [QrGenerationController::class, 'void'])->name('qr.void');

    // Dokumen Final
    Route::post('/qr/{uuid}/document', [QrDocumentController::class, 'upload'])->name('qr.document.upload');
    Route::post('/qr/{uuid}/document/replace', [QrDocumentController::class, 'replace'])->name('qr.document.replace');
    Route::get('/qr/{uuid}/document', [QrDocumentController::class, 'show'])->name('qr.document.show');

    Route::get('/tanpa-nomor', [TanpaNomorController::class, 'create'])->name('tanpa-nomor.create');
    Route::post('/tanpa-nomor', [TanpaNomorController::class, 'store'])->name('tanpa-nomor.store');

    // QR Link (404 selama TTE_QR_LINK_ENABLED=false). 'create' harus sebelum '{link}'.
    Route::middleware('qr-link.enabled')->prefix('qr-link')->name('qr-link.')->group(function () {
        Route::get('/', [QrLinkController::class, 'index'])->name('index');
        Route::get('/create', [QrLinkController::class, 'create'])->name('create');
        Route::post('/', [QrLinkController::class, 'store'])->name('store');
        Route::get('/{link}', [QrLinkController::class, 'show'])->name('show');
        Route::get('/{link}/png', [QrLinkController::class, 'png'])->name('png');
        Route::get('/{link}/edit', [QrLinkController::class, 'edit'])->name('edit');
        Route::patch('/{link}', [QrLinkController::class, 'update'])->name('update');
        Route::patch('/{link}/toggle', [QrLinkController::class, 'toggle'])->name('toggle');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::resource('blocks', NumberBlockController::class)->only(['index', 'create', 'store']);
        Route::resource('users', UserManagementController::class)->except(['show']);
        Route::patch('/users/{user}/toggle', [UserManagementController::class, 'toggle'])->name('users.toggle');
        Route::resource('signers', SignerController::class)->except(['show']);
        Route::patch('/signers/{signer}/toggle', [SignerController::class, 'toggle'])->name('signers.toggle');
        Route::get('/logs', [AuditLogController::class, 'index'])->name('logs.index');
    });
});

Route::get('/verify/{uuid}', [VerificationController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('verify.show');

Route::get('/verify/{uuid}/document', [QrDocumentController::class, 'publicStream'])
    ->middleware('throttle:60,1')
    ->name('verify.document');

// QR Link publik: redirect 302 ke tujuan (tanpa login, 404 bila flag mati).
Route::get('/l/{code}', [QrLinkRedirectController::class, 'go'])
    ->where('code', '[a-z2-9]{10}')
    ->middleware(['qr-link.enabled', 'throttle:60,1'])
    ->name('qr-link.go');

require __DIR__.'/auth.php';
