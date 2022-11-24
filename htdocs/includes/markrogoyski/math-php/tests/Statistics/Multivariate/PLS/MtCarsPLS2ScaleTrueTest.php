<?php

namespace MathPHP\Tests\Statistics\Multivariate\PLS;

use MathPHP\Exception;
use MathPHP\LinearAlgebra\Matrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\SampleData;
use MathPHP\Statistics\Multivariate\PLS;

class MtCarsPLS2ScaleTrueTest extends \PHPUnit\Framework\TestCase
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
     *   X = mtcars[,c(2:3, 5:7, 10:11)]
     *   Y = mtcars[,c(1,4)]
     *   pls.model = pls2_nipals(X, Y, 2, scale=TRUE)
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

        // exclude mpg and hp.
        self::$X = $continuous->columnExclude(3)->columnExclude(0);

        // mpg and hp, columns 0 and 3.
        self::$Y = $continuous
            ->columnExclude(2)
            ->columnExclude(1)
            ->submatrix(0, 0, $continuous->getM() - 1, 1);
        self::$pls = new PLS(self::$X, self::$Y, 2, true);
    }

    /**
     * @test         Construction
     * @throws       Exception\MathException
     */
    public function testConstruction()
    {
        // When
        $pls = new PLS(self::$X, self::$Y, 2, true);

        // Then
        $this->assertInstanceOf(PLS::class, $pls);
    }

    /**
     * @test Construction error - row mismatch
     */
    public function testConstructionFailureXAndYRowMismatch()
    {
        // Given
        $Y = self::$Y->rowExclude(0);

        // Then
        $this->expectException(\MathPHP\Exception\BadDataException::class);

        // When
        $pls = new PLS(self::$X, $Y, 2, true);
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
            [-0.2143731,  0.22289146],
            [-0.2105791,  0.20363413],
            [ 0.1588566, -0.05241863],
            [-0.2034550,  0.14419053],
            [ 0.1246612, -0.26901292],
            [ 0.1007163,  0.07176686],
            [-0.1473158,  0.29083061],
        ];

        // When
        $B = self::$pls->getCoefficients()->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $B, .00001, '');
    }

    public function testC()
    {
        // Given.
        $expected = [
            [ 0.454770, 0.03737499],
            [-0.430135, 0.25598916],
        ];

        // When
        $C = self::$pls->getYLoadings()->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $C, .00001, '');
    }

    public function testP()
    {
        // Given.
        $expected = [
            [-0.4830909,  0.008542336],
            [-0.4731801, -0.101398021],
            [ 0.3706942,  0.362571565],
            [-0.4418500, -0.185442628],
            [ 0.2779994, -0.501504936],
            [ 0.2450876,  0.576659047],
            [-0.2948947,  0.495757659],
        ];

        // When
        $P = self::$pls->getProjection()->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $P, .00001, '');
    }

    public function testT()
    {
        // Given.
        $expected = [
            [0.3204847, 1.30386443],
            [0.3067005, 1.0981707],
            [2.2145883, -0.35041917],
            [0.169134, -1.9075156],
            [-1.4608329, -0.78730905],
            [0.1298623, -2.38042406],
            [-2.1455482, 0.21474065],
            [1.6070201, -0.67486532],
            [2.2992852, -1.3660906],
            [0.2666728, 0.64453581],
            [0.3730258, 0.47038762],
            [-1.632483, -0.74293298],
            [-1.4463028, -0.74344181],
            [-1.3975669, -0.86800244],
            [-3.099056, -0.85271805],
            [-3.1174811, -0.7932455],
            [-2.9400458, -0.521798],
            [2.668381, -0.43889341],
            [3.055, 0.79125128],
            [3.0191218, -0.41494088],
            [2.026571, -1.65106247],
            [-1.6002063, -1.00743915],
            [-1.1991955, -0.8746524],
            [-1.9911201, 0.61920095],
            [-1.8270286, -0.90357751],
            [2.6837019, -0.22856777],
            [2.2680306, 1.69918659],
            [2.2787298, 1.32962432],
            [-1.0916947, 2.85867373],
            [-0.2461754, 2.80033238],
            [-2.3811881, 3.61645679],
            [1.8896156, 0.06147091],
        ];

        // When
        $T = self::$pls->getXScores()->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $T, .00001, '');
    }

    public function testU()
    {
        // Given.
        $expected = [
            [0.29878003, -0.1196459],
            [0.29878003, -0.12014881],
            [0.54125196, -0.10284985],
            [0.32896247, -0.12268734],
            [-0.28255201, 0.04378714],
            [0.11132526, -0.16325269],
            [-1.05370979, 0.2528747],
            [0.85646285, -0.23083751],
            [0.52870479, -0.09229244],
            [0.08140245, -0.08423422],
            [-0.02423609, -0.08903582],
            [-0.48746897, 0.04192974],
            [-0.41955848, 0.05430363],
            [-0.57801629, 0.04305899],
            [-1.0970452, 0.04455559],
            [-1.15978105, 0.08121978],
            [-0.92942358, 0.17036369],
            [1.43501732, -0.12756913],
            [1.37193531, -0.1781371],
            [1.55447506, -0.10920417],
            [0.41806469, -0.10283672],
            [-0.36717192, -0.07448308],
            [-0.38980875, -0.06171276],
            [-1.12916589, 0.25230763],
            [-0.24482396, 0.03352729],
            [1.0501912, -0.15863685],
            [0.79525865, -0.08852311],
            [0.98924664, 0.02129312],
            [-1.05972375, 0.37156526],
            [-0.20709591, 0.09430472],
            [-1.56551315, 0.58464612],
            [0.33523606, -0.06364992],
        ];

        // When
        $U = self::$pls->getYScores()->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $U, .00001, '');
    }

    public function testW()
    {
        // Given.
        $expected = [
            [-0.4770668,  0.01413703],
            [-0.4643040, -0.03817455],
            [ 0.3217142,  0.37286624],
            [ -0.4337710, -0.21556426],
            [ 0.3167445, -0.48216394],
            [ 0.1743495,  0.59339427],
            [-0.3666701,  0.47775186],
        ];

        // When
        $W = self::$pls->getXLoadings()->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $W, .00001, '');
    }

    /**
     * R code for expected values:
     *   ones = matrix(1L, nrow = dim(X)[1], ncol = 1)
     *   scale(X) %*% pls.model$B %*% diag(apply(Y, 2, sd)) + ones %*% colMeans(Y)
     *
     * @test         predict Y values from X
     * @dataProvider dataProviderForRegression
     * @param        array $X
     * @param        array $Y
     */
    public function testRegression($X, $expected)
    {
        // Given.
        $input = MatrixFactory::create($X);

        // When
        $actual = self::$pls->predict($input)->getMatrix();

        // Then
        $this->assertEqualsWithDelta($expected, $actual, .00001, '');
    }

    public function dataProviderForRegression()
    {
        return [
            [
                [[6, 160, 3.9, 2.62, 16.46, 4, 4]],
                [[21.26274, 160.12058]],
            ],
            [
                [[6, 160, 3.9, 2.875, 17.02, 4, 4]],
                [[21.17862, 156.91689]],
            ]
        ];
    }

    /**
     * @test predict error if the input X columns do not match
     */
    public function testPredictDataColumnMisMatch()
    {
        // Given
        $X = MatrixFactory::create([[6, 160, 3.9, 2.62, 16.46]]);

        // Then
        $this->expectException(\MathPHP\Exception\BadDataException::class);

        // When
        $prediction = self::$pls->predict($X);
    }
}
