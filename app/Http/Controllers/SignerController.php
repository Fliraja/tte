<?php

namespace App\Http\Controllers;

use App\Models\Signer;
use Illuminate\Http\Request;

class SignerController extends Controller
{
    public function index()
    {
        $signers = Signer::latest()->paginate(15);
        return view('admin.signers.index', compact('signers'));
    }

    public function create()
    {
        return view('admin.signers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ]);
        Signer::create($validated);
        return redirect()->route('signers.index')->with('success', 'Penandatangan berhasil ditambahkan.');
    }

    public function edit(Signer $signer)
    {
        return view('admin.signers.edit', compact('signer'));
    }

    public function update(Request $request, Signer $signer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ]);
        $signer->update($validated);
        return redirect()->route('signers.index')->with('success', 'Data diperbarui.');
    }

    public function destroy(Signer $signer)
    {
        $signer->update(['is_active' => false]);
        return redirect()->route('signers.index')->with('success', 'Signer dinonaktifkan.');
    }

    public function toggle(Signer $signer)
    {
        $signer->update(['is_active' => ! $signer->is_active]);
        $status = $signer->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('signers.index')->with('success', "Signer {$status}.");
    }
}
