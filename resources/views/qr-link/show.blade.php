@php
    $tz = config('app.timezone', 'Asia/Makassar');
    $shortUrl = route('qr-link.go', $link->code);
    $eventLabels = [
        'created' => 'Dibuat',
        'target_changed' => 'Tujuan diganti',
        'disabled' => 'Dinonaktifkan',
        'enabled' => 'Diaktifkan kembali',
    ];
    $eventDots = [
        'created' => 'bg-emerald-500',
        'target_changed' => 'bg-indigo-500',
        'disabled' => 'bg-rose-500',
        'enabled' => 'bg-emerald-500',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight break-words">
                    {{ $link->label }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Detail QR Link</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('qr-link.create') }}" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-md">
                    + QR Link Baru
                </a>
                <a href="{{ route('qr-link.index') }}" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-white text-xs font-medium rounded-md">
                    Riwayat QR Link
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            {{-- Banner nonaktif --}}
            @if ($link->isDisabled())
                <div class="p-5 bg-rose-50 border-2 border-rose-500 rounded-xl text-rose-900 space-y-2">
                    <div class="text-rose-700 font-bold uppercase tracking-wider text-sm">QR Link nonaktif</div>
                    <div class="text-sm">
                        Pemindai QR ini melihat halaman "dinonaktifkan" dan tidak diarahkan ke tujuan. Gambar QR tetap bisa diunduh.
                    </div>
                    <div class="text-sm">
                        <strong>Dinonaktifkan oleh:</strong> {{ $link->disabler?->name ?? 'Sistem' }}
                        @if ($link->disabled_at)
                            pada {{ $link->disabled_at->setTimezone($tz)->translatedFormat('d F Y, H:i') }} WITA
                        @endif
                    </div>
                    <div class="text-sm"><strong>Alasan:</strong> {{ $link->disable_reason }}</div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 p-6 sm:p-8">

                {{-- Preview QR, unduh, tautan pendek --}}
                <div class="pb-6 flex flex-col items-center justify-center">
                    <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-sm">
                        <img src="{{ route('qr-link.png', $link) }}" alt="Gambar QR untuk {{ $link->label }}" class="w-48 h-48 object-contain" />
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ route('qr-link.png', $link) }}" download="qr-link-{{ $link->code }}.png"
                           class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Unduh PNG
                        </a>
                        <a href="{{ $link->target_url }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                            Uji Buka Tautan
                        </a>
                    </div>

                    <div class="mt-5 w-full max-w-md" x-data="{ copied: false }">
                        <div class="text-xs font-medium text-gray-500 mb-1">Tautan pendek (isi QR)</div>
                        <div class="flex items-stretch gap-2">
                            <code class="flex-1 min-w-0 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-mono text-gray-800 break-all">{{ $shortUrl }}</code>
                            <button type="button"
                                    @click="navigator.clipboard.writeText({{ Js::from($shortUrl) }}).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                    class="shrink-0 px-3 py-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-50">
                                <span x-show="!copied">Salin</span>
                                <span x-show="copied" x-cloak class="text-emerald-700">Tersalin</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Metadata --}}
                <div class="pt-6 border-t border-gray-100 text-sm">
                    <dl class="divide-y divide-gray-100">
                        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-500">Keterangan</dt>
                            <dd class="text-gray-900 sm:col-span-2 break-words">{{ $link->label }}</dd>
                        </div>
                        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-500">Tujuan saat ini</dt>
                            <dd class="text-gray-900 font-mono text-xs sm:col-span-2 break-all">{{ $link->target_url }}</dd>
                        </div>
                        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-500">Status</dt>
                            <dd class="sm:col-span-2">
                                @if ($link->isDisabled())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">Nonaktif</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Aktif</span>
                                @endif
                            </dd>
                        </div>
                        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-500">Jumlah akses</dt>
                            <dd class="text-gray-900 sm:col-span-2">
                                {{ number_format($link->scan_count, 0, ',', '.') }}x
                                <span class="block text-xs text-gray-500">Jumlah akses, bukan jumlah orang. Pratinjau tautan di aplikasi chat dan pemindaian berulang ikut terhitung.</span>
                            </dd>
                        </div>
                        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-500">Akses terakhir</dt>
                            <dd class="text-gray-900 sm:col-span-2">
                                {{ $link->last_scanned_at ? $link->last_scanned_at->setTimezone($tz)->translatedFormat('d F Y, H:i') . ' WITA' : 'Belum pernah diakses' }}
                            </dd>
                        </div>
                        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-500">Dibuat oleh</dt>
                            <dd class="text-gray-900 sm:col-span-2">
                                {{ $link->creator?->name ?? '-' }}
                                <span class="text-gray-500">pada {{ $link->created_at->setTimezone($tz)->translatedFormat('d F Y, H:i') }} WITA</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="mt-6">
                    @include('qr-link.partials.warning')
                </div>

                {{-- Aksi: ubah, nonaktifkan / aktifkan --}}
                <div class="mt-6 pt-6 border-t border-gray-100 space-y-4" x-data="{ openDisable: {{ $errors->has('disable_reason') ? 'true' : 'false' }} }">
                    <div class="flex flex-wrap items-center gap-3">
                        @can('update', $link)
                            <a href="{{ route('qr-link.edit', $link) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold rounded-md">
                                Ubah Tujuan atau Keterangan
                            </a>
                        @endcan

                        @can('toggle', $link)
                            @if ($link->isActive())
                                <button type="button" @click="openDisable = !openDisable" :aria-expanded="openDisable.toString()" aria-controls="form-nonaktifkan"
                                        class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold rounded-md border border-rose-200 transition">
                                    Nonaktifkan QR Link
                                </button>
                            @else
                                <form method="POST" action="{{ route('qr-link.toggle', $link) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-md shadow-sm">
                                        Aktifkan Kembali
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>

                    @can('toggle', $link)
                        @if ($link->isActive())
                            <div id="form-nonaktifkan" x-show="openDisable" x-cloak class="p-4 bg-rose-50 border border-rose-300 rounded-xl">
                                <form method="POST" action="{{ route('qr-link.toggle', $link) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label for="disable_reason" class="block text-xs font-semibold text-rose-950">
                                        Alasan penonaktifan <span class="text-rose-600">*</span> (minimal 5 karakter):
                                    </label>
                                    <textarea name="disable_reason" id="disable_reason" rows="2" required minlength="5"
                                              placeholder="Contoh: QR salah sebar ke pihak luar"
                                              class="mt-1 block w-full rounded-md border-rose-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500">{{ old('disable_reason') }}</textarea>
                                    <x-input-error class="mt-2" :messages="$errors->get('disable_reason')" />
                                    <div class="mt-3 flex items-center justify-end gap-2">
                                        <button @click="openDisable = false" type="button" class="px-3 py-1.5 text-xs text-gray-600 hover:text-gray-900">Batal</button>
                                        <button type="submit" onclick="return confirm('Nonaktifkan QR Link ini? QR yang sudah beredar akan menampilkan halaman nonaktif.')"
                                                class="px-4 py-2 bg-rose-600 text-white text-xs font-semibold rounded-md hover:bg-rose-700 shadow-sm">
                                            Konfirmasi Nonaktifkan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>

            {{-- Riwayat event --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 border-b border-gray-100 pb-3">
                    Riwayat Perubahan
                </h3>
                <ul class="mt-4 space-y-3">
                    @forelse ($link->events as $event)
                        <li class="flex items-start gap-3 text-xs">
                            <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $eventDots[$event->event] ?? 'bg-gray-400' }}"></span>
                            <div class="flex-1 min-w-0">
                                <span class="font-medium text-gray-900">{{ $eventLabels[$event->event] ?? ucfirst(str_replace('_', ' ', $event->event)) }}</span>
                                @if ($event->note)
                                    <div class="text-gray-600 break-all">{{ $event->note }}</div>
                                @endif
                                <div class="text-[11px] text-gray-500 mt-0.5">
                                    Oleh: {{ $event->user?->name ?? 'Sistem' }} • {{ $event->created_at->setTimezone($tz)->format('d/m/Y H:i') }} WITA
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-xs text-gray-500">Belum ada event tercatat.</li>
                    @endforelse
                </ul>
            </div>

        </div>
    </div>
</x-app-layout>
