<?php

namespace App\Support;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

class Qr
{
    public static function svg(string $data, int $size = 220): string
    {
        return (new SvgWriter)->write(new QrCode(data: $data, size: $size, margin: 8))->getString();
    }

    public static function pngDataUri(string $data, int $size = 260): string
    {
        $png = (new PngWriter)->write(new QrCode(data: $data, size: $size, margin: 8))->getString();

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
