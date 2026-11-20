<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Ubah QR Link') }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Kode dan gambar QR tidak berubah, hanya tujuannya</p>
            </div>
            <a href="{{ route('qr-link.show', $link) }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium whitespace-nowrap">
                Kembali ke detail
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6 text-gray-900 space-y-5">

                    <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl text-xs text-indigo-900">
                        Perubahan tujuan berlaku seketika pada QR yang sudah dicetak atau dibagikan. Pastikan tautan baru sudah dibagikan sebagai "Siapa saja yang memiliki link".
                    </div>

                    @if ($errors->any())
                        <div class="p-4 bg-rose-50 border border-rose-200 rounded-lg">
                            <div class="text-sm font-semibold text-rose-800">Terdapat kesalahan isian:</div>
                            <ul class="mt-2 text-xs text-rose-700 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('qr-link.update', $link) }}" class="space-y-5">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="target_url" :value="__('Tautan Tujuan')" />
                            <x-text-input id="target_url" name="target_url" type="text" inputmode="url" autocomplete="off"
                                class="mt-1 block w-full text-sm font-mono" :value="old('target_url', $link->target_url)" required autofocus />
                            <p class="mt-1 text-xs text-gray-500">
                                Wajib diawali https://. Domain yang diizinkan: {{ implode(', ', $allowedHosts) }}.
                            </p>
                            <x-input-error class="mt-2" :messages="$errors->get('target_url')" />
                        </div>

                        <div>
                            <x-input-label for="label" :value="__('Keterangan')" />
                            <x-text-input id="label" name="label" type="text" maxlength="255"
                                class="mt-1 block w-full text-sm" :value="old('label', $link->label)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('label')" />
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <a href="{{ route('qr-link.show', $link) }}" class="px-3 py-2 text-xs text-gray-600 hover:text-gray-900">Batal</a>
                            <x-primary-button class="py-2.5 px-5">
                                {{ __('Simpan Perubahan') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
