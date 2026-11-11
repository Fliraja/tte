<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Hasil Penerbitan QR & Nomor Surat') }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Surat terdaftar di sistem RSU Nirwana</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('qr.create') }}" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-md">
                    + Terbitkan Surat Baru
                </a>
                <a href="{{ route('qr.history') }}" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-white text-xs font-medium rounded-md">
                    Riwayat Surat
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            {{-- Banner Void jika surat telah dibatalkan --}}
            @if ($generation->isVoid())
                <div class="p-5 bg-rose-50 border-2 border-rose-500 rounded-xl text-rose-900 space-y-2">
                    <div class="flex items-center gap-2 text-rose-700 font-bold uppercase tracking-wider text-sm">
                        <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        SURAT TELAH DIBATALKAN (VOID)
                    </div>
                    <div class="text-sm">
                        <strong>Dibatalkan oleh:</strong> {{ $generation->voider?->name ?? 'Admin' }} pada {{ $generation->voided_at?->setTimezone(config('app.timezone', 'Asia/Makassar'))->translatedFormat('d F Y, H:i') }} WITA
                    </div>
                    <div class="text-sm">
                        <strong>Alasan Pembatalan:</strong> {{ $generation->void_reason }}
                    </div>
                </div>
            @endif

            {{-- Card Utama: Nomor & QR --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 p-6 sm:p-8" x-data="{ openVoid: false }">

                @if ($generation->letter_number)
                    <div class="text-center pb-6 border-b border-gray-100">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Nomor Surat Resmi Diterbitkan</span>
                        <div class="mt-2 flex items-center justify-center gap-3">
                            <span class="text-2xl sm:text-3xl font-mono font-bold text-gray-900 tracking-tight" id="letterNumberText">
                                {{ $generation->letter_number }}
                            </span>
                            <button type="button"
                                    x-data="{ copied: false }"
                                    @click="navigator.clipboard.writeText('{{ $generation->letter_number }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                                    title="Salin Nomor Surat">
                                <svg x-show="!copied" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span x-show="copied" x-cloak class="text-xs font-medium text-emerald-600">Tersalin!</span>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="text-center pb-6 border-b border-gray-100">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Dokumen TTE Tanpa Nomor ({{ $generation->letterType?->name ?? 'MCU' }})
                        </span>
                    </div>
                @endif

                {{-- QR Preview & Download --}}
                <div class="py-6 flex flex-col items-center justify-center">
                    <div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-sm">
                        <img src="{{ route('qr.png', $generation->uuid) }}" alt="QR Code" class="w-48 h-48 object-contain" />
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <a href="{{ route('qr.png', $generation->uuid) }}" download="qr-ttd-{{ $generation->uuid }}.png"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Unduh Gambar QR (PNG)
                        </a>
                        <a href="{{ url('/verify/' . $generation->uuid) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                            Cek Halaman Verifikasi ↗
                        </a>
                    </div>
                </div>

                {{-- Metadata List --}}
                <div class="pt-6 border-t border-gray-100 text-sm">
                    <dl class="divide-y divide-gray-100">
                        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-500">Jenis Surat</dt>
                            <dd class="text-gray-900 sm:col-span-2">{{ $generation->letterType?->name ?? 'Surat Standar' }}</dd>
                        </div>
                        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-500">Pejabat Penandatangan</dt>
                            <dd class="text-gray-900 sm:col-span-2">
                                <span class="font-medium">{{ $generation->signer_name }}</span>
                                <span class="text-gray-500">({{ $generation->signer_position }})</span>
                            </dd>
                        </div>
                        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-500">Perihal</dt>
                            <dd class="text-gray-900 sm:col-span-2">{{ $generation->perihal }}</dd>
                        </div>
                        @if ($generation->reference)
                            <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="font-medium text-gray-500">{{ $generation->letterType?->reference_label ?? 'Referensi' }}</dt>
                                <dd class="text-gray-900 font-mono sm:col-span-2">{{ $generation->reference }}</dd>
                            </div>
                        @endif
                        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-500">Waktu Terbit</dt>
                            <dd class="text-gray-900 sm:col-span-2">
                                {{ $generation->created_at->setTimezone(config('app.timezone', 'Asia/Makassar'))->translatedFormat('d F Y, H:i') }} WITA
                            </dd>
                        </div>
                        <div class="py-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="font-medium text-gray-500">Status Surat</dt>
                            <dd class="sm:col-span-2">
                                @if ($generation->isVoid())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">Dibatalkan (Void)</span>
                                @elseif ($generation->isFinal())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Final (Terkunci)</span>
                                @elseif ($generation->isLegacy())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700">Legacy</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">Issued</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Reminder Petunjuk Staf --}}
                @if (! $generation->isVoid())
                    <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="space-y-1">
                            <div class="font-semibold text-amber-950">Langkah Selanjutnya:</div>
                            <p>1. Ketik nomor surat di atas pada berkas fisik / file dokumen Word Anda.</p>
                            <p>2. Unduh dan tempelkan gambar QR di kolom tanda tangan surat.</p>
                            <p>3. Setelah dokumen ditandatangani dan diekspor ke PDF, dokumen final dapat diunggah ke sistem untuk penguncian arsip.</p>
                        </div>
                    </div>
                @endif

                {{-- Tombol & Form Void (K5: Staf selama issued, Admin kapan saja) --}}
                @can('void', $generation)
                    <div class="mt-6 pt-6 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold text-gray-700">Pembatalan Surat (Void)</div>
                            <div class="text-xs text-gray-400">Batalkan surat jika terdapat kesalahan cetak/isi. Nomor surat hangus dan tidak didaur ulang.</div>
                        </div>
                        <button @click="openVoid = !openVoid" type="button" class="shrink-0 px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold rounded-lg border border-rose-200 transition">
                            Batalkan Surat Ini (Void)
                        </button>
                    </div>

                    <div x-show="openVoid" x-cloak class="mt-4 p-4 bg-rose-50 border border-rose-300 rounded-xl space-y-3">
                        <form method="POST" action="{{ route('qr.void', $generation->uuid) }}">
                            @csrf
                            <label for="void_reason" class="block text-xs font-semibold text-rose-950">
                                Alasan Pembatalan Surat <span class="text-rose-600">*</span> (minimal 5 karakter):
                            </label>
                            <textarea name="void_reason" id="void_reason" rows="2" required minlength="5" placeholder="Contoh: Salah ketik perihal dan nama pada naskah surat fisik..."
                                      class="mt-1 block w-full rounded-md border-rose-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
                            <div class="mt-3 flex items-center justify-end gap-2">
                                <button @click="openVoid = false" type="button" class="px-3 py-1.5 text-xs text-gray-600 hover:text-gray-900">
                                    Batal
                                </button>
                                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin membatalkan surat ini secara permanen? Nomor surat hangus dan tidak dapat dipakai lagi.')"
                                        class="px-4 py-1.5 bg-rose-600 text-white text-xs font-semibold rounded-md hover:bg-rose-700 shadow-sm">
                                    Konfirmasi Batalkan Surat
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan

            </div>

            {{-- Card Log Aktivitas & Event --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Riwayat Perubahan &amp; Event Dokumen
                </h3>
                <ul class="mt-4 space-y-3">
                    @forelse ($generation->events as $event)
                        <li class="flex items-start gap-3 text-xs">
                            <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $event->event === 'voided' ? 'bg-rose-500' : ($event->event === 'number_overridden' ? 'bg-purple-500' : 'bg-emerald-500') }}"></span>
                            <div class="flex-1">
                                <span class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $event->event)) }}</span>
                                @if ($event->note)
                                    <span class="text-gray-600">— "{{ $event->note }}"</span>
                                @endif
                                <div class="text-[11px] text-gray-400 mt-0.5">
                                    Oleh: {{ $event->user?->name ?? 'Sistem' }} • {{ $event->created_at->setTimezone(config('app.timezone', 'Asia/Makassar'))->format('d/m/Y H:i') }} WITA
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-xs text-gray-400">Belum ada event tercatat.</li>
                    @endforelse
                </ul>
            </div>

        </div>
    </div>
</x-app-layout>
