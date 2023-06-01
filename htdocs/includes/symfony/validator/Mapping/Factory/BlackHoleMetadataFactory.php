<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Mapping\Factory;

use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Mapping\MetadataInterface;

/**
 * Metadata factory that does not store metadata.
 *
 * This implementation is useful if you want to validate values against
 * constraints only and you don't need to add constraints to classes and
 * properties.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class BlackHoleMetadataFactory implements MetadataFactoryInterface
{
    public function getMetadataFor(mixed $value): MetadataInterface
    {
        throw new LogicException('This class does not support metadata.');
    }

    public function hasMetadataFor(mixed $value): bool
    {
        return false;
    }
}
