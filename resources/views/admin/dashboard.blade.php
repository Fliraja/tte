<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Dashboard Admin') }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Ringkasan sistem QR tanda tangan elektronik</p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 text-xs text-gray-500 bg-gray-100 px-3 py-1.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                {{ now()->translatedFormat('l, d F Y') }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Stat cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-start gap-4">
                    <div class="shrink-0 w-11 h-11 rounded-lg bg-brand-500/10 text-brand-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm text-gray-500">Penandatangan Aktif</div>
                        <div class="text-2xl font-semibold text-gray-900 mt-0.5">{{ $stats['active_signers'] }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $stats['inactive_signers'] }} nonaktif</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-start gap-4">
                    <div class="shrink-0 w-11 h-11 rounded-lg bg-violet-500/10 text-violet-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm text-gray-500">Akun Staf Aktif</div>
                        <div class="text-2xl font-semibold text-gray-900 mt-0.5">{{ $stats['active_staff'] }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $stats['inactive_staff'] }} nonaktif</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-start gap-4">
                    <div class="shrink-0 w-11 h-11 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm text-gray-500">QR 30 Hari Terakhir</div>
                        <div class="text-2xl font-semibold text-gray-900 mt-0.5">{{ $stats['qr_last_30_days'] }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">generate</div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-start gap-4">
                    <div class="shrink-0 w-11 h-11 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm text-gray-500">Total QR Digenerate</div>
                        <div class="text-2xl font-semibold text-gray-900 mt-0.5">{{ $stats['qr_total'] }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">sepanjang waktu</div>
                    </div>
                </div>
            </div>
            {{-- Main content: recent activity + quick access --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Recent activity (2/3 width on desktop) --}}
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800 text-sm">Generate QR Terbaru</h3>
                        <a href="{{ route('logs.index') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">
                            Lihat semua →
                        </a>
                    </div>

                    @if ($recentLogs->isEmpty())
                        <div class="px-5 py-10 text-center">
                            <p class="text-sm text-gray-400">Belum ada data generate QR.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                                        <th class="px-5 py-2.5 text-left font-medium">Waktu</th>
                                        <th class="px-5 py-2.5 text-left font-medium">Signer</th>
                                        <th class="px-5 py-2.5 text-left font-medium">Perihal</th>
                                        <th class="px-5 py-2.5 text-left font-medium">Oleh</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($recentLogs as $log)
                                        <tr class="hover:bg-gray-50/60">
                                            <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at->translatedFormat('d M, H:i') }}</td>
                                            <td class="px-5 py-3 text-gray-800 font-medium">{{ $log->signer->name }}</td>
                                            <td class="px-5 py-3 text-gray-600 max-w-[180px] truncate">{{ $log->perihal ?? '-' }}</td>
                                            <td class="px-5 py-3 text-gray-500">{{ $log->generator->name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                {{-- Quick access (1/3 width on desktop) --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden self-start">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800 text-sm">Akses Cepat</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <a href="{{ route('signers.index') }}" class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition">
                            <div class="w-8 h-8 rounded-lg bg-brand-500/10 text-brand-600 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-800">Kelola Penandatangan</div>
                                <div class="text-xs text-gray-400">Tambah, edit, nonaktifkan</div>
                            </div>
                        </a>
                        <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition">
                            <div class="w-8 h-8 rounded-lg bg-violet-500/10 text-violet-600 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-800">Kelola Akun Staf</div>
                                <div class="text-xs text-gray-400">Tambah akun, reset password</div>
                            </div>
                        </a>
                        <a href="{{ route('logs.index') }}" class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-800">Log Audit</div>
                                <div class="text-xs text-gray-400">Riwayat generate QR</div>
                            </div>
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
