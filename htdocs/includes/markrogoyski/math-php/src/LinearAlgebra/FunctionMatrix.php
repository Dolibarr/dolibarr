<?php

namespace MathPHP\LinearAlgebra;

use MathPHP\Exception;

class FunctionMatrix
{
    /** @var int Number of rows */
    protected $m;

    /** @var int Number of columns */
    protected $n;

    /** @var array[] Matrix array of arrays */
    protected $A;

    /**
     * @param array[] $A of arrays $A m x n matrix
     *
     * @throws Exception\BadDataException if any rows have a different column count
     */
    public function __construct(array $A)
    {
        $this->A       = $A;
        $this->m       = \count($A);
        $this->n       = $this->m > 0 ? \count($A[0]) : 0;

        $this->validateMatrixDimensions();
    }

    /**
     * Validate the matrix is entirely m x n
     *
     * @throws Exception\BadDataException
     */
    protected function validateMatrixDimensions()
    {
        foreach ($this->A as $i => $row) {
            if (\count($row) !== $this->n) {
                throw new Exception\BadDataException("Row $i has a different column count: " . \count($row) . "; was expecting {$this->n}.");
            }
        }
    }

    /**
     * Evaluate
     *
     * @param array $params
     *
     * @return NumericMatrix
     *
     * @throws Exception\BadDataException
     * @throws Exception\IncorrectTypeException
     * @throws Exception\MathException
     * @throws Exception\MatrixException
     */
    public function evaluate(array $params): NumericMatrix
    {
        $m = $this->m;
        $n = $this->n;
        $R = [];
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $func = $this->A[$i][$j];
                $R[$i][$j] = $func($params);
            }
        }
        return MatrixFactory::create($R);
    }
}
