<?php

namespace MathPHP\Tests\InformationTheory;

use MathPHP\InformationTheory\Entropy;
use MathPHP\Exception;

class EntropyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataProviderForShannonEntropy
     */
    public function testShannonEntropy(array $p, $expected)
    {
        // When
        $H = Entropy::shannonEntropy($p);

        // Then
        $this->assertEqualsWithDelta($expected, $H, 0.001);
    }

    public function dataProviderForShannonEntropy(): array
    {
        return [
            // Test data created from: http://www.shannonentropy.netmark.pl/
            [
                [1],
                0
            ],
            [
                [0.6, 0.4],
                0.97095,
            ],
            [
                [0.514, 0.486],
                0.99941,
            ],
            [
                [0.231, 0.385, 0.308, 0.077],
                1.82625,
            ],
            // Test data from: http://www.csun.edu/~twang/595DM/Slides/Information%20&%20Entropy.pdf
            [
                [4 / 9, 3 / 9, 2 / 9],
                1.5304755,
            ],
            // Test data from: http://www.cs.rochester.edu/u/james/CSC248/Lec6.pdf
            [
                [0.4, 0.1, 0.25, 0.25],
                1.86,
            ],
            // Other
            [
                [1 / 2, 1 / 4, 1 / 4, 0],
                3 / 2,
            ],
        ];
    }

    public function testShannonEntropyExceptionNotProbabilityDistributionThatAddsUpToOne()
    {
        // Given
        $p = [0.2, 0.2, 0.1];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Entropy::shannonEntropy($p);
    }

    /**
     * @dataProvider dataProviderForShannonNatEntropy
     */
    public function testShannonNatEntropy(array $p, $expected)
    {
        // When
        $H = Entropy::shannonNatEntropy($p);

        // Then
        $this->assertEqualsWithDelta($expected, $H, 0.000001);
    }

    public function dataProviderForShannonNatEntropy(): array
    {
        return [
            [
                [1],
                0
            ],
            [
                [0.6, 0.4],
                0.67301166700925,
            ],
            [
                [0.514, 0.486],
                0.69275512932254,
            ],
            [
                [0.231, 0.385, 0.308, 0.077],
                1.2661221087912,
            ],
            [
                [4 / 9, 3 / 9, 2 / 9],
                1.06085694715802,
            ],
        ];
    }

    public function testShannonNatEntropyExceptionNotProbabilityDistributionThatAddsUpToOne()
    {
        // Given
        $p = [0.2, 0.2, 0.1];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Entropy::shannonNatEntropy($p);
    }

    /**
     * @dataProvider dataProviderForShannonHartleyEntropy
     */
    public function testShannonHartleyEntropy(array $p, $expected)
    {
        // When
        $H = Entropy::shannonHartleyEntropy($p);

        // Then
        $this->assertEqualsWithDelta($expected, $H, 0.000001);
    }

    public function dataProviderForShannonHartleyEntropy(): array
    {
        return [
            [
                [1],
                0
            ],
            [
                [0.6, 0.4],
                0.29228525323863,
            ],
            [
                [0.514, 0.486],
                0.30085972997496,
            ],
            [
                [0.231, 0.385, 0.308, 0.077],
                0.54986984526372,
            ],
            [
                [4 / 9, 3 / 9, 2 / 9],
                0.46072431823946,
            ],
        ];
    }

    public function testShannonHartleyEntropyExceptionNotProbabilityDistributionThatAddsUpToOne()
    {
        // Given
        $p = [0.2, 0.2, 0.1];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Entropy::shannonHartleyEntropy($p);
    }

    /**
     * @dataProvider dataProviderForCrossEntropy
     */
    public function testCrossEntropy(array $p, array $q, $expected)
    {
        // When
        $BD = Entropy::crossEntropy($p, $q);

        // Then
        $this->assertEqualsWithDelta($expected, $BD, 0.01);
    }

    public function dataProviderForCrossEntropy(): array
    {
        return [
            // Test data from: http://www.cs.rochester.edu/u/james/CSC248/Lec6.pdf
            [
                [0.4, 0.1, 0.25, 0.25],
                [0.25, 0.25, 0.25, 0.25],
                2,
            ],
            [
                [0.4, 0.1, 0.25, 0.25],
                [0.4, 0.1, 0.1, 0.4],
                2.02,
            ],
        ];
    }

    public function testCrossEntropyExceptionArraysDifferentLength()
    {
        // Given
        $p = [0.4, 0.5, 0.1];
        $q = [0.2, 0.8];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Entropy::crossEntropy($p, $q);
    }

    public function testCrossEntropyExceptionNotProbabilityDistributionThatAddsUpToOne()
    {
        // Given
        $p = [0.2, 0.2, 0.1];
        $q = [0.2, 0.4, 0.6];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Entropy::crossEntropy($p, $q);
    }

    /**
     * @dataProvider dataProviderForShannonEntropy
     */
    public function testJointEntropy(array $p, $expected)
    {
        // When
        $H = Entropy::jointEntropy($p);

        // Then
        $this->assertEqualsWithDelta($expected, $H, 0.001);
    }

    public function testJointEntropyExceptionNotProbabilityDistributionThatAddsUpToOne()
    {
        // Given
        $p = [0.2, 0.2, 0.1];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Entropy::jointEntropy($p);
    }

    /**
     * @dataProvider dataProviderForRenyiEntropy
     */
    public function testRenyiEntropy(array $p, $α, $expected)
    {
        // When
        $H = Entropy::renyiEntropy($p, $α);

        // Then
        $this->assertEqualsWithDelta($expected, $H, 0.001);
    }

    public function dataProviderForRenyiEntropy(): array
    {
        return [
            [
                [0.4, 0.6], 0.5, 0.985352,
                [0.2, 0.3, 0.5], 0.8, 1.504855,
            ],
        ];
    }

    public function testRenyiEntropyExceptionNotProbabilityDistributionThatAddsUpToOne()
    {
        // Given
        $p = [0.2, 0.2, 0.1];
        $α = 0.5;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Entropy::renyiEntropy($p, $α);
    }


    public function testRenyiEntropyExceptionAlphaOutOfBounds()
    {
        // Given
        $p = [0.4, 0.4, 0.2];
        $α = -3;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Entropy::renyiEntropy($p, $α);
    }

    public function testRenyiEntropyExceptionAlphaEqualsOne()
    {
        // Given
        $p = [0.4, 0.4, 0.2];
        $α = 1;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Entropy::renyiEntropy($p, $α);
    }

    /**
     * @dataProvider dataProviderForPerplexity
     */
    public function testPerplexity(array $p, $expected)
    {
        // When
        $H = Entropy::perplexity($p);

        // Then
        $this->assertEqualsWithDelta($expected, $H, 0.001);
    }

    public function dataProviderForPerplexity(): array
    {
        return [
            [
                [1 / 10, 1 / 10, 1 / 10, 1 / 10, 1 / 10, 1 / 10, 1 / 10, 1 / 10, 1 / 10, 1 / 10],
                10
            ],
            [
                [1],
                1
            ],
            [
                [0.6, 0.4],
                1.960130896546316,
            ],
            [
                [0.514, 0.486],
                1.999182253549837,
            ],
            [
                [0.231, 0.385, 0.308, 0.077],
                3.546141242991336,
            ],
            [
                [4 / 9, 3 / 9, 2 / 9],
                2.888810361450759,
            ],
            [
                [1 / 2, 1 / 4, 1 / 4, 0],
                2.82842712474619,
            ],
        ];
    }

    public function testPerplexityExceptionNotProbabilityDistributionThatAddsUpToOne()
    {
        // Given
        $p = [0.2, 0.2, 0.1];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Entropy::perplexity($p);
    }
}
