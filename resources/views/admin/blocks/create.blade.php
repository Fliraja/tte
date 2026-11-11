<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tambah Blok Nomor Surat') }}
            </h2>
            <a href="{{ route('blocks.index', ['year' => $year]) }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← Kembali ke Daftar Blok
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <form method="POST" action="{{ route('blocks.store') }}" class="p-6 space-y-6">
                    @csrf

                    @if ($errors->any())
                        <div class="p-4 bg-rose-50 border border-rose-200 rounded-lg">
                            <div class="text-sm font-medium text-rose-800">Terdapat kesalahan pada isian form:</div>
                            <ul class="mt-2 text-sm text-rose-700 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <x-input-label for="year" :value="__('Tahun')" />
                        <x-text-input id="year" name="year" type="number" class="mt-1 block w-full" :value="old('year', $year)" required min="2020" max="2100" />
                        <p class="mt-1 text-xs text-gray-500">Tahun penerbitan surat untuk blok nomor ini.</p>
                        <x-input-error class="mt-2" :messages="$errors->get('year')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="start_number" :value="__('Nomor Awal (Mulai)')" />
                            <x-text-input id="start_number" name="start_number" type="number" class="mt-1 block w-full" :value="old('start_number')" required min="1" placeholder="Contoh: 1000" />
                            <p class="mt-1 text-xs text-gray-500">Nomor urut pertama dalam rentang blok.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('start_number')" />
                        </div>

                        <div>
                            <x-input-label for="end_number" :value="__('Nomor Akhir (Batas)')" />
                            <x-text-input id="end_number" name="end_number" type="number" class="mt-1 block w-full" :value="old('end_number')" min="1" placeholder="Kosongkan jika tanpa batas" />
                            <p class="mt-1 text-xs text-gray-500">Kosongkan jika blok tanpa batas akhir (mis. saat cutover penuh dari buku TU).</p>
                            <x-input-error class="mt-2" :messages="$errors->get('end_number')" />
                        </div>
                    </div>

                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-800 space-y-1.5">
                        <div class="font-semibold text-blue-900">Ketentuan Pembuatan Blok Nomor:</div>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li>Nomor awal harus minimal 1 dan nomor akhir harus &ge; nomor awal (jika diisi).</li>
                            <li>Rentang nomor tidak boleh tumpang tindih dengan blok lain di tahun yang sama.</li>
                            <li>Jika sudah ada blok tanpa batas akhir, tidak boleh membuat blok baru setelahnya.</li>
                            <li>Nomor awal harus lebih besar dari nomor terbesar yang sudah pernah diterbitkan sistem pada tahun bersangkutan.</li>
                            <li>Setelah disimpan, blok yang sudah dipakai tidak dapat diedit atau dihapus demi integritas deret nomor.</li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('blocks.index', ['year' => $year]) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Batal
                        </a>
                        <x-primary-button>
                            {{ __('Simpan Blok Nomor') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

