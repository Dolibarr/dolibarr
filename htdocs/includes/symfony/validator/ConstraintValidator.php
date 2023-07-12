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

use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Base class for constraint validators.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class ConstraintValidator implements ConstraintValidatorInterface
{
    /**
     * Whether to format {@link \DateTime} objects, either with the {@link \IntlDateFormatter}
     * (if it is available) or as RFC-3339 dates ("Y-m-d H:i:s").
     */
    public const PRETTY_DATE = 1;

    /**
     * Whether to cast objects with a "__toString()" method to strings.
     */
    public const OBJECT_TO_STRING = 2;

    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    public function initialize(ExecutionContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * Returns a string representation of the type of the value.
     *
     * This method should be used if you pass the type of a value as
     * message parameter to a constraint violation. Note that such
     * parameters should usually not be included in messages aimed at
     * non-technical people.
     */
    protected function formatTypeOf(mixed $value): string
    {
        return get_debug_type($value);
    }

    /**
     * Returns a string representation of the value.
     *
     * This method returns the equivalent PHP tokens for most scalar types
     * (i.e. "false" for false, "1" for 1 etc.). Strings are always wrapped
     * in double quotes ("). Objects, arrays and resources are formatted as
     * "object", "array" and "resource". If the $format bitmask contains
     * the PRETTY_DATE bit, then {@link \DateTime} objects will be formatted
     * with the {@link \IntlDateFormatter}. If it is not available, they will be
     * formatted as RFC-3339 dates ("Y-m-d H:i:s").
     *
     * Be careful when passing message parameters to a constraint violation
     * that (may) contain objects, arrays or resources. These parameters
     * should only be displayed for technical users. Non-technical users
     * won't know what an "object", "array" or "resource" is and will be
     * confused by the violation message.
     *
     * @param int $format A bitwise combination of the format constants in this class
     */
    protected function formatValue(mixed $value, int $format = 0): string
    {
        if (($format & self::PRETTY_DATE) && $value instanceof \DateTimeInterface) {
            if (class_exists(\IntlDateFormatter::class)) {
                $formatter = new \IntlDateFormatter(\Locale::getDefault(), \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, 'UTC');

                return $formatter->format(new \DateTime(
                    $value->format('Y-m-d H:i:s.u'),
                    new \DateTimeZone('UTC')
                ));
            }

            return $value->format('Y-m-d H:i:s');
        }

        if (\is_object($value)) {
            if (($format & self::OBJECT_TO_STRING) && $value instanceof \Stringable) {
                return $value->__toString();
            }

            return 'object';
        }

        if (\is_array($value)) {
            return 'array';
        }

        if (\is_string($value)) {
            return '"'.$value.'"';
        }

        if (\is_resource($value)) {
            return 'resource';
        }

        if (null === $value) {
            return 'null';
        }

        if (false === $value) {
            return 'false';
        }

        if (true === $value) {
            return 'true';
        }

        return (string) $value;
    }

    /**
     * Returns a string representation of a list of values.
     *
     * Each of the values is converted to a string using
     * {@link formatValue()}. The values are then concatenated with commas.
     *
     * @param array $values A list of values
     * @param int   $format A bitwise combination of the format
     *                      constants in this class
     *
     * @see formatValue()
     */
    protected function formatValues(array $values, int $format = 0): string
    {
        foreach ($values as $key => $value) {
            $values[$key] = $this->formatValue($value, $format);
        }

        return implode(', ', $values);
    }
}
