<?php

namespace App\Http\Controllers;

use App\Models\LetterNumberBlock;
use App\Models\QrGeneration;
use App\Models\Signer;
use App\Models\User;
use App\Services\NumberBlockService;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index(NumberBlockService $blockService)
    {
        $tz = config('app.timezone', 'Asia/Makassar');
        $currentYear = (int) Carbon::now($tz)->format('Y');

        $activeBlock = $blockService->activeBlock($currentYear);
        $hasAnyBlockThisYear = LetterNumberBlock::where('year', $currentYear)->exists();

        $lastIssued = QrGeneration::where('number_year', $currentYear)
            ->whereNotNull('sequence_number')
            ->orderByDesc('sequence_number')
            ->first();

        $stats = [
            'active_signers' => Signer::where('is_active', true)->count(),
            'inactive_signers' => Signer::where('is_active', false)->count(),
            'active_staff' => User::where('is_active', true)->count(),
            'inactive_staff' => User::where('is_active', false)->count(),
            'qr_last_30_days' => QrGeneration::where('created_at', '>=', now()->subDays(30))->count(),
            'qr_total' => QrGeneration::count(),
            'current_year' => $currentYear,
            'last_issued_seq' => $lastIssued?->sequence_number,
            'last_issued_number' => $lastIssued?->letter_number,
            'active_block' => $activeBlock,
            'has_any_block_this_year' => $hasAnyBlockThisYear,
        ];

        $recentLogs = QrGeneration::with('signer', 'generator')->latest()->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentLogs'));
    }
}
