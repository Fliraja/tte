<?php

namespace App\Http\Controllers;

use App\Models\LetterNumberBlock;
use App\Services\NumberBlockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NumberBlockController extends Controller
{
    public function index(Request $request, NumberBlockService $blockService)
    {
        $tz = config('app.timezone', 'Asia/Makassar');
        $currentYear = (int) Carbon::now($tz)->format('Y');

        $year = (int) $request->input('year', $currentYear);

        $availableYears = LetterNumberBlock::distinct()
            ->pluck('year')
            ->push($currentYear)
            ->unique()
            ->sortDesc()
            ->values();

        $blocks = LetterNumberBlock::with('creator')
            ->where('year', $year)
            ->orderBy('start_number', 'asc')
            ->get();

        $activeBlock = $blockService->activeBlock($year);

        return view('admin.blocks.index', compact('blocks', 'year', 'availableYears', 'currentYear', 'activeBlock'));
    }

    public function create(Request $request)
    {
        $tz = config('app.timezone', 'Asia/Makassar');
        $defaultYear = (int) Carbon::now($tz)->format('Y');
        $year = (int) $request->input('year', $defaultYear);

        return view('admin.blocks.create', compact('year'));
    }

    public function store(Request $request, NumberBlockService $blockService)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'start_number' => ['required', 'integer', 'min:1'],
            'end_number' => ['nullable', 'integer', 'min:1'],
        ], [
            'year.required' => 'Tahun wajib diisi.',
            'start_number.required' => 'Nomor awal wajib diisi.',
            'start_number.min' => 'Nomor awal minimal 1.',
        ]);

        try {
            $blockService->createBlock(
                $validated['year'],
                $validated['start_number'],
                $validated['end_number'] ?? null,
                $request->user()->id
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('blocks.index', ['year' => $validated['year']])
            ->with('success', "Blok nomor tahun {$validated['year']} berhasil ditambahkan.");
    }
}

