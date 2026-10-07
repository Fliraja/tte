<?php

namespace App\Http\Controllers;

use App\Models\QrGeneration;
use App\Models\Signer;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'active_signers' => Signer::where('is_active', true)->count(),
            'inactive_signers' => Signer::where('is_active', false)->count(),
            'active_staff' => User::where('is_active', true)->count(),
            'inactive_staff' => User::where('is_active', false)->count(),
            'qr_last_30_days' => QrGeneration::where('created_at', '>=', now()->subDays(30))->count(),
            'qr_total' => QrGeneration::count(),
        ];

        $recentLogs = QrGeneration::with('signer', 'generator')->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentLogs'));
    }
}
