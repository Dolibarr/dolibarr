<?php

namespace MathPHP\Tests\Probability\Distribution\Multivariate;

use MathPHP\Probability\Distribution\Multivariate\Multinomial;
use MathPHP\Exception;

class MultinomialTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pmf
     * @dataProvider dataProviderForPmf
     * @param        array $frequencies
     * @param        array $probabilities
     * @param        $expectedPmf
     * @throws       \Exception
     */
    public function testPmf(array $frequencies, array $probabilities, $expectedPmf)
    {
        // Given
        $multinomial = new Multinomial($probabilities);

        // When
        $pmf = $multinomial->pmf($frequencies);

        // Then
        $this->assertEqualsWithDelta($expectedPmf, $pmf, 0.001);
    }

    /**
     * @return array
     */
    public function dataProviderForPmf(): array
    {
        return [
            [ [1, 1], [0.5, 0.5], 0.5 ],
            [ [1, 1], [0.4, 0.6], 0.48 ],
            [ [7, 2, 3], [0.40, 0.35, 0.25], 0.0248 ],
            [ [1, 2, 3], [0.2, 0.3, 0.5], 0.135 ],
            [ [2, 3, 3, 2], [0.25, 0.25, 0.25, 0.25], 0.024 ],
            [ [5, 2], [0.4, 0.6], 0.07741440000000005 ],
        ];
    }

    /**
     * @test     pmf throws Exception\BadDataException if the number of frequencies does not match the number of probabilities
     * @throws   \Exception
     */
    public function testPmfExceptionCountFrequenciesAndProbabilitiesDoNotMatch()
    {
        // Given
        $probabilities = [0.3, 0.4, 0.2, 0.1];
        $frequencies   = [1, 2, 3];
        $multinomial   = new Multinomial($probabilities);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // when
        $multinomial->pmf($frequencies);
    }

    /**
     * @test     pmf throws Exception\BadDataException if one of the frequencies is not an int
     * @throws   \Exception
     */
    public function testPmfExceptionFrequenciesAreNotAllIntegers()
    {
        // Given
        $probabilities = [0.3, 0.4, 0.2, 0.1];
        $frequencies   = [1, 2.3, 3, 4.4];
        $multinomial   = new Multinomial($probabilities);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // when
        $multinomial->pmf($frequencies);
    }

    /**
     * @test     constructor throws Exception\BadDataException if the probabilities do not add up to 1
     * @throws   \Exception
     */
    public function testPMFExceptionProbabilitiesDoNotAddUpToOne()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $multinomial = new Multinomial([0.3, 0.2, 0.1]);
    }
}
