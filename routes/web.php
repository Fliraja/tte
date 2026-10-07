<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\QrGenerationController;
use App\Http\Controllers\SignerController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', [QrGenerationController::class, 'create'])->name('qr.create');
    Route::post('/qr/generate', [QrGenerationController::class, 'store'])->name('qr.generate');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::resource('users', UserManagementController::class)->except(['show']);
        Route::patch('/users/{user}/toggle', [UserManagementController::class, 'toggle'])->name('users.toggle');
        Route::resource('signers', SignerController::class)->except(['show']);
        Route::patch('/signers/{signer}/toggle', [SignerController::class, 'toggle'])->name('signers.toggle');
        Route::get('/logs', [AuditLogController::class, 'index'])->name('logs.index');
    });
});

Route::get('/verify/{uuid}', [VerificationController::class, 'show'])->name('verify.show');

require __DIR__.'/auth.php';
