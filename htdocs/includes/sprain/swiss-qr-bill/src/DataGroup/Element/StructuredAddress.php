<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\DataGroup\Element;

use Sprain\SwissQrBill\DataGroup\AddressInterface;
use Sprain\SwissQrBill\DataGroup\Element\Abstracts\Address;
use Sprain\SwissQrBill\DataGroup\QrCodeableInterface;
use Sprain\SwissQrBill\String\StringModifier;
use Sprain\SwissQrBill\Validator\SelfValidatableInterface;
use Sprain\SwissQrBill\Validator\SelfValidatableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

final class StructuredAddress extends Address implements AddressInterface, SelfValidatableInterface, QrCodeableInterface
{
    use SelfValidatableTrait;

    public const ADDRESS_TYPE = 'S';

    /**
     * Name or company
     */
    private string $name;

    /**
     * Street / P.O. box
     *
     * May not include building or house number.
     */
    private ?string $street;

    /**
     * Building number
     */
    private ?string $buildingNumber;

    /**
     * Postal code without country code
     */
    private string $postalCode;

    /**
     * City
     */
    private string $city;

    /**
     * Country (ISO 3166-1 alpha-2)
     */
    private string $country;

    private function __construct(
        string $name,
        ?string $street,
        ?string $buildingNumber,
        string $postalCode,
        string $city,
        string $country
    ) {
        $this->name = self::normalizeString($name);
        $this->street = self::normalizeString($street);
        $this->buildingNumber = self::normalizeString($buildingNumber);
        $this->postalCode = self::normalizeString($postalCode);
        $this->city = self::normalizeString($city);
        $this->country = strtoupper(self::normalizeString($country));
    }

    public static function createWithoutStreet(
        string $name,
        string $postalCode,
        string $city,
        string $country
    ): self {
        return new self(
            $name,
            null,
            null,
            $postalCode,
            $city,
            $country
        );
    }

    public static function createWithStreet(
        string $name,
        string $street,
        ?string $buildingNumber,
        string $postalCode,
        string $city,
        string $country
    ): self {
        return new self(
            $name,
            $street,
            $buildingNumber,
            $postalCode,
            $city,
            $country
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getBuildingNumber(): ?string
    {
        return $this->buildingNumber;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getFullAddress(bool $forReceipt = false): string
    {
        $lines[1] = $this->getName();

        if ($this->getStreet()) {
            $lines[2] = $this->getStreet();

            if ($this->getBuildingNumber()) {
                $lines[2] .= ' ' . $this->getBuildingNumber();
            }
        }

        if ('CH' === $this->getCountry()) {
            $lines[3] = sprintf("%s %s", $this->getPostalCode(), $this->getCity());
        } else {
            $lines[3] = sprintf("%s-%s %s", $this->getCountry(), $this->getPostalCode(), $this->getCity());
        }

        if ($forReceipt) {
            $lines = self::clearMultilines($lines);
        }

        return implode("\n", $lines);
    }

    public function getQrCodeData(): array
    {
        return [
            $this->getCity() ? self::ADDRESS_TYPE : '',
            $this->getName(),
            $this->getStreet(),
            $this->getBuildingNumber(),
            $this->getPostalCode(),
            $this->getCity(),
            $this->getCountry()
        ];
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank(),
            new Assert\Length([
                'max' => 70
            ])
        ]);

        $metadata->addPropertyConstraints('street', [
            new Assert\Length([
                'max' => 70
            ])
        ]);

        $metadata->addPropertyConstraints('buildingNumber', [
            new Assert\Length([
                'max' => 16
            ])
        ]);

        $metadata->addPropertyConstraints('postalCode', [
            new Assert\NotBlank(),
            new Assert\Length([
                'max' => 16
            ])
        ]);

        $metadata->addPropertyConstraints('city', [
            new Assert\NotBlank(),
            new Assert\Length([
                'max' => 35
            ])
        ]);

        $metadata->addPropertyConstraints('country', [
            new Assert\NotBlank(),
            new Assert\Country()
        ]);
    }
}
