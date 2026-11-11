<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Kelola Blok Nomor Surat') }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Pengaturan deret nomor surat dari buku nomor TU</p>
            </div>
            <a href="{{ route('blocks.create', ['year' => $year]) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                + Tambah Blok Nomor
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Year Selector & Info --}}
            <div class="bg-white p-5 rounded-xl border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <form method="GET" action="{{ route('blocks.index') }}" class="flex items-center gap-3">
                    <label for="year" class="text-sm font-medium text-gray-700">Pilih Tahun:</label>
                    <select name="year" id="year" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($availableYears as $y)
                            <option value="{{ $y }}" @selected($y === $year)>
                                Tahun {{ $y }} {{ $y === $currentYear ? '(Tahun Berjalan)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <div class="text-xs text-gray-500">
                    Sistem mengambil nomor dari blok aktif dengan nomor terkecil yang belum habis.
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6 text-gray-900">
                    @if ($blocks->isEmpty())
                        <div class="py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900">Belum ada blok nomor tahun {{ $year }}</h3>
                            <p class="mt-1 text-sm text-gray-500">Buat blok nomor baru agar surat penomoran otomatis dapat diterbitkan.</p>
                            <div class="mt-4">
                                <a href="{{ route('blocks.create', ['year' => $year]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md uppercase tracking-widest hover:bg-indigo-700">
                                    Tambah Blok Sekarang
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                                        <th class="px-6 py-3 text-left font-semibold">Rentang Nomor</th>
                                        <th class="px-6 py-3 text-left font-semibold">Nomor Berikutnya</th>
                                        <th class="px-6 py-3 text-left font-semibold">Sisa Nomor</th>
                                        <th class="px-6 py-3 text-left font-semibold">Status</th>
                                        <th class="px-6 py-3 text-left font-semibold">Dibuat Oleh</th>
                                        <th class="px-6 py-3 text-left font-semibold">Waktu Dibuat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($blocks as $block)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="px-6 py-4 font-mono font-medium text-gray-900">
                                                @if ($block->end_number === null)
                                                    {{ $block->start_number }} – Seterusnya (Tanpa Batas)
                                                @else
                                                    {{ $block->start_number }} – {{ $block->end_number }}
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 font-mono text-gray-700">
                                                #{{ $block->next_number }}
                                            </td>
                                            <td class="px-6 py-4">
                                                @if ($block->end_number === null)
                                                    <span class="text-xs font-medium text-gray-600">Tanpa Batas</span>
                                                @else
                                                    <span class="font-semibold {{ $block->remainingNumbers() === 0 ? 'text-rose-600' : 'text-gray-900' }}">
                                                        {{ $block->remainingNumbers() }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                @if ($block->isExhausted())
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                        Habis
                                                    </span>
                                                @elseif ($activeBlock && $activeBlock->id === $block->id)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800 font-semibold">
                                                        Aktif
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                                        Menunggu
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-gray-600">
                                                {{ $block->creator?->name ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-xs text-gray-500">
                                                {{ $block->created_at->format('d/m/Y H:i') }} WITA
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

