<?php

namespace MathPHP\Tests\Statistics\Multivariate\PLS;

use MathPHP\Exception;
use MathPHP\LinearAlgebra\Matrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\SampleData;
use MathPHP\Statistics\Multivariate\PLS;

class MtCarsPLS1ScaleFalseTest extends \PHPUnit\Framework\TestCase
{
    /** @var PLS */
    private static $pls;

    /** @var Matrix */
    private static $X;

    /** @var Matrix */
    private static $Y;

    /**
     * R code for expected values:
     *   library(chemometrics)
     *   X = mtcars[,c(2:7, 10:11)]
     *   Y = mtcars[,c(1)]
     *   pls.model = pls2_nipals(X, Y, 1)
     *
     * @throws Exception\MathException
     */
    public static function setUpBeforeClass(): void
    {
        $mtCars = new SampleData\MtCars();

        // Remove any categorical variables
        $continuous = MatrixFactory::create($mtCars->getData())
            ->columnExclude(8)
            ->columnExclude(7);
        // Exclude mpg.
        self::$X = $continuous->columnExclude(0);

        // Just grab column 0.
        self::$Y = $continuous->submatrix(0, 0, $continuous->getM() - 1, 0);

        self::$pls = new PLS(self::$X, self::$Y, 1, false);
    }

    /**
     * @test         Construction
     * @throws       Exception\MathException
     */
    public function testConstruction()
    {
        // When
        $pls = new PLS(self::$X, self::$Y, 1, false);

        // Then
        $this->assertInstanceOf(PLS::class, $pls);
    }

    /**
     * @test The class returns the correct values for B
     *
     * R code for expected values:
     *   pls.model$B
     */
    public function testB()
    {
        // Given
        $expected = [
            [-0.0004929300],
            [-0.0340230796],
            [-0.0172363614],
            [0.0001179642],
            [-0.0002749742],
            [0.0002423248],
            [0.0001147732],
            [-0.0002882169],
        ];

        // When
        $B = self::$pls->getCoefficients()->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $B, .00001);
    }
}
