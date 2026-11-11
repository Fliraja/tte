<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Riwayat Penerbitan Surat & QR') }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    @if (Auth::user()->role === 'admin')
                        Daftar seluruh nomor surat &amp; QR tanda tangan yang terdaftar di sistem
                    @else
                        Daftar surat &amp; QR tanda tangan yang Anda terbitkan
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('qr.create') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md uppercase tracking-widest shadow-sm">
                    + Surat Bernomor
                </a>
                <a href="{{ route('tanpa-nomor.create') }}" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold rounded-md uppercase tracking-widest shadow-sm">
                    + TTE Tanpa Nomor
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filter & Search Bar (Task 8.9) --}}
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <form method="GET" action="{{ route('qr.history') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    {{-- Keyword Search --}}
                    <div class="lg:col-span-2">
                        <label for="search" class="block text-xs font-medium text-gray-700">Cari Nomor, Perihal, atau Referensi</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..."
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    {{-- Jenis Surat --}}
                    <div>
                        <label for="letter_type_id" class="block text-xs font-medium text-gray-700">Jenis Surat</label>
                        <select name="letter_type_id" id="letter_type_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Jenis</option>
                            @foreach ($types as $t)
                                <option value="{{ $t->id }}" @selected(request('letter_type_id') == $t->id)>
                                    {{ $t->code ? $t->code . ' — ' . $t->name : $t->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Penandatangan --}}
                    <div>
                        <label for="signer_id" class="block text-xs font-medium text-gray-700">Penandatangan</label>
                        <select name="signer_id" id="signer_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Signer</option>
                            @foreach ($signers as $s)
                                <option value="{{ $s->id }}" @selected(request('signer_id') == $s->id)>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tombol Filter --}}
                    <div class="flex items-end gap-2">
                        <button type="submit" class="w-full py-2 px-3 bg-gray-800 text-white text-xs font-semibold rounded-md hover:bg-gray-700 uppercase tracking-wider">
                            Filter
                        </button>
                        @if (request()->hasAny(['search', 'letter_type_id', 'signer_id', 'start_date', 'end_date']))
                            <a href="{{ route('qr.history') }}" class="py-2 px-3 border border-gray-300 text-gray-600 text-xs font-semibold rounded-md hover:bg-gray-50">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table Riwayat --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6 text-gray-900">
                    @if ($generations->isEmpty())
                        <div class="py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak ada data surat</h3>
                            <p class="mt-1 text-sm text-gray-500">Belum ada penerbitan surat yang cocok dengan filter pencarian.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                                        <th class="px-5 py-3 text-left font-semibold">Nomor Surat</th>
                                        <th class="px-5 py-3 text-left font-semibold">Jenis / Perihal</th>
                                        <th class="px-5 py-3 text-left font-semibold">Penandatangan</th>
                                        <th class="px-5 py-3 text-left font-semibold">Waktu Terbit</th>
                                        @if (Auth::user()->role === 'admin')
                                            <th class="px-5 py-3 text-left font-semibold">Pembuat</th>
                                        @endif
                                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                                        <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($generations as $item)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="px-5 py-4">
                                                @if ($item->letter_number)
                                                    <span class="font-mono font-medium text-gray-900 block">{{ $item->letter_number }}</span>
                                                    @if ($item->number_source === 'manual')
                                                        <span class="text-[10px] uppercase font-semibold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded">Manual</span>
                                                    @elseif ($item->number_source === 'override')
                                                        <span class="text-[10px] uppercase font-semibold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded">Override</span>
                                                    @endif
                                                @else
                                                    <span class="text-xs text-gray-400 italic">(Tanpa Nomor)</span>
                                                @endif
                                            </td>

                                            <td class="px-5 py-4">
                                                <div class="font-medium text-gray-900">{{ $item->perihal }}</div>
                                                <div class="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                                    <span>{{ $item->letterType?->name ?? 'Surat' }}</span>
                                                    @if ($item->reference)
                                                        <span>•</span>
                                                        <span class="font-mono text-indigo-600 bg-indigo-50 px-1.5 py-0.2 rounded">{{ $item->reference }}</span>
                                                    @endif
                                                </div>
                                            </td>

                                            <td class="px-5 py-4 text-gray-800">
                                                <div>{{ $item->signer_name ?? $item->signer?->name }}</div>
                                                <div class="text-xs text-gray-400">{{ $item->signer_position ?? $item->signer?->position }}</div>
                                            </td>

                                            <td class="px-5 py-4 text-xs text-gray-500 whitespace-nowrap">
                                                {{ $item->created_at->setTimezone(config('app.timezone', 'Asia/Makassar'))->format('d/m/Y H:i') }} WITA
                                            </td>

                                            @if (Auth::user()->role === 'admin')
                                                <td class="px-5 py-4 text-xs text-gray-600">
                                                    {{ $item->generator?->name ?? '-' }}
                                                </td>
                                            @endif

                                            <td class="px-5 py-4 whitespace-nowrap">
                                                @if ($item->isVoid())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800">Dibatalkan</span>
                                                @elseif ($item->isFinal())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Final</span>
                                                @elseif ($item->isLegacy())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Legacy</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Issued</span>
                                                @endif
                                            </td>

                                            <td class="px-5 py-4 text-right whitespace-nowrap text-xs">
                                                <a href="{{ route('qr.show', $item->uuid) }}" class="font-medium text-indigo-600 hover:text-indigo-900 mr-2">
                                                    Detail &amp; QR
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination 20 items --}}
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            {{ $generations->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
