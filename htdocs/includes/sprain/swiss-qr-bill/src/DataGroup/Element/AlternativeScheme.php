<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\DataGroup\Element;

use Sprain\SwissQrBill\DataGroup\QrCodeableInterface;
use Sprain\SwissQrBill\Validator\SelfValidatableInterface;
use Sprain\SwissQrBill\Validator\SelfValidatableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * For available alternative schemes see link.
 * @link https://www.paymentstandards.ch/en/home/software-partner/alternative-schemes.html
 */
final class AlternativeScheme implements QrCodeableInterface, SelfValidatableInterface
{
    use SelfValidatableTrait;

    /**
     * Parameter character chain of the alternative scheme
     */
    private string $parameter;

    private function __construct(string $parameter)
    {
        $this->parameter = $parameter;
    }

    public static function create(string $parameter): self
    {
        return new self($parameter);
    }

    public function getParameter(): string
    {
        return $this->parameter;
    }

    public function getQrCodeData(): array
    {
        return [
            $this->getParameter()
        ];
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraints('parameter', [
            new Assert\NotBlank(),
            new Assert\Length([
                'max' => 100
            ])
        ]);
    }
}
