<x-guest-layout>
    <div class="max-w-md mx-auto py-12 text-center">
        <div class="text-green-600 text-3xl mb-2">&#10003;</div>
        <h1 class="text-lg font-semibold">Dokumen Terverifikasi</h1>

        <div class="mt-6 text-left border rounded p-4">
            <p><strong>Nama:</strong> {{ $generation->signer->name }}</p>
            <p><strong>Jabatan:</strong> {{ $generation->signer->position }}</p>
            @if ($generation->letter_number)
                <p><strong>Nomor Surat:</strong> {{ $generation->letter_number }}</p>
            @endif
            @if ($generation->perihal)
                <p><strong>Perihal:</strong> {{ $generation->perihal }}</p>
            @endif
            <p><strong>Waktu Generate:</strong> {{ $generation->created_at->translatedFormat('d F Y, H:i') }} WIB</p>
        </div>

        <p class="mt-6 text-sm text-gray-500">
            QR ini digenerate lewat sistem internal Rumah Sakit dan tercatat di sistem audit.
        </p>
    </div>
</x-guest-layout>
