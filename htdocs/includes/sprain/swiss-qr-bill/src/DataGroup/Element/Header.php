<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\DataGroup\Element;

use Sprain\SwissQrBill\DataGroup\QrCodeableInterface;
use Sprain\SwissQrBill\Validator\SelfValidatableInterface;
use Sprain\SwissQrBill\Validator\SelfValidatableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

final class Header implements QrCodeableInterface, SelfValidatableInterface
{
    use SelfValidatableTrait;

    public const QRTYPE_SPC = 'SPC';
    public const VERSION_0200 = '0200';
    public const CODING_LATIN = 1;

    /**
     * Unambiguous indicator for the Swiss QR code.
     */
    private string $qrType;

    /**
     * Version of the specifications (Implementation Guidelines) in use on
     * the date on which the Swiss QR code was created.
     * The first two positions indicate the main version, the following the
     * two positions the sub-version ("0200" for version 2.0).
     */
    private string $version;

    /**
     * Character set code
     */
    private int $coding;

    private function __construct(string $qrType, string $version, int $coding)
    {
        $this->qrType = $qrType;
        $this->version = $version;
        $this->coding = $coding;
    }

    public static function create(string $qrType, string $version, int $coding): self
    {
        return new self($qrType, $version, $coding);
    }

    public function getQrType(): string
    {
        return $this->qrType;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCoding(): int
    {
        return $this->coding;
    }

    public function getQrCodeData(): array
    {
        return [
            $this->getQrType(),
            $this->getVersion(),
            $this->getCoding()
        ];
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        // Fixed length, three-digit, alphanumeric
        $metadata->addPropertyConstraints('qrType', [
            new Assert\NotBlank(),
            new Assert\Regex([
                'pattern' => '/^[a-zA-Z0-9]{3}$/',
                'match' => true
            ])
        ]);

        // Fixed length, four-digit, numeric
        $metadata->addPropertyConstraints('version', [
            new Assert\NotBlank(),
            new Assert\Regex([
                'pattern' => '/^\d{4}$/',
                'match' => true
            ])
        ]);

        // One-digit, numeric
        $metadata->addPropertyConstraints('coding', [
            new Assert\NotBlank(),
            new Assert\Regex([
                'pattern' => '/^\d{1}$/',
                'match' => true
            ])
        ]);
    }
}
