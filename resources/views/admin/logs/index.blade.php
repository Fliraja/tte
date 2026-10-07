<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Log Audit Generate QR') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" class="mb-4 flex flex-wrap gap-2">
                        <input type="text" name="signer_name" placeholder="Nama signer" value="{{ request('signer_name') }}"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <input type="text" name="nip" placeholder="NIP staf" value="{{ request('nip') }}"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Filter
                        </button>
                        @if (request()->anyFilled(['signer_name', 'nip', 'date_from', 'date_to']))
                            <a href="{{ route('logs.index') }}" class="inline-flex items-center px-3 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                Reset
                            </a>
                        @endif
                    </form>

                    @if ($logs->isEmpty())
                        <p class="text-gray-500 text-center py-8">Belum ada data generate QR.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Signer</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomor Surat</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perihal</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Digenerate Oleh</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">UUID</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($logs as $log)
                                        <tr>
                                            <td class="px-4 py-3">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</td>
                                            <td class="px-4 py-3">{{ $log->signer->name }}</td>
                                            <td class="px-4 py-3">{{ $log->letter_number ?? '-' }}</td>
                                            <td class="px-4 py-3">{{ $log->perihal ?? '-' }}</td>
                                            <td class="px-4 py-3">{{ $log->generator->name }} ({{ $log->generator->nip }})</td>
                                            <td class="px-4 py-3 font-mono">{{ $log->ip_address }}</td>
                                            <td class="px-4 py-3 font-mono text-xs">{{ $log->uuid }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
