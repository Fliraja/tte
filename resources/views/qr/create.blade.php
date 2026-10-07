<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Generate QR Tanda Tangan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($errors->any())
                        <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                            @foreach ($errors->all() as $error)
                                <p class="text-sm">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('qr.generate') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="signer_id" class="block text-sm font-medium text-gray-700">Nama Penandatangan</label>
                            <select name="signer_id" id="signer_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih --</option>
                                @foreach ($signers as $signer)
                                    <option value="{{ $signer->id }}">{{ $signer->name }} — {{ $signer->position }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="letter_number" class="block text-sm font-medium text-gray-700">Nomor Surat <span class="text-red-600">*</span></label>
                            <input type="text" name="letter_number" id="letter_number" value="{{ old('letter_number') }}" placeholder="Contoh: 001/RS/VII/2026" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="mb-4">
                            <label for="perihal" class="block text-sm font-medium text-gray-700">Perihal <span class="text-red-600">*</span></label>
                            <input type="text" name="perihal" id="perihal" value="{{ old('perihal') }}" placeholder="Contoh: Persetujuan Cuti Tahunan" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                Generate &amp; Download QR
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
