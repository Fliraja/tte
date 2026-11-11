@if (! config('tte.auto_numbering'))
    @include('qr.create-legacy')
@else
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Terbitkan Nomor Surat & QR') }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Penomoran otomatis resmi RSU Nirwana & QR verifikasi dokumen</p>
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
                        selectedTypeId: '{{ old('letter_type_id') }}',
                        get currentType() {
                            return this.types.find(t => String(t.id) === String(this.selectedTypeId)) || null;
                        }
                     }">

                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-lg">
                            <div class="text-sm font-semibold text-rose-800">Terdapat kesalahan pada isian form:</div>
                            <ul class="mt-2 text-xs text-rose-700 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('qr.generate') }}" class="space-y-5">
                        @csrf

                        {{-- 1. Jenis Surat --}}
                        <div>
                            <x-input-label for="letter_type_id" :value="__('Jenis Surat')" />
                            <select name="letter_type_id" id="letter_type_id" x-model="selectedTypeId" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Jenis Surat --</option>
                                <template x-for="t in types" :key="t.id">
                                    <option :value="t.id" x-text="t.code ? (t.code + ' — ' + t.name) : (t.name + ' (Nomor Manual)')"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('letter_type_id')" />
                        </div>

                        {{-- 2. Kondisional berdasarkan Jenis Surat --}}
                        {{-- Kasus: Surat Standar (Auto tanpa Unit) -> Pilih Peruntukan --}}
                        <div x-show="currentType && currentType.numbering === 'auto' && !currentType.uses_unit" x-cloak>
                            <x-input-label for="destination_id" :value="__('Peruntukan Surat')" />
                            <select name="destination_id" id="destination_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih Kode Peruntukan --</option>
                                @foreach ($destinations as $dest)
                                    <option value="{{ $dest->id }}" @selected(old('destination_id') == $dest->id)>
                                        {{ $dest->code }} — {{ $dest->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Kode peruntukan / tujuan surat resmi.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('destination_id')" />
                        </div>

                        {{-- Kasus: Surat dengan Unit (SPO) -> Peruntukan Terkunci, Pilih Unit --}}
                        <div x-show="currentType && currentType.numbering === 'auto' && currentType.uses_unit" x-cloak class="space-y-3">
                            <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-600 flex items-center justify-between">
                                <span>Peruntukan Tetap:</span>
                                <span class="font-mono font-semibold text-gray-900" x-text="currentType?.fixed_peruntukan || 'SPO'"></span>
                            </div>
                            <div>
                                <x-input-label for="unit_id" :value="__('Unit Pembuat SPO')" />
                                <select name="unit_id" id="unit_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach ($units as $u)
                                        <option value="{{ $u->id }}" @selected(old('unit_id') == $u->id)>
                                            {{ $u->code }} — {{ $u->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Unit / instalasi penerbit Standar Prosedur Operasional.</p>
                                <x-input-error class="mt-2" :messages="$errors->get('unit_id')" />
                            </div>
                        </div>

                        {{-- Kasus: Surat Manual (SDM) -> Ketik Nomor --}}
                        <div x-show="currentType && currentType.numbering === 'manual'" x-cloak>
                            <x-input-label for="manual_number" :value="__('Nomor Surat Manual')" />
                            <x-text-input id="manual_number" name="manual_number" type="text" class="mt-1 block w-full text-sm font-mono"
                                :value="old('manual_number')" placeholder="Contoh: 050/SDM-RSUN/2026" />
                            <p class="mt-1 text-xs text-gray-500">Ketik nomor surat resmi yang diberikan bagian HRD/SDM.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('manual_number')" />
                        </div>

                        {{-- Kasus: Kolom Referensi Opsional / Wajib jika ada reference_label --}}
                        <div x-show="currentType && currentType.reference_label" x-cloak>
                            <label for="reference" class="block text-sm font-medium text-gray-700">
                                <span x-text="currentType?.reference_label || 'Nomor Referensi'"></span>
                                <span x-show="currentType?.reference_required" class="text-rose-600">*</span>
                            </label>
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full text-sm"
                                :value="old('reference')" placeholder="Masukkan nomor referensi..." />
                            <p class="mt-1 text-xs text-rose-600">Perhatian: Jangan mengisi nama pasien atau nomor rekam medis pada referensi.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('reference')" />
                        </div>

                        {{-- 3. Penandatangan --}}
                        <div>
                            <x-input-label for="signer_id" :value="__('Pejabat Penandatangan')" />
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

                        {{-- 4. Perihal --}}
                        <div>
                            <x-input-label for="perihal" :value="__('Perihal Surat')" />
                            <x-text-input id="perihal" name="perihal" type="text" class="mt-1 block w-full text-sm"
                                :value="old('perihal')" required placeholder="Contoh: Undangan Rapat Koordinasi Pelayanan" />
                            <p class="mt-1 text-xs text-gray-500">Uraian ringkas maksud atau subjek surat.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('perihal')" />
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-400">
                                Format nomor resmi: <span class="font-mono">{kode}.{urut}/{peruntukan}/RSUN/{bulan}/{tahun}</span>
                            </span>
                            <x-primary-button class="py-2.5 px-5">
                                {{ __('Terbitkan Nomor & QR') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
@endif
