<?php

namespace MathPHP\Tests\NumberTheory;

use MathPHP\NumberTheory\Integer;
use MathPHP\Exception;

class IntegerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         isPerfectNumber
     * @dataProvider dataProviderForPerfectNumbers
     * @param        int $n
     */
    public function testIsPerfectNumber(int $n)
    {
        // When
        $isPerfectNumber = Integer::isPerfectNumber($n);

        // Then
        $this->assertTrue($isPerfectNumber);
    }

    /**
     * @see    https://oeis.org/A000396
     * @return array
     */
    public function dataProviderForPerfectNumbers(): array
    {
        return [
            [6],
            [28],
            [496],
            [8128],
            [33550336],
            [8589869056],
            [137438691328],
        ];
    }

    /**
     * @test         isPerfectNumber is not a perfect number
     * @dataProvider dataProviderForNonPerfectNumbers
     * @dataProvider dataProviderForAbundantNumbers
     * @dataProvider dataProviderForDeficientNumbers
     * @dataProvider dataProviderForPrimeFactorizationOutOfBoundsException
     * @param        int $n
     */
    public function testIsNotPerfectNumber(int $n)
    {
        // When
        $isPerfectNumber = Integer::isPerfectNumber($n);

        // Then
        $this->assertFalse($isPerfectNumber);
    }

    /**
     * @return array
     */
    public function dataProviderForNonPerfectNumbers(): array
    {
        return [
            [-1],
            [0],
            [1],
            [2],
            [3],
            [4],
            [5],
            [7],
            [8],
            [9],
            [10],
            [26],
            [498],
            [8124],
            [23550336],
            [2589869056],
            [133438691328],
        ];
    }

    /**
     * @test         isAbundantNumber returns true if n is an abundant number
     * @dataProvider dataProviderForAbundantNumbers
     * @param        int   $n
     * @throws       \Exception
     */
    public function testIsAbundantNumber(int $n)
    {
        // When
        $isAbundantNumber = Integer::isAbundantNumber($n);

        // Then
        $this->assertTrue($isAbundantNumber);
    }

    /**
     * A005101 abundant numbers: numbers n such that σ₁(n) > 2n
     * @see    https://oeis.org/A005101
     * @return array
     */
    public function dataProviderForAbundantNumbers(): array
    {
        return [
            [12],
            [18],
            [20],
            [24],
            [30],
            [36],
            [40],
            [42],
            [48],
            [54],
            [56],
            [60],
            [66],
            [70],
            [72],
            [78],
            [80],
            [84],
            [88],
            [90],
            [96],
            [100],
            [102],
            [104],
            [270],
        ];
    }

    /**
     * @test         isNotAbundantNumber returns true if n is not an abundant number
     * @dataProvider dataProviderForDeficientNumbers
     * @dataProvider dataProviderForPerfectNumbers
     * @dataProvider dataProviderForPrimeFactorizationOutOfBoundsException
     * @param        int   $n
     * @throws       \Exception
     */
    public function testIsNotAbundantNumber(int $n)
    {
        // When
        $isAbundantNumber = Integer::isAbundantNumber($n);

        // Then
        $this->assertFalse($isAbundantNumber);
    }

    /**
     * @test     isDeficientNumber returns true if n is a deficient number
     * @dataProvider dataProviderForDeficientNumbers
     * @param        int   $n
     * @throws       \Exception
     */
    public function testIsDeficientNumber(int $n)
    {
        // When
        $isDeficientNumber = Integer::isDeficientNumber($n);

        // Then
        $this->assertTrue($isDeficientNumber);
    }

    /**
     * A005100 deficient numbers: numbers n such that σ₁(n) < 2n
     * @see    https://oeis.org/A005100
     * @return array
     */
    public function dataProviderForDeficientNumbers(): array
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [5],
            [7],
            [8],
            [9],
            [10],
            [11],
            [13],
            [14],
            [15],
            [16],
            [17],
            [19],
            [21],
            [22],
            [23],
            [25],
            [26],
            [27],
            [29],
            [31],
            [32],
        ];
    }

    /**
     * @test         isNotDeficientNumber returns true if n is not a deficient number
     * @dataProvider dataProviderForAbundantNumbers
     * @dataProvider dataProviderForPerfectNumbers
     * @dataProvider dataProviderForPrimeFactorizationOutOfBoundsException
     * @param        int   $n
     * @throws       \Exception
     */
    public function testIsNotDeficientNumber(int $n)
    {
        // When
        $isDeficientNumber = Integer::isDeficientNumber($n);

        // Then
        $this->assertFalse($isDeficientNumber);
    }

    /**
     * @test         isRefactorableNumber returns true if n is a refactorable number
     * @dataProvider dataProviderForRefactorableNumbers
     * @param        int   $n
     * @throws       \Exception
     */
    public function testIsRefactorableNumber(int $n)
    {
        // When
        $isRefactorableNumber = Integer::isRefactorableNumber($n);

        // Then
        $this->assertTrue($isRefactorableNumber);
    }

    /**
     * A033950 Refactorable numbers: number of divisors of n divides n. Also known as tau numbers.
     * @see    https://oeis.org/A033950
     * @return array
     */
    public function dataProviderForRefactorableNumbers(): array
    {
        return [
            [1],
            [2],
            [8],
            [9],
            [12],
            [18],
            [24],
            [36],
            [40],
            [56],
            [60],
            [72],
            [80],
            [84],
            [88],
        ];
    }

    /**
     * @test         isNotRefactorableNumber returns true if n is not a refactorable number
     * @dataProvider dataProviderForNonRefactorableNumbers
     * @param        int   $n
     * @throws       \Exception
     */
    public function testIsNotRefactorableNumber(int $n)
    {
        // When
        $isRefactorableNumber = Integer::isRefactorableNumber($n);

        // Then
        $this->assertFalse($isRefactorableNumber);
    }

    /**
     * @return array
     */
    public function dataProviderForNonRefactorableNumbers(): array
    {
        return [
            [-1],
            [0],
            [3],
            [10],
            [13],
            [17],
        ];
    }

    /**
     * @test         testIsSphenicNumber
     * @dataProvider dataProviderForSphenicNumbers
     * @param        int $n
     * @throws       \Exception
     */
    public function testIsSphenicNumber(int $n)
    {
        // When
        $isSphenicNumber = Integer::isSphenicNumber($n);

        // Then
        $this->assertTrue($isSphenicNumber);
    }

    /**
     * A007304 Sphenic numbers: products of 3 distinct primes
     * @see    https://oeis.org/A007304
     * @return array
     */
    public function dataProviderForSphenicNumbers(): array
    {
        return [
            [30],
            [42],
            [66],
            [70],
            [78],
            [102],
            [105],
            [110],
            [114],
            [130],
            [138],
            [154],
            [165],
            [170],
            [174],
            [182],
            [186],
            [190],
            [195],
        ];
    }

    /**
     * @test         testIsNotSphenicNumber
     * @dataProvider dataProviderForNonSphenicNumbers
     * @param        int $n
     * @throws       \Exception
     */
    public function testIsNotSphenicNumber(int $n)
    {
        // When
        $isSphenicNumber = Integer::isSphenicNumber($n);

        // Then
        $this->assertFalse($isSphenicNumber);
    }

    /**
     * @return array
     */
    public function dataProviderForNonSphenicNumbers(): array
    {
        return [
            [2],
            [2 * 3],
            [2 * 2 * 2],
            [2 * 2 * 3 * 5],
            [2 * 3 * 5 * 7],
        ];
    }

    /**
     * @test         aliquotSum returns the sum of all proper divisors of n
     * @dataProvider dataProviderForAliquotSums
     * @param        int   $n
     * @param        int   $expected
     * @throws       \Exception
     */
    public function testAliquotSum(int $n, int $expected)
    {
        // When
        $actual = Integer::aliquotSum($n);

        // Then
        $this->assertEquals($expected, $actual);
    }

    /**
     * A001065 sum of proper divisors (or aliquot parts) of n
     * @see    https://oeis.org/A001065
     * @return array
     */
    public function dataProviderForAliquotSums(): array
    {
        return [
            [1, 0],
            [2, 1],
            [3, 1],
            [4, 3],
            [5, 1],
            [6, 6],
            [7, 1],
            [8, 7],
            [9, 4],
            [10, 8],
            [11, 1],
            [12, 16],
            [13, 1],
            [14, 10],
            [15, 9],
            [16, 15],
            [17, 1],
            [18, 21],
            [19, 1],
            [20, 22],
            [21, 11],
            [22, 14],
            [23, 1],
            [24, 36],
            [25, 6],
            [26, 16],
            [27, 13],
            [28, 28],
            [29, 1],
            [30, 42],
            [31, 1],
            [32, 31],
            [33, 15],
            [34, 20],
            [35, 13],
            [36, 55],
            [2 * 3 * 5 * 7 * 11, 4602],
        ];
    }

    /**
     * @test         aliquotSum throws an OutOfBoundsException if n is < 1.
     * @dataProvider dataProviderForPrimeFactorizationOutOfBoundsException
     * @param        int $n
     * @throws       \Exception
     */
    public function testAliquotSumOutOfBoundsException(int $n)
    {
        // When
        $this->expectException(Exception\OutOfBoundsException::class);

        // Then
        Integer::aliquotSum($n);
    }

    /**
     * @test         testRadical
     * @dataProvider dataProviderForRadical
     * @param        int $n
     * @param        int $expected
     * @throws       \Exception
     */
    public function testRadical(int $n, int $expected)
    {
        // When
        $radical = Integer::radical($n);

        // Then
        $this->assertEquals($expected, $radical);
    }

    /**
     * A007947 the squarefree kernel of n
     * @see    https://oeis.org/A007947
     * @return array
     */
    public function dataProviderForRadical(): array
    {
        return [
            [1, 1],
            [2, 2],
            [3, 3],
            [4, 2],
            [5, 5],
            [6, 6],
            [7, 7],
            [8, 2],
            [9, 3],
            [10, 10],
            [11, 11],
            [12, 6],
            [13, 13],
            [14, 14],
            [15, 15],
            [16, 2],
            [17, 17],
            [18, 6],
            [19, 19],
        ];
    }

    /**
     * @test         radical throws an OutOfBoundsException if n is < 1.
     * @dataProvider dataProviderForPrimeFactorizationOutOfBoundsException
     * @param        int $n
     * @throws       \Exception
     */
    public function testRadicalOutOfBoundsException(int $n)
    {
        // When
        $this->expectException(Exception\OutOfBoundsException::class);

        // Then
        Integer::radical($n);
    }


    /**
     * @test         testTotient
     * @dataProvider dataProviderForTotient
     * @param        int $n
     * @param        int $k
     * @param        int $expected
     * @throws       \Exception
     */
    public function testTotient(int $n, int $k, int $expected)
    {
        // When
        $totient = Integer::totient($n, $k);

        // Then
        $this->assertEquals($expected, $totient);
    }

    /**
     * @see    https://oeis.org/A000010 (k=1)
     * @see    https://oeis.org/A007434 (k=2)
     * @see    https://oeis.org/A059376 (k=3)
     * @see    https://oeis.org/A059377 (k=4)
     * @see    https://oeis.org/A059378 (k=5)
     * @return array
     */
    public function dataProviderForTotient(): array
    {
        return [
            [1,  1, 1],
            [2,  1, 1],
            [3,  1, 2],
            [4,  1, 2],
            [5,  1, 4],
            [6,  1, 2],
            [7,  1, 6],
            [8,  1, 4],
            [9,  1, 6],
            [10, 1, 4],
            [11, 1, 10],
            [12, 1, 4],
            [13, 1, 12],
            [14, 1, 6],
            [15, 1, 8],
            [16, 1, 8],
            [17, 1, 16],
            [18, 1, 6],
            [1,  2, 1],
            [2,  2, 3],
            [3,  2, 8],
            [4,  2, 12],
            [5,  2, 24],
            [6,  2, 24],
            [7,  2, 48],
            [8,  2, 48],
            [9,  2, 72],
            [10, 2, 72],
            [1,  3, 1],
            [2,  3, 7],
            [3,  3, 26],
            [4,  3, 56],
            [5,  3, 124],
            [6,  3, 182],
            [7,  3, 342],
            [8,  3, 448],
            [9,  3, 702],
            [10, 3, 868],
        ];
    }

    /**
     * @test         totient throws an OutOfBoundsException if n is < 1 or k is < 1.
     * @dataProvider dataProviderForTotientOutOfBoundsException
     * @param        int $n
     * @param        int $k
     * @throws       \Exception
     */
    public function testTotientOutOfBoundsException(int $n, int $k)
    {
        // When
        $this->expectException(Exception\OutOfBoundsException::class);

        // Then
        Integer::totient($n, $k);
    }

    /**
     * @return array
     */
    public function dataProviderForTotientOutOfBoundsException(): array
    {
        return [
            [2, -1],
            [2, 0],
            [0, 0],
            [0, 1],
            [-1, -1],
            [-1, 1],
            [-2, 1],
            [-100, 1],
            [-98352299832, 1],
        ];
    }

    /**
     * @test         testCototient
     * @dataProvider dataProviderForCototient
     * @param        int $n
     * @param        int $expected
     * @throws       \Exception
     */
    public function testCototient(int $n, int $expected)
    {
        // When
        $cototient = Integer::cototient($n);

        // Then
        $this->assertEquals($expected, $cototient);
    }

    /**
     * A051953 n - φ(n)
     * @see    https://oeis.org/A051953
     * @return array
     */
    public function dataProviderForCototient(): array
    {
        return [
            [1,  0],
            [2,  1],
            [3,  1],
            [4,  2],
            [5,  1],
            [6,  4],
            [7,  1],
            [8,  4],
            [9,  3],
            [10, 6],
            [11, 1],
            [12, 8],
            [13, 1],
            [14, 8],
            [15, 7],
            [16, 8],
            [17, 1],
            [18, 12],
            [19, 1],
            [20, 12],
            [80, 48],
        ];
    }

    /**
     * @test         cototient throws an OutOfBoundsException if n is < 1.
     * @dataProvider dataProviderForPrimeFactorizationOutOfBoundsException
     * @param        int $n
     * @throws       \Exception
     */
    public function testCototientOutOfBoundsException(int $n)
    {
        // When
        $this->expectException(Exception\OutOfBoundsException::class);

        // Then
        Integer::cototient($n);
    }

    /**
     * @test         testReducedTotient
     * @dataProvider dataProviderForReducedTotient
     * @param        int $n
     * @param        int $expected
     * @throws       \Exception
     */
    public function testReducedTotient(int $n, int $expected)
    {
        // When
        $result = Integer::reducedTotient($n);

        // Then
        $this->assertEquals($expected, $result);
    }

    /**
     * @see    https://oeis.org/A002322
     * @return array
     */
    public function dataProviderForReducedTotient(): array
    {
        return [
            [1,  1],
            [2,  1],
            [3,  2],
            [4,  2],
            [5,  4],
            [6,  2],
            [7,  6],
            [8,  2],
            [9,  6],
            [10, 4],
            [11, 10],
            [12, 2],
            [13, 12],
            [14, 6],
            [15, 4],
            [16, 4],
            [17, 16],
            [18, 6],
            [19, 18],
            [64, 16],
            [80, 4],
            [81, 54],
        ];
    }

    /**
     * @test         reducedTotient throws an OutOfBoundsException if n is < 1.
     * @dataProvider dataProviderForPrimeFactorizationOutOfBoundsException
     * @param        int $n
     * @throws       \Exception
     */
    public function testReducedTotientOutOfBoundsException(int $n)
    {
        // When
        $this->expectException(Exception\OutOfBoundsException::class);

        // Then
        Integer::reducedTotient($n);
    }

    /**
     * @test         testMobius
     * @dataProvider dataProviderForMobius
     * @param        int $n
     * @param        int $expected
     * @throws       \Exception
     */
    public function testMobius(int $n, int $expected)
    {
        // When
        $actual = Integer::mobius($n);

        // Then
        $this->assertEquals($expected, $actual);
    }

    /**
     * A008683
     * @see    https://oeis.org/A008683
     * @return array
     */
    public function dataProviderForMobius(): array
    {
        return [
            [1, 1],
            [2, -1],
            [3, -1],
            [4, 0],
            [5, -1],
            [6, 1],
            [7, -1],
            [8, 0],
            [9, 0],
            [10, 1],
            [11, -1],
            [12, 0],
            [13, -1],
            [14, 1],
            [15, 1],
        ];
    }

    /**
     * @test         mobius throws an OutOfBoundsException if n is < 1.
     * @dataProvider dataProviderForPrimeFactorizationOutOfBoundsException
     * @param        int $n
     * @throws       \Exception
     */
    public function testMobiusOutOfBoundsException(int $n)
    {
        // When
        $this->expectException(Exception\OutOfBoundsException::class);

        // Then
        Integer::mobius($n);
    }

    /**
     * @test         testIsSquarefree
     * @dataProvider dataProviderForSquarefreeIntegers
     * @param        int $n
     * @throws       \Exception
     */
    public function testIsSquarefree(int $n)
    {
        // When
        $isSquarefree = Integer::isSquarefree($n);

        // Then
        $this->assertTrue($isSquarefree);
    }

    /**
     * A005117 squarefree numbers: numbers that are not divisible by a square greater than 1
     * @see    https://oeis.org/A005117
     * @return array
     */
    public function dataProviderForSquarefreeIntegers(): array
    {
        return [
            [1],
            [2],
            [3],
            [5],
            [6],
            [7],
            [10],
            [11],
            [13],
            [14],
            [15],
            [17],
            [19],
            [21],
            [22],
            [23],
            [26],
            [29],
            [30],
            [31],
        ];
    }

    /**
     * @test         testIsNotSquarefree
     * @dataProvider dataProviderForNonSquarefreeIntegers
     * @param        int $n
     * @throws       \Exception
     */
    public function testIsNotSquarefree(int $n)
    {
        // When
        $isSquarefree = Integer::isSquarefree($n);

        // Then
        $this->assertFalse($isSquarefree);
    }

    /**
     * @return array
     */
    public function dataProviderForNonSquarefreeIntegers(): array
    {
        return [
            [-1],
            [0],
            [2 * 2],
            [2 * 2 * 2],
            [2 * 3 * 3],
            [2 * 3 * 5 * 7 * 11 * 13 * 17 * 17],
        ];
    }

    /**
     * @test         testSumOfDivisors
     * @dataProvider dataProviderForSumOfDivisors
     * @param        int $n
     * @param        int $expected
     * @throws       \Exception
     */
    public function testSumOfDivisors(int $n, int $expected)
    {
        // When
        $actual = Integer::sumOfDivisors($n);

        // Then
        $this->assertEquals($expected, $actual);
    }

    /**
     * A000203 the sum of the divisors of n
     * @see    https://oeis.org/A000203
     * @return array
     */
    public function dataProviderForSumOfDivisors(): array
    {
        return [
            [1, 1],
            [2, 3],
            [3, 4],
            [4, 7],
            [5, 6],
            [6, 12],
            [7, 8],
            [8, 15],
            [9, 13],
            [10, 18],
            [11, 12],
            [12, 28],
            [13, 14],
            [14, 24],
            [15, 24],
            [70, 144],
            [44100, 160797],
        ];
    }

    /**
     * @test         sumOfDivisors throws an OutOfBoundsException if n is < 1.
     * @dataProvider dataProviderForPrimeFactorizationOutOfBoundsException
     * @param        int $n
     * @throws       \Exception
     */
    public function testSumOfDivisorsOutOfBoundsException(int $n)
    {
        // When
        $this->expectException(Exception\OutOfBoundsException::class);

        // Then
        Integer::sumOfDivisors($n);
    }

    /**
     * @test         testNumberOfDivisors
     * @dataProvider dataProviderForNumberOfDivisors
     * @param        int $n
     * @param        int $expected
     * @throws       \Exception
     */
    public function testNumberOfDivisors(int $n, int $expected)
    {
        // When
        $actual = Integer::numberOfDivisors($n);

        // Then
        $this->assertEquals($expected, $actual);
    }

    /**
     * A000005 the numbers of divisors of n
     * @see    https://oeis.org/A000005
     * @return array
     */
    public function dataProviderForNumberOfDivisors(): array
    {
        return [
            [1, 1],
            [2, 2],
            [3, 2],
            [4, 3],
            [5, 2],
            [6, 4],
            [7, 2],
            [8, 4],
            [9, 3],
            [10, 4],
            [96, 12],
            [103, 2],
        ];
    }

    /**
     * @test         numberOfDivisors throws an OutOfBoundsException if n is < 1.
     * @dataProvider dataProviderForPrimeFactorizationOutOfBoundsException
     * @param        int $n
     * @throws       \Exception
     */
    public function testNumberOfDivisorsOutOfBoundsException(int $n)
    {
        // When
        $this->expectException(Exception\OutOfBoundsException::class);

        // Then
        Integer::numberOfDivisors($n);
    }

    /**
     * @test         isPerfectPower returns true if n is a perfect prime.
     * @dataProvider dataProviderForIsPerfectPower
     * @param        int $n
     */
    public function testIsPerfectPower(int $n)
    {
        // When
        $isPerfectPower = Integer::isPerfectPower($n);

        // Then
        $this->assertTrue($isPerfectPower);
    }

    /**
     * A001597 Perfect powers: m^k where m > 0 and k >= 2.
     * @see    https://oeis.org/A001597
     * @return array
     */
    public function dataProviderForIsPerfectPower(): array
    {
        return [
            [4],
            [8],
            [9],
            [16],
            [16],
            [25],
            [27],
            [32],
            [36],
            [49],
            [64],
            [64],
            [64],
            [81],
            [81],
            [100],
            [121],
            [125],
            [128],
            [144],
            [169],
            [196],
            [216],
            [225],
            [243],
            [256],
            [256],
            [256],
            [289],
            [324],
            [343],
            [361],
            [400],
            [441],
            [484],
            [512],
            [512],
            [529],
            [576],
            [625],
            [625],
            [676],
            [729],
            [729],
            [729],
            [784],
            [841],
            [900],
            [961],
            [1000],
            [1024],
            [1024],
            [1024],
            [1089],
        ];
    }

    /**
     * @test         isPerfectPower returns false if n is not a perfect prime.
     * @dataProvider dataProviderForIsNotPerfectPower
     * @param        int $n
     */
    public function testIsNotPerfectPower(int $n)
    {
        // When
        $isPerfectPower = Integer::isPerfectPower($n);

        // Then
        $this->assertFalse($isPerfectPower);
    }

    /**
     * A007916 Numbers that are not perfect powers.
     * @see    https://oeis.org/A007916
     * @return array
     */
    public function dataProviderForIsNotPerfectPower(): array
    {
        return [
            [2],
            [3],
            [5],
            [6],
            [7],
            [10],
            [11],
            [12],
            [13],
            [14],
            [15],
            [17],
            [18],
            [19],
            [20],
            [21],
            [22],
            [23],
            [24],
            [26],
            [28],
            [29],
            [30],
            [31],
            [33],
            [34],
            [35],
            [37],
            [38],
            [39],
            [40],
            [41],
            [42],
            [43],
            [44],
            [45],
            [46],
            [47],
            [48],
            [50],
            [51],
            [52],
            [53],
            [54],
            [55],
            [56],
            [57],
            [58],
            [59],
            [60],
            [61],
            [62],
            [63],
            [65],
            [66],
            [67],
            [68],
            [69],
            [70],
            [71],
            [72],
            [73],
            [74],
            [75],
            [76],
            [77],
            [78],
            [79],
            [80],
            [82],
            [83],
        ];
    }

    /**
     * @test         perfectPower returns m and k for n such that mᵏ = n if n is a perfect power.
     * @dataProvider dataProviderForPerfectPower
     * @param        int $n
     * @param        int $expected_m
     * @param        int $expected_k
     */
    public function testPerfectPower(int $n, int $expected_m, int $expected_k)
    {
        // When
        [$m, $k] = Integer::perfectPower($n);

        // Then
        $this->assertEquals($expected_m, $m);
        $this->assertEquals($expected_k, $k);
    }

    /**
     * Perfect powers: m^k where m > 0 and k >= 2.
     * @return array
     */
    public function dataProviderForPerfectPower(): array
    {
        return [
            [4, 2, 2],
            [8, 2, 3],
            [9, 3, 2],
            [16, 2, 4],
            [25, 5, 2],
            [27, 3, 3],
            [32, 2, 5],
            [36, 6, 2],
            [49, 7, 2],
            [64, 2, 6],
            [81, 3, 4],
            [100, 10, 2],
            [121, 11, 2],
            [125, 5, 3],
            [128, 2, 7],
            [144, 12, 2],
            [169, 13, 2],
            [196, 14, 2],
            [216, 6, 3],
            [225, 15, 2],
            [243, 3, 5],
            [256, 2, 8],
            [1000, 10, 3],
            [1024, 2, 10],
        ];
    }

    /**
     * @test         perfectPower returns a non-empty array comtaining numeric m and k both > 1 if n is a perfect power.
     * @dataProvider dataProviderForIsPerfectPower
     * @param        int $n
     */
    public function testPerfectPowerArray(int $n)
    {
        // When
        $perfect_power = Integer::perfectPower($n);

        // Then
        $this->assertNotEmpty($perfect_power);

        // And
        $m = \array_shift($perfect_power);
        $k = \array_shift($perfect_power);
        $this->assertTrue(\is_numeric($m));
        $this->assertTrue(\is_numeric($k));
        $this->assertGreaterThan(1, $m);
        $this->assertGreaterThan(1, $k);
    }

    /**
     * @test         perfectPower returns an empty array if n is not a perfect power.
     * @dataProvider dataProviderForIsNotPerfectPower
     * @param        int $n
     */
    public function testEmptyPerfectPower(int $n)
    {
        // When
        $empty = Integer::perfectPower($n);

        // Then
        $this->assertEmpty($empty);
    }

    /**
     * @test         primeFactorization returns an array of the prime factors of an integer n.
     * @dataProvider dataProviderForPrimeFactorization
     * @param        int   $n
     * @param        array $expected_actors
     * @throws       \Exception
     */
    public function testPrimeFactorization(int $n, array $expected_actors)
    {
        // When
        $factors = Integer::primeFactorization($n);

        // Then
        $this->assertEquals($expected_actors, $factors);
    }

    /**
     * @return array
     */
    public function dataProviderForPrimeFactorization(): array
    {
        return [
            [1, []],
            [2, [2]],
            [3, [3]],
            [4, [2, 2]],
            [5, [5]],
            [6, [2, 3]],
            [7, [7]],
            [8, [2, 2, 2]],
            [9, [3, 3]],
            [10, [2, 5]],
            [11, [11]],
            [12, [2, 2, 3]],
            [13, [13]],
            [14, [2, 7]],
            [15, [3, 5]],
            [16, [2, 2, 2, 2]],
            [17, [17]],
            [18, [2, 3, 3]],
            [19, [19]],
            [20, [2, 2, 5]],
            [48, [2, 2, 2, 2, 3]],
            [99, [3, 3, 11]],
            [100, [2, 2, 5, 5]],
            [101, [101]],
            [111, [3, 37]],
            [147, [3, 7, 7]],
            [200, [2, 2, 2, 5, 5]],
            [5555, [5, 11, 101]],
            [8463, [3, 7, 13, 31]],
            [12345, [3, 5, 823]],
            [45123, [3, 13, 13, 89]],
            [99999, [3, 3, 41, 271]],
            [5465432, [2, 2, 2, 7, 17, 5741]],
            [25794349, [7, 619, 5953]],
            [87534987, [3, 23, 1268623]],
            [123456789, [3, 3, 3607, 3803]],
            [8654893156, [2, 2, 7, 13, 157, 269, 563]],
            [2 * 3 * 5 * 7, [2, 3, 5, 7]],
            [2 * 2 * 2 * 5 * 7 * 7 * 7 * 13, [2, 2, 2, 5, 7, 7, 7, 13]],
        ];
    }

    /**
     * @test         primeFactorization throws an OutOfBoundsException if n is < 1.
     * @dataProvider dataProviderForPrimeFactorizationOutOfBoundsException
     * @param        int $n
     * @throws       \Exception
     */
    public function testPrimeFactorizationOutOfBoundsException(int $n)
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Integer::primeFactorization($n);
    }

    /**
     * @return array
     */
    public function dataProviderForPrimeFactorizationOutOfBoundsException(): array
    {
        return [
            [0],
            [-1],
            [-2],
            [-100],
            [-98352299832],
        ];
    }

    /**
     * @test         coprime returns true if a and b are coprime
     * @dataProvider dataProviderForCoprime
     * @param        int $a
     * @param        int $b
     */
    public function testCoprime(int $a, int $b)
    {
        // When
        $coprime = Integer::coprime($a, $b);

        // Then
        $this->assertTrue($coprime);
    }

    /**
     * @return array
     */
    public function dataProviderForCoprime(): array
    {
        return [
            [1, 0],
            [-1, 1],
            [1, 2],
            [1, 3],
            [1, 4],
            [1, 5],
            [1, 6],
            [1, 7],
            [1, 8],
            [1, 9],
            [1, 10],
            [1, 20],
            [1, 30],
            [1, 100],
            [2, 3],
            [2, 5],
            [2, 7],
            [2, 9],
            [2, 11],
            [2, 13],
            [2, 15],
            [2, 17],
            [2, 19],
            [2, 21],
            [2, 23],
            [2, 25],
            [2, 27],
            [2, 29],
            [3, 4],
            [3, 5],
            [3, 7],
            [3, 8],
            [3, 10],
            [3, 11],
            [3, 13],
            [3, 14],
            [3, 16],
            [4, 3],
            [4, 5],
            [4, 7],
            [4, 17],
            [4, 21],
            [4, 35],
            [5, 6],
            [5, 7],
            [5, 8],
            [5, 9],
            [5, 11],
            [5, 12],
            [5, 13],
            [5, 14],
            [5, 16],
            [5, 27],
            [6, 7],
            [6, 11],
            [6, 13],
            [6, 17],
            [6, 29],
            [6, 23],
            [6, 25],
            [6, 29],
            [19, 20],
            [20, 21],
            [23, 24],
            [23, 25],
            [27, 16],
            [28, 29],
            [29, 30],
        ];
    }

    /**
     * @test         coprime returns false if a and b are not coprime
     * @dataProvider dataProviderForNotCoprime
     * @param        int $a
     * @param        int $b
     */
    public function testNotCoprime(int $a, int $b)
    {
        // When
        $coprime = Integer::coprime($a, $b);

        // Then
        $this->assertFalse($coprime);
    }

    /**
     * @return array
     */
    public function dataProviderForNotCoprime(): array
    {
        return [
            [2, 4],
            [2, 6],
            [2, 8],
            [2, 10],
            [2, 12],
            [2, 14],
            [2, 16],
            [2, 18],
            [2, 20],
            [2, 22],
            [2, 24],
            [2, 26],
            [2, 28],
            [2, 30],
            [3, 6],
            [3, 9],
            [3, 12],
            [3, 15],
            [4, 8],
            [4, 12],
            [4, 20],
            [4, 22],
            [4, 24],
            [4, 30],
            [5, 10],
            [5, 15],
            [5, 20],
            [5, 25],
            [5, 30],
            [5, 50],
            [5, 100],
            [5, 200],
            [5, 225],
            [5, 555],
            [6, 12],
            [6, 14],
            [6, 16],
            [6, 18],
            [6, 26],
            [6, 28],
            [6, 30],
            [6, 32],
            [12, 21],
            [18, 20],
            [20, 22],
            [21, 24],
        ];
    }

    /**
     * @test         isOdd returns true for an odd number
     * @dataProvider dataProviderForOddNumbers
     * @param        int $x
     */
    public function testIsOdd(int $x)
    {
        // When
        $isOdd = Integer::isOdd($x);

        // Then
        $this->assertTrue($isOdd);
    }

    /**
     * @test          isOdd returns false for an even number
     * @dataProvider  dataProviderForEvenNumbers
     * @param        int $x
     */
    public function testIsNotOdd(int $x)
    {
        // When
        $isOdd = Integer::isOdd($x);

        // Then
        $this->assertFalse($isOdd);
    }

    /**
     * @test         isEven returns true for an even number
     * @dataProvider dataProviderForEvenNumbers
     * @param        int $x
     */
    public function testIsEven(int $x)
    {
        // When
        $isEven = Integer::isEven($x);

        // Then
        $this->assertTrue($isEven);
    }

    /**
     * @test         isEven returns false for an odd number
     * @dataProvider dataProviderForOddNumbers
     * @param        int $x
     */
    public function testIsNotEven(int $x)
    {
        // When
        $isEven = Integer::isEven($x);

        // Then
        $this->assertFalse($isEven);
    }

    /**
     * @return \Generator
     */
    public function dataProviderForOddNumbers(): \Generator
    {
        foreach (\range(-11, 101, 2) as $x) {
            yield [$x];
        }
    }

    /**
     * @return \Generator
     */
    public function dataProviderForEvenNumbers(): \Generator
    {
        foreach (\range(-10, 100, 2) as $x) {
            yield [$x];
        }
    }
}
