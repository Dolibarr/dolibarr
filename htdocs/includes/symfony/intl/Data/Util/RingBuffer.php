<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Intl\Data\Util;

use Symfony\Component\Intl\Exception\OutOfBoundsException;

/**
 * Implements a ring buffer.
 *
 * A ring buffer is an array-like structure with a fixed size. If the buffer
 * is full, the next written element overwrites the first bucket in the buffer,
 * then the second and so on.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @template TKey of array-key
 * @template TValue
 *
 * @implements \ArrayAccess<TKey, TValue>
 *
 * @internal
 */
class RingBuffer implements \ArrayAccess
{
    /** @var array<int, TValue> */
    private array $values = [];
    /** @var array<TKey, int> */
    private array $indices = [];
    private int $cursor = 0;
    private int $size;

    public function __construct(int $size)
    {
        $this->size = $size;
    }

    public function offsetExists(mixed $key): bool
    {
        return isset($this->indices[$key]);
    }

    public function offsetGet(mixed $key): mixed
    {
        if (!isset($this->indices[$key])) {
            throw new OutOfBoundsException(sprintf('The index "%s" does not exist.', $key));
        }

        return $this->values[$this->indices[$key]];
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        if (false !== ($keyToRemove = array_search($this->cursor, $this->indices))) {
            unset($this->indices[$keyToRemove]);
        }

        $this->values[$this->cursor] = $value;
        $this->indices[$key] = $this->cursor;

        $this->cursor = ($this->cursor + 1) % $this->size;
    }

    public function offsetUnset(mixed $key): void
    {
        if (isset($this->indices[$key])) {
            $this->values[$this->indices[$key]] = null;
            unset($this->indices[$key]);
        }
    }
}
