<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\DataGroup;

/**
 * @internal
 */
interface QrCodeableInterface
{
    public function getQrCodeData(): array;
}
