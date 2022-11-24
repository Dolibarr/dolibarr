<?php

namespace MathPHP\Statistics\Regression\Methods;

use MathPHP\Exception;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\MatrixFactory;

trait WeightedLeastSquares
{
    /**
     * Weighted linear least squares fitting using Matrix algebra (Polynomial).
     *
     * Generalizing from a straight line (first degree polynomial) to a kᵗʰ degree polynomial:
     *  y = a₀ + a₁x + ⋯ + akxᵏ
     *
     * Leads to equations in matrix form:
     *  [n    Σxᵢ   ⋯  Σxᵢᵏ  ] [a₀]   [Σyᵢ   ]
     *  [Σxᵢ  Σxᵢ²  ⋯  Σxᵢᵏ⁺¹] [a₁]   [Σxᵢyᵢ ]
     *  [ ⋮     ⋮    ⋱  ⋮    ] [ ⋮ ] = [ ⋮    ]
     *  [Σxᵢᵏ Σxᵢᵏ⁺¹ ⋯ Σxᵢ²ᵏ ] [ak]   [Σxᵢᵏyᵢ]
     *
     * This is a Vandermonde matrix:
     *  [1 x₁ ⋯ x₁ᵏ] [a₀]   [y₁]
     *  [1 x₂ ⋯ x₂ᵏ] [a₁]   [y₂]
     *  [⋮  ⋮  ⋱ ⋮ ] [ ⋮ ] = [ ⋮]
     *  [1 xn ⋯ xnᵏ] [ak]   [yn]
     *
     * Can write as equation:
     *  y = Xa
     *
     * Solve by premultiplying by transpose Xᵀ:
     *  XᵀWy = XᵀWXa
     *
     * Invert to yield vector solution:
     *  a = (XᵀWX)⁻¹XᵀWy
     *
     * (http://mathworld.wolfram.com/LeastSquaresFittingPolynomial.html)
     *
     * For reference, the traditional way to do least squares:
     *        _ _   __
     *        x y - xy        _    _
     *   m = _________    b = y - mx
     *        _     __
     *       (x)² - x²
     *
     * @param  array $ys y values
     * @param  array $xs x values
     * @param  array $ws weight values
     * @param  int   $order
     *
     * @return NumericMatrix [[m], [b]]
     *
     * @throws Exception\MathException
     */
    public function leastSquares(array $ys, array $xs, array $ws, int $order = 1): NumericMatrix
    {
        // y = Xa
        $X = MatrixFactory::vandermonde($xs, $order + 1);
        $y = MatrixFactory::createFromColumnVector($ys);
        $W = MatrixFactory::diagonal($ws);

        // a = (XᵀWX)⁻¹XᵀWy
        $Xᵀ       = $X->transpose();
        $beta_hat = $Xᵀ->multiply($W)
                       ->multiply($X)
                       ->inverse()
                       ->multiply($Xᵀ)
                       ->multiply($W)
                       ->multiply($y);

        return $beta_hat;
    }
}
