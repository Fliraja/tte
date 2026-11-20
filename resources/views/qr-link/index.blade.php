<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Riwayat QR Link') }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    @if (Auth::user()->role === 'admin')
                        Daftar seluruh QR Link yang dibuat di sistem
                    @else
                        Daftar QR Link yang Anda buat
                    @endif
                </p>
            </div>
            <a href="{{ route('qr-link.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md uppercase tracking-widest shadow-sm">
                + Buat QR Link Baru
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Kotak cari --}}
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                <form method="GET" action="{{ route('qr-link.index') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <div class="flex-1">
                        <label for="search" class="block text-xs font-medium text-gray-700">Cari keterangan atau tautan tujuan</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..."
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="py-2.5 px-4 bg-gray-800 text-white text-xs font-semibold rounded-md hover:bg-gray-700 uppercase tracking-wider">
                            Cari
                        </button>
                        @if (request()->filled('search'))
                            <a href="{{ route('qr-link.index') }}" class="py-2.5 px-4 border border-gray-300 text-gray-600 text-xs font-semibold rounded-md hover:bg-gray-50">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6 text-gray-900">
                    @if ($links->isEmpty())
                        <div class="py-12 text-center">
                            <h3 class="text-sm font-semibold text-gray-900">
                                @if (request()->filled('search'))
                                    Tidak ada QR Link yang cocok
                                @else
                                    Belum ada QR Link
                                @endif
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                @if (request()->filled('search'))
                                    Coba kata kunci lain atau reset pencarian.
                                @else
                                    Buat QR berlogo RS dari tautan Google Drive atau Docs.
                                @endif
                            </p>
                            @unless (request()->filled('search'))
                                <a href="{{ route('qr-link.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md uppercase tracking-widest">
                                    Buat QR Link Pertama
                                </a>
                            @endunless
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                                        <th class="px-5 py-3 text-left font-semibold">Keterangan</th>
                                        <th class="px-5 py-3 text-left font-semibold">Tujuan</th>
                                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                                        <th class="px-5 py-3 text-left font-semibold">Akses</th>
                                        <th class="px-5 py-3 text-left font-semibold">Dibuat</th>
                                        @if (Auth::user()->role === 'admin')
                                            <th class="px-5 py-3 text-left font-semibold">Pembuat</th>
                                        @endif
                                        <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($links as $item)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="px-5 py-4 font-medium text-gray-900">{{ $item->label }}</td>
                                            <td class="px-5 py-4 text-xs text-gray-600 font-mono" title="{{ $item->target_url }}">
                                                {{ \Illuminate\Support\Str::limit($item->target_url, 48) }}
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                @if ($item->isDisabled())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800">Nonaktif</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Aktif</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-xs text-gray-600 whitespace-nowrap">
                                                <div class="font-medium text-gray-900">{{ number_format($item->scan_count, 0, ',', '.') }}x</div>
                                                <div class="text-gray-500">
                                                    {{ $item->last_scanned_at ? $item->last_scanned_at->setTimezone(config('app.timezone', 'Asia/Makassar'))->format('d/m/Y H:i') : 'Belum pernah' }}
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-xs text-gray-500 whitespace-nowrap">
                                                {{ $item->created_at->setTimezone(config('app.timezone', 'Asia/Makassar'))->format('d/m/Y H:i') }} WITA
                                            </td>
                                            @if (Auth::user()->role === 'admin')
                                                <td class="px-5 py-4 text-xs text-gray-600">{{ $item->creator?->name ?? '-' }}</td>
                                            @endif
                                            <td class="px-5 py-4 text-right whitespace-nowrap text-xs">
                                                <a href="{{ route('qr-link.show', $item) }}" class="font-medium text-indigo-600 hover:text-indigo-900">
                                                    Detail &amp; QR
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100">
                            {{ $links->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
