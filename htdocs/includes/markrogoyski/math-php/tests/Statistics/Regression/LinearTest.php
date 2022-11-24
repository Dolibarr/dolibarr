<?php

namespace MathPHP\Tests\Statistics\Regression;

use MathPHP\Statistics\Regression\Linear;

class LinearTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test constructor
     */
    public function testConstructor()
    {
        // Given
        $points = [ [1,2], [2,3], [4,5], [5,7], [6,8] ];

        // When
        $regression = new Linear($points);

        // Then
        $this->assertInstanceOf(\MathPHP\Statistics\Regression\Regression::class, $regression);
        $this->assertInstanceOf(\MathPHP\Statistics\Regression\Linear::class, $regression);
    }

    /**
     * @test getPoints
     */
    public function testGetPoints()
    {
        // Given
        $points = [ [1,2], [2,3], [4,5], [5,7], [6,8] ];
        $regression = new Linear($points);

        // Then
        $this->assertEquals($points, $regression->getPoints());
    }

    /**
     * @test getXs
     */
    public function testGetXs()
    {
        // Given
        $points = [ [1,2], [2,3], [4,5], [5,7], [6,8] ];
        $regression = new Linear($points);

        // Then
        $this->assertEquals([1,2,4,5,6], $regression->getXs());
    }

    /**
     * @test getYs
     */
    public function testGetYs()
    {
        // Given
        $points = [ [1,2], [2,3], [4,5], [5,7], [6,8] ];
        $regression = new Linear($points);

        // Then
        $this->assertEquals([2,3,5,7,8], $regression->getYs());
    }

    /**
     * @test         getEquation - Equation matches pattern y = mx + b
     * @dataProvider dataProviderForEquation
     * @param        array $points
     */
    public function testGetEquation(array $points)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertRegExp('/^y = -?\d+[.]\d+x [+] -?\d+[.]\d+$/', $regression->getEquation());
    }

    /**
     * @return array [points]
     */
    public function dataProviderForEquation(): array
    {
        return [
            [ [ [0,0], [1,1], [2,2], [3,3], [4,4] ] ],
            [ [ [1,2], [2,3], [4,5], [5,7], [6,8] ] ],
            [ [ [4,390], [9,580], [10,650], [14,730], [4,410], [7,530], [12,600], [22,790], [1,350], [3,400], [8,590], [11,640], [5,450], [6,520], [10,690], [11,690], [16,770], [13,700], [13,730], [10,640] ] ],
        ];
    }

    /**
     * @test         getParameters
     * @dataProvider dataProviderForParameters
     * @param        array $points
     * @param        float $m
     * @param        float $b
     */
    public function testGetParameters(array $points, float $m, float $b)
    {
        // Given
        $regression = new Linear($points);

        // When
        $parameters = $regression->getParameters();

        // Then
        $this->assertEqualsWithDelta($m, $parameters['m'], 0.0001);
        $this->assertEqualsWithDelta($b, $parameters['b'], 0.0001);
    }

    /**
     * @return array [points, m, b]
     */
    public function dataProviderForParameters(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                1.2209302325581, 0.60465116279069
            ],
            [
                [ [4,390], [9,580], [10,650], [14,730], [4,410], [7,530], [12,600], [22,790], [1,350], [3,400], [8,590], [11,640], [5,450], [6,520], [10,690], [11,690], [16,770], [13,700], [13,730], [10,640] ],
                25.326467777896, 353.16487949889
            ],
            // Example data from http://reliawiki.org/index.php/Simple_Linear_Regression_Analysis
            [
                [ [50,122], [53,118], [54,128], [55,121], [56,125], [59,136], [62,144], [65,142], [67,149], [71,161], [72,167], [74,168], [75,162], [76,171], [79,175], [80,182], [82,180], [85,183], [87,188], [90,200], [93,194], [94,206], [95,207], [97,210], [100,219] ],
                1.9952, 17.0016
            ],
            // Example data from http://faculty.cas.usf.edu/mbrannick/regression/regbas.html, http://www.alcula.com/calculators/statistics/linear-regression/
            [
                [ [61,105], [62,120], [63,120], [65,160], [65,120], [68,145], [69,175], [70,160], [72,185], [75,210] ],
                6.968085106383, -316.86170212766
            ],
            [
                [ [6,562], [3,421], [6,581], [9,630], [3,412], [9,560], [6,434], [3,443], [9,590], [6,570], [3,346], [9,672] ],
                 34.583333333333, 310.91666666667
            ],
            [
                [ [95,85], [85,95], [80,70], [70,65], [60,70] ],
                0.64383562, 26.780821917808
            ],
            [
                [ [1,1], [2,2], [3,1.3], [4,3.75], [5,2.25] ],
                0.425, 0.785
            ],
        ];
    }

    /**
     * @test         getSampleSize
     * @dataProvider dataProviderForSampleSize
     * @param        array $points
     * @param        int   $n
     */
    public function testGetSampleSize(array $points, int $n)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEquals($n, $regression->getSampleSize());
    }

    /**
     * @return array [points, n]
     */
    public function dataProviderForSampleSize(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ], 5
            ],
            [
                [ [4,390], [9,580], [10,650], [14,730], [4,410], [7,530], [12,600], [22,790], [1,350], [3,400], [8,590], [11,640], [5,450], [6,520], [10,690], [11,690], [16,770], [13,700], [13,730], [10,640] ], 20
            ],
        ];
    }

    /**
     * @test         evaluate
     * @dataProvider dataProviderForEvaluate
     * @param        array $points
     * @param        float $x
     * @param        float $y
     */
    public function testEvaluate(array $points, float $x, float $y)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($y, $regression->evaluate($x), 0.01);
    }

    /**
     * @return array [points, x, y]
     */
    public function dataProviderForEvaluate(): array
    {
        return [
            [
                [ [0,0], [1,1], [2,2], [3,3], [4,4] ], // y = x + 0
                5, 5,
            ],
            [
                [ [0,0], [1,1], [2,2], [3,3], [4,4] ], // y = x + 0
                18, 18,
            ],
            [
                [ [0,0], [1,2], [2,4], [3,6] ], // y = 2x + 0
                4, 8,
            ],
            [
                [ [0,1], [1,3.5], [2,6] ], // y = 2.5x + 1
                5, 13.5
            ],
            [
                [ [0,2], [1,1], [2,0], [3,-1] ], // y = -x + 2
                4, -2
            ],
            // Example data from http://reliawiki.org/index.php/Simple_Linear_Regression_Analysis
            [
                [ [50,122], [53,118], [54,128], [55,121], [56,125], [59,136], [62,144], [65,142], [67,149], [71,161], [72,167], [74,168], [75,162], [76,171], [79,175], [80,182], [82,180], [85,183], [87,188], [90,200], [93,194], [94,206], [95,207], [97,210], [100,219] ],
                93, 202.5552
            ],
        ];
    }

    /**
     * @test         ci
     * @dataProvider dataProviderForCI
     * @param        array $points
     * @param        float $x
     * @param        float $p
     * @param        float $ci
     * @throws       \Exception
     */
    public function testCI(array $points, float $x, float $p, float $ci)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($ci, $regression->ci($x, $p), .0000001);
    }

    /**
     * @return array [points, x, p, ci]
     */
    public function dataProviderForCI(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                2, .05, 0.651543596,
            ],
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                3, .05, 0.518513005,
            ],
            [
               [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                3, .1, 0.383431307,
            ],
        ];
    }

    /**
     * @test Github issue 429 - ci division by zero
     */
    public function testBugIssue429CI()
    {
        // Given
        $points = [[5,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,0],[8,1],[8,0],[8,1],[8,1],[8,1],[8,1],[8,0],[8,1],[8,0],[8,1],[8,0],[8,1],[8,0],[8,1],[8,0],[8,1],[8,1],[8,1],[8,0],[8,1],[8,0],[8,1],[8,1],[8,1],[8,0],[8,1],[8,0],[8,1],[8,0],[8,1],[8,0],[8,1],[8,0],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1],[8,1]];
        $x      = 5.0;

        // And
        $regression = new Linear($points);

        // When
        $ci = $regression->ci($x, 0.05);

        // Then
        $this->assertEqualsWithDelta(0.39030395, $ci, 0.000001);
    }

    /**
     * @test         pi
     * @dataProvider dataProviderForPI
     * @param        array $points
     * @param        float $x
     * @param        float $p
     * @param        float $q
     * @param        float $pi
     * @throws       \Exception
     */
    public function testPI(array $points, float $x, float $p, float $q, float $pi)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($pi, $regression->pi($x, $p, $q), .0000001);
    }

    /**
     * @return array [points, x, p, q, pi]
     */
    public function dataProviderForPI(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                2, .05, 1, 1.281185007,
            ],
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                3, .05, 1, 1.218926455,
            ],
            [
               [ [1,2], [2,3], [4,5], [5,7], [6,8] ],  // when q gets large, pi approaches ci.
                3, .1, 10000000, 0.383431394
            ],
        ];
    }

    /**
     * @test         fProbability
     * @dataProvider dataProviderForFProbability
     * @param        array $points
     * @param        float $probability
     */
    public function testFProbability(array $points, float $probability)
    {
        // Given
        $regression = new Linear($points);

        // When
        $Fprob = $regression->fProbability();

        // Then
        $this->assertEqualsWithDelta($probability, $Fprob, .0000001);
    }

    /**
     * @return array [points, probability]
     */
    public function dataProviderForFProbability(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                .999304272,
            ],
        ];
    }

    /**
     * @test         tProbability
     * @dataProvider dataProviderForTProbability
     * @param        array $points
     * @param        float $beta0
     * @param        float $beta1
     */
    public function testTProbability(array $points, float $beta0, float $beta1)
    {
        // Given
        $regression = new Linear($points);

        // When
        $Tprob = $regression->tProbability();

        // Then
        $this->assertEqualsWithDelta($beta0, $Tprob['m'], .0000001);
        $this->assertEqualsWithDelta($beta1, $Tprob['b'], .0000001);
    }

    /**
     * @return array [points, beta0, beta1]
     */
    public function dataProviderForTProbability(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                0.999652136, 0.913994632,
            ],
        ];
    }

    /**
     * @test         leverages
     * @dataProvider dataProviderForLeverages
     * @param        array $points
     * @param        array $leverages
     */
    public function testLeverages(array $points, array $leverages)
    {
        // Given
        $regression = new Linear($points);

        // When
        $test_leverages = $regression->leverages();

        // Then
        foreach ($leverages as $key => $value) {
            $this->assertEqualsWithDelta($value, $test_leverages[$key], .0000001);
        }
    }

    /**
     * @return array [points, leverages]
     */
    public function dataProviderForLeverages(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                [0.593023255813953, 0.348837209302325, 0.209302325581395, 0.313953488372093, 0.534883720930232],
            ],
        ];
    }

    /**
     * @test         degreesOfFreedom
     * @dataProvider dataProviderForDF
     * @param        array $points
     * @param        int   $df
     */
    public function testDF(array $points, int $df)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($df, $regression->degreesOfFreedom(), .0000001);
    }

    /**
     * @return array [points, df]
     */
    public function dataProviderForDF(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                3,
            ],
        ];
    }

    /**
     * @test         getProjectionMatrix
     * @dataProvider dataProviderForGetProjection
     * @param        array $points
     * @param        array $P
     */
    public function testGetProjection(array $points, array $P)
    {
        // Given
        $regression = new Linear($points);

        // When
        $test_P = $regression->getProjectionMatrix();

        // Then
        foreach ($P as $row_num => $row) {
            foreach ($row as $column_num => $value) {
                $this->assertEqualsWithDelta($value, $test_P[$row_num][$column_num], .0000001);
            }
        }
    }

    /**
     * @return array [points, P]
     */
    public function dataProviderForGetProjection(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                [ [0.593023255813953, 0.441860465116279, 0.13953488372093, -0.0116279069767443, -0.162790697674419],
                  [0.441860465116279, 0.348837209302325, 0.162790697674418, 0.069767441860465, -0.0232558139534887],
                  [0.13953488372093, 0.162790697674418, 0.209302325581395, 0.232558139534884, 0.255813953488372],
                  [-0.0116279069767442, 0.069767441860465, 0.232558139534884, 0.313953488372093, 0.395348837209302],
                  [-0.162790697674419, -0.0232558139534885, 0.255813953488372, 0.395348837209302, 0.534883720930232] ],
            ],
        ];
    }

    /**
     * @test         mean squares
     * @dataProvider dataProviderForMeanSquares
     * @param        array $points
     * @param        array $sums
     */
    public function testMeanSquares(array $points, array $sums)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($sums['mse'], $regression->meanSquareResidual(), .0000001);
        $this->assertEqualsWithDelta($sums['msr'], $regression->meanSquareRegression(), .0000001);
        $this->assertEqualsWithDelta($sums['mst'], $regression->meanSquareTotal(), .0000001);
        $this->assertEqualsWithDelta($sums['sd'], $regression->errorSd(), .0000001);
    }

    /**
     * @return array [points, sums]
     */
    public function dataProviderForMeanSquares(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                [
                    'mse' => 0.1201550388,
                    'msr' => 25.6395348837,
                    'mst' => 6.5,
                    'sd' => 0.3466338685,
                ],
            ],
        ];
    }

    /**
     * @test         outliers
     * @dataProvider dataProviderForOutliers
     * @param        array $points
     * @param        array $cook
     * @param        array $DFFITS
     */
    public function testOutliers(array $points, array $cook, array $DFFITS)
    {
        // Given
        $regression = new Linear($points);

        // When
        $test_cook   = $regression->cooksD();
        $test_dffits = $regression->dffits();

        // Then
        foreach ($test_cook as $key => $value) {
            $this->assertEqualsWithDelta($value, $cook[$key], .0000001);
        }
        foreach ($test_dffits as $key => $value) {
            $this->assertEqualsWithDelta($value, $DFFITS[$key], .0000001);
        }
    }

    /**
     * @return array [points, cook, DFFITS]
     */
    public function dataProviderForOutliers(): array
    {
        return [
            // Example data from http://www.real-statistics.com/multiple-regression/outliers-and-influencers/
            [
                [ [5, 80], [23, 78], [25, 60], [48, 53], [17, 85], [8, 84], [4, 73], [26, 79], [11, 81], [19, 75], [14, 68], [35, 72], [29, 58], [4, 92], [23, 65] ],
                [0.012083306344603, 0.0300594698005975, 0.0757553251307135, 0.0741065959898502, 0.0624057528075083, 0.0142413619931789, 0.212136415565691, 0.0755417128075708, 0.00460659919090967, 0.00088992920763197, 0.0592838137660013, 0.142372813997539, 0.0975938916424623, 0.157390753959856, 0.0261198759356697],
                [-0.150079950062248, 0.24285101704604, -0.401412101080541, -0.372557646651725, 0.363674389274495, 0.163387818699222, -0.679956836684882, 0.398634868702933, 0.0925181155407344, 0.0405721294627194, -0.349647454278992, 0.540607683240147, -0.45315456934644, 0.572499188557405, -0.225453165214519],
            ],
        ];
    }
}
