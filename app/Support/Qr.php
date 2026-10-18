<?php

namespace App\Support;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class Qr
{
    public static function svg(string $data): string
    {
        return (new QRCode(new QROptions([
            'outputType' => 'svg',
            'outputBase64' => false,
            'eccLevel' => 'M',
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'svgUseFillAttributes' => true,
            'drawLightModules' => false,
        ])))->render($data);
    }
}
