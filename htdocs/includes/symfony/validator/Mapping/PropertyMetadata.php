<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Mapping;

use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Stores all metadata needed for validating a class property.
 *
 * The value of the property is obtained by directly accessing the property.
 * The property will be accessed by reflection, so the access of private and
 * protected properties is supported.
 *
 * This class supports serialization and cloning.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @see PropertyMetadataInterface
 */
class PropertyMetadata extends MemberMetadata
{
    /**
     * @param string $class The class this property is defined on
     * @param string $name  The name of this property
     *
     * @throws ValidatorException
     */
    public function __construct(string $class, string $name)
    {
        if (!property_exists($class, $name)) {
            throw new ValidatorException(sprintf('Property "%s" does not exist in class "%s".', $name, $class));
        }

        parent::__construct($class, $name, $name);
    }

    public function getPropertyValue(mixed $object): mixed
    {
        $reflProperty = $this->getReflectionMember($object);

        if ($reflProperty->hasType() && !$reflProperty->isInitialized($object)) {
            // There is no way to check if a property has been unset or if it is uninitialized.
            // When trying to access an uninitialized property, __get method is triggered.

            // If __get method is not present, no fallback is possible
            // Otherwise we need to catch an Error in case we are trying to access an uninitialized but set property.
            if (!method_exists($object, '__get')) {
                return null;
            }

            try {
                return $reflProperty->getValue($object);
            } catch (\Error) {
                return null;
            }
        }

        return $reflProperty->getValue($object);
    }

    protected function newReflectionMember(object|string $objectOrClassName): \ReflectionMethod|\ReflectionProperty
    {
        $originalClass = \is_string($objectOrClassName) ? $objectOrClassName : $objectOrClassName::class;

        while (!property_exists($objectOrClassName, $this->getName())) {
            $objectOrClassName = get_parent_class($objectOrClassName);

            if (false === $objectOrClassName) {
                throw new ValidatorException(sprintf('Property "%s" does not exist in class "%s".', $this->getName(), $originalClass));
            }
        }

        return new \ReflectionProperty($objectOrClassName, $this->getName());
    }
}
