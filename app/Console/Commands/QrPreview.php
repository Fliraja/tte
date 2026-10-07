<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QrCodeService;

class QrPreview extends Command
{
    protected $signature = 'qr:preview';
    protected $description = 'Generate QR contoh (dengan logo & margin terbaru) ke public/qr-preview.png buat dicek cepat di browser';

    public function handle(QrCodeService $qrCodeService): int
    {
        $pngData = $qrCodeService->generate(url('/verify/preview-test-uuid'));

        $outputPath = public_path('qr-preview.png');
        file_put_contents($outputPath, $pngData);

        $this->info('QR preview dibuat: ' . $outputPath);
        $this->info('Buka di browser: ' . url('/qr-preview.png') . ' (tambahin ?v=' . time() . ' kalau browser nge-cache)');

        return self::SUCCESS;
    }
}
