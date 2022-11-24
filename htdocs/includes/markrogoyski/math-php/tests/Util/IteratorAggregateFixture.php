<?php

namespace MathPHP\Tests\Util;

class IteratorAggregateFixture implements \IteratorAggregate
{
    /** @var array */
    private $values;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->values);
    }
}
