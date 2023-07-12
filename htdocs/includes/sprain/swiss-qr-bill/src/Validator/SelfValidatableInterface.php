<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\Validator;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @internal
 */
interface SelfValidatableInterface
{
    public function getViolations(): ConstraintViolationListInterface;

    public function isValid(): bool;

    public static function loadValidatorMetadata(ClassMetadata $metadata): void;
}
