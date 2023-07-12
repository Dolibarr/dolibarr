<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\Validator;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
trait SelfValidatableTrait
{
    private ?ValidatorInterface $validator = null;

    public function getViolations(): ConstraintViolationListInterface
    {
        if (null === $this->validator) {
            $this->validator = Validation::createValidatorBuilder()
                ->addMethodMapping('loadValidatorMetadata')
                ->getValidator();
        }

        return $this->validator->validate($this);
    }

    public function isValid(): bool
    {
        return (0 === $this->getViolations()->count());
    }
}
