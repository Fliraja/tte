<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $message }}</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-white text-gray-900">
    <main class="max-w-md mx-auto px-4 py-12 text-center">
        <div class="text-red-600 text-3xl mb-2" aria-hidden="true">&#10007;</div>
        <h1 class="text-lg font-semibold">{{ $message }}</h1>
        <p class="mt-4 text-gray-600">
            QR ini tidak dapat dialihkan ke tujuannya. Hubungi pihak yang membagikan QR ini bila Anda memerlukan akses.
        </p>
    </main>
</body>
</html>
