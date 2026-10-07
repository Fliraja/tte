<?php

namespace App\Http\Controllers;

use App\Models\QrGeneration;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = QrGeneration::with('signer', 'generator')->latest();

        if ($request->filled('signer_name')) {
            $query->whereHas('signer', fn ($q) =>
                $q->where('name', 'like', '%' . $request->signer_name . '%'));
        }

        if ($request->filled('nip')) {
            $query->whereHas('generator', fn ($q) =>
                $q->where('nip', 'like', '%' . $request->nip . '%'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.logs.index', compact('logs'));
    }
}
