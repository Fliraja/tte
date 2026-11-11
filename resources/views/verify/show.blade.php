<x-guest-layout>
    <div class="max-w-md mx-auto py-10 px-4 text-center">
        @if ($generation->isVoid())
            <div class="text-rose-500 text-4xl mb-2">&#10007;</div>
            <h1 class="text-xl font-bold text-rose-500 uppercase tracking-wide">Surat Dibatalkan (Void)</h1>
            <p class="text-xs text-rose-400 mt-1">Dokumen ini telah dinyatakan tidak berlaku lagi oleh manajemen RSU Nirwana.</p>

            <div class="mt-5 p-4 bg-rose-950/40 border border-rose-500/40 rounded-2xl text-left text-sm space-y-2">
                @if ($generation->letter_number)
                    <p class="line-through text-slate-400"><strong>Nomor Surat:</strong> {{ $generation->letter_number }}</p>
                @endif
                <p><strong>Perihal:</strong> {{ $generation->perihal }}</p>
                <p><strong>Waktu Pembatalan:</strong> {{ $generation->voided_at?->setTimezone(config('app.timezone', 'Asia/Makassar'))->translatedFormat('d F Y, H:i') }} WITA</p>
                <p class="text-rose-300"><strong>Alasan:</strong> {{ $generation->void_reason }}</p>
            </div>
        @else
            <div class="text-emerald-500 text-4xl mb-2">&#10003;</div>
            <h1 class="text-xl font-bold text-white">Verifikasi Dokumen Internal</h1>
            <p class="text-xs text-slate-400 mt-1">RSU Nirwana — Sistem Tanda Tangan Elektronik</p>

            {{-- Banner Dokumen Belum Diunggah jika expects_document dan documents_enabled true --}}
            @if ($generation->status === 'issued' && ($generation->letterType?->expects_document ?? true) && config('tte.documents_enabled'))
                <div class="mt-4 p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl text-xs text-amber-300 text-left">
                    <strong>Pemberitahuan:</strong> Isi naskah fisik surat final belum diunggah ke sistem.
                </div>
            @endif

            <div class="mt-6 text-left bg-slate-900/80 border border-slate-700/60 rounded-2xl p-5 space-y-2.5 text-sm">
                <p><strong>Nama:</strong> {{ $generation->signer_name ?? $generation->signer?->name }}</p>
                <p><strong>Jabatan:</strong> {{ $generation->signer_position ?? $generation->signer?->position }}</p>

                @if ($generation->letter_number)
                    <p><strong>Nomor Surat:</strong> <span class="font-mono text-brand-300">{{ $generation->letter_number }}</span></p>
                @endif

                @if ($generation->letterType)
                    <p><strong>Jenis Dokumen:</strong> {{ $generation->letterType->name }}</p>
                @endif

                @if ($generation->reference)
                    <p><strong>{{ $generation->letterType?->reference_label ?? 'Nomor Referensi' }}:</strong> <span class="font-mono text-blue-300">{{ $generation->reference }}</span></p>
                @endif

                @if ($generation->perihal)
                    <p><strong>Perihal:</strong> {{ $generation->perihal }}</p>
                @endif

                <p><strong>Waktu Terbit:</strong> {{ $generation->created_at->setTimezone(config('app.timezone', 'Asia/Makassar'))->translatedFormat('d F Y, H:i') }} WITA</p>
            </div>
        @endif

        {{-- Disclaimer Wajib (Spec §7) --}}
        <div class="mt-6 p-3.5 bg-slate-900/40 border border-slate-800 rounded-xl text-left text-xs text-slate-400">
            @if ($generation->number_source === 'none' || ($generation->letterType && $generation->letterType->numbering === 'none'))
                <p><strong>Disclaimer:</strong> QR ini hanya mencatat bahwa dokumen diterbitkan oleh penandatangan di atas pada waktu tertentu. Isi dan keaslian dokumen tidak dapat diverifikasi dari halaman ini.</p>
            @else
                <p><strong>Disclaimer:</strong> Verifikasi internal RSU Nirwana. Bukan tanda tangan elektronik tersertifikasi. Cocokkan isi dengan dokumen fisik.</p>
            @endif
        </div>
    </div>
</x-guest-layout>
