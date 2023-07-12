<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\DataGroup\EmptyElement;

use Sprain\SwissQrBill\DataGroup\QrCodeableInterface;

/**
 * @internal
 */
final class EmptyAdditionalInformation implements QrCodeableInterface
{
    public const TRAILER_EPD = 'EPD';

    public function getQrCodeData(): array
    {
        return [
            null,
            self::TRAILER_EPD
        ];
    }
}
