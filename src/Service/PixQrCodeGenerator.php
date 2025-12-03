<?php

namespace BetoCampoy\CoraSdk\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Gera QRCode (PNG) a partir do payload EMV do Pix
 * e devolve diretamente a data URI ("data:image/png;base64,...").
 */
class PixQrCodeGenerator
{
    public function __construct(
        private int $defaultSize = 700,
        private int $defaultMargin = 5,
    ) {
    }

    /**
     * Gera uma data URI de QRCode a partir do EMV Pix.
     */
    public function dataUriFromEmv(string $emvEncoded, ?int $size = null, ?int $margin = null): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $emvEncoded,
            size: $size ?? $this->defaultSize,
            margin: $margin ?? $this->defaultMargin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $result = $builder->build();

        return $result->getDataUri();
    }

    /**
     * Se em algum momento você quiser o binário puro do PNG.
     */
    public function pngFromEmv(string $emvEncoded, ?int $size = null, ?int $margin = null): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $emvEncoded,
            size: $size ?? $this->defaultSize,
            margin: $margin ?? $this->defaultMargin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $result = $builder->build();

        return $result->getString();
    }
}
