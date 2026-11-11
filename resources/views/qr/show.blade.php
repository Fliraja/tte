<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Hasil Penerbitan QR & Nomor Surat') }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Surat berhasil didaftarkan ke sistem RSU Nirwana</p>
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

            {{-- Card Utama: Nomor & QR --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 p-6 sm:p-8">

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
                    </dl>
                </div>

                {{-- Reminder Petunjuk Staf --}}
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

            </div>

        </div>
    </div>
</x-app-layout>

