<?php

namespace MathPHP\Tests\Probability;

use MathPHP\Probability\Combinatorics;
use MathPHP\Exception;

class CombinatoricsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         factorial
     * @dataProvider dataProviderForFactorialPermutations
     * @param        int   $n
     * @param        float $expected
     * @throws       \Exception
     */
    public function testFactorial(int $n, float $expected)
    {
        // When
        $factorial = Combinatorics::factorial($n);

        // Then
        $this->assertEquals($expected, $factorial);
    }

    /**
     * @test   factorial bounds exception
     * @throws \Exception
     */
    public function testFactorialBoundsException()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::factorial(-1);
    }

    /**
     * @test         doubleFactorial
     * @dataProvider dataProviderForDoubleFactorial
     * @param        int   $n
     * @param        float $expected
     * @throws       \Exception
     */
    public function testDoubleFactorial(int $n, float $expected)
    {
        // When
        $doubleFactorial = Combinatorics::doubleFactorial($n);

        // Then
        $this->assertEquals($expected, $doubleFactorial);
    }

    /**
     * @return array [n, doubleFactorial]
     */
    public function dataProviderForDoubleFactorial(): array
    {
        return [
            [0, 1],
            [1, 1],
            [2, 2],
            [3, 3],
            [4, 8],
            [5, 15],
            [6, 48],
            [7, 105],
            [8, 384],
            [9, 945],
            [10, 3840],
            [11, 10395],
            [12, 46080],
            [13, 135135],
            [14, 645120],
        ];
    }

    /**
     * @test   doubleFactorial n less than zero
     * @throws \Exception
     */
    public function testDoubleFactorialExceptionNLessThanZero()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::doubleFactorial(-1);
    }

    /**
     * @test         risingFactorial
     * @dataProvider dataProviderForRisingFactorial
     * @param        int   $x
     * @param        int   $n
     * @param        float $expected
     * @throws       \Exception
     */
    public function testRisingFactorial(int $x, int $n, float $expected)
    {
        // When
        $risingFactorial = Combinatorics::risingFactorial($x, $n);

        // Then
        $this->assertEquals($expected, $risingFactorial);
    }

    /**
     * @return array [x, n, risingFactorial]
     */
    public function dataProviderForRisingFactorial(): array
    {
        return [
            [5, 0, 1],
            [5, 1, 5],
            [5, 2, 30],
            [5, 3, 210],
            [4, 4, 840],
            [3, 5, 2520],
            [2, 6, 5040],
        ];
    }

    /**
     * @test   risingFactorial n less than zero
     * @throws \Exception
     */
    public function testRisingFactorialExceptionNLessThanZero()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::risingFactorial(5, -1);
    }

    /**
     * @test         fallingFactorial
     * @dataProvider dataProviderForFallingFactorial
     * @param        int   $x
     * @param        int   $n
     * @param        float $expected
     * @throws       \Exception
     */
    public function testFallingFactorial(int $x, int $n, float $expected)
    {
        // When
        $fallingFactorial = Combinatorics::fallingFactorial($x, $n);

        // Then
        $this->assertEquals($expected, $fallingFactorial);
    }

    /**
     * @return array [x, n, fallingFactorial]
     */
    public function dataProviderForFallingFactorial(): array
    {
        return [
            [5, 0, 1],
            [5, 1, 5],
            [5, 2, 20],
            [5, 3, 60],
            [5, 4, 120],
            [5, 5, 120],
            [5, 6, 0],
            [4, 3, 24],
            [4, 4, 24],
            [4, 5, 0],
            [8, 5, 6720],
            [3, 5, 0],
            [2, 6, 0],
        ];
    }

    /**
     * @test   fallingFactorial n less than zero
     * @throws \Exception
     */
    public function testFallingFactorialExceptionNLessThanZero()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::fallingFactorial(5, -1);
    }

    /**
     * @test         subfactorial
     * @dataProvider dataProviderForSubfactorial
     * @param        int   $n
     * @param        float $！n
     * @throws       \Exception
     */
    public function testSubfactorial(int $n, float $！n)
    {
        // When
        $subfactorial = Combinatorics::subfactorial($n);

        // Then
        $this->assertEqualsWithDelta($！n, $subfactorial, 0.000000001);
    }

    /**
     * @return array [n, ！n]
     */
    public function dataProviderForSubfactorial(): array
    {
        return [
            [0, 1],
            [1, 0],
            [2, 1],
            [3, 2],
            [4, 9],
            [5, 44],
            [6, 265],
            [7, 1854],
            [8, 14833],
            [9, 133496],
            [10, 1334961],
        ];
    }

    /**
     * @test   subfactorial n less than zero
     * @throws \Exception
     */
    public function testSubactorialExceptionNLessThanZero()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::subfactorial(-1);
    }

    /**
     * @test         permutations
     * @dataProvider dataProviderForFactorialPermutations
     * @param        int   $n
     * @param        float $expected
     * @throws       \Exception
     */
    public function testPermutations(int $n, float $expected)
    {
        // When
        $permutations = Combinatorics::permutations($n);

        // Then
        $this->assertEquals($expected, $permutations);
    }

    /**
     * @test   permutations bounds exception
     * @throws \Exception
     */
    public function testPermutationsBoundsException()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::permutations(-1);
    }

    /**
     * @return array [n, permutations]
     */
    public function dataProviderForFactorialPermutations(): array
    {
        return [
            [1,  1],
            [2,  2],
            [3,  6],
            [4,  24],
            [5,  120],
            [6,  720],
            [7,  5040],
            [8,  40320],
            [9,  362880],
            [10, 3628800],
            [11, 39916800],
            [12, 479001600],
            [13, 6227020800],
            [14, 87178291200],
            [15, 1307674368000],
            [16, 20922789888000],
            [17, 355687428096000],
            [18, 6402373705728000],
            [19, 121645100408832000],
            [20, 2432902008176640000],
        ];
    }

    /**
     * @test         permutations choose k
     * @dataProvider dataProviderForPermutationsChooseK
     * @param        int   $n
     * @param        int   $k
     * @param        float $nPk
     * @throws       \Exception
     */
    public function testPermutationsChooseK(int $n, int $k, float $nPk)
    {
        // When
        $permutations = Combinatorics::permutations($n, $k);

        // Then
        $this->assertEquals($nPk, $permutations);
    }

    /**
     * @return array [n, k, permutations]
     */
    public function dataProviderForPermutationsChooseK(): array
    {
        return [
            [10,  0,       1],
            [10,  1,      10],
            [10,  2,      90],
            [10,  3,     720],
            [10,  4,    5040],
            [10,  5,   30240],
            [10,  6,  151200],
            [10,  7,  604800],
            [10,  8, 1814400],
            [10,  9, 3628800],
            [10, 10, 3628800],
            [ 5,  3,      60],
            [ 6,  4,     360],
            [16,  3,    3360],
            [20,  3,    6840],
            [23,  5, 4037880],
        ];
    }

    /**
     * @test   permutations choose k bounds exception
     * @throws \Exception
     */
    public function testPermutationsChooseKBoundsException()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::permutations(-1, 3);
    }

    /**
     * @test   permutations choose k - k greater than n exception
     * @throws \Exception
     */
    public function testPermutationsChooseKKGreaterThanNException()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::permutations(3, 4);
    }

    /**
     * @test         combinations
     * @dataProvider dataProviderForCombinations
     * @param        int   $n
     * @param        int   $r
     * @param        float $expected
     * @throws       \Exception
     */
    public function testCombinations(int $n, int $r, float $expected)
    {
        // When
        $combinations = Combinatorics::combinations($n, $r);

        // Then
        $this->assertEquals($expected, $combinations);
    }

    /**
     * Test data produced with Python scipy.special.comb(n, k, exact=True, repetition=False)
     * @return array [n, r, combinations]
     */
    public function dataProviderForCombinations(): array
    {
        return [
            [10,  0,          1],
            [10,  1,         10],
            [10,  2,         45],
            [10,  3,        120],
            [10,  4,        210],
            [10,  5,        252],
            [10,  6,        210],
            [10,  7,        120],
            [10,  8,         45],
            [10,  9,         10],
            [10, 10,          1],
            [ 5,  3,         10],
            [ 6,  4,         15],
            [16,  3,        560],
            [20,  3,       1140],
            [35, 20, 3247943160],
            [35, 25,  183579396],
        ];
    }

    /**
     * @test         combinations with large floating point overflow result
     * @dataProvider dataProviderForCombinationsWithLargeFloatingPointOverflowResult
     * @param        int   $n
     * @param        int   $r
     * @param        float $expected
     * @param        float ε
     * @throws       \Exception
     */
    public function testCombinationsWithLargeFloatingPointOverflowResult(int $n, int $r, float $expected, float $ε)
    {
        // When
        $combinations = Combinatorics::combinations($n, $r);

        // Then
        $this->assertEqualsWithDelta($expected, $combinations, $ε);
    }

    /**
     * Test data produced with Python scipy.special.comb(n, k, exact=False, repetition=False)
     * @return array [n, r, combinations, ε]
     */
    public function dataProviderForCombinationsWithLargeFloatingPointOverflowResult(): array
    {
        return [
            [70, 30, 5.534774005814348e+19, 0],
            [100, 50, 1.0089134454556415e+29, 1e14],
        ];
    }

    /**
     * @test   combinations n less than zero
     * @throws \Exception
     */
    public function testCombinationsExceptionNLessThanZero()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::combinations(-1, 2);
    }

    /**
     * @test   combinations r larger than n
     * @throws \Exception
     */
    public function testCombinationsExceptionRLargerThanN()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::combinations(1, 2);
    }

    /**
     * @test         combinations with repetition
     * @dataProvider dataProviderForCombinationsWithRepetition
     * @param        int   $n
     * @param        int   $r
     * @param        float $expected
     * @throws       \Exception
     */
    public function testCombinationsWithRepetition(int $n, int $r, float $expected)
    {
        // When
        $combinations = Combinatorics::combinations($n, $r, Combinatorics::REPETITION);

        // Then
        $this->assertEquals($expected, $combinations);
    }

    /**
     * @test   combinations with repetition bounds exception
     * @throws \Exception
     */
    public function testCombinationsWithRepetitionBoundsException()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::combinations(-1, 3, Combinatorics::REPETITION);
    }

    /**
     * Test data produced with Python scipy.special.comb(n, k, exact=True, repetition=True)
     * @return array [n, r, combinations]
     */
    public function dataProviderForCombinationsWithRepetition(): array
    {
        return [
            [10,  0,                  1],
            [10,  1,                 10],
            [10,  2,                 55],
            [10,  3,                220],
            [10,  4,                715],
            [10,  5,               2002],
            [10,  6,               5005],
            [10,  7,              11440],
            [10,  8,              24310],
            [10,  9,              48620],
            [10, 10,              92378],
            [5,   3,                 35],
            [5,   7,                330],
            [6,   4,                126],
            [16,  3,                816],
            [20,  3,               1540],
            [21, 20,       137846528820],
            [35, 25,  30284005485024837],

        ];
    }

    /**
     * @test         combinations with repetition with large floating point overflow result
     * @dataProvider dataProviderForCombinationsWithRepetitionWithLargeFloatingPointOverflowResult
     * @param        int   $n
     * @param        int   $r
     * @param        float $expected
     * @param        float ε
     * @throws       \Exception
     */
    public function testCombinationsWithRepetitionWithLargeFloatingPointOverflowResult(int $n, int $r, float $expected, float $ε)
    {
        // When
        $combinations = Combinatorics::combinations($n, $r, Combinatorics::REPETITION);

        // Then
        $this->assertEqualsWithDelta($expected, $combinations, $ε);
    }

    /**
     * Test data produced with Python scipy.special.comb(n, k, exact=False, repetition=True)
     * @return array [n, r, combinations, ε]
     */
    public function dataProviderForCombinationsWithRepetitionWithLargeFloatingPointOverflowResult(): array
    {
        return [
            [70, 30, 2.0560637875127662e+25, 1e10],
            [100, 50, 1.341910727315462e+40, 1e25],
        ];
    }

    /**
     * @test         centralBinomialCoefficient
     * @dataProvider dataProviderForCentralBinomialCoefficient
     * @param        int   $n
     * @param        float $！n
     * @throws       \Exception
     */
    public function testCentralBinomialCoefficient(int $n, float $！n)
    {
        // When
        $binomial = Combinatorics::centralBinomialCoefficient($n);

        // Then
        $this->assertEqualsWithDelta($！n, $binomial, 0.000000001);
    }

    /**
     * @return array [n, ！n]
     */
    public function dataProviderForCentralBinomialCoefficient(): array
    {
        return [
            [0, 1],
            [1, 2],
            [2, 6],
            [3, 20],
            [4, 70],
            [5, 252],
            [6, 924],
            [7, 3432],
            [8, 12870],
            [9, 48620],
            [10, 184756],
        ];
    }

    /**
     * @test   centralBinomialCoefficient n less than zero
     * @throws \Exception
     */
    public function testCentralBinomialCoefficientExceptionNLessThanZero()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::centralBinomialCoefficient(-1);
    }

    /**
     * @test         catalanNumber
     * @dataProvider dataProviderForCatalanNumber
     * @param        int   $n
     * @param        float $！n
     * @throws       \Exception
     */
    public function testCatalanNumber(int $n, float $！n)
    {
        // When
        $catalanNumber = Combinatorics::catalanNumber($n);

        // Then
        $this->assertEqualsWithDelta($！n, $catalanNumber, 0.000000001);
    }

    /**
     * @return array [n, ！n]
     */
    public function dataProviderForCatalanNumber(): array
    {
        return [
            [0, 1],
            [1, 1],
            [2, 2],
            [3, 5],
            [4, 14],
            [5, 42],
            [6, 132],
            [7, 429],
            [8, 1430],
            [9, 4862],
            [10, 16796],
            [11, 58786],
        ];
    }

    /**
     * @test   catalanNumber n less than zero
     * @throws \Exception
     */
    public function testCatalanNumberExceptionNLessThanZero()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::catalanNumber(-1);
    }

    /**
     * @test         multinomial
     * @dataProvider dataProviderForMultinomialTheorem
     * @param        array $groups
     * @param        int   $expected
     * @throws       \Exception
     */
    public function testMultinomialTheorem(array $groups, int $expected)
    {
        // When
        $divisions = Combinatorics::multinomial($groups);

        // Then
        $this->assertEquals($expected, $divisions);
    }

    /**
     * @return array [groups, divisions]
     */
    public function dataProviderForMultinomialTheorem(): array
    {
        return [
            [[2, 0, 1], 3],
            [[1, 1, 1], 6],
            [[ 5, 2, 3 ], 2520],
            [[ 5, 5 ],     252],
            [[ 1, 4, 4, 2 ], 34650],
            [[3, 4, 5, 8], 3491888400],
        ];
    }

    /**
     * @test         lahNumber
     * @dataProvider dataProviderForLahNumber
     * @param        int   $k
     * @param        int   $n
     * @param        float $expected
     * @throws       \Exception
     */
    public function testLahNumber(int $k, int $n, float $expected)
    {
        // When
        $lahNumber = Combinatorics::lahNumber($k, $n);

        // Then
        $this->assertEquals($expected, $lahNumber);
    }

    /**
     * @return array [k, n, lah]
     */
    public function dataProviderForLahNumber(): array
    {
        return [
            [1, 1, 1],
            [2, 1, 2],
            [2, 2, 1],
            [3, 1, 6],
            [3, 2, 6],
            [3, 3, 1],
            [4, 1, 24],
            [4, 2, 36],
            [4, 3, 12],
            [4, 4, 1],
            [5, 1, 120],
            [5, 2, 240],
            [5, 3, 120],
            [5, 4, 20],
            [5, 5, 1],
            [6, 1, 720],
            [6, 2, 1800],
            [6, 3, 1200],
            [6, 4, 300],
            [6, 5, 30],
            [6, 6, 1],
            [12, 1, 479001600],
            [12, 2, 2634508800],
            [12, 3, 4390848000],
            [12, 4, 3293136000],
            [12, 5, 1317254400],
            [12, 6, 307359360],
            [12, 7, 43908480],
            [12, 8, 3920400],
            [12, 9, 217800],
            [12, 10, 7260],
            [12, 11, 132],
            [12, 12, 1],
        ];
    }

    /**
     * @test         lahNumber n or k less than one
     * @dataProvider dataProviderForLahNumberExceptionNOrKLessThanOne
     * @param        int $n
     * @param        int $k
     * @throws       \Exception
     */
    public function testLahNumberExceptionNOrKLessThanOne(int $n, int $k)
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::lahNumber($n, $k);
    }

    /**
     * @return array [n, k]
     */
    public function dataProviderForLahNumberExceptionNOrKLessThanOne(): array
    {
        return [
            [-1, 2],
            [2, -2],
            [-3, -3],
        ];
    }

    /**
     * @test   lahNumber n less than k
     * @throws \Exception
     */
    public function testLahNumberExceptionNLessThanK()
    {
        // Given
        $k = 4;
        $n = 2;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Combinatorics::lahNumber($n, $k);
    }
}
