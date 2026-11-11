<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Penerbitan TTE Tanpa Nomor') }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Khusus dokumen tanpa sistem penomoran internal (mis. Hasil Pemeriksaan MCU)</p>
            </div>
            <a href="{{ route('qr.history') }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                Lihat Riwayat Surat →
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6 text-gray-900"
                     x-data="{
                        types: {{ Js::from($types) }},
                        selectedTypeId: '{{ old('letter_type_id', $types->first()?->id) }}',
                        perihal: '{{ old('perihal', $types->first()?->name ?? '') }}',
                        get currentType() {
                            return this.types.find(t => String(t.id) === String(this.selectedTypeId)) || null;
                        },
                        init() {
                            this.$watch('selectedTypeId', (id) => {
                                const t = this.types.find(item => String(item.id) === String(id));
                                if (t && (!this.perihal || this.types.some(other => other.name === this.perihal))) {
                                    this.perihal = t.name;
                                }
                            });
                        }
                     }">

                    {{-- Peringatan Privasi (Spec §6.1b & §8) --}}
                    <div class="mb-5 p-4 bg-amber-50 border border-amber-300 rounded-xl text-xs text-amber-900 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <div class="font-bold text-amber-950">Peringatan Perlindungan Data Pasien (UU PDP):</div>
                            <p class="mt-0.5">Jangan mengisi <strong>nama pasien</strong> atau <strong>nomor rekam medis</strong> pada kolom Perihal maupun Referensi. Informasi tersebut tampil publik pada halaman verifikasi.</p>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="mb-5 p-4 bg-rose-50 border border-rose-200 rounded-lg">
                            <div class="text-sm font-semibold text-rose-800">Terdapat kesalahan isian:</div>
                            <ul class="mt-2 text-xs text-rose-700 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('duplicate_warning'))
                        <div class="mb-5 p-4 bg-rose-50 border border-rose-300 rounded-xl text-xs text-rose-900">
                            <div class="font-bold text-rose-950 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Peringatan Duplikasi Nomor Referensi:
                            </div>
                            <p class="mt-1">{{ session('duplicate_warning') }}</p>
                            <label class="mt-3 flex items-center gap-2 font-medium text-rose-800 cursor-pointer">
                                <input type="checkbox" name="confirm_duplicate" value="1" form="formTanpaNomor" class="rounded border-rose-400 text-rose-600 focus:ring-rose-500">
                                Centang di sini untuk konfirmasi tetap terbitkan ulang nomor referensi ini
                            </label>
                        </div>
                    @endif

                    <form id="formTanpaNomor" method="POST" action="{{ route('tanpa-nomor.store') }}" class="space-y-5">
                        @csrf

                        {{-- 1. Jenis Dokumen --}}
                        <div>
                            <x-input-label for="letter_type_id" :value="__('Jenis Dokumen Tanpa Nomor')" />
                            <select name="letter_type_id" id="letter_type_id" x-model="selectedTypeId" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($types as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('letter_type_id')" />
                        </div>

                        {{-- 2. Referensi (mis. Nomor Pemeriksaan) --}}
                        <div x-show="currentType && currentType.reference_label" x-cloak>
                            <label for="reference" class="block text-sm font-medium text-gray-700">
                                <span x-text="currentType?.reference_label || 'Nomor Pemeriksaan / Referensi'"></span>
                                <span x-show="currentType?.reference_required" class="text-rose-600">*</span>
                                <span x-show="!currentType?.reference_required" class="text-xs text-gray-400 font-normal">(opsional)</span>
                            </label>
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full text-sm font-mono"
                                :value="old('reference')" placeholder="Contoh: MCU-2026-00123" />
                            <p class="mt-1 text-xs text-gray-500">Identifikasi nomor formulir atau pemeriksaan MCU.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('reference')" />
                        </div>

                        {{-- 3. Penandatangan --}}
                        <div>
                            <x-input-label for="signer_id" :value="__('Dokter / Pejabat Penandatangan')" />
                            <select name="signer_id" id="signer_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Penandatangan --</option>
                                @foreach ($signers as $signer)
                                    <option value="{{ $signer->id }}" @selected(old('signer_id') == $signer->id)>
                                        {{ $signer->name }} — {{ $signer->position }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('signer_id')" />
                        </div>

                        {{-- 4. Perihal Dokumen --}}
                        <div>
                            <x-input-label for="perihal" :value="__('Perihal / Judul Dokumen')" />
                            <x-text-input id="perihal" name="perihal" type="text" class="mt-1 block w-full text-sm"
                                x-model="perihal" required />
                            <p class="mt-1 text-xs text-gray-500">Terisi otomatis dengan nama jenis dokumen dan dapat Anda sesuaikan.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('perihal')" />
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-400">
                                Penerbitan ini tidak menggunakan jatah counter buku TU.
                            </span>
                            <x-primary-button class="py-2.5 px-5">
                                {{ __('Terbitkan QR TTE') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
