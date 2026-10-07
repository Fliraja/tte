<?php

namespace App\Services;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    /**
     * Generate QR code PNG dengan logo di tengah, background logo rounded corner.
     *
     * @param  string  $data  Konten/URL yang di-encode ke QR
     * @param  int  $logoWidth  Lebar logo setelah di-resize (px)
     * @param  int  $logoPadding  Jarak logo ke tepi background (px), tiap sisi
     * @param  int  $cornerRadius  Radius siku background logo (px)
     */
    public function generate(
        string $data,
        int $logoWidth = 70,
        int $logoPadding = 12,
        int $cornerRadius = 12
    ): string {
        $qrCode = new QrCode(
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            backgroundColor: new Color(255, 255, 255),
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        $image = imagecreatefromstring($result->getString());
        if ($image === false) {
            throw new \RuntimeException('Gagal generate base image QR code.');
        }

        $logoPath = public_path('qr-logo.png');
        $logoSource = imagecreatefromstring(file_get_contents($logoPath));
        if ($logoSource === false) {
            throw new \RuntimeException('Gagal load logo QR dari: '.$logoPath);
        }

        $origWidth = imagesx($logoSource);
        $origHeight = imagesy($logoSource);
        $logoHeight = (int) round($logoWidth * $origHeight / $origWidth);

        $logo = imagecreatetruecolor($logoWidth, $logoHeight);
        imagealphablending($logo, false);
        imagesavealpha($logo, true);
        $logoTransparent = imagecolorallocatealpha($logo, 0, 0, 0, 127);
        imagefill($logo, 0, 0, $logoTransparent);
        imagecopyresampled($logo, $logoSource, 0, 0, 0, 0, $logoWidth, $logoHeight, $origWidth, $origHeight);
        imagedestroy($logoSource);

        $boxWidth = $logoWidth + ($logoPadding * 2);
        $boxHeight = $logoHeight + ($logoPadding * 2);
        $qrSize = imagesx($image);
        $boxX = (int) (($qrSize - $boxWidth) / 2);
        $boxY = (int) (($qrSize - $boxHeight) / 2);

        $roundedBox = $this->createRoundedBox($boxWidth, $boxHeight, $cornerRadius);

        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagecopy($image, $roundedBox, $boxX, $boxY, 0, 0, $boxWidth, $boxHeight);
        imagedestroy($roundedBox);

        imagecopy($image, $logo, $boxX + $logoPadding, $boxY + $logoPadding, 0, 0, $logoWidth, $logoHeight);
        imagedestroy($logo);

        ob_start();
        imagepng($image);
        $pngData = ob_get_clean();
        imagedestroy($image);

        return $pngData;
    }

    /**
     * Bikin kotak putih rounded-corner dengan alpha channel (background transparan di luar bentuk).
     * Digambar 4x lipat ukuran asli lalu di-downsample biar sikunya anti-aliased, gak jaggy.
     */
    private function createRoundedBox(int $width, int $height, int $radius, int $scale = 4): \GdImage
    {
        $radius = min($radius, intdiv($width, 2), intdiv($height, 2));

        $bigW = $width * $scale;
        $bigH = $height * $scale;
        $bigRadius = $radius * $scale;

        $big = imagecreatetruecolor($bigW, $bigH);
        imagealphablending($big, false);
        imagesavealpha($big, true);
        $bigTransparent = imagecolorallocatealpha($big, 0, 0, 0, 127);
        imagefill($big, 0, 0, $bigTransparent);

        $white = imagecolorallocatealpha($big, 255, 255, 255, 0);
        imagefilledrectangle($big, $bigRadius, 0, $bigW - $bigRadius - 1, $bigH - 1, $white);
        imagefilledrectangle($big, 0, $bigRadius, $bigW - 1, $bigH - $bigRadius - 1, $white);
        imagefilledellipse($big, $bigRadius, $bigRadius, $bigRadius * 2, $bigRadius * 2, $white);
        imagefilledellipse($big, $bigW - $bigRadius - 1, $bigRadius, $bigRadius * 2, $bigRadius * 2, $white);
        imagefilledellipse($big, $bigRadius, $bigH - $bigRadius - 1, $bigRadius * 2, $bigRadius * 2, $white);
        imagefilledellipse($big, $bigW - $bigRadius - 1, $bigH - $bigRadius - 1, $bigRadius * 2, $bigRadius * 2, $white);

        $small = imagecreatetruecolor($width, $height);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        $smallTransparent = imagecolorallocatealpha($small, 0, 0, 0, 127);
        imagefill($small, 0, 0, $smallTransparent);
        imagecopyresampled($small, $big, 0, 0, 0, 0, $width, $height, $bigW, $bigH);
        imagedestroy($big);

        return $small;
    }
}
