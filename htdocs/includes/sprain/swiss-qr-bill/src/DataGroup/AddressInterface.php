<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\DataGroup;

/**
 * @internal
 */
interface AddressInterface
{
    public function getName(): ?string;

    public function getCountry(): ?string;

    public function getFullAddress(bool $forReceipt = false): string;
}
