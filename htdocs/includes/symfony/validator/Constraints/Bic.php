<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Constraints;

use Symfony\Component\Intl\Countries;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\LogicException;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Michael Hirschler <michael.vhirsch@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Bic extends Constraint
{
    public const INVALID_LENGTH_ERROR = '66dad313-af0b-4214-8566-6c799be9789c';
    public const INVALID_CHARACTERS_ERROR = 'f424c529-7add-4417-8f2d-4b656e4833e2';
    public const INVALID_BANK_CODE_ERROR = '00559357-6170-4f29-aebd-d19330aa19cf';
    public const INVALID_COUNTRY_CODE_ERROR = '1ce76f8d-3c1f-451c-9e62-fe9c3ed486ae';
    public const INVALID_CASE_ERROR = '11884038-3312-4ae5-9d04-699f782130c7';
    public const INVALID_IBAN_COUNTRY_CODE_ERROR = '29a2c3bb-587b-4996-b6f5-53081364cea5';

    protected const ERROR_NAMES = [
        self::INVALID_LENGTH_ERROR => 'INVALID_LENGTH_ERROR',
        self::INVALID_CHARACTERS_ERROR => 'INVALID_CHARACTERS_ERROR',
        self::INVALID_BANK_CODE_ERROR => 'INVALID_BANK_CODE_ERROR',
        self::INVALID_COUNTRY_CODE_ERROR => 'INVALID_COUNTRY_CODE_ERROR',
        self::INVALID_CASE_ERROR => 'INVALID_CASE_ERROR',
    ];

    /**
     * @deprecated since Symfony 6.1, use const ERROR_NAMES instead
     */
    protected static $errorNames = self::ERROR_NAMES;

    public $message = 'This is not a valid Business Identifier Code (BIC).';
    public $ibanMessage = 'This Business Identifier Code (BIC) is not associated with IBAN {{ iban }}.';
    public $iban;
    public $ibanPropertyPath;

    public function __construct(array $options = null, string $message = null, string $iban = null, string $ibanPropertyPath = null, string $ibanMessage = null, array $groups = null, mixed $payload = null)
    {
        if (!class_exists(Countries::class)) {
            throw new LogicException('The Intl component is required to use the Bic constraint. Try running "composer require symfony/intl".');
        }

        parent::__construct($options, $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->ibanMessage = $ibanMessage ?? $this->ibanMessage;
        $this->iban = $iban ?? $this->iban;
        $this->ibanPropertyPath = $ibanPropertyPath ?? $this->ibanPropertyPath;

        if (null !== $this->iban && null !== $this->ibanPropertyPath) {
            throw new ConstraintDefinitionException('The "iban" and "ibanPropertyPath" options of the Iban constraint cannot be used at the same time.');
        }

        if (null !== $this->ibanPropertyPath && !class_exists(PropertyAccess::class)) {
            throw new LogicException(sprintf('The "symfony/property-access" component is required to use the "%s" constraint with the "ibanPropertyPath" option.', self::class));
        }
    }
}
