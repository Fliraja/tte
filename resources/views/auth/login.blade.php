<x-guest-layout>
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div class="space-y-1.5">
            <label for="login" class="text-sm font-medium text-slate-300">NIP / Nama</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i data-lucide="user" class="w-5 h-5 text-slate-500"></i>
                </div>
                <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus
                    autocomplete="username" placeholder="Masukkan NIP atau Nama"
                    class="w-full pl-11 pr-4 py-3 bg-slate-800/80 border border-slate-600 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all @error('login') border-red-500 @enderror">
            </div>
            @error('login')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="password" class="text-sm font-medium text-slate-300">Kata Sandi</label>
                {{-- @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                        class="text-xs font-medium text-brand-400 hover:text-brand-300 transition-colors">Lupa
                        sandi?</a>
                @endif --}}
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i data-lucide="lock" class="w-5 h-5 text-slate-500"></i>
                </div>
                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                    placeholder="••••••••"
                    class="w-full pl-11 pr-12 py-3 bg-slate-800/80 border border-slate-600 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all @error('password') border-red-500 @enderror">
                <button type="button" @click="togglePassword()"
                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-white transition-colors">
                    <i :data-lucide="showPassword ? 'eye-off' : 'eye'" id="eyeIcon" class="w-5 h-5"></i>
                </button>
            </div>
            @error('password')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <button type="submit"
                class="w-full flex justify-center items-center gap-2 py-3.5 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-500 hover:to-brand-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-brand-500 transition-all transform hover:scale-[1.01] active:scale-[0.99]">
                Masuk ke Dashboard
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </div>
    </form>

    {{-- @if (Route::has('register'))
        <div class="mt-8 text-center">
            <p class="text-sm text-slate-400">
                Belum memiliki akun?
                <a href="{{ route('register') }}"
                    class="font-semibold text-brand-400 hover:text-brand-300 transition-colors">Daftar sekarang</a>
            </p>
        </div>
    @endif --}}
</x-guest-layout>
