<?php

namespace MathPHP\Tests\Statistics;

use MathPHP\Statistics\ANOVA;
use MathPHP\Exception;

class ANOVATest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         oneWay with three samples
     * @dataProvider dataProviderForOneWayWithThreeSamples
     * @param        array $sample1
     * @param        array $sample2
     * @param        array $sample3
     * @param        array $expected
     */
    public function testOneWayWithThreeSamples(array $sample1, array $sample2, array $sample3, array $expected)
    {
        // When
        $anova = ANOVA::oneWay($sample1, $sample2, $sample3);

        // Then
        $this->assertEqualsWithDelta($expected, $anova, 0.0001);
    }

    /**
     * @return array [sample1, sample2, sample3, expectedAnova]
     */
    public function dataProviderForOneWayWithThreeSamples(): array
    {
        return [
            [
                [1, 2, 3],
                [3, 4, 5],
                [5, 6, 7],
                [
                    'ANOVA' => [
                        'treatment' => [
                            'SS' => 24,
                            'df' => 2,
                            'MS' => 12,
                            'F'  => 12,
                            'P'  => 0.008,
                        ],
                        'error' => [
                            'SS' => 6,
                            'df' => 6,
                            'MS' => 1,
                        ],
                        'total' => [
                            'SS' => 30,
                            'df' => 8,
                        ],
                    ],
                    'total_summary' => [
                        'n'        => 9,
                        'sum'      => 36,
                        'mean'     => 4,
                        'SS'       => 174,
                        'variance' => 3.75,
                        'sd'       => 1.9365,
                        'sem'      => 0.6455,
                    ],
                    'data_summary' => [
                        0 => [
                            'n'        => 3,
                            'sum'      => 6,
                            'mean'     => 2,
                            'SS'       => 14,
                            'variance' => 1,
                            'sd'       => 1,
                            'sem'      => 0.5774,
                        ],
                        1 => [
                            'n'        => 3,
                            'sum'      => 12,
                            'mean'     => 4,
                            'SS'       => 50,
                            'variance' => 1,
                            'sd'       => 1,
                            'sem'      => 0.5774,
                        ],
                        2 => [
                            'n'        => 3,
                            'sum'      => 18,
                            'mean'     => 6,
                            'SS'       => 110,
                            'variance' => 1,
                            'sd'       => 1,
                            'sem'      => 0.5774,
                        ],
                    ],
                ],
            ],
            [
                [6, 8, 4, 5, 3, 4],
                [8, 12, 9, 11, 6, 8],
                [13, 9, 11, 8, 7, 12],
                [
                    'ANOVA' => [
                        'treatment' => [
                            'SS' => 84,
                            'df' => 2,
                            'MS' => 42,
                            'F'  => 9.26477400569122,
                            'P'  => 0.002404,
                        ],
                        'error' => [
                            'SS' => 68,
                            'df' => 15,
                            'MS' => 4.5333,
                        ],
                        'total' => [
                            'SS' => 152,
                            'df' => 17,
                        ],
                    ],
                    'total_summary' => [
                        'n'        => 18,
                        'sum'      => 144,
                        'mean'     => 8,
                        'SS'       => 1304,
                        'variance' => 8.9412,
                        'sd'       => 2.9902,
                        'sem'      => 0.7048,
                    ],
                    'data_summary' => [
                        0 => [
                            'n'        => 6,
                            'sum'      => 30,
                            'mean'     => 5,
                            'SS'       => 166,
                            'variance' => 3.2,
                            'sd'       => 1.7889,
                            'sem'      => 0.7303,
                        ],
                        1 => [
                            'n'        => 6,
                            'sum'      => 54,
                            'mean'     => 9,
                            'SS'       => 510,
                            'variance' => 4.8,
                            'sd'       => 2.1909,
                            'sem'      => 0.8944,
                        ],
                        2 => [
                            'n'        => 6,
                            'sum'      => 60,
                            'mean'     => 10,
                            'SS'       => 628,
                            'variance' => 5.6,
                            'sd'       => 2.3664,
                            'sem'      => 0.9661,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test         oneWay with four samples
     * @dataProvider dataProviderForOneWayWithFourSamples
     * @param        array $sample1
     * @param        array $sample2
     * @param        array $sample3
     * @param        array $sample4
     * @param        array $expected
     */
    public function testOneWayWithFourSamples(array $sample1, array $sample2, array $sample3, array $sample4, array $expected)
    {
        // When
        $anova = ANOVA::oneWay($sample1, $sample2, $sample3, $sample4);

        // Then
        $this->assertEqualsWithDelta($expected, $anova, 0.0001);
    }

    /**
     * @return array [sample1, sample2, sample3, sample4, expetedAnova]
     */
    public function dataProviderForOneWayWithFourSamples(): array
    {
        return [
            [
                [0.28551035, 0.338524035, 0.088313218, 0.205930807, 0.363240102],
                [0.52173913, 0.763358779, 0.32546786, 0.425305688, 0.378071834],
                [0.989119683, 1.192718142, 0.788288288, 0.549176236, 0.544588155],
                [1.26705653, 1.625320787, 1.266108976, 1.154187629, 1.268498943],
                [
                    'ANOVA' => [
                        'treatment' => [
                            'SS' => 3.176758,
                            'df' => 3,
                            'MS' => 1.058919,
                            'F'  => 27.5254,
                            'P'  => 1.4876e-06,
                        ],
                        'error' => [
                            'SS' => 0.615529,
                            'df' => 16,
                            'MS' => 0.038471,
                        ],
                        'total' => [
                            'SS' => 3.792287,
                            'df' => 19,
                        ],
                    ],
                    'total_summary' => [
                        'n'        => 20,
                        'sum'      => 14.340525,
                        'mean'     => 0.717026,
                        'SS'       => 14.07482,
                        'variance' => 0.199594,
                        'sd'       => 0.446759,
                        'sem'      => 0.099898,
                    ],
                    'data_summary' => [
                        0 => [
                            'n'        => 5,
                            'sum'      => 1.281519,
                            'mean'     => 0.256304,
                            'SS'       => 0.378265,
                            'variance' => 0.012452,
                            'sd'       => 0.111587,
                            'sem'      => 0.049903,
                        ],
                        1 => [
                            'n'        => 5,
                            'sum'      => 2.413943,
                            'mean'     => 0.482789,
                            'SS'       => 1.284681,
                            'variance' => 0.029814,
                            'sd'       => 0.172668,
                            'sem'      => 0.077219,
                        ],
                        2 => [
                            'n'        => 5,
                            'sum'      => 4.063891,
                            'mean'     => 0.812778,
                            'SS'       => 3.620504,
                            'variance' => 0.079366,
                            'sd'       => 0.281719,
                            'sem'      => 0.125989,
                        ],
                        3 => [
                            'n'        => 5,
                            'sum'      => 6.581173,
                            'mean'     => 1.316235,
                            'SS'       => 8.791371,
                            'variance' => 0.032251,
                            'sd'       => 0.179585,
                            'sem'      => 0.080313,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test oneWay throws a BadDataException if there are fewer than three samples
     */
    public function testOneWayExceptionLessThanThreeSamples()
    {
        // Given
        $sample1 = [1, 2, 3];
        $sample2 = [3, 4, 5];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        ANOVA::oneWay($sample1, $sample2);
    }

    /**
     * @test oneWay throws a BadDataException if the samples of different sample sizes
     */
    public function testOneWayExceptionDifferentSampleSizes()
    {
        // Given
        $sample1 = [1, 2, 3];
        $sample2 = [3, 4, 5, 6];
        $sample3 = [5, 6, 7, 8, 9];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        ANOVA::oneWay($sample1, $sample2, $sample3);
    }

    /**
     * @test         Axioms of one-way ANOVA results using three samples
     * @dataProvider dataProviderForOneWayAxiomsThreeSamples
     * @param        array $sample1
     * @param        array $sample2
     * @param        array $sample3
     */
    public function testOneWayAxiomsThreeSamples(array $sample1, array $sample2, array $sample3)
    {
        // When
        $anova = ANOVA::oneWay($sample1, $sample2, $sample3);

        // Then SST = SSB + SSW
        $SST = $anova['ANOVA']['total']['SS'];
        $SSB = $anova['ANOVA']['treatment']['SS'];
        $SSW = $anova['ANOVA']['error']['SS'];
        $this->assertEquals($SST, $SSB + $SSW);

        // And dfT = dfB + dfW
        $dfT = $anova['ANOVA']['total']['df'];
        $dfB = $anova['ANOVA']['treatment']['df'];
        $dfW = $anova['ANOVA']['error']['df'];
        $this->assertEquals($dfT, $dfB + $dfW);
    }

    /**
     * @return array [sample1, sample2, sample3
     */
    public function dataProviderForOneWayAxiomsThreeSamples(): array
    {
        return [
            [
                [1, 2, 3],
                [3, 4, 5],
                [5, 6, 7],
            ],
            [
                [4, 5, 3, 6, 5],
                [5, 4, 3, 4, 4],
                [7, 6, 6, 5, 6],
            ],
            [
                [-4, 4, 5, 6, 7],
                [-5, 4, 6, 6, 7],
                [0, 1, 2, 3, 4],
            ],
        ];
    }

    /**
     * @test         Axioms of one-way ANOVA results using five samples
     * @dataProvider dataProviderForOneWayAxiomsFiveSamples
     * @param        array $sample1
     * @param        array $sample2
     * @param        array $sample3
     * @param        array $sample4
     * @param        array $sample5
     */
    public function testOneWayAxiomsFiveSamples(array $sample1, array $sample2, array $sample3, array $sample4, array $sample5)
    {
        // When
        $anova = ANOVA::oneWay($sample1, $sample2, $sample3, $sample4, $sample5);

        // Then SST = SSB + SSW
        $SST = $anova['ANOVA']['total']['SS'];
        $SSB = $anova['ANOVA']['treatment']['SS'];
        $SSW = $anova['ANOVA']['error']['SS'];
        $this->assertEquals($SST, $SSB + $SSW);

        // And dfT = dfB + dfW
        $dfT = $anova['ANOVA']['total']['df'];
        $dfB = $anova['ANOVA']['treatment']['df'];
        $dfW = $anova['ANOVA']['error']['df'];
        $this->assertEquals($dfT, $dfB + $dfW);
    }

    /**
     * @return array [sample1, sample2, sample3, sample4, sample5]
     */
    public function dataProviderForOneWayAxiomsFiveSamples(): array
    {
        return [
            [
                [1, 2, 3],
                [3, 4, 5],
                [5, 6, 7],
                [7, 8, 9],
                [9, 10, 11],
            ],
            [
                [4, 5, 3, 6, 5],
                [5, 4, 3, 4, 4],
                [7, 6, 6, 5, 6],
                [5, 6, 6, 5, 4],
                [8, 7, 7, 6, 7],
            ],
            [
                [-4, 4, 5, 6, 7],
                [-5, 4, 6, 6, 7],
                [0, 1, 2, 3, 4],
                [-2, -1, -1, 4, 5],
                [5, 5, 5, 5, 5],
            ],
        ];
    }

    /**
     * @test         twoWay using two sample sets
     * @dataProvider dataProviderForTwoWayTwoAs
     * @param        array $A₁
     * @param        array $A₂
     * @param        array $expected
     */
    public function testTwoWayTwoAs(array $A₁, array $A₂, array $expected)
    {
        // When
        $anova = ANOVA::twoWay($A₁, $A₂);

        // Then
        $this->assertEqualsWithDelta($expected, $anova['ANOVA'], 0.001);
    }

    /**
     * @return array [A₁, $A₂, expectedAnova]
     */
    public function dataProviderForTwoWayTwoAs(): array
    {
        return [
            [
                // Factor A₁
                [
                    [4, 6, 8],  // Factor B₁
                    [6, 6, 9],  // Factor B₂
                    [8, 9, 13], // Factor B₃
                ],
                // Factor A₂
                [
                    [4, 8, 9],    // Factor B₁
                    [7, 10, 13],  // Factor B₂
                    [12, 14, 16], // Factor B₃
                ],
                // ANOVA result
                [
                    'factorA' => [
                        'SS' => 32,
                        'df' => 1,
                        'MS' => 32,
                        'F'  => 5.647059,
                        'P'  => 0.03499435
                    ],
                    'factorB' => [
                        'SS' => 93,
                        'df' => 2,
                        'MS' => 46.5,
                        'F'  => 8.205882,
                        'P'  => 0.005676730,
                    ],
                    'interaction' => [
                        'SS' => 7,
                        'df' => 2,
                        'MS' => 3.5,
                        'F'  => 0.617647,
                        'P'  => 0.5555023,
                    ],
                    'error' => [
                        'SS' => 68,
                        'df' => 12,
                        'MS' => 5.6667,
                    ],
                    'total' => [
                        'SS' => 200,
                        'df' => 17,
                    ],
                ],
            ],
            // Calculations: http://scistatcalc.blogspot.com/2013/11/two-factor-anova-test-calculator.html
            [
                // Factor A₁
                [
                    [4.1, 3.1, 3.5], // Factor B₁
                    [3.9, 2.8, 3.2], // Factor B₂
                    [4.3, 3.3, 3.6], // Factor B₃
                ],
                // Factor A₂
                [
                    [2.7, 1.9, 2.7], // Factor B₁
                    [3.1, 2.2, 2.3], // Factor B₂
                    [2.6, 2.3, 2.5], // Factor B₃
                ],
                // ANOVA result
                [
                    'factorA' => [
                        'SS' => 5.013889,
                        'df' => 1,
                        'MS' => 5.013889,
                        'F'  => 23.022959,
                        'P'  => 4.348485e-4
                    ],
                    'factorB' => [
                        'SS' => 0.101111,
                        'df' => 2,
                        'MS' => 0.050556,
                        'F'  => 0.232143,
                        'P'  => 7.963117e-1,
                    ],
                    'interaction' => [
                        'SS' => 0.201111,
                        'df' => 2,
                        'MS' => 0.100556,
                        'F'  => 0.461735,
                        'P'  => 6.409332e-1,
                    ],
                    'error' => [
                        'SS' => 2.613333,
                        'df' => 12,
                        'MS' => 0.217778,
                    ],
                    'total' => [
                        'SS' => 7.9294,
                        'df' => 17,
                    ],
                ],
            ],
        ];
    }

    /**
     * @test         twoWay using three sample sets
     * @dataProvider dataProviderForTwoWayThreeAs
     * @param        array $A₁
     * @param        array $A₂
     * @param        array $A₃
     * @param        array $expected
     */
    public function testTwoWayThreeAs(array $A₁, array $A₂, array $A₃, array $expected)
    {
        // When
        $anova = ANOVA::twoWay($A₁, $A₂, $A₃);

        // Then
        $this->assertEqualsWithDelta($expected, $anova['ANOVA'], 0.001);
    }

    /**
     * @return array [A₁, A₂, A₃, expectedAnova]
     */
    public function dataProviderForTwoWayThreeAs(): array
    {
        return [
            // Example data from: https://people.richland.edu/james/lecture/m170/ch13-2wy.html
            [
                // Factor A₁
                [
                    [106, 110], // Factor B₁
                    [95, 100],  // Factor B₂
                    [94, 107],  // Factor B₃
                    [103, 104], // Factor B₄
                    [100, 102], // Factor B₅
                ],
                // Factor A₂
                [
                    [110, 112], // Factor B₁
                    [98, 99],   // Factor B₂
                    [100, 101], // Factor B₃
                    [108, 112], // Factor B₄
                    [105, 107], // Factor B₅
                ],
                // Factor A₃
                [
                    [94, 97],  // Factor B₁
                    [86, 87],  // Factor B₂
                    [98, 99],  // Factor B₃
                    [99, 101], // Factor B₄
                    [94, 98],  // Factor B₅
                ],
                // ANOVA result
                [
                    'factorA' => [
                        'SS' => 512.8667,
                        'df' => 2,
                        'MS' => 256.4333,
                        'F'  => 28.283,
                        'P'  => 0.000008
                    ],
                    'factorB' => [
                        'SS' => 449.4667,
                        'df' => 4,
                        'MS' => 112.3667,
                        'F'  => 12.393,
                        'P'  => 0.000119,
                    ],
                    'interaction' => [
                        'SS' => 143.1333,
                        'df' => 8,
                        'MS' => 17.8917,
                        'F'  => 1.973,
                        'P'  => 0.122090,
                    ],
                    'error' => [
                        'SS' => 136.0000,
                        'df' => 15,
                        'MS' => 9.0667,
                    ],
                    'total' => [
                        'SS' => 1241.4667,
                        'df' => 29,
                    ],
                ],
            ],
            // Example data from: https://people.richland.edu/james/ictcm/2004/twoway.html
            // Calculations: http://scistatcalc.blogspot.com/2013/11/two-factor-anova-test-calculator.html
            [
                // Factor A₁
                [
                    [54, 49, 59, 39, 55], // Factor B₁
                    [25, 29, 47, 26, 28], // Factor B₂
                ],
                // Factor A₂
                [
                    [53, 72, 43, 56, 52], // Factor B₁
                    [46, 51, 33, 47, 41], // Factor B₂
                ],
                // Factor A₃
                [
                    [33, 30, 26, 25, 29],  // Factor B₁
                    [18, 21, 34, 40, 24],  // Factor B₂
                ],
                // ANOVA result
                [
                    'factorA' => [
                        'SS' => 2328.2,
                        'df' => 2,
                        'MS' => 1164.10,
                        'F'  => 17.580166,
                        'P'  => 1.986862e-5
                    ],
                    'factorB' => [
                        'SS' => 907.5,
                        'df' => 1,
                        'MS' => 907.50,
                        'F'  => 13.705009,
                        'P'  => 1.114639e-3,
                    ],
                    'interaction' => [
                        'SS' => 452.6,
                        'df' => 2,
                        'MS' => 226.30,
                        'F'  => 3.417569,
                        'P'  => 4.942928e-2,
                    ],
                    'error' => [
                        'SS' => 1589.2,
                        'df' => 24,
                        'MS' => 66.21666666666667,
                    ],
                    'total' => [
                        'SS' => 5277.5,
                        'df' => 29,
                    ],
                ],
            ],
        ];
    }

    /**
     * @test twoWay throws a BadDataException if there are fewer than two sample sets
     */
    public function testTwoWayExceptionLessThanTwoAs()
    {
        // Given
        $A₁ = [1, 2, 3];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        ANOVA::twoWay($A₁);
    }

    /**
     * @test twoWay throws a BadDataException if the sample sets have unequal factors
     */
    public function testTwoWAyExceptionDifferentNumbersOfFactorBs()
    {
        // Given
        $A₁ = [
            [106, 110], // Factor B₁
            [95, 100],  // Factor B₂
        ];
        $A₂ = [
            [106, 110], // Factor B₁
            [95, 100],  // Factor B₂
            [95, 100],  // Factor B₃!
        ];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // Then
        ANOVA::twoWay($A₁, $A₂);
    }

    /**
     * @test twoWay throws a BadDataException if the sample sets have factors with unequal elements
     */
    public function testTwoWAyExceptionDifferentNumbersOfFactorElements()
    {
        // Given
        $A₁ = [
            [106, 110], // Factor B₁
            [95, 100],  // Factor B₂
        ];
        $A₂ = [
            [106, 110, 200], // Factor B₁ has 3 elements!
            [95, 100],       // Factor B₂
        ];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        ANOVA::twoWay($A₁, $A₂);
    }
}
