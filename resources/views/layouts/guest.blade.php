<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="bg-slate-950 text-slate-200 font-sans min-h-screen flex items-center justify-center relative overflow-hidden glow-bg">

    <div class="absolute inset-0 bg-grid pointer-events-none z-0"></div>

    <div
        class="absolute top-[-10%] right-[-5%] w-96 h-96 bg-brand-500 rounded-full mix-blend-multiply filter blur-[128px] opacity-50 animate-pulse-slow z-0">
    </div>
    <div
        class="absolute bottom-[-10%] left-[-5%] w-96 h-96 bg-blue-600 rounded-full mix-blend-multiply filter blur-[128px] opacity-40 animate-pulse-slow z-0">
    </div>

    <div class="container mx-auto px-4 relative z-10 flex h-screen lg:h-[600px] max-w-6xl items-center justify-center">

        <div
            class="flex flex-col lg:flex-row w-full h-full lg:h-auto bg-slate-900/60 backdrop-blur-xl border border-slate-700/50 rounded-3xl shadow-2xl overflow-hidden">

            <div
                class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12 relative bg-gradient-to-br from-slate-900 to-slate-950 border-r border-slate-700/50">
                <div>
                    <div class="flex items-center gap-3 text-brand-400 mb-6">
                        <img src="{{ asset('icon.png') }}" alt="Logo" class="w-8 h-8 rounded" />
                        <span class="text-xl font-bold tracking-wide text-white">TTE RSU Nirwana</span>
                    </div>
                    <h1 class="text-3xl font-bold leading-snug text-white mb-4">Tanda Tangan Elektronik<br>Generator
                    </h1>
                    <p class="text-slate-400 text-sm leading-relaxed max-w-md">
                        Sistem generator tanda tangan digital berbasis QR Code internal dengan enkripsi tingkat lanjut
                        untuk menjamin keaslian dan kerahasiaan setiap dokumen medis Anda.
                    </p>
                </div>

                <div class="flex-1 flex items-center justify-center mt-8">
                    <div
                        class="relative w-48 h-48 bg-slate-800/50 rounded-2xl border border-slate-700 flex items-center justify-center shadow-[0_0_30px_rgba(14,165,233,0.15)]">
                        <div
                            class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-brand-500 rounded-tl-xl -translate-x-2 -translate-y-2">
                        </div>
                        <div
                            class="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-brand-500 rounded-tr-xl translate-x-2 -translate-y-2">
                        </div>
                        <div
                            class="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-brand-500 rounded-bl-xl -translate-x-2 translate-y-2">
                        </div>
                        <div
                            class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-brand-500 rounded-br-xl translate-x-2 translate-y-2">
                        </div>

                        <i data-lucide="qr-code" class="w-32 h-32 text-slate-500 opacity-50"></i>

                        <div
                            class="absolute top-4 left-4 right-4 h-1 bg-brand-400 shadow-[0_0_15px_rgba(56,189,248,0.8)] rounded-full animate-scan">
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-1/2 flex items-center justify-center p-8 lg:p-12 relative">
                <div class="w-full max-w-md" x-data="loginForm()">
                    <div class="text-center lg:text-left mb-8">
                        <div class="flex justify-center lg:hidden items-center gap-2 text-brand-400 mb-6">
                            <img src="{{ asset('icon.png') }}" alt="Logo" class="w-8 h-8 rounded" />
                            <span class="text-2xl font-bold tracking-wide text-white">RSU Nirwana</span>
                        </div>
                        <h2 class="text-2xl lg:text-3xl font-bold text-white mb-2">Selamat Datang!</h2>
                        <p class="text-slate-400 text-sm">Masuk ke dashboard untuk mengelola tanda tangan Anda.</p>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });

        function loginForm() {
            return {
                showPassword: false,
                togglePassword() {
                    this.showPassword = !this.showPassword;
                    setTimeout(() => {
                        if (typeof lucide !== 'undefined') lucide.createIcons();
                    }, 50);
                }
            }
        }
    </script>
</body>

</html>
