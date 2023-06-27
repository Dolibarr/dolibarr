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

use Symfony\Component\Validator\Constraint;

/**
 * A container for validation metadata.
 *
 * Most importantly, the metadata stores the constraints against which an object
 * and its properties should be validated.
 *
 * Additionally, the metadata stores whether objects should be validated
 * against their class' metadata and whether traversable objects should be
 * traversed or not.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @see CascadingStrategy
 * @see TraversalStrategy
 */
interface MetadataInterface
{
    /**
     * Returns the strategy for cascading objects.
     *
     * @see CascadingStrategy
     */
    public function getCascadingStrategy(): int;

    /**
     * Returns the strategy for traversing traversable objects.
     *
     * @see TraversalStrategy
     */
    public function getTraversalStrategy(): int;

    /**
     * Returns all constraints of this element.
     *
     * @return Constraint[]
     */
    public function getConstraints(): array;

    /**
     * Returns all constraints for a given validation group.
     *
     * @param string $group The validation group
     *
     * @return Constraint[]
     */
    public function findConstraints(string $group): array;
}
