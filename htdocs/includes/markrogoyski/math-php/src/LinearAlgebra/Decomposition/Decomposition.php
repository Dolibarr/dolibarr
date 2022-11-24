<?php

namespace MathPHP\LinearAlgebra\Decomposition;

use MathPHP\LinearAlgebra\NumericMatrix;

abstract class Decomposition
{
    abstract public static function decompose(NumericMatrix $M);
}
