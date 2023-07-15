<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator;

use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Entry point for the Validator component.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class Validation
{
    /**
     * Creates a callable chain of constraints.
     */
    public static function createCallable(Constraint|ValidatorInterface $constraintOrValidator = null, Constraint ...$constraints): callable
    {
        $validator = self::createIsValidCallable($constraintOrValidator, ...$constraints);

        return static function ($value) use ($validator) {
            if (!$validator($value, $violations)) {
                throw new ValidationFailedException($value, $violations);
            }

            return $value;
        };
    }

    /**
     * Creates a callable that returns true/false instead of throwing validation exceptions.
     *
     * @return callable(mixed $value, ConstraintViolationListInterface &$violations = null): bool
     */
    public static function createIsValidCallable(Constraint|ValidatorInterface $constraintOrValidator = null, Constraint ...$constraints): callable
    {
        $validator = $constraintOrValidator;

        if ($constraintOrValidator instanceof Constraint) {
            $constraints = \func_get_args();
            $validator = null;
        }

        $validator ??= self::createValidator();

        return static function (mixed $value, ConstraintViolationListInterface &$violations = null) use ($constraints, $validator): bool {
            $violations = $validator->validate($value, $constraints);

            return 0 === $violations->count();
        };
    }

    /**
     * Creates a new validator.
     *
     * If you want to configure the validator, use
     * {@link createValidatorBuilder()} instead.
     */
    public static function createValidator(): ValidatorInterface
    {
        return self::createValidatorBuilder()->getValidator();
    }

    /**
     * Creates a configurable builder for validator objects.
     */
    public static function createValidatorBuilder(): ValidatorBuilder
    {
        return new ValidatorBuilder();
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
