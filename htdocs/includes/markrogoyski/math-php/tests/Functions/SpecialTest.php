<?php

namespace MathPHP\Tests\Functions;

use MathPHP\Functions\Special;
use MathPHP\Exception;

class SpecialTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         signum/sgn returns the expected value
     * @dataProvider dataProviderForSignum
     * @param        $x
     * @param        $sign
     */
    public function testSignum($x, $sign)
    {
        // When
        $signum = Special::signum($x);
        $sgn    = Special::sgn($x);

        // Then
        $this->assertEquals($sign, $signum);
        $this->assertEquals($sign, $sgn);
    }

    public function dataProviderForSignum(): array
    {
        return [
            [ 0, 0 ],
            [ 1, 1 ],
            [ 0.5, 1 ],
            [ 1.5, 1 ],
            [ 4, 1 ],
            [ 123241.342, 1 ],
            [ -1, -1 ],
            [ -0.5, -1 ],
            [ -1.5, -1 ],
            [ -4, -1 ],
            [ -123241.342, -1 ],
        ];
    }

    /**
     * @test         gamma returns the expected value
     * @dataProvider dataProviderForGamma
     * @param        $z
     * @param        $Γ
     * @throws       \Exception
     */
    public function testGamma($z, $Γ)
    {
        // When
        $gamma = Special::gamma($z);

        // Then
        $this->assertEqualsWithDelta($Γ, $gamma, 0.001);
    }

    /**
     * Test data created with R gamma(z) and online calculator https://keisan.casio.com/exec/system/1180573444
     * @return array (z, Γ)
     */
    public function dataProviderForGamma(): array
    {
        return [
            [0.1, 9.51350769866873183629],
            [0.2, 4.5908437119988030532],
            [0.3, 2.99156898768759062831],
            [0.4, 2.21815954375768822306],
            [0.5, 1.772453850905516027298],
            [0.6, 1.489192248812817102394],
            [0.7, 1.29805533264755778568],
            [0.8, 1.16422971372530337364],
            [0.9, 1.0686287021193193549],
            [1,   1],
            [1.0, 1],
            [1.1, 0.951350769866873183629],
            [1.2, 0.91816874239976061064],
            [1.3, 0.89747069630627718849],
            [1.4, 0.88726381750307528922],
            [1.5, 0.88622692545275801365],
            [1.6, 0.89351534928769026144],
            [1.7, 0.90863873285329044998],
            [1.8, 0.9313837709802426989091],
            [1.9, 0.96176583190738741941],
            [2,   1],
            [2.0, 1],
            [3,   2],
            [3.0, 2],
            [4,   6],
            [4.0, 6],
            [5,   24],
            [5.0, 24],
            [6,   120],
            [6.0, 120],
            [7, 720],
            [8, 5040],
            [9, 40320],
            [10, 362880],
            [11, 3628800],
            [12, 39916800],
            [13, 479001600],
            [14, 6227020800],
            [15, 87178291200],
            [16, 1307674368000],
            [17, 20922789888000],
            [18, 355687428096000],
            [19, 6402373705728000],
            [20, 121645100408832000],
            [2.5, 1.32934038817913702047],
            [5.324, 39.54287866273389258523],
            [10.2, 570499.02784103598123],
            [-0.1, -10.686287021193193549],
            [-0.4, -3.72298062203204275599],
            [-1.1, 9.7148063829029032263],
            [-1.2, 4.8509571405220973902],
            [-1.9, 5.563455],
            [-1.99, 50.47083],
            [-1.999, 500.4623],
            [-1.9999, 5000.461],
            [-1.99999, 50000.4614015337837734],
            [1E-207, 1E207],
            [2E-308, \INF],
            [-2E-309, -\INF],
            [-172.25, 0],
            [-168.0000000000001, -3.482118E-290],
            [-167.9999999999999, 3.482118E-290],
        ];
    }

    /**
     * @test gamma throws an NanException if gamma is undefined.
     * @dataProvider dataProviderUndefinedGamma
     */
    public function testGammaExceptionUndefined($x)
    {
        // Then
        $this->expectException(Exception\NanException::class);

        // When
        Special::gamma($x);
    }

    public function dataProviderUndefinedGamma()
    {
        return [
            [0],
            [0.0],
            [-1],
            [-2],
            [-2.0],
            [-3],
            [-4],
            [-10],
            [-20],
        ];
    }

    /**
     * @test         gamma of large values
     * @dataProvider dataProviderForGammaLargeValues
     * @param        $z
     * @param        $Γ
     * @param        float $ε - relative error
     * @throws       \Exception
     */
    public function testGammaLargeValues($z, $Γ, float $ε = 1E-10)
    {
        // When
        $gamma = Special::gamma($z);

        // Then
        $this->assertEqualsWithDelta($Γ, $gamma, $Γ * $ε);
    }

    /**
     * Test data created with high-precision online calculator: https://keisan.casio.com/exec/system/1180573444
     * @return array (z, Γ, ε)
     */
    public function dataProviderForGammaLargeValues(): array
    {
        return [
            [15, 87178291200],
            [20, 121645100408832000],
            [50, 6.082818640342675608723E+62],
            [100, 9.33262154439441526817E+155],
            [100.6, 1.477347552911177316693E+157],
            [171, 7.257415615307998967397E+306],
            [200, 3.943289336823952517762E+372],
        ];
    }

    /**
     * @test         gammaLanczos returns the expected value
     * @dataProvider dataProviderForGammaLanczos
     * @param        $z
     * @param        $Γ
     * @throws       \Exception
     */
    public function testGammaLanczos($z, $Γ)
    {
        // When
        $gammaLanczos = Special::gammaLanczos($z);

        // Then
        $this->assertEqualsWithDelta($Γ, $gammaLanczos, 0.001);
    }

    public function dataProviderForGammaLanczos(): array
    {
        return [
            [0.1, 9.51350769866873183629],
            [0.2, 4.5908437119988030532],
            [0.3, 2.99156898768759062831],
            [0.4, 2.21815954375768822306],
            [0.5, 1.772453850905516027298],
            [0.6, 1.489192248812817102394],
            [0.7, 1.29805533264755778568],
            [0.8, 1.16422971372530337364],
            [0.9, 1.0686287021193193549],
            [1,   1],
            [1.0, 1],
            [1.1, 0.951350769866873183629],
            [1.2, 0.91816874239976061064],
            [1.3, 0.89747069630627718849],
            [1.4, 0.88726381750307528922],
            [1.5, 0.88622692545275801365],
            [1.6, 0.89351534928769026144],
            [1.7, 0.90863873285329044998],
            [1.8, 0.9313837709802426989091],
            [1.9, 0.96176583190738741941],
            [2,   1],
            [2.0, 1],
            [3,   2],
            [3.0, 2],
            [4,   6],
            [4.0, 6],
            [5,   24],
            [5.0, 24],
            [6,   120],
            [6.0, 120],
            [2.5, 1.32934038817913702047],
            [5.324, 39.54287866273389258523],
            [10.2, 570499.02784103598123],
            [0, \INF],
            [0.0, \INF],
            [-1, -\INF],
            [-2, -\INF],
            [-2.0, -\INF],
            [-0.1, -10.686287021193193549],
            [-0.4, -3.72298062203204275599],
            [-1.1, 9.7148063829029032263],
            [-1.2, 4.8509571405220973902],
        ];
    }

    /**
     * @test         gammaStirling returns the expected value
     * @dataProvider dataProviderForGammaStirling
     * @param        $n
     * @param        $Γ
     * @throws       \Exception
     */
    public function testGammaStirling($n, $Γ)
    {
        // When
        $gammaSterling = Special::gammaStirling($n);

        // Then
        $this->assertEqualsWithDelta($Γ, $gammaSterling, 0.01);
    }

    public function dataProviderForGammaStirling(): array
    {
        return [
            [ 1, 1 ],
            [ 1.0, 1 ],
            [ 2, 1 ],
            [ 3, 2 ],
            [ 4, 6 ],
            [ 5, 24 ],
            [ 6, 120 ],
            [ 1.1, 0.951350769866873183629 ],
            [ 1.2, 0.91816874239976061064 ],
            [ 1.5, 0.88622692545275801365 ],
            [ 2.5, 1.32934038817913702047 ],
            [ 5.324, 39.54287866273389258523 ],
            [ 10.2, 570499.02784103598123 ],
            [ 0, \INF ],
            [ -1, -\INF ],
            [ -2, -\INF ],
            [ -2.0, -\INF ],
        ];
    }

    /**
     * @test         logGamma returns the expected value
     * @dataProvider dataProviderForLogGamma
     * @param        $z
     * @param        $Γ
     * @throws       \Exception
     */
    public function testLogGamma($z, $Γ)
    {
        // When
        $log_gamma = Special::logGamma($z);

        // Then
        $this->assertEqualsWithDelta($Γ, $log_gamma, abs($Γ) * 0.00001);
    }

    /**
     * Test data produced with R function lgamma(x)
     * @return array
     */
    public function dataProviderForLogGamma(): array
    {
        return [
            [-2, \INF],
            [-1.9, 1.716219],
            [-1.5, 0.860047],
            [-1, \INF],
            [-0.1, 2.368961],
            [0, \INF],
            [0.1, 2.252713],
            [0.5, 0.5723649],
            [1, 0],
            [1.0, 0],
            [2, 0],
            [2.1, 0.04543774],
            [2.5, 0.2846829],
            [3, 0.6931472],
            [10, 12.80183],
            [100, 359.1342],
            [1000, 5905.22],
            [10000, 82099.72],
            [100000, 1051288],
            [5E-307, 705.2842],
            [2.6E305, \INF],
            [2E17, 7.767419e+18],
            [4934770, 71118994],
            [-.9, 2.358073],
            [-11.2, -16.31644474],
        ];
    }

    /**
     * @test logGamma returns NaNException appropriately
     */
    public function testLogGammaNan()
    {
        // Given
        $nan = acos(1.01);

        // Then
        $this->expectException(Exception\NanException::class);

        // When
        $nan = Special::logGamma($nan);
    }

     /**
     * @test gamma returns NaNException appropriately
     */
    public function testGammaNan()
    {
        // Given
        $nan = \NAN;

        // Then
        $this->expectException(Exception\NanException::class);

        // When
        $nan = Special::gamma($nan);
    }

    /**
     * @test         beta returns the expected value
     * @dataProvider dataProviderForBeta
     * @param        float $x
     * @param        float $y
     * @param        float $expected
     * @throws       \Exception
     */
    public function testBeta(float $x, float $y, float $expected)
    {
        // When
        $beta = Special::beta($x, $y);
        $β    = Special::β($y, $x);

        // Then
        $this->assertEqualsWithDelta($expected, $beta, 0.0000001);
        $this->assertEqualsWithDelta($expected, $β, 0.0000001);
    }

    /**
     * Test data created with R beta (x, y) and online calculator https://keisan.casio.com/exec/system/1180573394
     * @return array (x, y, β)
     */
    public function dataProviderForBeta(): array
    {
        return [
            [1.5, 0, \INF],
            [0, 1.5, \INF],
            [0, 0, \INF],
            [1, 1, 1],
            [1, 2, 0.5],
            [1, 3, 0.3333333333],
            [1, 4, 0.25],
            [1, 5, 0.2],
            [1, 6, 0.1666667],
            [1, 7, 0.1428571],
            [1, 8, 0.125],
            [1, 9, 0.1111111],
            [1, 10, 0.1],
            [1, 11, 0.09090909],
            [1, 20, 0.05],
            [2, 0, \INF],
            [2, 1, 0.5],
            [2, 2, 0.1666667],
            [2, 3, 0.08333333],
            [2, 4, 0.05],
            [2, 5, 0.03333333],
            [2, 6, 0.02380952],
            [2, 7, 0.01785714],
            [2, 8, 0.01388889],
            [2, 9, 0.01111111],
            [2, 10, 0.009090909],
            [2, 11, 0.007575758],
            [2, 20, 0.002380952],
            [3, 0, \INF],
            [3, 1, 0.3333333],
            [3, 2, 0.08333333],
            [3, 3, 0.03333333],
            [3, 4, 0.01666667],
            [3, 5, 0.00952381],
            [3, 6, 0.005952381],
            [3, 7, 0.003968254],
            [3, 8, 0.002777778],
            [3, 9, 0.002020202],
            [3, 10, 0.001515152],
            [3, 11, 0.001165501],
            [3, 20, 0.0002164502],
            [1.5, 0.2, 4.4776093743471688104],
            [0.2, 1.5, 4.4776093743471688104],
            [0.1, 0.9, 10.16640738463051963162],
            [0.9, 0.1, 10.16640738463051963162],
            [3, 9, 0.002020202],
            [9, 3, 0.002020202],
            [10, 10, 1.082509e-06],
            [20, 20, 7.254445e-13],
            [\INF, 2, 0],
            [2, \INF, 0],
            [1.5, 170.5, 0.0003971962],  // Issue #429
        ];
    }

    /**
     * @testCase     logBeta returns the expected value
     * @dataProvider dataProviderForLogBeta
     */
    public function testLogBeta($x, $y, float $log_beta)
    {
        $this->assertEqualsWithDelta($log_beta, Special::logBeta($x, $y), 0.001);
    }

    public function dataProviderForLogBeta(): array
    {
        return [
            [1.5, 0, \INF],
            [0, 1.5, \INF],
            [0, 0, \INF],
            [1, 1, 0],
            [1, 2, -0.6931472],
            [2, 1, -0.6931472],
            [2, 2, -1.791759],
            [.9, .1, 2.319089],
            [20, 20, -27.95199],
            [1, \INF, -\INF],
            [1, 11, -2.397895],
            [5E-307, 5, 705.2842],
            [94907000, 11, -186.9481],
        ];
    }

    /**
     * @test beta returns NaNException appropriately
     */
    public function testBetaNan()
    {
        // Given
        $nan = \NAN;

        // Then
        $this->expectException(Exception\NanException::class);

        // When
        $beta = Special::beta($nan, 2);
    }

    /**
     * @test logBeta returns NaNException appropriately
     */
    public function testLogBetaNan()
    {
        // Given
        $nan = \NAN;

        // Then
        $this->expectException(Exception\NanException::class);

        // When
        $lbeta = Special::logBeta($nan, 2);
    }

    /**
     * @test logBeta parameters must be greater than 0
     */
    public function testLogBetaOutOfBounds()
    {
        // Given
        $p = -1;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $lbeta = Special::logBeta($p, 2);
    }

    /**
     * @test beta parameters must be greater than 0
     */
    public function testBetaOutOfBounds()
    {
        // Given
        $p = -1;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $beta = Special::beta($p, 2);
    }

    /**
     * @test logGammaCorr parameter must be greater than 10
     */
    public function testLogGammaCorrOutOfBounds()
    {
        // Given
        $x = 1;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $correction = Special::logGammaCorr($x);
    }

    /**
     * @test         multivariateBeta returns the expected value
     * @dataProvider dataProviderForBeta
     * @param        float $x
     * @param        float $y
     * @param        float $expected
     * @throws       \Exception
     */
    public function testMultivariateBeta(float $x, float $y, float $expected)
    {
        // When
        $beta = Special::multivariateBeta([$x, $y]);

        // Then
        $this->assertEqualsWithDelta($expected, $beta, 0.0000001);
    }

    /**
     * @test         logistic returns the expected value
     * @dataProvider dataProviderForLogistic
     * @param        float $x₀
     * @param        float $L
     * @param        float $k
     * @param        float $x
     * @param        float $expected
     */
    public function testLogistic(float $x₀, float $L, float $k, float $x, float $expected)
    {
        // When
        $logistic = Special::logistic($x₀, $L, $k, $x);

        // Then
        $this->assertEqualsWithDelta($expected, $logistic, 0.001);
    }

    public function dataProviderForLogistic(): array
    {
        return [
            [0, 0, 0, 0, 0],
            [1, 1, 1, 1, 0.5],
            [2, 2, 2, 2, 1],
            [3, 2, 2, 2, 0.238405844044],
            [3, 4, 2, 2, 0.476811688088],
            [3, 4, 5, 2, 0.0267714036971],
            [3, 4, 5, 6, 3.99999877639],
            [2.1, 3.2, 1.5, 0.6, 0.305118287677],
            // Test data created with R (sigmoid) logistic(x, k, x₀) where L = 1
            [0, 1, 1, 0, 0.5],
            [0, 1, 1, 1, 0.7310586],
            [1, 1, 1, 0, 0.2689414],
        ];
    }

    /**
     * @test         sigmoid
     * @dataProvider dataProviderForSigmoid
     * @param        float $x
     * @param        float $expected
     */
    public function testSigmoid(float $x, float $expected)
    {
        // When
        $sigmoid = Special::sigmoid($x);

        // Then
        $this->assertEqualsWithDelta($expected, $sigmoid, 0.0000001);
    }

    /**
     * Test data created with R (sigmoid) sigmoid(x)
     * @return array (x, sigmoid)
     */
    public function dataProviderForSigmoid(): array
    {
        return [
            [0, 0.5],
            [1, 0.7310586],
            [2, 0.8807971],
            [3, 0.9525741],
            [4, 0.9820138],
            [5, 0.9933071],
            [6, 0.9975274],
            [10, 0.9999546],
            [13, 0.9999977],
            [15, 0.9999997],
            [16, 0.9999999],
            [17, 1],
            [20, 1],
            [-1, 0.2689414],
            [-2, 0.1192029],
            [-3, 0.04742587],
            [-4, 0.01798621],
            [-5, 0.006692851],
            [-6, 0.002472623],
            [-10, 4.539787e-05],
            [-15, 3.059022e-07],
            [-20, 2.061154e-09],
            [0.5, 0.6224593],
            [5.5, 0.9959299],
            [-0.5, 0.3775407],
            [-5.5, 0.004070138],
        ];
    }

    /**
     * @test sigmoid returns the expected value
     * Sigmoid is just a special case of the logistic function.
     */
    public function testSigmoidSpecialCaseOfLogisitic()
    {
        $this->assertEquals(Special::logistic(1, 1, 1, 2), Special::sigmoid(1));
        $this->assertEquals(Special::logistic(1, 1, 2, 2), Special::sigmoid(2));
        $this->assertEquals(Special::logistic(1, 1, 3, 2), Special::sigmoid(3));
        $this->assertEquals(Special::logistic(1, 1, 4, 2), Special::sigmoid(4));
        $this->assertEquals(Special::logistic(1, 1, 4.6, 2), Special::sigmoid(4.6));
    }


    /**
     * @test         errorFunction returns the expected value
     * @dataProvider dataProviderForErrorFunction
     * @param        float $x
     * @param        float $expected
     */
    public function testErrorFunction(float $x, float $expected)
    {
        // When
        $errorFunction = Special::errorFunction($x);
        $erf           = Special::erf($x);

        // Then
        $this->assertEqualsWithDelta($expected, $errorFunction, 0.0001);
        $this->assertEqualsWithDelta($expected, $erf, 0.0001);
    }

    /**
     * Test data created with R (VGAM) erf(x) and online calculator https://keisan.casio.com/exec/system/1180573449
     * @return array (x, erf)
     */
    public function dataProviderForErrorFunction(): array
    {
        return [
            [0, 0],
            [1, 0.8427007929497148693412],
            [-1, -0.8427007929497148693412],
            [2, 0.9953222650189527341621],
            [3.4, 0.9999984780066371377146],
            [0.154, 0.1724063976196591819236],
            [-2.31, -0.9989124231037000500402],
            [-1.034, -0.856340111375020118952],
            [3, 0.9999779],
            [4, 1],
            [5, 1],
            [10, 1],
            [-2, -0.9953223],
            [-3, -0.9999779],
            [-4, -1],
            [-5, -1],
            [-10, -1],
        ];
    }

    /**
     * @test         complementaryErrorFunction returns the expected value
     * @dataProvider dataProviderForComplementaryErrorFunction
     * @param        float $x
     * @param        float $expected
     */
    public function testComplementaryErrorFunction(float $x, float $expected)
    {
        // When
        $complementaryErrorFunction = Special::complementaryErrorFunction($x);
        $efc                        = Special::erfc($x);

        // Then
        $this->assertEqualsWithDelta($expected, $complementaryErrorFunction, 0.0001);
        $this->assertEqualsWithDelta($expected, $efc, 0.0001);
    }

    /**
     * Test data created with R (VGAM) erfc and online calculator https://keisan.casio.com/exec/system/1180573449
     * @return array (x, erfc)
     */
    public function dataProviderForComplementaryErrorFunction(): array
    {
        return [
            [0, 1],
            [1, 0.1572992070502851306588],
            [-1, 1.842700792949714869341],
            [2, 0.004677734981047265837931],
            [3.4, 1.521993362862285361757E-6],
            [0.154, 0.8275936023803408180764],
            [-2.31, 1.99891242310370005004],
            [-1.034, 1.856340111375020118952],
            [3, 2.20905e-05],
            [4, 1.541726e-08],
            [5, 1.53746e-12],
            [10, 2.088488e-45],
            [-2, 1.995322],
            [-3, 1.999978],
            [-4, 2],
            [-5, 2],
            [-10, 2],
        ];
    }

    /**
     * @test         lowerIncompleteGamma returns the expected value
     * @dataProvider dataProviderForLowerIncompleteGamma
     * @param        float $s
     * @param        float $x
     * @param        float $lig
     */
    public function testLowerIncompleteGamma(float $s, float $x, float $lig)
    {
        // When
        $lowerIncompleteGamma = Special::lowerIncompleteGamma($s, $x);

        // Then
        $this->assertEqualsWithDelta($lig, $lowerIncompleteGamma, 0.00001);
    }

    /**
     * Test data created with R (pracma) gammainc(x, a)
     * @return array (s, x, gamma)
     */
    public function dataProviderForLowerIncompleteGamma(): array
    {
        return [
            [0.0001, 1, 9999.2034895],
            [1, 1, 0.6321206],
            [1, 2, 0.8646647],
            [2, 2, 0.5939942],
            [3, 2.5, 0.9123738],
            [3.5, 2, 0.7318770],
            [4.6, 2, 1.07178785],
            [4, 2.6, 1.5839901],
            [2.7, 2.6, 0.8603568],
            [1.5, 2.5, 0.7339757],
            [0.5, 4, 1.764162782],
            [2, 3, 0.8008517],
            [4.5, 2.3, 1.5389745],
            [7, 9.55, 603.9624331],
            [1, 3, 0.95021293],
            [0.5, 0.5, 1.2100356],
            [0.5, 1, 1.4936483],
            [0.5, 4, 1.764162782],
            // Negative x it not officially supported, but sometimes works.
            // Other times it gets NAN.
            [1, -1, -1.718282],
            [1, -2, -6.389056],
            [2, -2, 8.389056],
            [3, -2.5, -37.59311],
            [4, -2.6, 98.84594],
            [2, -3, 41.17107],
            [1, -3, -19.08554],
        ];
    }

    /**
     * @test         lowerIncompleteGamma returns 0 for x = 0, and s and x = 0
     * @dataProvider dataProviderForLowerIncompleteGammaZeroBoundary
     * @param        float $s
     * @param        float $x
     */
    public function testLowerIncompleteGammaZeroBoundary(float $s, float $x)
    {
        // When
        $lowerIncompleteGamma = Special::lowerIncompleteGamma($s, $x);

        // Then
        $this->assertEqualsWithDelta(0, $lowerIncompleteGamma, 0.00001);
    }

    /**
     * @return array (s, x)
     */
    public function dataProviderForLowerIncompleteGammaZeroBoundary(): array
    {
        return [
            [0, 0],
            [1, 0],
            [2, 0],
            [100, 0]
        ];
    }

    /**
     * @test         lowerIncompleteGamma returns NAN for x = 0 and x nonzero
     * @dataProvider dataProviderForLowerIncompleteGammaNanBoundary
     * @param        float $s
     * @param        float $x
     */
    public function testLowerIncompleteGammaNanBoundary(float $s, float $x)
    {
        // When
        $lowerIncompleteGamma = Special::lowerIncompleteGamma($s, $x);

        // Then
        $this->assertNan($lowerIncompleteGamma);
    }

    /**
     * @return array (s, x)
     */
    public function dataProviderForLowerIncompleteGammaNanBoundary(): array
    {
        return [
            [0, 1],
            [0, 2],
            [0, 10],
            [0, 1000]
        ];
    }

    /**
     * @test         upperIncompleteGamma returns the expected value
     * @dataProvider dataProviderForUpperIncompleteGamma
     * @param        float $s
     * @param        float $x
     * @param        float $uig
     * @throws       \Exception
     */
    public function testUpperIncompleteGamma(float $s, float $x, float $uig)
    {
        // When
        $upperIncompleteGamma = Special::upperIncompleteGamma($s, $x);

        // Then
        $this->assertEqualsWithDelta($uig, $upperIncompleteGamma, 0.00001);
    }

    /**
     * Test data created with R (pracma) gammainc(x, a)
     * @return array (s, x, gamma)
     */
    public function dataProviderForUpperIncompleteGamma(): array
    {
        return [
            [0.0001, 1, 0.21939372],
            [1, 1, 0.3678794],
            [1, 2, 0.1353353],
            [2, 2, 0.40600585],
            [3, 2.5, 1.08762623],
            [3.5, 2, 2.59147401],
            [4.6, 2, 12.30949802],
            [4, 2.6, 4.41600987],
            [2.7, 2.6, 0.68432904],
            [1.5, 2.5, 0.15225125],
            [0.5, 4, 0.008291069],
            [2, 3, 0.1991483],
            [4.5, 2.3, 10.0927539],
            [7, 9.55, 116.0375669],
            [0.5, 0.5, 0.5624182],
            [0.5, 1, 0.2788056],
            [0.5, 4, 0.008291069],
        ];
    }

    /**
     * @test         regularizedLowerIncompleteGamma
     * @dataProvider dataProviderForRegularizedLowerIncompleteGamma
     * @param        float $s
     * @param        float $x
     * @param        float $expected
     * @throws       \Exception
     */
    public function testRegularizedLowerIncompleteGamma(float $s, float $x, float $expected)
    {
        // When
        $gammainc = Special::regularizedLowerIncompleteGamma($s, $x);

        // Then
        $this->assertEqualsWithDelta($expected, $gammainc, 0.00001);
    }

    public function dataProviderForRegularizedLowerIncompleteGamma(): array
    {
        return [
            [0.0001, 1, 0.9999781],

            [1, 1, 0.6321206],
            [1, 2, 0.8646647],
            [2, 2, 0.5939942],
            [3, 2.5, 0.4561869],
            [3.5, 2, 0.2202226],
            [4.6, 2, 0.08009603],
            [4, 2.6, 0.2639984],
            [2.7, 2.6, 0.5569785],
            [1.5, 2.5, 0.8282029],
            [0.5, 4, 0.995322265],
            [2, 3, 0.8008517],
            [4.5, 2.3, 0.1323083],
            [7, 9.55, 0.8388367],
            [1, 3, 0.95021293],
            [0.5, 0.5, 0.6826895],
            [0.5, 1, 0.8427008],
            [0.5, 4, 0.995322265],
        ];
    }

    /**
     * @test         regularizedIncompleteBeta returns the expected value
     * @dataProvider dataProviderForRegularizedIncompleteBeta
     * @param        float $x
     * @param        float $a
     * @param        float $b
     * @param        float $rib
     * @throws       \Exception
     */
    public function testRegularizedIncompleteBeta(float $x, float $a, float $b, float $rib)
    {
        // When
        $regularizedIncompleteBeta = Special::regularizedIncompleteBeta($x, $a, $b);

        // Then
        $this->assertEqualsWithDelta($rib, $regularizedIncompleteBeta, 0.0000001);
    }

    /**
     * Test data created with Python scipy.special.betainc(a, b, x) and online calculator https://keisan.casio.com/exec/system/1180573396
     * @return array (x, a, b, beta)
     */
    public function dataProviderForRegularizedIncompleteBeta(): array
    {
        return [
            [0.4, 1, 2, 0.64],
            [0.4, 1, 4, 0.87040],
            [0.4, 2, 4, 0.663040],
            [0.4, 4, 4, 0.2897920],
            [0.7, 6, 10, 0.99634748],
            [0.7, 7, 10, 0.99287048],
            [0.44, 3, 8.4, 0.90536083],
            [0.44, 3.5, 8.5, 0.86907356],
            [0.3, 2.5, 4.5, 0.40653902],
            [0.5, 1, 2, 0.750],
            [0.2, 3.4, 2.3, 0.02072722],
            [0.8, 3.4, 2.3, 0.84323132],
            [0.45, 12.45, 3.49, 0.00283809],
            [0.294, 0.23, 2.11, 0.88503883],
            [0.993, 0.23, 2.11, 0.99999612],
            [0.76, 4, 0.3, 0.08350803],
            [0.55, 2, 2.5, 0.67737732],
            [0.55, 2.5, 2, 0.47672251],
            [0.55, 3.5, 2, 0.31772153],
            [0.73, 3.5, 5, 0.97317839],
            [0, 1, 1, 0],
            [0.1, 1, 1, 0.1],
            [0.2, 1, 1, 0.2],
            [0.3, 1, 1, 0.3],
            [0.4, 1, 1, 0.4],
            [0.5, 1, 1, 0.5],
            [0.6, 1, 1, 0.6],
            [0.7, 1, 1, 0.7],
            [0.8, 1, 1, 0.8],
            [0.9, 1, 1, 0.9],
            [1, 1, 1, 1],
            [0, 2, 2, 0],
            [0.1, 2, 2, 0.028000000000000004],
            [0.2, 2, 2, 0.10400000000000002],
            [0.3, 2, 2, 0.21600000000000003],
            [0.4, 2, 2, 0.3520000000000001],
            [0.5, 2, 2, 0.5],
            [0.6, 2, 2, 0.6479999999999999],
            [0.7, 2, 2, 0.7839999999999999],
            [0.8, 2, 2, 0.896],
            [0.9, 2, 2, 0.972],
            [1, 2, 2, 1],
            // SciPy examples - https://docs.scipy.org/doc/scipy/reference/generated/scipy.special.betainc.html#scipy.special.betainc
            [1, 0.2, 3.5, 1],
            [0.5, 1.4, 3.1, 0.8148904036225296],
            [0.4, 2.2, 3.1, 0.49339638807619446],
            // Issue #429
            [0.0041461509490402, 0.5, 170.5, 0.7657225092554765],
            [0.0041461509490402, 1.5, 170.5, 0.29887797299850866],
        ];
    }

    /**
     * @test regularizedIncompleteBeta throws an OutOfBoundsException if a is less than 0
     */
    public function testRegularizedIncompleteBetaExceptionALessThanZero()
    {
        // Given
        $a = -1;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Special::regularizedIncompleteBeta(0.4, $a, 4);
    }

    /**
     * @test regularizedIncompleteBeta throws an OutOfBoundsException if x is out of bounds
     */
    public function testRegularizedIncompleteBetaExceptionXOutOfBounds()
    {
        // Given
        $x = -1;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Special::regularizedIncompleteBeta($x, 4, 4);
    }

    /**
     * @test Github Issue 393 Bug - regularizedIncompleteBeta
     * Reference expected values:
     *   Python scipy.special.betainc(a, b, x)
     *     >>> scipy.special.betainc(274.40782656165, 0.5, 0.99993441100298)
     *     0.8495884744315958
     *   Online calculator https://keisan.casio.com/exec/system/1180573396
     *     0.8495884744316587246283
     */
    public function testIssue393BugInRegularizedIncompleteBeta()
    {
        // Given
        $x = 0.99993441100298;
        $a = 274.40782656165;
        $b = 0.5;

        // And
        $expected = 0.8495884744315958;

        // When
        $betainc = Special::regularizedIncompleteBeta($x, $a, $b);

        // Then
        $this->assertEqualsWithDelta($expected, $betainc, 0.00001);
    }

    /**
     * @test         incompleteBeta returns the expected value
     * @dataProvider dataProviderForIncompleteBeta
     * @param        float $x
     * @param        float $a
     * @param        float $b
     * @param        float $ib
     * @throws       \Exception
     */
    public function testIncompleteBeta(float $x, float $a, float $b, float $ib)
    {
        // When
        $incompleteBeta = Special::incompleteBeta($x, $a, $b);

        // Then
        $this->assertEqualsWithDelta($ib, $incompleteBeta, 0.0001);
    }

    public function dataProviderForIncompleteBeta(): array
    {
        return [
            [0.1, 1, 3, 0.09033333333333333333333],
            [0.4, 1, 3, 0.2613333333333333333333],
            [0.9, 1, 3, 0.333],
            [0.4, 1, 2, 0.32],
            [0.4, 1, 4, 0.2176],
            [0.4, 2, 4, 0.033152],
            [0.4, 4, 4, 0.002069942857142857142857],
            [0.7, 6, 10, 3.31784042288233100233E-5],
            [0.7, 7, 10, 1.23984824870524912587E-5],
            [0.44, 3, 8.4, 0.0022050133154863808046],
            [0.44, 3.5, 8.5, 0.00101547937082370450368],
            [0.3, 2.5, 4.5, 0.00873072257563537808667],
            [0.5, 1, 2, 0.375],
            [0.2, 3.4, 2.3, 9.94015483378346364195E-4],
            [0.8, 3.4, 2.3, 0.040438859297104036187],
            [0.45, 12.45, 3.49, 1.016239733540625974803E-6],
            [0.294, 0.23, 2.11, 3.082589637435583044388],
            [0.993, 0.23, 2.11, 3.48298583651202868119],
            [0.76, 4, 0.3, 0.1692673319857469933301],
            [0.55, 2, 2.5, 0.0774145505281552534703],
            [0.55, 2.5, 2, 0.05448257245698492387678],
            [0.55, 3.5, 2, 0.0201727956188770976315],
            [0.73, 3.5, 5, 0.00553077297647439276549],
        ];
    }

    /**
     * @test upperIncompleteGamma throws an OutOfBoundsException if s is less than 0
     */
    public function testUpperIncompleteGammaExceptionSLessThanZero()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Special::upperIncompleteGamma(-1, 1);
    }

    /**
     * @test generalizedHypergeometric throws a BadParameterException if the parameter count is wrong
     */
    public function testGeneralizedHypergeometricExceptionParameterCount()
    {
        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        Special::generalizedHypergeometric(2, 1, ...[6.464756838, 0.509199496, 0.241379523]);
    }

    /**
     * @test         confluentHypergeometric returns the expected value
     * @dataProvider dataProviderForConfluentHypergeometric
     * @param        float $a
     * @param        float $b
     * @param        float $z
     * @param        float $expected
     * @throws       \Exception
     */
    public function testConfluentHypergeometric(float $a, float $b, float $z, float $expected)
    {
        // Given
        $tol = .000001 * $expected;

        // When
        $actual = Special::confluentHypergeometric($a, $b, $z);

        // Then
        $this->assertEqualsWithDelta($expected, $actual, $tol);
    }

    public function dataProviderForConfluentHypergeometric(): array
    {
        return [
            [6.464756838, 0.509199496, 0.241379523, 6.48114845060471],
            [5.12297443791641, 5.26188297019653, 0.757399855727661, 2.09281409280936],
            [9.89309990528122, 6.92782493869175, 0.71686043176351, 2.73366255092188],
            [8.59824618495037, 6.66955518297157, 0.0293511981644408, 1.03854226944163],
        ];
    }

    /**
     * @test         hypergeometric returns the expected value
     * @dataProvider dataProviderForHypergeometric
     * @param        float $a
     * @param        float $b
     * @param        float $c
     * @param        float $z
     * @param        float $expected
     * @throws       \Exception
     */
    public function testHypergeometric(float $a, float $b, float $c, float $z, float $expected)
    {
        // Given
        $tol = .000001 * $expected;

        // When
        $actual = Special::hypergeometric($a, $b, $c, $z);

        // Then
        $this->assertEqualsWithDelta($expected, $actual, $tol);
    }

    public function dataProviderForHypergeometric(): array
    {
        return [
            [1, 1, 1, .9, 10],
            [1, 1, 1, .8, 5],
            [1, 1, 1, .7, 3.3333333],
            [3, 1.4, 1.5, .926, 1746.206366],
        ];
    }

    /**
     * @test hypergeometric throws an OutOfBoundsException if n is greater than 1
     */
    public function testHypergeometricExceptionNGreaterThanOne()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Special::hypergeometric(1, 1, 1, 1);
    }

    /**
     * @test         softmax returns the expected value
     * @dataProvider dataProviderForSoftmax
     * @param        array $𝐳
     * @param        array $expected
     */
    public function testSoftmax(array $𝐳, array $expected)
    {
        // When
        $σ⟮𝐳⟯ⱼ = Special::softmax($𝐳);

        // Then
        $this->assertEqualsWithDelta($expected, $σ⟮𝐳⟯ⱼ, 0.00001);
        $this->assertEquals(1, \array_sum($σ⟮𝐳⟯ⱼ));
    }

    public function dataProviderForSoftmax(): array
    {
        return [
            // Test data: Wikipedia
            [
                [1, 2, 3, 4, 1, 2, 3],
                [0.023640543021591392, 0.064261658510496159, 0.17468129859572226, 0.47483299974438037, 0.023640543021591392, 0.064261658510496159, 0.17468129859572226],
            ],
            // Test data: http://www.kdnuggets.com/2016/07/softmax-regression-related-logistic-regression.html
            [
                [0.07, 0.22, 0.28],
                [0.29450637, 0.34216758, 0.363332605],
            ],
            [
                [0.35, 0.78, 1.12],
                [0.21290077, 0.32728332, 0.45981591],
            ],
            [
                [-0.33, -0.58, -0.92],
                [0.42860913, 0.33380113, 0.23758974],
            ],
            [
                [-0.39, -0.7, -1.1],
                [0.44941979, 0.32962558, 0.22095463],
            ],
            // Test data: https://martin-thoma.com/softmax/
            [
                [0.1, 0.2],
                [0.47502081, 0.52497919],
            ],
            [
                [-0.1, 0.2],
                [0.42555748, 0.57444252],
            ],
            [
                [0.9, -10],
                [0.999981542, 1.84578933e-05],
            ],
            [
                [0, 10],
                [4.53978687e-05, 0.999954602],
            ],
            // Test data: http://stackoverflow.com/questions/34968722/softmax-function-python
            [
                [3.0, 1.0, 0.2],
                [0.8360188, 0.11314284, 0.05083836],
            ],
            [
                [1, 2, 3],
                [0.09003057, 0.24472847, 0.66524096],
            ],
            [
                [2, 4, 8],
                [0.00242826, 0.01794253, 0.97962921],
            ],
            [
                [3, 5, 7],
                [0.01587624, 0.11731043, 0.86681333],
            ],
            [
                [6, 6, 6],
                [0.33333333, 0.33333333, 0.33333333],
            ],
            [
                [1, 2, 3, 6],
                [0.00626879, 0.01704033, 0.04632042, 0.93037047],
            ],
        ];
    }

    /**
     * @test         chebyshevEval bounds condition error on n
     * @dataProvider dataProviderForChebyshevEvalNOutOfBounds
     * @param        int $n
     */
    public function testChebyshevEvalNOutOfBounds(int $n)
    {
        // Given
        $x = 1;
        $a = [2, 3];

        // And
        $chebyshevEval = new \ReflectionMethod(Special::Class, 'chebyshevEval');
        $chebyshevEval->setAccessible(true);

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $chebyshevEval->invokeArgs(null, [$x, $a, $n]);
    }

    public function dataProviderForChebyshevEvalNOutOfBounds(): array
    {
        return [
            [-2],
            [1001],
        ];
    }

    /**
     * @test         chebyshevEval bounds condition error on x
     * @dataProvider dataProviderForChebyshevEvalXOutOfBounds
     * @param        float $x
     */
    public function testChebyshevEvalXOutOfBounds(float $x): void
    {
        // Given
        $n = 5;
        $a = [2, 3];

        // And
        $chebyshevEval = new \ReflectionMethod(Special::Class, 'chebyshevEval');
        $chebyshevEval->setAccessible(true);

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $chebyshevEval->invokeArgs(null, [$x, $a, $n]);
    }

    public function dataProviderForChebyshevEvalXOutOfBounds(): array
    {
        return [
            [-1.2],
            [1.2],
        ];
    }

    /**
     * @test         stirlingError
     * @dataProvider dataProviderForSterlingError
     * @param        float $n
     * @param        float $expected
     */
    public function testStirlingError(float $n, float $expected): void
    {
        // When
        $stirlingError = Special::stirlingError($n);

        // Then
        $this->assertEqualsWithDelta($expected, $stirlingError, 0.000001);
    }

    /**
     * Test data produces with R library DPQmpfr: stirlerrM(n)
     * @return array
     */
    public function dataProviderForSterlingError(): array
    {
        return [
            [0.5, 0.1534264],
            [1, 0.08106147],
            [1.5, 0.05481412],
            [2, 0.0413407],
            [5, 0.01664469],
            [5.5, 0.01513497],
            [14, 0.00595137],
            [14.5, 0.005746217],
            [15, 0.005554734],

            [0.1, 0.5127401],
            [0.2, 0.3222939],
            [0.9, 0.08958191],
            [1.1, 0.07400292],
            [5.3, 0.0157048],
            [14.7, 0.005668061],

            [15.5, 0.005375599],
            [20, 0.00416632],
            [22.4, 0.003719991],
        ];
    }

    /**
     * @test stirlingError infinity at 0
     */
    public function testStirlingErrorInfinity(): void
    {
        // Given
        $n = 0;

        // When
        $stirlingError = Special::stirlingError($n);

        // Then
        $this->assertInfinite($stirlingError);
    }

    /**
     * @test         stirlingError NAN for negative values
     * @dataProvider dataProviderForSterlingErrorNan
     * @param        float $n
     */
    public function testStirlingErrorNan(float $n): void
    {
        // Then
        $this->expectException(Exception\NanException::class);

        // When
        $stirlingError = Special::stirlingError($n);
    }

    public function dataProviderForSterlingErrorNan(): array
    {
        return [
            [-0.1],
            [-1],
            [-10],
        ];
    }
}
