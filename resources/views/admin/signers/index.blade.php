<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Kelola Penandatangan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4">
                <a href="{{ route('signers.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    + Tambah Penandatangan
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($signers as $signer)
                                <tr>
                                    <td class="px-6 py-4">{{ $signer->name }}</td>
                                    <td class="px-6 py-4">{{ $signer->position }}</td>
                                    <td class="px-6 py-4">
                                        @if ($signer->is_active)
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Aktif</span>
                                        @else
                                            <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('signers.edit', $signer) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                        @if ($signer->is_active)
                                            <form method="POST" action="{{ route('signers.destroy', $signer) }}" class="inline" onsubmit="return confirm('Nonaktifkan penandatangan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Nonaktifkan</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('signers.toggle', $signer) }}" class="inline" onsubmit="return confirm('Aktifkan kembali penandatangan ini?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-green-600 hover:text-green-900">Aktifkan</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">
                        {{ $signers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
