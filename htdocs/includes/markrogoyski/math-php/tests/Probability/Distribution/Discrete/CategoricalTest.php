<?php

namespace MathPHP\Tests\Probability\Distribution\Discrete;

use MathPHP\Probability\Distribution\Discrete\Categorical;
use MathPHP\Exception;

class CategoricalTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         Constructor throws a BadParameterException if k is <= 0
     * @dataProvider dataProviderForBadK
     * @param        int $k
     * @throws       \Exception
     */
    public function testBadK(int $k)
    {
        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $categorical = new Categorical($k, []);
    }

    /**
     * @return array
     */
    public function dataProviderForBadK(): array
    {
        return [
            [0],
            [-1],
            [-40],
        ];
    }

    /**
     * @test     Constructor throws a BadDataException if there are no exactly k probabilities
     * @throws   \Exception
     */
    public function testBadCount()
    {
        // Given
        $k             = 3;
        $probabilities = [0.4, 0.6];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $categorical = new Categorical($k, $probabilities);
    }

    /**
     * @test     Constructor throws a BadDataException if the probabilities do not add up to 1
     * @throws   \Exception
     */
    public function testBadProbabilities()
    {
        // Given
        $k             = 2;
        $probabilities = [0.3, 0.2];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $categorical = new Categorical($k, $probabilities);
    }

    /**
     * @test         pmf returns the expected probability for the category x
     * @dataProvider dataProviderForPmf
     * @param        int    $k
     * @param        array  $probabilities
     * @param        int    $x
     * @param        float  $expectedPmf
     * @throws       \Exception
     */
    public function testPmf(int $k, array $probabilities, $x, float $expectedPmf)
    {
        // Given
        $categorical = new Categorical($k, $probabilities);

        // When
        $pmf = $categorical->pmf($x);

        // Then
        $this->assertEquals($expectedPmf, $pmf);
    }

    /**
     * @return array
     */
    public function dataProviderForPmf(): array
    {
        return [
            [
                1,
                ['a' => 1],
                'a',
                1
            ],
            [
                2,
                ['a' => 0.4, 'b' => 0.6],
                'a',
                0.4
            ],
            [
                2,
                ['a' => 0.4, 'b' => 0.6],
                'b',
                0.6
            ],
            [
                3,
                ['a' => 0.3, 'b' => 0.2, 'c' => 0.5],
                'a',
                0.3
            ],
            [
                3,
                ['a' => 0.3, 'b' => 0.2, 'c' => 0.5],
                'b',
                0.2
            ],
            [
                3,
                ['a' => 0.3, 'b' => 0.2, 'c' => 0.5],
                'c',
                0.5
            ],
        ];
    }

    /**
     * @test     pmf throws a BadDataException if x is not a valid category
     */
    public function testPmfException()
    {
        // Given
        $k             = 2;
        $probabilities = [0.4, 0.6];
        $categorical   = new Categorical($k, $probabilities);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $p = $categorical->pmf(99);
    }

    /**
     * @test         mode returns the expected category name
     * @dataProvider dataProviderForMode
     * @param        int    $k
     * @param        array  $probabilities
     * @param        mixed  $expectedMode
     * @throws       \Exception
     */
    public function testMode(int $k, array $probabilities, $expectedMode)
    {
        // Given
        $categorical = new Categorical($k, $probabilities);

        // When
        $mode = $categorical->mode();

        // Then
        $this->assertEquals($expectedMode, $mode);
    }

    /**
     * @return array
     */
    public function dataProviderForMode(): array
    {
        return [
            [
                1,
                ['a' => 1],
                'a',
            ],
            [
                2,
                ['a' => 0.4, 'b' => 0.6],
                'b',
            ],
            [
                2,
                ['a' => 0.4, 'b' => 0.6],
                'b',
            ],
            [
                3,
                ['a' => 0.3, 'b' => 0.2, 'c' => 0.5],
                'c',
            ],
        ];
    }

    /**
     * @test     __get returns the expected attributes
     * @throws   \Exception
     */
    public function testGet()
    {
        // Given
        $expectedK             = 2;
        $expectedProbabilities = [0.4, 0.6];
        $categorical           = new Categorical($expectedK, $expectedProbabilities);

        // When
        $k             = $categorical->k;
        $probabilities = $categorical->probabilities;

        // Then
        $this->assertSame($expectedK, $k);
        $this->assertSame($expectedProbabilities, $probabilities);
    }

    /**
     * @test     __get throws a BadDataException if the attribute does not exist
     * @throws   \Exception
     */
    public function testGetException()
    {
        // Given
        $k             = 2;
        $probabilities = [0.4, 0.6];
        $categorical   = new Categorical($k, $probabilities);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $does_not_exist = $categorical->does_not_exist;
    }
}
